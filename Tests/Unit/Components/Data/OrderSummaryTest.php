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
use WirecardShopwareElasticEngine\Components\Data\OrderSummary;
use WirecardShopwareElasticEngine\Components\Mapper\BasketMapper;
use WirecardShopwareElasticEngine\Components\Mapper\UserMapper;
use WirecardShopwareElasticEngine\Components\Payments\PaymentInterface;

class OrderSummaryTest extends TestCase
{
    public function testOrderSummary()
    {
        /** @var PaymentInterface|\PHPUnit_Framework_MockObject_MockObject $payment */
        $payment = $this->createMock(PaymentInterface::class);
        /** @var UserMapper|\PHPUnit_Framework_MockObject_MockObject $user */
        $user = $this->createMock(UserMapper::class);
        /** @var BasketMapper|\PHPUnit_Framework_MockObject_MockObject $basket */
        $basket = $this->createMock(BasketMapper::class);
        /** @var Amount|\PHPUnit_Framework_MockObject_MockObject $amount */
        $amount = $this->createMock(Amount::class);

        $order = new OrderSummary(
            $payment,
            $user,
            $basket,
            $amount
        );

        $this->assertSame($payment, $order->getPayment());
        $this->assertSame($user, $order->getUserMapper());
        $this->assertSame($basket, $order->getBasketMapper());
        $this->assertSame($amount, $order->getAmount());
    }
}