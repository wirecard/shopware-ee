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
use Wirecard\PaymentSdk\Transaction\RatepayInvoiceTransaction;
use WirecardElasticEngine\Components\Data\PaymentConfig;
use WirecardElasticEngine\Components\Payments\RatepayInvoicePayment;

class RatepayInvoicePaymentTest extends TestCase
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

    /** @var RatepayInvoicePayment */
    protected $payment;

    public function setUp()
    {
        $this->container    = \Shopware()->Container();
        $this->em           = $this->container->get('models');
        $this->config       = $this->container->get('config');
        $this->installer    = $this->container->get('shopware_plugininstaller.plugin_manager');
        $this->router       = $this->container->get('router');
        $this->eventManager = $this->container->get('events');

        $this->payment = new RatepayInvoicePayment(
            $this->em,
            $this->config,
            $this->installer,
            $this->router,
            $this->eventManager
        );
    }

    public function testGetPaymentOptions()
    {
        $this->assertEquals('WirecardRatepayInvoice', $this->payment->getLabel());
        $this->assertEquals('wirecard_elastic_engine_ratepay_invoice', $this->payment->getName());
        $this->assertEquals([
            'name'                  => 'wirecard_elastic_engine_ratepay_invoice',
            'description'           => 'WirecardRatepayInvoice',
            'action'                => 'WirecardElasticEnginePayment',
            'active'                => 0,
            'position'              => 2,
            'additionalDescription' => '',
        ], $this->payment->getPaymentOptions());
    }

    public function testGetTransaction()
    {
        $this->assertInstanceOf(RatepayInvoiceTransaction::class, $this->payment->getTransaction());
    }

    public function testGetPaymentConfig()
    {
        $config = $this->payment->getPaymentConfig();

        $this->assertInstanceOf(PaymentConfig::class, $config);
        $this->assertEquals('https://api-test.wirecard.com', $config->getBaseUrl());
        $this->assertEquals('70000-APITEST-AP', $config->getHttpUser());
        $this->assertEquals('qD2wzQ_hrc!8', $config->getHttpPassword());
        $this->assertEquals('fa02d1d4-f518-4e22-b42b-2abab5867a84', $config->getTransactionMAID());
        $this->assertEquals('dbc5a498-9a66-43b9-bf1d-a618dd399684', $config->getTransactionSecret());
        $this->assertEquals('reserve', $config->getTransactionOperation());
        $this->assertTrue($config->sendBasket());
        $this->assertEquals('20', $config->getMinAmount());
        $this->assertEquals('3500', $config->getMaxAmount());
        $this->assertEquals([1], $config->getAcceptedCurrencies());
        $this->assertEquals([2, 23, 26], $config->getShippingCountries());
        $this->assertEquals([2, 23, 26], $config->getBillingCountries());
        $this->assertFalse($config->isAllowedDifferentBillingShipping());
        $this->assertTrue($config->hasFraudPrevention());
    }

    public function testGetTransactionConfig()
    {
        $config = $this->payment->getTransactionConfig($this->container->getParameterBag(), 'EUR');

        $this->assertInstanceOf(Config::class, $config);
        $this->assertEquals('https://api-test.wirecard.com', $config->getBaseUrl());
        $this->assertEquals('70000-APITEST-AP', $config->getHttpUser());
        $this->assertEquals('qD2wzQ_hrc!8', $config->getHttpPassword());
        $paymentMethod = $config->get(RatepayInvoiceTransaction::NAME);
        $this->assertInstanceOf(PaymentMethodConfig::class, $paymentMethod);
        $this->assertEquals('fa02d1d4-f518-4e22-b42b-2abab5867a84', $paymentMethod->getMerchantAccountId());
        $this->assertEquals('dbc5a498-9a66-43b9-bf1d-a618dd399684', $paymentMethod->getSecret());
    }

    public function testGetTransactionType()
    {
        $this->assertEquals('authorization', $this->payment->getTransactionType());
    }
}
