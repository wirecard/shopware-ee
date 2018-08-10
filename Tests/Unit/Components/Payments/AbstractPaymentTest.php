<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

namespace WirecardElasticEngine\Tests\Unit\Components\Payments;

use WirecardElasticEngine\Components\Payments\Payment;
use WirecardElasticEngine\Components\Payments\PaymentInterface;
use WirecardElasticEngine\Tests\Unit\PaymentTestCase;

class AbstractPaymentTest extends PaymentTestCase
{
    /** @var Payment */
    protected $payment;

    public function setUp()
    {
        parent::setUp();

        $this->payment = $this->getMockForAbstractClass(Payment::class, [
            $this->em,
            $this->config,
            $this->installer,
            $this->router,
            $this->eventManager,
        ], 'FooPayment');
    }

    public function testInstanceOfPaymentInterface()
    {
        $this->assertInstanceOf(PaymentInterface::class, $this->payment);
    }

    public function testGetName()
    {
        $this->assertEquals('wirecard_ee_foo', $this->payment->getName());
    }

    public function testGetLabel()
    {
        $this->assertEquals('Wirecard EE Foo', $this->payment->getLabel());
    }

    public function testGetPaymentOptions()
    {
        $options = $this->payment->getPaymentOptions();
        $this->assertTrue(is_array($options));
        $this->assertArrayHasKey('name', $options);
        $this->assertArrayHasKey('description', $options);
        $this->assertArrayHasKey('action', $options);
        $this->assertArrayHasKey('active', $options);
        $this->assertArrayHasKey('position', $options);
        $this->assertArrayHasKey('additionalDescription', $options);
    }
}
