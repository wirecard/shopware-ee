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
use Wirecard\PaymentSdk\Transaction\CreditCardTransaction;
use WirecardShopwareElasticEngine\Components\Data\PaymentConfig;
use WirecardShopwareElasticEngine\Components\Payments\CreditCardPayment;

class CreditCardPaymentTest extends TestCase
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

    /** @var CreditCardPayment */
    protected $payment;

    public function setUp()
    {
        $this->container    = \Shopware()->Container();
        $this->em           = $this->container->get('models');
        $this->config       = $this->container->get('config');
        $this->installer    = $this->container->get('shopware_plugininstaller.plugin_manager');
        $this->router       = $this->container->get('router');
        $this->eventManager = $this->container->get('events');

        $this->payment = new CreditCardPayment(
            $this->em,
            $this->config,
            $this->installer,
            $this->router,
            $this->eventManager
        );
    }

    public function testGetPaymentOptions()
    {
        $this->assertEquals('Wirecard Credit Card', $this->payment->getLabel());
        $this->assertEquals('wirecard_elastic_engine_credit_card', $this->payment->getName());
        $this->assertEquals([
            'name'                  => 'wirecard_elastic_engine_credit_card',
            'description'           => 'Wirecard Credit Card',
            'action'                => 'WirecardElasticEnginePayment',
            'active'                => 0,
            'position'              => 0,
            'additionalDescription' => '',
        ], $this->payment->getPaymentOptions());
    }

    public function testGetTransaction()
    {
        $this->assertInstanceOf(CreditCardTransaction::class, $this->payment->getTransaction());
    }

    public function testGetPaymentConfig()
    {
        $config = $this->payment->getPaymentConfig();

        $this->assertInstanceOf(PaymentConfig::class, $config);
        $this->assertEquals('https://api-test.wirecard.com', $config->getBaseUrl());
        $this->assertEquals('70000-APITEST-AP', $config->getHttpUser());
        $this->assertEquals('qD2wzQ_hrc!8', $config->getHttpPassword());
        $this->assertEquals('53f2895a-e4de-4e82-a813-0d87a10e55e6', $config->getTransactionMAID());
        $this->assertEquals('dbc5a498-9a66-43b9-bf1d-a618dd399684', $config->getTransactionSecret());
        $this->assertEquals('pay', $config->getTransactionOperation());
        $this->assertEquals('dbc5a498-9a66-43b9-bf1d-a618dd399684', $config->getThreeDSecret());
        $this->assertEquals(300, $config->getThreeDSslMaxLimit());
        $this->assertEmpty($config->getThreeDSslMaxLimitCurrency());
        $this->assertEquals(100, $config->getThreeDMinLimit());
        $this->assertEmpty($config->getThreeDMinLimitCurrency());
        $this->assertEquals('508b8896-b37d-4614-845c-26bf8bf2c948', $config->getThreeDMAID());
    }

    public function testGetTransactionConfig()
    {
        $shop = $this->container->get('models')->getRepository(Shop::class)->getActiveDefault();

        $config = $this->payment->getTransactionConfig($shop, $this->container->getParameterBag(), 'EUR');

        $this->assertInstanceOf(Config::class, $config);
        $this->assertEquals('https://api-test.wirecard.com', $config->getBaseUrl());
        $this->assertEquals('70000-APITEST-AP', $config->getHttpUser());
        $this->assertEquals('qD2wzQ_hrc!8', $config->getHttpPassword());
        $this->assertInstanceOf(PaymentMethodConfig::class, $config->get(CreditCardTransaction::NAME));
        $this->assertEquals(
            '53f2895a-e4de-4e82-a813-0d87a10e55e6',
            $config->get(CreditCardTransaction::NAME)->getMerchantAccountId()
        );
        $this->assertEquals(
            'dbc5a498-9a66-43b9-bf1d-a618dd399684',
            $config->get(CreditCardTransaction::NAME)->getSecret()
        );
        $this->assertEquals(
            '508b8896-b37d-4614-845c-26bf8bf2c948',
            $config->get(CreditCardTransaction::NAME)->getThreeDMerchantAccountId()
        );
    }

    public function testGetTransactionType()
    {
        $this->assertEquals('purchase', $this->payment->getTransactionType());
    }
}
