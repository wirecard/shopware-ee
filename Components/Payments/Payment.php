<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

namespace WirecardElasticEngine\Components\Payments;

use Doctrine\ORM\EntityManagerInterface;
use Shopware\Bundle\PluginInstallerBundle\Service\InstallerService;
use Shopware\Components\Routing\RouterInterface;
use Shopware\Models\Shop\Shop;
use Shopware_Components_Config;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Wirecard\PaymentSdk\Config\Config;
use WirecardElasticEngine\Exception\UnknownTransactionTypeException;
use WirecardElasticEngine\WirecardElasticEngine;

/**
 * Base class for payment implementations.
 *
 * @package WirecardElasticEngine\Components\Payments
 *
 * @since   1.0.0
 */
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
     * @var \Enlight_Event_EventManager
     */
    protected $eventManager;

    /**
     * @param EntityManagerInterface      $em
     * @param Shopware_Components_Config  $shopwareConfig
     * @param InstallerService            $installerService
     * @param RouterInterface             $router
     * @param \Enlight_Event_EventManager $eventManager
     *
     * @since 1.0.0
     */
    public function __construct(
        EntityManagerInterface $em,
        Shopware_Components_Config $shopwareConfig,
        InstallerService $installerService,
        RouterInterface $router,
        \Enlight_Event_EventManager $eventManager
    ) {
        $this->em               = $em;
        $this->shopwareConfig   = $shopwareConfig;
        $this->installerService = $installerService;
        $this->router           = $router;
        $this->eventManager     = $eventManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'Wirecard EE ' . preg_replace('/Payment$/', '', get_class($this));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return str_replace(' ', '_', strtolower($this->getLabel()));
    }

    /**
     * {@inheritdoc}
     */
    public function getPosition()
    {
        return 0;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function getTransactionType()
    {
        $operation = $this->getPaymentConfig()->getTransactionOperation();
        if ($operation === self::TRANSACTION_OPERATION_PAY) {
            return Payment::TRANSACTION_TYPE_PURCHASE;
        }
        if ($operation === self::TRANSACTION_OPERATION_RESERVE) {
            return Payment::TRANSACTION_TYPE_AUTHORIZATION;
        }
        throw new UnknownTransactionTypeException($operation);
    }

    /**
     * {@inheritdoc}
     */
    public function getTransactionConfig(Shop $shop, ParameterBagInterface $parameterBag, $selectedCurrency)
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

        $plugin = $this->installerService->getPluginByName(WirecardElasticEngine::NAME);

        $config->setPluginInfo($plugin->getName(), $plugin->getVersion());

        return $config;
    }

    /**
     * {@inheritdoc}
     */
    public function getBackendTransaction($operation, $paymentMethod, $transactionType)
    {
        return $this->getTransaction();
    }

    /**
     * @param string $name
     * @param string $prefix
     *
     * @return string
     *
     * @since 1.0.0
     */
    protected function getPluginConfig($name, $prefix = 'wirecardElasticEngine')
    {
        return $this->shopwareConfig->getByNamespace(WirecardElasticEngine::NAME, $prefix . $name);
    }
}
