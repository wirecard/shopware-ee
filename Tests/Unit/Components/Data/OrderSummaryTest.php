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

namespace WirecardShopwareElasticEngine\Tests\Unit\Components\Data;

use PHPUnit\Framework\TestCase;
use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Entity\Device;
use Wirecard\PaymentSdk\Transaction\Transaction;
use WirecardShopwareElasticEngine\Components\Data\OrderSummary;
use WirecardShopwareElasticEngine\Components\Data\PaymentConfig;
use WirecardShopwareElasticEngine\Components\Mapper\BasketMapper;
use WirecardShopwareElasticEngine\Components\Mapper\UserMapper;
use WirecardShopwareElasticEngine\Components\Payments\PaymentInterface;

class OrderSummaryTest extends TestCase
{
    public function testOrderSummary()
    {
        /** @var PaymentInterface|\PHPUnit_Framework_MockObject_MockObject $payment */
        /** @var UserMapper|\PHPUnit_Framework_MockObject_MockObject $user */
        /** @var BasketMapper|\PHPUnit_Framework_MockObject_MockObject $basket */
        /** @var Amount|\PHPUnit_Framework_MockObject_MockObject $amount */

        $payment = $this->createMock(PaymentInterface::class);
        $user    = $this->createMock(UserMapper::class);
        $basket  = $this->createMock(BasketMapper::class);
        $amount  = $this->createMock(Amount::class);

        $order = new OrderSummary(
            20001,
            $payment,
            $user,
            $basket,
            $amount,
            'device-fingerprint',
            ['foo' => 'bar']
        );

        $this->assertSame(20001, $order->getPaymentUniqueId());
        $this->assertSame($payment, $order->getPayment());
        $this->assertSame($user, $order->getUserMapper());
        $this->assertSame($basket, $order->getBasketMapper());
        $this->assertSame($amount, $order->getAmount());
        $this->assertSame('device-fingerprint', $order->getDeviceFingerprintId());
        $device = $order->getWirecardDevice();
        $this->assertInstanceOf(Device::class, $device);
        $this->assertEquals('device-fingerprint', $device->getFingerprint());
        $this->assertEquals(['foo' => 'bar'], $order->getAdditionalPaymentData());
    }

    public function testToArray()
    {
        /** @var PaymentInterface|\PHPUnit_Framework_MockObject_MockObject $payment */
        /** @var UserMapper|\PHPUnit_Framework_MockObject_MockObject $user */
        /** @var BasketMapper|\PHPUnit_Framework_MockObject_MockObject $basket */
        /** @var Amount|\PHPUnit_Framework_MockObject_MockObject $amount */

        $paymentConfig = $this->createMock(PaymentConfig::class);
        $paymentConfig->method('toArray')->willReturn(['paymentConfig']);

        $transaction = $this->createMock(Transaction::class);
        $transaction->method('mappedProperties')->willReturn(['transaction']);

        $payment = $this->createMock(PaymentInterface::class);
        $payment->method('getName')->willReturn('paymentName');
        $payment->method('getPaymentConfig')->willReturn($paymentConfig);
        $payment->method('getTransaction')->willReturn($transaction);

        $user = $this->createMock(UserMapper::class);
        $user->method('toArray')->willReturn(['user']);

        $basket = $this->createMock(BasketMapper::class);
        $basket->method('toArray')->willReturn(['basket']);

        $amount = $this->createMock(Amount::class);
        $amount->method('mappedProperties')->willReturn(['amount']);

        $order = new OrderSummary(
            '1532083067-uniqueId',
            $payment,
            $user,
            $basket,
            $amount,
            'device-fingerprint'
        );
        $this->assertEquals([
            'paymentUniqueId' => '1532083067-uniqueId',
            'payment'         => [
                'name'          => 'paymentName',
                'paymentConfig' => ['paymentConfig'],
                'transaction'   => ['transaction'],
            ],
            'user'            => ['user'],
            'basket'          => ['basket'],
            'amount'          => ['amount'],
        ], $order->toArray());
    }
}
