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

        if (!$orderNumber) {
            return $this->View()->assign(['success' => false]);
        }

        $builder = $this->getManager()->createQueryBuilder();
        $builder->select('transaction')
                ->from(Transaction::class, 'transaction')
                ->where('transaction.orderNumber = :orderNumber')
                ->setParameter('orderNumber', $orderNumber);

        $query = $builder->getQuery();
        $result = $query->getArrayResult();

        if (!$result || empty($result)) {
            return $this->View()->assign(['success' => false]);
        }

        return $this->View()->assign(['success' => true, 'params' => $params, 'data' => $result]);
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

        $query = $builder->getQuery();
        Shopware()->PluginLogger()->notice($query->getSQL());

        return $builder;
    }

    /**
     * {@inheritdoc}
     */
    public function getWhitelistedCSRFActions()
    {
        return array('testSettings');
    }
}
