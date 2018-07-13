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

namespace WirecardShopwareElasticEngine\Components\Payments;

use Doctrine\ORM\EntityManagerInterface;
use Shopware\Bundle\PluginInstallerBundle\Service\InstallerService;
use Shopware\Components\Routing\RouterInterface;
use Shopware\Models\Shop\Shop;
use Shopware_Components_Config;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Wirecard\PaymentSdk\Config\Config;
use Wirecard\PaymentSdk\TransactionService;
use WirecardShopwareElasticEngine\Exception\UnknownTransactionTypeException;
use WirecardShopwareElasticEngine\WirecardShopwareElasticEngine;

abstract class Payment implements PaymentInterface
{
    const ACTION = 'WirecardElasticEnginePayment';

    const TRANSACTION_OPERATION_PAY = 'pay';
    const TRANSACTION_OPERATION_RESERVE = 'reserve';

    const TRANSACTION_TYPE_AUTHORIZATION = 'authorization';
    const TRANSACTION_TYPE_PURCHASE = 'purchase';
    const TRANSACTION_TYPE_UNKNOWN = 'unknown';

    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @var Shopware_Components_Config
     */
    protected $shopwareConfig;

    /**
     * @var InstallerService
     */
    protected $installerService;

    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @param EntityManagerInterface     $em
     * @param Shopware_Components_Config $shopwareConfig
     * @param InstallerService           $installerService
     * @param RouterInterface            $router
     */
    public function __construct(
        EntityManagerInterface $em,
        Shopware_Components_Config $shopwareConfig,
        InstallerService $installerService,
        RouterInterface $router
    ) {
        $this->em               = $em;
        $this->shopwareConfig   = $shopwareConfig;
        $this->installerService = $installerService;
        $this->router           = $router;
    }

    /**
     * @inheritdoc
     */
    public function getLabel()
    {
        return 'Wirecard EE ' . preg_replace('/Payment$/', '', get_class($this));
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return str_replace(' ', '_', strtolower($this->getLabel()));
    }

    /**
     * @return int
     */
    public function getPosition()
    {
        return 0;
    }

    /**
     * @inheritdoc
     */
    public function getPaymentOptions()
    {
        return [
            'name'                  => $this->getName(),
            'description'           => $this->getLabel(),
            'action'                => self::ACTION,
            'active'                => 0,
            'position'              => $this->getPosition(),
            'additionalDescription' => '',
        ];
    }

    /**
     * @return string
     * @throws UnknownTransactionTypeException
     */
    public function getTransactionType()
    {
        $operation = $this->getPaymentConfig()->getTransactionOperation();

        switch ($operation) {
            case self::TRANSACTION_OPERATION_PAY:
                return Payment::TRANSACTION_TYPE_PURCHASE;
            case self::TRANSACTION_OPERATION_RESERVE:
                return Payment::TRANSACTION_TYPE_AUTHORIZATION;
        }

        throw new UnknownTransactionTypeException($operation);
    }

    /**
     * @inheritdoc
     */
    public function getTransactionConfig(Shop $shop, ParameterBagInterface $parameterBag)
    {
        $config = new Config(
            $this->getPaymentConfig()->getBaseUrl(),
            $this->getPaymentConfig()->getHttpUser(),
            $this->getPaymentConfig()->getHttpPassword()
        );

        $config->setShopInfo(
            $parameterBag->get('kernel.name'),
            $parameterBag->get('shopware.release.version')
        );

        $plugin = $this->installerService->getPluginByName(WirecardShopwareElasticEngine::NAME);

        $config->setPluginInfo($plugin->getName(), $plugin->getVersion());

        return $config;
    }

    /**
     * @inheritdoc
     */
    public function getAdditionalFormFields()
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function processReturn(
        TransactionService $transactionService,
        \Enlight_Controller_Request_Request $request
    ) {
        return null;
    }

    /**
     * @param string $name
     * @param string $prefix
     *
     * @return string
     */
    protected function getPluginConfig($name, $prefix = 'wirecardElasticEngine')
    {
        return $this->shopwareConfig->getByNamespace(WirecardShopwareElasticEngine::NAME, $prefix . $name);
    }
}
