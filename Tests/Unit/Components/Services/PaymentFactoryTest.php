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

namespace WirecardShopwareElasticEngine\Tests\Unit\Components\Services;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Shopware\Bundle\PluginInstallerBundle\Service\InstallerService;
use Shopware\Components\Routing\RouterInterface;
use Wirecard\PaymentSdk\Transaction\Transaction;
use WirecardShopwareElasticEngine\Components\Payments\PaymentInterface;
use WirecardShopwareElasticEngine\Components\Payments\PaypalPayment;
use WirecardShopwareElasticEngine\Components\Services\PaymentFactory;
use WirecardShopwareElasticEngine\Exception\UnknownPaymentException;

class PaymentFactoryTest extends TestCase
{
    /**
     * @var PaymentFactory
     */
    protected $factory;

    public function setUp()
    {
        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $em */
        /** @var \Shopware_Components_Config|\PHPUnit_Framework_MockObject_MockObject $config */
        /** @var InstallerService|\PHPUnit_Framework_MockObject_MockObject $installer */
        /** @var RouterInterface|\PHPUnit_Framework_MockObject_MockObject $router */
        /** @var \Enlight_Event_EventManager|\PHPUnit_Framework_MockObject_MockObject $router */

        $em           = $this->createMock(EntityManagerInterface::class);
        $config       = $this->createMock(\Shopware_Components_Config::class);
        $installer    = $this->createMock(InstallerService::class);
        $router       = $this->createMock(RouterInterface::class);
        $eventManager = $this->createMock(\Enlight_Event_EventManager::class);

        $this->factory = new PaymentFactory($em, $config, $installer, $router, $eventManager);
    }

    public function testPaypalInstance()
    {
        $this->assertInstanceOf(PaypalPayment::class, $this->factory->create(PaypalPayment::PAYMETHOD_IDENTIFIER));
    }

    public function testGetSupportedPayments()
    {
        $payments = $this->factory->getSupportedPayments();
        foreach ($payments as $payment) {
            $this->assertInstanceOf(PaymentInterface::class, $payment);
            $this->assertStringStartsWith('wirecard_elastic_engine_', $payment->getName());
            $this->assertStringStartsWith('Wirecard ', $payment->getLabel());
            $options = $payment->getPaymentOptions();
            $this->assertTrue(is_array($options));
            $this->assertArrayHasKey('name', $options);
            $this->assertArrayHasKey('description', $options);
            $this->assertArrayHasKey('action', $options);
            $this->assertArrayHasKey('active', $options);
            $this->assertArrayHasKey('position', $options);
            $this->assertArrayHasKey('additionalDescription', $options);
        }
    }

    public function testPaymentsGetTransactionImplementation()
    {
        $payments = $this->factory->getSupportedPayments();
        foreach ($payments as $payment) {
            $transaction = $payment->getTransaction();
            $this->assertInstanceOf(Transaction::class, $transaction);
            $this->assertSame($transaction, $payment->getTransaction());
        }
    }

    public function testUnknownPaymentException()
    {
        $this->expectException(UnknownPaymentException::class);
        $this->factory->create('foobar');
    }
}
