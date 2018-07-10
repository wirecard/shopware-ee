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

use Shopware\Components\Model\QueryBuilder;
use Shopware\Models\Order\Order;

use Wirecard\PaymentSdk\Config\Config;
use Wirecard\PaymentSdk\TransactionService;
use WirecardShopwareElasticEngine\Models\Transaction;

use WirecardShopwareElasticEngine\Components\Payments\CreditCardPayment;
use WirecardShopwareElasticEngine\Components\Payments\PaypalPayment;

// @codingStandardsIgnoreStart
class Shopware_Controllers_Backend_WirecardTransactions extends Shopware_Controllers_Backend_Application implements CSRFWhitelistAware
{
    // @codingStandardsIgnoreEnd

    /**
     * @var string
     */
    protected $model = Order::class;

    /**
     * @var string
     */
    protected $alias = 'sOrder';

    /**
     * Checks credential to wirecard
     */
    public function testSettingsAction()
    {
        $config = $this->Request()->getParams();

        $payMethod = $config['method'];

        if (empty($config['wirecardElasticEngine' . $payMethod . 'Server'])
            || empty($config['wirecardElasticEngine' . $payMethod . 'HttpUser'])
            || empty($config['wirecardElasticEngine' . $payMethod . 'HttpPassword'])) {
            return $this->View()->assign([
                'status' => 'failed',
                'msg' => 'One or more credential information are empty'
            ]);
        }

        $wirecardUrl = $config['wirecardElasticEngine' . $payMethod . 'Server'];
        $httpUser = $config['wirecardElasticEngine' . $payMethod . 'HttpUser'];
        $httpPassword = $config['wirecardElasticEngine' . $payMethod . 'HttpPassword'];

        $testConfig = new Config($wirecardUrl, $httpUser, $httpPassword);
        $transactionService = new TransactionService($testConfig, Shopware()->PluginLogger());

        try {
            $data['status'] = $transactionService->checkCredentials() ? 'success' : 'failed';
        } catch (\Exception $e) {
            return $this->View()->assign([
                'status' => 'failed',
                'msg' => $e->getMessage(),
            ]);
        }
        $data['method'] = $payMethod;

        return $this->View()->assign($data);
    }

    /**
     * Loads transaction details for order given with orderNumber
     */
    public function detailsAction()
    {
        $params = $this->Request()->getParams();

        $orderNumber = $params['orderNumber'];
        $payMethod = $params['payMethod'];

        if (!$orderNumber) {
            return $this->View()->assign(['success' => false]);
        }

        $builder = $this->getManager()->createQueryBuilder();
        $builder->select('transaction')
                ->from(OrderNumberAssignment::class, 'transaction')
                ->where('transaction.orderNumber = :orderNumber')
                ->setParameter('orderNumber', $orderNumber);

        $query = $builder->getQuery();
        $transactionData = $query->getArrayResult();

        if (!$transactionData || empty($transactionData)) {
            return $this->View()->assign([ 'success' => false ]);
        }

        $historyBuilder = $this->getManager()->createQueryBuilder();
        $historyBuilder->select('orderTransaction')
            ->from(Transaction::class, 'orderTransaction')
            ->where('orderTransaction.parentTransactionId = :parentTransactionId')
            ->setParameter('parentTransactionId', $transactionData[0]['transactionId']);

        $historyQuery = $historyBuilder->getQuery();
        $transactionsHistory = $historyQuery->getArrayResult();

        foreach ($transactionsHistory as &$entry) {
            $requestId = '';
            if ($entry['notificationResponse']) {
                $notificationResponse = unserialize($entry['notificationResponse']);
                $entry['notificationResponse'] = print_r($notificationResponse, true);
                $requestId = $notificationResponse['request-id'];
            }
            if ($entry['returnResponse']) {
                $returnResponse = unserialize($entry['returnResponse']);
                $entry['returnResponse'] = print_r($returnResponse, true);
                $requestId = $returnResponse['request-id'];
            }
            $entry['requestId'] = $requestId;
        }

        $backendOperations = [];

        if ($payMethod === PaypalPayment::PAYMETHOD_IDENTIFIER) {
            $paypal = new PaypalPayment();
            $backendOperations = $paypal->getBackendOperations($transactionData[0]['transactionId']);
        } elseif ($payMethod === CreditCardPayment::PAYMETHOD_IDENTIFIER) {
            $creditCard = new CreditCardPayment();
            $backendOperations = $creditCard->getBackendOperations($transactionData[0]['transactionId']);
        }

        $result = [
            'transactionData'   => $transactionData[0],
            'transactionHistory' => $transactionsHistory,
            'backendOperations' => $backendOperations
        ];

        $this->View()->assign([ 'success' => true, 'data' => $result ]);
    }

    /**
     *
     */
    public function processBackendOperationsAction()
    {
        $params = $this->Request()->getParams();

        $operation = $params['operation'];
        $orderNumber = $params['orderNumber'];
        $payMethod = $params['payMethod'];
        $amount = empty($params['amount']) ? null : $params['amount'];
        $currency = empty($params['currency']) ? null : $params['currency'];
        $payment = null;

        if (!$operation || !$orderNumber || !$payMethod) {
            return $this->View()->assign([ 'success' => false, 'msg' => 'unsufficiantData' ]);
        }

        if ($payMethod === PaypalPayment::PAYMETHOD_IDENTIFIER) {
            $payment = new PaypalPayment();
        } elseif ($payMethod === CreditCardPayment::PAYMETHOD_IDENTIFIER) {
            $payment = new CreditCardPayment();
        }

        if (!$payment) {
            return $this->View()->assign([ 'success' => false, 'msg' => 'unknownPaymethod' ]);
        }

        $result = $payment->processBackendOperationsForOrder($orderNumber, $operation, $amount, $currency);
        $this->View()->assign($result);
    }

    /**
     * {@inheritdoc}
     */
    protected function getList($offset, $limit, $sort = [], $filter = [], array $wholeParams = [])
    {
        $result = parent::getList($offset, $limit, $sort, $filter, $wholeParams);

        foreach ($result['data'] as $key => $current) {
            $number = $current['number'];
            $builder = $this->getManager()->createQueryBuilder();
            $builder->select(['wirecardTransactions'])
                ->from(OrderNumberAssignment::class, 'wirecardTransactions')
                ->where('wirecardTransactions.orderNumber = :orderNumber')
                ->setParameter('orderNumber', $number);
            $elasticEngineTransactions = $builder->getQuery()->getArrayResult();
            if ($elasticEngineTransactions) {
                $result['data'][$key]['wirecardTransactions'] = $elasticEngineTransactions;
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function getListQuery()
    {
        return $this->prepareOrderQueryBuilder(parent::getListQuery());
    }

    /**
     * ignores empty orders in query for Order View
     *
     * @param QueryBuilder $builder
     * @return QueryBuilder
     * @throws Enlight_Exception
     */
    private function prepareOrderQueryBuilder(QueryBuilder $builder)
    {
        $builder->leftJoin('sOrder.languageSubShop', 'languageSubShop')
                ->leftJoin('sOrder.orderStatus', 'orderStatus')
                ->leftJoin('sOrder.paymentStatus', 'paymentStatus')
                ->leftJoin('sOrder.payment', 'payment')
                ->addSelect('payment')
                ->addSelect('orderStatus')
                ->addSelect('paymentStatus')
                ->where('sOrder.number != 0');

        return $builder;
    }

    /**
     * {@inheritdoc}
     */
    public function getWhitelistedCSRFActions()
    {
        return array('notify', 'testSettings');
    }
}
