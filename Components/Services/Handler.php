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

namespace WirecardShopwareElasticEngine\Components\Services;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Shopware\Components\Routing\RouterInterface;
use Wirecard\PaymentSdk\Response\Response;

abstract class Handler
{
    protected $devEnvironments = ['dev', 'development'];

    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var \Shopware_Components_Config
     */
    protected $shopwareConfig;

    /**
     * @var TransactionFactory
     */
    protected $transactionFactory;

    /**
     * @param EntityManagerInterface      $em
     * @param RouterInterface             $router
     * @param LoggerInterface             $logger
     * @param \Shopware_Components_Config $config
     * @param TransactionFactory          $transactionFactory
     */
    public function __construct(
        EntityManagerInterface $em,
        RouterInterface $router,
        LoggerInterface $logger,
        \Shopware_Components_Config $config,
        TransactionFactory $transactionFactory
    ) {
        $this->em                 = $em;
        $this->router             = $router;
        $this->logger             = $logger;
        $this->shopwareConfig     = $config;
        $this->transactionFactory = $transactionFactory;
    }

    /**
     * @param int $orderNumber
     * @return string
     */
    protected function getOrderNumberForTransaction($orderNumber)
    {
        if (in_array(getenv('SHOPWARE_ENV'), $this->devEnvironments)) {
            $orderNumber = uniqid() . '-' . $orderNumber;
        }

        return $orderNumber;
    }

    /**
     * @param Response $response
     * @return string
     */
    protected function getOrderNumberFromResponse(Response $response)
    {
        $orderNumber = $response->findElement('order-number');

        if (in_array(getenv('SHOPWARE_ENV'), $this->devEnvironments) && strpos($orderNumber, '-') >= 0) {
            $orderNumber = explode('-', $orderNumber)[1];
        }

        return $orderNumber;
    }
}
