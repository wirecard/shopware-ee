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

use Doctrine\DBAL\Connection;

use Shopware\Components\CSRFWhitelistAware;

use Shopware\Components\Model\QueryBuilder;
use Shopware\Models\Order\Order;

use Wirecard\PaymentSdk\Config\Config;
use Wirecard\PaymentSdk\TransactionService;

class Shopware_Controllers_Backend_WirecardTransactions // phpcs:ignore
    extends Shopware_Controllers_Backend_Application
    implements CSRFWhitelistAware
{
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

        $wirecardUrl = $config['wirecardElasticEnginePaypalServer'];
        $httpUser = $config['wirecardElasticEnginePaypalHttpUser'];
        $httpPassword = $config['wirecardElasticEnginePaypalHttpPassword'];

        $testConfig = new Config($wirecardUrl, $httpUser, $httpPassword);
        $transactionService = new TransactionService($testConfig, Shopware()->PluginLogger());
        
        if ($transactionService->checkCredentials()) {
            $data['status'] = 'success';
        } else {
            $data['status'] = 'failed';
        }
      
        $this->View()->assign($data);
    }

    /**
     * Loads transaction details for order given with orderNumber
     */
    public function detailsAction()
    {
        $params = $this->Request()->getParams();

        $orderNumber = $params['orderNumber'];

        if (!$orderNumber) {
            return $this->View()->assign([ 'success' => false]);
        }

        $builder = $this->getManager()->createQueryBuilder();
        $builder->select('transaction')
            ->from('WirecardShopwareElasticEngine\Models\Transaction', 'transaction')
            ->where('transaction.orderNumber = ' . $orderNumber);
            

        $query = $builder->getQuery();
        $result = $query->getArrayResult();

        if(!$result || empty($result)) {
            return $this->View()->assign([ 'success' => false]);
        }

        $this->View()->assign([ 'success' => true, 'params' => $params, 'data' => $result ]);
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
        $query=$builder->getQuery();
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
