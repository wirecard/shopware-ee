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
use Wirecard\PaymentSdk\Transaction\IdealTransaction;
use Wirecard\PaymentSdk\Transaction\SepaCreditTransferTransaction;
use WirecardElasticEngine\Components\Data\PaymentConfig;
use WirecardElasticEngine\Components\Payments\IdealPayment;

class IdealPaymentTest extends TestCase
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

    /** @var IdealPayment */
    protected $payment;

    public function setUp()
    {
        $this->container    = \Shopware()->Container();
        $this->em           = $this->container->get('models');
        $this->config       = $this->container->get('config');
        $this->installer    = $this->container->get('shopware_plugininstaller.plugin_manager');
        $this->router       = $this->container->get('router');
        $this->eventManager = $this->container->get('events');

        $this->payment = new IdealPayment(
            $this->em,
            $this->config,
            $this->installer,
            $this->router,
            $this->eventManager
        );
    }

    public function testGetPaymentOptions()
    {
        $this->assertEquals('WirecardiDEAL', $this->payment->getLabel());
        $this->assertEquals('wirecard_elastic_engine_ideal', $this->payment->getName());
        $this->assertEquals([
            'name'                  => 'wirecard_elastic_engine_ideal',
            'description'           => 'WirecardiDEAL',
            'action'                => 'WirecardElasticEnginePayment',
            'active'                => 0,
            'position'              => 3,
            'additionalDescription' => '',
        ], $this->payment->getPaymentOptions());
    }

    public function testGetTransaction()
    {
        $this->assertInstanceOf(IdealTransaction::class, $this->payment->getTransaction());
    }

    public function testGetPaymentConfig()
    {
        $config = $this->payment->getPaymentConfig();

        $this->assertInstanceOf(PaymentConfig::class, $config);
        $this->assertEquals('https://api-test.wirecard.com', $config->getBaseUrl());
        $this->assertEquals('16390-testing', $config->getHttpUser());
        $this->assertEquals('3!3013=D3fD8X7', $config->getHttpPassword());
        $this->assertEquals('4aeccf39-0d47-47f6-a399-c05c1f2fc819', $config->getTransactionMAID());
        $this->assertEquals('dbc5a498-9a66-43b9-bf1d-a618dd399684', $config->getTransactionSecret());
        $this->assertEquals('59a01668-693b-49f0-8a1f-f3c1ba025d45', $config->getBackendTransactionMAID());
        $this->assertEquals('ecdf5990-0372-47cd-a55d-037dccfe9d25', $config->getBackendTransactionSecret());
        $this->assertEquals('DE98ZZZ09999999999', $config->getBackendCreditorId());
        $this->assertEquals('pay', $config->getTransactionOperation());
    }

    public function testGetTransactionConfig()
    {
        $shop = $this->container->get('models')->getRepository(Shop::class)->getActiveDefault();

        $config = $this->payment->getTransactionConfig($this->container->getParameterBag(), 'EUR');

        $this->assertInstanceOf(Config::class, $config);
        $this->assertEquals('https://api-test.wirecard.com', $config->getBaseUrl());
        $this->assertEquals('16390-testing', $config->getHttpUser());
        $this->assertEquals('3!3013=D3fD8X7', $config->getHttpPassword());
        $this->assertInstanceOf(PaymentMethodConfig::class, $config->get(IdealTransaction::NAME));
        $this->assertEquals(
            '4aeccf39-0d47-47f6-a399-c05c1f2fc819',
            $config->get(IdealTransaction::NAME)->getMerchantAccountId());
        $this->assertEquals(
            'dbc5a498-9a66-43b9-bf1d-a618dd399684',
            $config->get(IdealTransaction::NAME)->getSecret()
        );
        $this->assertInstanceOf(PaymentMethodConfig::class, $config->get(SepaCreditTransferTransaction::NAME));
        $this->assertEquals(
            '59a01668-693b-49f0-8a1f-f3c1ba025d45',
            $config->get(SepaCreditTransferTransaction::NAME)->getMerchantAccountId());
        $this->assertEquals(
            'ecdf5990-0372-47cd-a55d-037dccfe9d25',
            $config->get(SepaCreditTransferTransaction::NAME)->getSecret()
        );
    }

    public function testGetTransactionType()
    {
        $this->assertEquals('purchase', $this->payment->getTransactionType());
    }
}
