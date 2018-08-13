<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

namespace WirecardElasticEngine\Tests\Unit\Components\Mapper;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Shopware\Models\Dispatch\Dispatch;
use Shopware\Models\Order\Detail;
use Shopware\Models\Order\Order;
use Wirecard\PaymentSdk\Entity\Basket;
use WirecardElasticEngine\Components\Mapper\OrderBasketMapper;

class OrderBasketMapperTest extends TestCase
{
    public function testCreateBasket()
    {
        $details = new ArrayCollection();
        $detail  = new Detail();
        $detail->setPrice(40.30);
        $detail->setQuantity(2);
        $detail->setTaxRate(20);
        $detail->setArticleNumber('foo');
        $details->add($detail);

        $detail = new Detail();
        $detail->setPrice(20.10);
        $detail->setQuantity(1);
        $detail->setTaxRate(20);
        $detail->setArticleNumber('bar');
        $details->add($detail);

        $order = $this->createMock(Order::class);
        $order->method('getDetails')->willReturn($details);
        $order->method('getCurrency')->willReturn('USD');
        $order->method('getInvoiceShipping')->willReturn(30.30);
        $order->method('getInvoiceShippingNet')->willReturn(25.25);
        $dispatch = new Dispatch();
        $dispatch->setName('dispatch');
        $order->method('getDispatch')->willReturn($dispatch);

        $mapper = new OrderBasketMapper();
        $basket = $mapper->createBasket($order);

        $this->assertInstanceOf(Basket::class, $basket);
        $this->assertEquals(40.30 * 2 + 20.10 + 30.30, $basket->getTotalAmount()->getValue());
        $this->assertEquals('USD', $basket->getTotalAmount()->getCurrency());
        $this->assertEquals([
            'order-item' => [
                [
                    'name'           => null,
                    'quantity'       => 2,
                    'amount'         => ['currency' => 'USD', 'value' => 40.3],
                    'article-number' => 'foo',
                    'tax-rate'       => 20,
                ],
                [
                    'name'           => null,
                    'quantity'       => 1,
                    'amount'         => ['currency' => 'USD', 'value' => 20.1],
                    'article-number' => 'bar',
                    'tax-rate'       => 20,
                ],
                [
                    'name'           => 'Shipping',
                    'quantity'       => 1,
                    'amount'         => ['currency' => 'USD', 'value' => 30.3],
                    'article-number' => 'shipping',
                    'tax-rate'       => 20.0,
                    'description'    => 'dispatch',
                ],
            ],
        ], $basket->mappedProperties());
    }
}
