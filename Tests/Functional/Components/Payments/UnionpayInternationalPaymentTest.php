<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

namespace WirecardElasticEngine\Tests\Functional\Components\Payments;

use Doctrine\ORM\EntityManagerInterface;
use Shopware\Bundle\PluginInstallerBundle\Service\InstallerService;
use Shopware\Components\Routing\RouterInterface;
use Shopware\Components\Test\Plugin\TestCase;
use Shopware\Models\Shop\Shop;
use Symfony\Component\DependencyInjection\Container;
use Wirecard\PaymentSdk\Config\Config;
use Wirecard\PaymentSdk\Config\PaymentMethodConfig;
use Wirecard\PaymentSdk\Transaction\UpiTransaction;
use WirecardElasticEngine\Components\Data\PaymentConfig;
use WirecardElasticEngine\Components\Payments\UnionpayInternationalPayment;

class UnionpayInternationalPaymentTest extends TestCase
{
    /** @var Container */
    private $container;

    /** @var EntityManagerInterface */
    private $em;

    /** @var \Shopware_Components_Config $config */
    private $config;

    /** @var InstallerService $config */
    private $installer;

    /** @var RouterInterface $config */
    private $router;

    /** @var \Enlight_Event_EventManager $config */
    private $eventManager;

    /** @var UnionpayInternationalPayment */
    protected $payment;

    public function setUp()
    {
        $this->container    = \Shopware()->Container();
        $this->em           = $this->container->get('models');
        $this->config       = $this->container->get('config');
        $this->installer    = $this->container->get('shopware_plugininstaller.plugin_manager');
        $this->router       = $this->container->get('router');
        $this->eventManager = $this->container->get('events');

        $this->payment = new UnionpayInternationalPayment(
            $this->em,
            $this->config,
            $this->installer,
            $this->router,
            $this->eventManager
        );
    }

    public function testGetPaymentOptions()
    {
        $this->assertEquals('WirecardUnionPayInternational', $this->payment->getLabel());
        $this->assertEquals('wirecard_elastic_engine_unionpay_international', $this->payment->getName());
        $this->assertEquals([
            'name'                  => 'wirecard_elastic_engine_unionpay_international',
            'description'           => 'WirecardUnionPayInternational',
            'action'                => 'WirecardElasticEnginePayment',
            'active'                => 0,
            'position'              => 10,
            'additionalDescription' => '',
        ], $this->payment->getPaymentOptions());
    }

    public function testGetTransaction()
    {
        $this->assertInstanceOf(UpiTransaction::class, $this->payment->getTransaction());
    }

    public function testGetPaymentConfig()
    {
        $config = $this->payment->getPaymentConfig();

        $this->assertInstanceOf(PaymentConfig::class, $config);
        $this->assertEquals('https://api-test.wirecard.com', $config->getBaseUrl());
        $this->assertEquals('70000-APILUHN-CARD', $config->getHttpUser());
        $this->assertEquals('8mhwavKVb91T', $config->getHttpPassword());
        $this->assertEquals('c6e9331c-5c1f-4fc6-8a08-ef65ce09ddb0', $config->getTransactionMAID());
        $this->assertEquals('16d85b73-79e2-4c33-932a-7da99fb04a9c', $config->getTransactionSecret());
        $this->assertEquals('pay', $config->getTransactionOperation());
    }

    public function testGetTransactionConfig()
    {
        $shop = $this->container->get('models')->getRepository(Shop::class)->getActiveDefault();

        $config = $this->payment->getTransactionConfig($shop, $this->container->getParameterBag(), 'EUR');

        $this->assertInstanceOf(Config::class, $config);
        $this->assertEquals('https://api-test.wirecard.com', $config->getBaseUrl());
        $this->assertEquals('70000-APILUHN-CARD', $config->getHttpUser());
        $this->assertEquals('8mhwavKVb91T', $config->getHttpPassword());
        $methodConfig = $config->get(UpiTransaction::NAME);
        $this->assertInstanceOf(PaymentMethodConfig::class, $methodConfig);
        $this->assertEquals('c6e9331c-5c1f-4fc6-8a08-ef65ce09ddb0', $methodConfig->getMerchantAccountId());
        $this->assertEquals('16d85b73-79e2-4c33-932a-7da99fb04a9c', $methodConfig->getSecret());
        $this->assertEquals([
            'headers' => [
                'shop-system-name'    => 'Shopware',
                'shop-system-version' => '___VERSION___',
                'plugin-name'         => 'WirecardElasticEngine',
                'plugin-version'      => '0.5.0',
            ],
        ], $config->getShopHeader());
    }

    public function testGetTransactionType()
    {
        $this->assertEquals('purchase', $this->payment->getTransactionType());
    }
}
