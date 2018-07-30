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

namespace WirecardShopwareElasticEngine\Tests\Functional\Components\Payments;

use Doctrine\ORM\EntityManagerInterface;
use Shopware\Bundle\PluginInstallerBundle\Service\InstallerService;
use Shopware\Components\Routing\RouterInterface;
use Shopware\Components\Test\Plugin\TestCase;
use Shopware\Models\Shop\Shop;
use Symfony\Component\DependencyInjection\Container;
use Wirecard\PaymentSdk\Config\Config;
use Wirecard\PaymentSdk\Config\PaymentMethodConfig;
use Wirecard\PaymentSdk\Transaction\SepaCreditTransferTransaction;
use Wirecard\PaymentSdk\Transaction\SofortTransaction;
use WirecardShopwareElasticEngine\Components\Data\PaymentConfig;
use WirecardShopwareElasticEngine\Components\Payments\SofortPayment;

class SofortPaymentTest extends TestCase
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

    /** @var SofortPayment */
    protected $payment;

    public function setUp()
    {
        $this->container    = \Shopware()->Container();
        $this->em           = $this->container->get('models');
        $this->config       = $this->container->get('config');
        $this->installer    = $this->container->get('shopware_plugininstaller.plugin_manager');
        $this->router       = $this->container->get('router');
        $this->eventManager = $this->container->get('events');

        $this->payment = new SofortPayment(
            $this->em,
            $this->config,
            $this->installer,
            $this->router,
            $this->eventManager
        );
    }

    public function testGetPaymentOptions()
    {
        $this->assertEquals('WirecardSofort', $this->payment->getLabel());
        $this->assertEquals('wirecard_elastic_engine_sofort', $this->payment->getName());
        $this->assertEquals([
            'name'                  => 'wirecard_elastic_engine_sofort',
            'description'           => 'WirecardSofort',
            'action'                => 'WirecardElasticEnginePayment',
            'active'                => 0,
            'position'              => 9,
            'additionalDescription' => '',
        ], $this->payment->getPaymentOptions());
    }

    public function testGetTransaction()
    {
        $this->assertInstanceOf(SofortTransaction::class, $this->payment->getTransaction());
    }

    public function testGetPaymentConfig()
    {
        $config = $this->payment->getPaymentConfig();

        $this->assertInstanceOf(PaymentConfig::class, $config);
        $this->assertEquals('https://api-test.wirecard.com', $config->getBaseUrl());
        $this->assertEquals('16390-testing', $config->getHttpUser());
        $this->assertEquals('3!3013=D3fD8X7', $config->getHttpPassword());
        $this->assertEquals('6c0e7efd-ee58-40f7-9bbd-5e7337a052cd', $config->getTransactionMAID());
        $this->assertEquals('dbc5a498-9a66-43b9-bf1d-a618dd399684', $config->getTransactionSecret());
        $this->assertEquals('59a01668-693b-49f0-8a1f-f3c1ba025d45', $config->getBackendTransactionMAID());
        $this->assertEquals('ecdf5990-0372-47cd-a55d-037dccfe9d25', $config->getBackendTransactionSecret());
        $this->assertEquals('DE98ZZZ09999999999', $config->getBackendCreditorId());
        $this->assertEquals('pay', $config->getTransactionOperation());
    }

    public function testGetTransactionConfig()
    {
        $shop = $this->container->get('models')->getRepository(Shop::class)->getActiveDefault();

        $config = $this->payment->getTransactionConfig($shop, $this->container->getParameterBag(), 'EUR');

        $this->assertInstanceOf(Config::class, $config);
        $this->assertEquals('https://api-test.wirecard.com', $config->getBaseUrl());
        $this->assertEquals('16390-testing', $config->getHttpUser());
        $this->assertEquals('3!3013=D3fD8X7', $config->getHttpPassword());
        $this->assertInstanceOf(PaymentMethodConfig::class, $config->get(SofortTransaction::NAME));
        $this->assertEquals(
            '6c0e7efd-ee58-40f7-9bbd-5e7337a052cd',
            $config->get(SofortTransaction::NAME)->getMerchantAccountId());
        $this->assertEquals(
            'dbc5a498-9a66-43b9-bf1d-a618dd399684',
            $config->get(SofortTransaction::NAME)->getSecret()
        );
        $this->assertInstanceOf(PaymentMethodConfig::class, $config->get(SepaCreditTransferTransaction::NAME));
        $this->assertEquals(
            '59a01668-693b-49f0-8a1f-f3c1ba025d45',
            $config->get(SepaCreditTransferTransaction::NAME)->getMerchantAccountId());
        $this->assertEquals(
            'ecdf5990-0372-47cd-a55d-037dccfe9d25',
            $config->get(SepaCreditTransferTransaction::NAME)->getSecret()
        );
        $this->assertEquals([
            'headers' => [
                'shop-system-name'    => 'Shopware',
                'shop-system-version' => '___VERSION___',
                'plugin-name'         => 'WirecardShopwareElasticEngine',
                'plugin-version'      => '0.5.0',
            ],
        ], $config->getShopHeader());
    }

    public function testGetTransactionType()
    {
        $this->assertEquals('purchase', $this->payment->getTransactionType());
    }
}
