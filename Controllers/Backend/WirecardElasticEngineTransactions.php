<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

use Shopware\Components\CSRFWhitelistAware;
use Shopware\Models\Order\Order;
use Shopware\Models\Shop\Shop;
use Wirecard\PaymentSdk\BackendService;
use Wirecard\PaymentSdk\Config\Config;
use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\TransactionService;
use WirecardElasticEngine\Components\Actions\Action;
use WirecardElasticEngine\Components\Actions\ErrorAction;
use WirecardElasticEngine\Components\Actions\ViewAction;
use WirecardElasticEngine\Components\Mapper\OrderBasketMapper;
use WirecardElasticEngine\Components\Services\BackendOperationHandler;
use WirecardElasticEngine\Components\Services\PaymentFactory;
use WirecardElasticEngine\Exception\UnknownActionException;
use WirecardElasticEngine\Exception\MissingCredentialsException;
use WirecardElasticEngine\Models\Transaction;

/**
 * @since 1.0.0
 */
// @codingStandardsIgnoreStart
class Shopware_Controllers_Backend_WirecardElasticEngineTransactions extends Shopware_Controllers_Backend_Application implements CSRFWhitelistAware
{
    // @codingStandardsIgnoreEnd

    /**
     * @var string
     */
    protected $model = Transaction::class;

    /**
     * @var string
     */
    protected $alias = 'transaction';

    /**
     * Check credentials against Wirecard server
     *
     * @since 1.0.0
     */
    public function testCredentialsAction()
    {
        $params = $this->Request()->getParams();
        $method = $params['method'];
        $prefix = 'wirecardElasticEngine' . $method;

        try {
            if (empty($params[$prefix . 'Server'])
                || empty($params[$prefix . 'HttpUser'])
                || empty($params[$prefix . 'HttpPassword'])
            ) {
                throw new MissingCredentialsException(
                    'Missing credentials. Please check Server, HttpUser and HttpPassword.'
                );
            }

            $testConfig         = new Config(
                $params[$prefix . 'Server'],
                $params[$prefix . 'HttpUser'],
                $params[$prefix . 'HttpPassword']
            );
            $transactionService = new TransactionService($testConfig, $this->getLogger());

            $success = $transactionService->checkCredentials();
        } catch (\Exception $e) {
            return $this->View()->assign([
                'status' => 'failed',
                'msg'    => $e->getMessage(),
            ]);
        }

        return $this->View()->assign([
            'status' => $success ? 'success' : 'failed',
            'method' => $method,
        ]);
    }

    /**
     * Loads transaction details for order given with orderNumber
     *
     * @since 1.0.0
     */
    public function detailsAction()
    {
        $paymentFactory = $this->getPaymentFactory();
        $payment        = $paymentFactory->create($this->Request()->getParam('payment'));

        $orderNumber = $this->Request()->getParam('orderNumber');
        if (! $orderNumber) {
            return $this->handleError('Order number not found');
        }
        $order = $this->getOrderByNumber($orderNumber);
        if (! $order) {
            return $this->handleError('Order not found');
        }

        $transactions = $this->getModelManager()
                             ->getRepository(Transaction::class)
                             ->findBy(['orderNumber' => $orderNumber]);
        if (! $transactions) {
            return $this->handleError('No transactions found');
        }

        $transactionManager = $this->container->get('wirecard_elastic_engine.transaction_manager');
        $shop               = $this->getModelManager()->getRepository(Shop::class)->getActiveDefault();
        $config             = $payment->getTransactionConfig(
            $shop,
            $this->container->getParameterBag(),
            $shop->getCurrency()->getCurrency()
        );
        $backendService     = new BackendService($config, $this->getLogger());
        $result             = [
            'transactions' => [],
        ];

        $transactions = $this->addTransactionsByPaymentUniqueId($transactions);
        foreach ($transactions as $transaction) {
            if ($transaction->getType() !== Transaction::TYPE_NOTIFY
                || $transaction->getState() === Transaction::STATE_CLOSED
                || $backendService->isFinal($transaction->getTransactionType())
            ) {
                $result['transactions'][] = $transaction->toArray();
                continue;
            }

            /** @var Transaction $transaction */
            $paymentTransaction = $payment->getBackendTransaction($order, null, $transaction);

            $backendOperations = [];
            $basket            = null;
            if ($paymentTransaction) {
                $paymentTransaction->setParentTransactionId($transaction->getTransactionId());
                $basket            = $paymentTransaction->getBasket();
                $backendOperations = $backendService->retrieveBackendOperations($paymentTransaction, true);
            }

            $result['transactions'][] = array_merge($transaction->toArray(), [
                'backendOperations' => $backendOperations,
                'remainingAmount'   => $transactionManager->getRemainingAmount($transaction),
                'basket'            => $transactionManager->getRemainingBasket($transaction, $basket)
            ]);
        }

        return $this->handleSuccess([
            'data' => $result,
        ]);
    }

