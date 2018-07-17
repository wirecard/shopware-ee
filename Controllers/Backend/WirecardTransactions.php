<?php
/**
 * Shop System Plugins - Terms of Use
 *
 * The plugins offered are provided free of charge by Wirecard AG and are explicitly not part
 * of the Wirecard AG range of products and services.
 *
 * They have been tested and approved for full functionality in the standard configuration
 * (status on delivery) of the corresponding shop system. They are under General Public
 * License version 3 (GPLv3) and can be used, developed and passed on to third parties under
 * the same terms.
 *
 * However, Wirecard AG does not provide any guarantee or accept any liability for any errors
 * occurring when used in an enhanced, customized shop system configuration.
 *
 * Operation in an enhanced, customized configuration is at your own risk and requires a
 * comprehensive test phase by the user of the plugin.
 *
 * Customers use the plugins at their own risk. Wirecard AG does not guarantee their full
 * functionality neither does Wirecard AG assume liability for any disadvantages related to
 * the use of the plugins. Additionally, Wirecard AG does not guarantee the full functionality
 * for customized shop systems or installed plugins of other vendors of plugins within the same
 * shop system.
 *
 * Customers are responsible for testing the plugin's functionality before starting productive
 * operation.
 *
 * By installing the plugin into the shop system the customer agrees to these terms of use.
 * Please do not use the plugin if you do not agree to these terms of use!
 */

use Shopware\Components\CSRFWhitelistAware;
use Shopware\Models\Order\Order;
use Shopware\Models\Shop\Shop;
use Wirecard\PaymentSdk\BackendService;
use Wirecard\PaymentSdk\Config\Config;
use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\TransactionService;
use WirecardShopwareElasticEngine\Components\Actions\Action;
use WirecardShopwareElasticEngine\Components\Actions\ErrorAction;
use WirecardShopwareElasticEngine\Components\Actions\ViewAction;
use WirecardShopwareElasticEngine\Components\Services\BackendOperationHandler;
use WirecardShopwareElasticEngine\Components\Services\PaymentFactory;
<<<<<<< HEAD
use WirecardShopwareElasticEngine\Exception\UnknownActionException;
=======
use WirecardShopwareElasticEngine\Exception\MissingCredentialsException;
>>>>>>> Improve code quality
use WirecardShopwareElasticEngine\Models\Transaction;

// @codingStandardsIgnoreStart
class Shopware_Controllers_Backend_WirecardTransactions extends Shopware_Controllers_Backend_Application implements CSRFWhitelistAware
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
     * Check credentials against wirecard server
     */
    public function testSettingsAction()
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
     */
    public function detailsAction()
    {
        /** @var PaymentFactory $paymentFactory */
        $paymentFactory = $this->get('wirecard_elastic_engine.payment_factory');
        $payment        = $paymentFactory->create($this->Request()->getParam('payment'));

        $orderNumber = $this->Request()->getParam('orderNumber');

        if (! $orderNumber) {
            return $this->handleError('Order number not found');
        }

        $transactions = $this->get('models')
                             ->getRepository(Transaction::class)
                             ->findBy([
                                 'orderNumber' => $orderNumber,
                             ]);

        if (! $transactions) {
            return $this->handleError('No transactions found');
        }

        $config             = $payment->getTransactionConfig(
            $this->getModelManager()->getRepository(Shop::class)->getActiveDefault(),
            $this->container->getParameterBag()
        );
        $backendService     = new BackendService($config, $this->getLogger());
        $paymentTransaction = $payment->getTransaction();

        $result = [
            'transactions' => [],
        ];

        foreach ($transactions as $transaction) {
            /** @var Transaction $transaction */
            $paymentTransaction->setParentTransactionId($transaction->getTransactionId());

            $result['transactions'][] = array_merge($transaction->toArray(), [
                'backendOperations' => $backendService->retrieveBackendOperations($paymentTransaction, true),
                'isFinal'           => $backendService->isFinal($transaction->getTransactionType()),
            ]);
        }

        return $this->handleSuccess([
            'data' => $result,
        ]);
    }

    public function processBackendOperationsAction()
    {
        $operation     = $this->Request()->getParam('operation');
        $transactionId = $this->Request()->getParam('transactionId');

        $amount   = $this->Request()->getParam('amount');
        $currency = $this->Request()->getParam('currency');

        if (! $operation) {
            return $this->handleError('BackendOperationFailed');
        }

        /** @var PaymentFactory $paymentFactory */
        $paymentFactory = $this->get('wirecard_elastic_engine.payment_factory');
        $payment        = $paymentFactory->create($this->Request()->getParam('payment'));

        $config         = $payment->getTransactionConfig(
            $this->getModelManager()->getRepository(Shop::class)->getActiveDefault(),
            $this->container->getParameterBag()
        );
        $backendService = new BackendService($config, $this->getLogger());

        $transaction = $payment->getTransaction();
        $transaction->setParentTransactionId($transactionId);

        if ($amount) {
            $transaction->setAmount(new Amount($amount, $currency));
        }

        /** @var BackendOperationHandler $backendOperationHandler */
        $backendOperationHandler = $this->get('wirecard_elastic_engine.backend_operation_handler');
        $action                  = $backendOperationHandler->execute(
            $transaction,
            $backendService,
            $operation
        );

        return $this->handleAction($action);
    }

    /**
     * @param Action $action
     *
     * @throws Exception
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
            $order = $this->getManager()->getRepository(Order::class)
                          ->findOneBy(['number' => $current['orderNumber']]);

            /** @var Shopware\Models\Payment\Payment $payment */
            $payment = $order ? $order->getPayment() : null;
            /** @var Shopware\Models\Order\Status $status */
            $status = $order ? $order->getOrderStatus() : null;

            $result['data'][$key]['orderId']       = $order ? $order->getId() : 0;
            $result['data'][$key]['orderStatus']   = $status ? $status->getId() : 0;
            $result['data'][$key]['paymentMethod'] = $payment ? $payment->getDescription() : 'N/A';
        }

        return $result;
    }

    /**
     * @param array $assignments
     *
     * @return Enlight_View|Enlight_View_Default
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
        return ['notify', 'testSettings'];
    }

    /**
     * @return \Shopware\Components\Logger
     * @throws Exception
     */
    private function getLogger()
    {
        return $this->get('pluginlogger');
    }
}
