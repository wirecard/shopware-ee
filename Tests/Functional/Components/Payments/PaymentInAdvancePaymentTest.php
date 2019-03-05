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
use Wirecard\PaymentSdk\Transaction\PoiPiaTransaction;
use WirecardElasticEngine\Components\Data\PaymentConfig;
use WirecardElasticEngine\Components\Payments\PaymentInAdvancePayment;

class PaymentInAdvancePaymentTest extends TestCase
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

    /** @var \Shopware_Components_Snippet_Manager $config */
    private $snippetManager;

    /** @var PaymentInAdvancePayment */
    protected $payment;

    public function setUp()
    {
        $this->container      = \Shopware()->Container();
        $this->em             = $this->container->get('models');
        $this->config         = $this->container->get('config');
        $this->installer      = $this->container->get('shopware_plugininstaller.plugin_manager');
        $this->router         = $this->container->get('router');
        $this->eventManager   = $this->container->get('events');
        $this->snippetManager = $this->container->get('snippets');

        $this->payment = new PaymentInAdvancePayment(
            $this->em,
            $this->config,
            $this->installer,
            $this->router,
            $this->eventManager,
            $this->snippetManager
        );
    }

    public function testGetPaymentOptions()
    {
        $this->assertEquals('WirecardPaymentInAdvance', $this->payment->getLabel());
        $this->assertEquals('wirecard_elastic_engine_pia', $this->payment->getName());
        $this->assertEquals([
            'name'                  => 'wirecard_elastic_engine_pia',
            'description'           => 'WirecardPaymentInAdvance',
            'action'                => 'WirecardElasticEnginePayment',
            'active'                => 0,
            'position'              => 6,
            'additionalDescription' => '',
        ], $this->payment->getPaymentOptions());
    }

    public function testGetTransaction()
    {
        $this->assertInstanceOf(PoiPiaTransaction::class, $this->payment->getTransaction());
    }

    public function testGetPaymentConfig()
    {
        $config = $this->payment->getPaymentConfig();

        $this->assertInstanceOf(PaymentConfig::class, $config);
        $this->assertEquals('https://api-test.wirecard.com', $config->getBaseUrl());
        $this->assertEquals('70000-APITEST-AP', $config->getHttpUser());
        $this->assertEquals('qD2wzQ_hrc!8', $config->getHttpPassword());
        $this->assertEquals('105ab3e8-d16b-4fa0-9f1f-18dd9b390c94', $config->getTransactionMAID());
        $this->assertEquals('2d96596b-9d10-4c98-ac47-4d56e22fd878', $config->getTransactionSecret());
        $this->assertEquals('reserve', $config->getTransactionOperation());
    }

    public function testGetTransactionConfig()
    {
        $shop = $this->container->get('models')->getRepository(Shop::class)->getActiveDefault();

        $config = $this->payment->getTransactionConfig($shop, $this->container->getParameterBag(), 'EUR');

        $this->assertInstanceOf(Config::class, $config);
        $this->assertEquals('https://api-test.wirecard.com', $config->getBaseUrl());
        $this->assertEquals('70000-APITEST-AP', $config->getHttpUser());
        $this->assertEquals('qD2wzQ_hrc!8', $config->getHttpPassword());
        $methodConfig = $config->get(PoiPiaTransaction::NAME);
        $this->assertInstanceOf(PaymentMethodConfig::class, $methodConfig);
        $this->assertEquals('105ab3e8-d16b-4fa0-9f1f-18dd9b390c94', $methodConfig->getMerchantAccountId());
        $this->assertEquals('2d96596b-9d10-4c98-ac47-4d56e22fd878', $methodConfig->getSecret());

    }

    public function testGetTransactionType()
    {
        $this->assertEquals('authorization', $this->payment->getTransactionType());
    }
}