    /**
     * Add transactions via paymentUniqueId that have no orderNumber yet.
     *
     * @param Transaction[] $transactions
     *
     * @return array|Transaction[]
     * @throws Exception
     *
     * @since 1.0.0
     */
    private function addTransactionsByPaymentUniqueId($transactions)
    {
        foreach ($transactions as $transaction) {
            $paymentUniqueId = $transaction->getPaymentUniqueId();
            if (! $paymentUniqueId) {
                continue;
            }
            $addTransactions = $this->getModelManager()
                                    ->getRepository(Transaction::class)
                                    ->findBy(['orderNumber' => null, 'paymentUniqueId' => $paymentUniqueId]);
            foreach ($addTransactions as $addTransaction) {
                $addTransaction->setOrderNumber($transaction->getOrderNumber());
                $this->getModelManager()->flush($addTransaction);
                $transactions[] = $addTransaction;
            }
            break;
        }
        usort($transactions, function (Transaction $a, Transaction $b) {
            return $a->getId() - $b->getId();
        });
        return $transactions;
    }

    /**
     * @return Enlight_View|Enlight_View_Default|void
     * @throws \WirecardElasticEngine\Exception\UnknownPaymentException
     *
     * @since 1.0.0
     */
    public function processBackendOperationsAction()
    {
        $id        = $this->Request()->getParam('id');
        $operation = $this->Request()->getParam('operation');
        $details   = $this->Request()->getParam('details');

        $transaction = $this->getModelManager()->getRepository(Transaction::class)->find($id);
        if (! $operation || ! $transaction || ! ($order = $this->getOrderByNumber($transaction->getOrderNumber()))) {
            $this->handleError('BackendOperationFailed');
            return;
        }

        $paymentFactory = $this->getPaymentFactory();
        $payment        = $paymentFactory->create($this->Request()->getParam('payment'));

        $shop           = $this->getModelManager()->getRepository(Shop::class)->getActiveDefault();
        $config         = $payment->getTransactionConfig(
            $shop,
            $this->container->getParameterBag(),
            $shop->getCurrency()->getCurrency()
        );
        $backendService = new BackendService($config, $this->getLogger());

        $backendTransaction = $payment->getBackendTransaction($order, $operation, $transaction);
        if (! $backendTransaction) {
            $this->handleError('BackendOperationFailed');
            return;
        }
        $backendTransaction->setParentTransactionId($transaction->getTransactionId());

        if (isset($details['basket'])) {
            $mapper = new OrderBasketMapper();
            $basket = $mapper->updateBasketItems($backendTransaction->getBasket(), $details['basket']);
            $mapper->setTransactionBasket($backendTransaction, $basket);
        }
        if (isset($details['amount'])) {
            $backendTransaction->setAmount(new Amount($details['amount'], $transaction->getCurrency()));
        }

        /** @var BackendOperationHandler $backendOperationHandler */
        $backendOperationHandler = $this->get('wirecard_elastic_engine.backend_operation_handler');
        $action                  = $backendOperationHandler->execute(
            $backendTransaction,
            $backendService,
            $operation
        );

        $this->handleAction($action);
    }

