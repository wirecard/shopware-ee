<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

namespace WirecardElasticEngine\Tests\Unit\Components\Data;

use PHPUnit\Framework\TestCase;
use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Entity\Device;
use Wirecard\PaymentSdk\Transaction\Transaction;
use WirecardElasticEngine\Components\Data\OrderSummary;
use WirecardElasticEngine\Components\Data\PaymentConfig;
use WirecardElasticEngine\Components\Mapper\AccountInfoMapper;
use WirecardElasticEngine\Components\Mapper\BasketMapper;
use WirecardElasticEngine\Components\Mapper\RiskInfoMapper;
use WirecardElasticEngine\Components\Mapper\UserMapper;
use WirecardElasticEngine\Components\Payments\PaymentInterface;

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

        /** @var AccountInfoMapper|\PHPUnit_Framework_MockObject_MockObject $accountInfoMapper */
        $accountInfoMapper = $this->createMock(AccountInfoMapper::class);
        /** @var RiskInfoMapper|\PHPUnit_Framework_MockObject_MockObject $riskInfoMapper */
        $riskInfoMapper = $this->createMock(RiskInfoMapper::class);

        $order = new OrderSummary(
            20001,
            $payment,
            $user,
            $basket,
            $amount,
            $accountInfoMapper,
            $riskInfoMapper,
            'device-fingerprint',
            ['foo' => 'bar']
        );

        $this->assertSame(20001, $order->getPaymentUniqueId());
        $this->assertSame($payment, $order->getPayment());
        $this->assertSame($user, $order->getUserMapper());
        $this->assertSame($basket, $order->getBasketMapper());
        $this->assertSame($amount, $order->getAmount());
        $this->assertSame($accountInfoMapper, $order->getAccountInfoMapper());
        $this->assertSame($riskInfoMapper, $order->getRiskInfoMapper());
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

        /** @var AccountInfoMapper|\PHPUnit_Framework_MockObject_MockObject $accountInfoMapper */
        $accountInfoMapper = $this->createMock(AccountInfoMapper::class);

        /** @var RiskInfoMapper|\PHPUnit_Framework_MockObject_MockObject $riskInfoMapper */
        $riskInfoMapper = $this->createMock(RiskInfoMapper::class);

        $order = new OrderSummary(
            '1532083067-uniqueId',
            $payment,
            $user,
            $basket,
            $amount,
            $accountInfoMapper,
            $riskInfoMapper,
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
