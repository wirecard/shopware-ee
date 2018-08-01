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