    /**
     * Send support mail to wirecard
     *
     * @since 1.0.0
     */
    public function submitMailAction()
    {
        $senderAddress = $this->Request()->getParam('address');
        $message       = $this->Request()->getParam('message');
        $replyTo       = $this->Request()->getParam('replyTo');

        try {
            $supportMail = $this->get('wirecard_elastic_engine.mail.support');
            $supportMail->send(
                $this->container->getParameterBag(),
                $senderAddress,
                $message,
                $replyTo
            );
        } catch (\Exception $e) {
            $this->getLogger()->error('Sending support mail failed: ' . $e->getMessage());
            return $this->View()->assign([
                'success' => false,
            ]);
        }

        return $this->View()->assign([
            'success' => true,
        ]);
    }

    /**
     * @param Action $action
     *
     * @throws Exception
     *
     * @since 1.0.0
     */
    protected function handleAction(Action $action)
    {
        if ($action instanceof ViewAction) {
            // we're not able to render templates here, so ignore `$action->getTemplate()` here
            foreach ($action->getAssignments() as $key => $value) {
                $this->View()->assign($key, $value);
            }
            return;
        }

        if ($action instanceof ErrorAction) {
            $this->handleError($action->getMessage());
            return;
        }

        throw new UnknownActionException(get_class($action));
    }

    /**
     * {@inheritdoc}
     */
    protected function getList($offset, $limit, $sort = [], $filter = [], array $wholeParams = [])
    {
        $result = parent::getList($offset, $limit, $sort, $filter, $wholeParams);

        foreach ($result['data'] as $key => $current) {
            $order     = $this->getOrderByNumber($current['orderNumber']);
            $createdAt = $result['data'][$key]['createdAt'];
            if ($createdAt instanceof \DateTime) {
                $result['data'][$key]['createdAt'] = $createdAt->format(\DateTime::W3C);
            }

            /** @var Shopware\Models\Payment\Payment $payment */
            $payment = $order ? $order->getPayment() : null;
            /** @var Shopware\Models\Order\Status $status */
            $status = $order ? $order->getOrderStatus() : null;

            $result['data'][$key]['orderId']            = $order ? $order->getId() : 0;
            $result['data'][$key]['orderStatus']        = $status ? $status->getId() : 0;
            $result['data'][$key]['orderPaymentMethod'] = $payment ? $payment->getDescription() : null;
        }

        return $result;
    }

    /**
     * @param string $orderNumber
     *
     * @return null|Order
     *
     * @since 1.1.0
     */
    private function getOrderByNumber($orderNumber)
    {
        return $this->getManager()->getRepository(Order::class)
                    ->findOneBy(['number' => $orderNumber]);
    }

    /**
     * @param array $assignments
     *
     * @return Enlight_View|Enlight_View_Default
     *
     * @since 1.0.0
     */
    private function handleSuccess(array $assignments)
    {
        return $this->View()->assign(array_merge([
            'success' => true,
        ], $assignments));
    }

    /**
     * @param string $message
     *
     * @return Enlight_View|Enlight_View_Default
     * @throws Exception
     *
     * @since 1.0.0
     */
    private function handleError($message = '')
    {
        $this->getLogger()->error($message, $this->Request()->getParams());

        return $this->View()->assign([
            'success' => false,
            'message' => $message,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getWhitelistedCSRFActions()
    {
        return ['notify', 'testCredentials'];
    }

    /**
     * @return \Shopware\Components\Logger
     * @throws Exception
     *
     * @since 1.0.0
     */
    private function getLogger()
    {
        return $this->get('pluginlogger');
    }

    /**
     * @return PaymentFactory
     * @throws Exception
     *
     * @since 1.0.0
     */
    private function getPaymentFactory()
    {
        return $this->get('wirecard_elastic_engine.payment_factory');
    }
}
