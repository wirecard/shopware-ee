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
use Wirecard\PaymentSdk\TransactionService;
use WirecardShopwareElasticEngine\Components\Services\PaymentFactory;
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
                throw new \Exception('Missing credentials. Please check Server, HttpUser and HttpPassword.');
            }

            $testConfig         = new Config(
                $params[$prefix . 'Server'],
                $params[$prefix . 'HttpUser'],
                $params[$prefix . 'HttpPassword']
            );
            $transactionService = new TransactionService($testConfig, $this->get('pluginlogger'));

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
            return $this->View()->assign(['success' => false]);
        }

        $transactions = $this->get('models')
                             ->getRepository(Transaction::class)
                             ->findBy([
                                 'orderNumber' => $orderNumber,
                             ]);

        if (! $transactions) {
            return $this->View()->assign(['success' => false]);
        }

        $config         = $payment->getTransactionConfig(
            $this->getModelManager()->getRepository(Shop::class)->getActiveDefault(),
            $this->container->getParameterBag()
        );
        $backendService = new BackendService($config, $this->get('pluginlogger'));

        $result = [
            'transactions'      => [],
            'backendOperations' => $backendService->retrieveBackendOperations(
                $payment->getTransaction()->setParentTransactionId($transactions)
            )
        ];

        return $this->View()->assign([
            'success' => true,
            'data'    => $result,
        ]);
    }

    public function processBackendOperationsAction()
    {
        $operation   = $this->Request()->getParam('operation');
        $orderNumber = $this->Request()->getParam('orderNumber');
        $payment     = $this->Request()->getParam('payment');


        //        $params = $this->Request()->getParams();
        //
        //        $operation   = $params['operation'];
        //        $orderNumber = $params['orderNumber'];
        //        $payMethod   = $params['payMethod'];
        //        $amount      = empty($params['amount']) ? null : $params['amount'];
        //        $currency    = empty($params['currency']) ? null : $params['currency'];
        //        $payment     = null;
        //
        //        if (! $operation || ! $orderNumber || ! $payMethod) {
        //            return $this->View()->assign(['success' => false, 'msg' => 'unsufficiantData']);
        //        }
        //
        //        if ($payMethod === PaypalPayment::PAYMETHOD_IDENTIFIER) {
        //            $payment = new PaypalPayment();
        //        } elseif ($payMethod === CreditCardPayment::PAYMETHOD_IDENTIFIER) {
        //            $payment = new CreditCardPayment();
        //        }
        //
        //        if (! $payment) {
        //            return $this->View()->assign(['success' => false, 'msg' => 'unknownPaymethod']);
        //        }
        //
        //        $result = $payment->processBackendOperationsForOrder($orderNumber, $operation, $amount, $currency);
        //        return $this->View()->assign($result);
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
     * {@inheritdoc}
     */
    public function getWhitelistedCSRFActions()
    {
        return ['notify', 'testSettings'];
    }
}
