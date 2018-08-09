<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

namespace WirecardElasticEngine\Tests\Unit\Components\Services;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Shopware\Bundle\PluginInstallerBundle\Service\InstallerService;
use Shopware\Components\Routing\RouterInterface;
use Wirecard\PaymentSdk\Transaction\Transaction;
use WirecardElasticEngine\Components\Payments\PaymentInterface;
use WirecardElasticEngine\Components\Payments\PaypalPayment;
use WirecardElasticEngine\Components\Services\PaymentFactory;
use WirecardElasticEngine\Exception\UnknownPaymentException;

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
            $this->assertStringStartsWith('Wirecard', $payment->getLabel());
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
