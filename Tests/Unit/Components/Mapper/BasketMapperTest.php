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

namespace WirecardShopwareElasticEngine\Tests\Unit\Components\Mapper;

use PHPUnit\Framework\TestCase;
use Wirecard\PaymentSdk\Entity\Basket;
use Wirecard\PaymentSdk\Transaction\Transaction;
use WirecardShopwareElasticEngine\Components\Mapper\BasketMapper;
use WirecardShopwareElasticEngine\Exception\InvalidBasketException;

class BasketMapperTest extends TestCase
{
    public function testBasket()
    {
        /** @var \sArticles|\PHPUnit_Framework_MockObject_MockObject $articles */
        $articles = $this->createMock(\sArticles::class);
        $articles->expects($this->exactly(2))->method('sGetProductByOrdernumber')->willReturn([
            'isAvailable' => true,
            'laststock'   => false,
            'instock'     => 1,
        ]);
        /** @var Transaction|\PHPUnit_Framework_MockObject_MockObject $transaction */
        $transaction = $this->createMock(Transaction::class);

        $basketArray = [
            'content' => [
                [
                    'articlename' => 'foo',
                    'ordernumber' => 10,
                    'tax'         => 150,
                    'tax_rate'    => 15,
                    'quantity'    => 1,
                    'price'       => 1000,
                ],
                [
                    'articlename' => 'bar',
                    'ordernumber' => 11,
                    'tax'         => 100,
                    'tax_rate'    => 20,
                    'quantity'    => 2,
                    'price'       => 500,
                ],
            ],
        ];
        $mapper      = new BasketMapper($basketArray, 'EUR', $articles, $transaction);
        $this->assertEquals($basketArray, $mapper->getShopwareBasket());
        $this->assertEquals(implode("\n", [
            'foo - 10 - 1000 - EUR - 1 - 15%',
            'bar - 11 - 500 - EUR - 2 - 20%',
        ]), $mapper->getBasketText());
        $this->assertInstanceOf(Basket::class, $mapper->getWirecardBasket());
        $this->assertEquals([
            'order-item' => [
                [
                    'name'           => 'foo',
                    'quantity'       => 1,
                    'amount'         => ['currency' => 'EUR', 'value' => 1000.0],
                    'description'    => '',
                    'article-number' => 10,
                    'tax-rate'       => 15,
                ],
                [
                    'name'           => 'bar',
                    'quantity'       => 2,
                    'amount'         => ['currency' => 'EUR', 'value' => 500.0],
                    'description'    => '',
                    'article-number' => 11,
                    'tax-rate'       => 20,
                ],
            ],
        ], $mapper->getWirecardBasket()->mappedProperties());
        $this->assertEquals(['content' => $basketArray['content']], $mapper->toArray());
    }

    public function testBasketWithShipping()
    {
        /** @var \sArticles|\PHPUnit_Framework_MockObject_MockObject $articles */
        $articles = $this->createMock(\sArticles::class);
        $articles->expects($this->once())->method('sGetProductByOrdernumber')->willReturn([
            'isAvailable' => true,
            'laststock'   => true,
            'instock'     => 1,
        ]);
        /** @var Transaction|\PHPUnit_Framework_MockObject_MockObject $transaction */
        $transaction = $this->createMock(Transaction::class);

        $basketArray = [
            'content'               => [
                [
                    'articlename' => 'foo',
                    'ordernumber' => 10,
                    'tax'         => 200,
                    'tax_rate'    => 20,
                    'quantity'    => 1,
                    'price'       => 1000,
                ],
            ],
            'sShippingcostsWithTax' => 10,
            'sShippingcostsTax'     => 2,
        ];
        $mapper      = new BasketMapper($basketArray, 'USD', $articles, $transaction);
        $this->assertEquals($basketArray, $mapper->getShopwareBasket());
        $this->assertEquals(implode("\n", [
            'foo - 10 - 1000 - USD - 1 - 20%',
            'Shipping - shipping - 10 USD - 2',
        ]), $mapper->getBasketText());
        $this->assertInstanceOf(Basket::class, $mapper->getWirecardBasket());
        $this->assertEquals([
            'order-item' => [
                [
                    'name'           => 'foo',
                    'quantity'       => 1,
                    'amount'         => ['currency' => 'USD', 'value' => 1000.0],
                    'description'    => '',
                    'article-number' => 10,
                    'tax-rate'       => 20,
                ],
            ],
        ], $mapper->getWirecardBasket()->mappedProperties());
        $this->assertEquals(['content' => $basketArray['content']], $mapper->toArray());
    }

    public function testBasketArticleNotAvailable()
    {
        /** @var \sArticles|\PHPUnit_Framework_MockObject_MockObject $articles */
        $articles = $this->createMock(\sArticles::class);
        $articles->expects($this->once())->method('sGetProductByOrdernumber')->willReturn([
            'isAvailable' => false,
        ]);
        /** @var Transaction|\PHPUnit_Framework_MockObject_MockObject $transaction */
        $transaction = $this->createMock(Transaction::class);

        $this->expectException(InvalidBasketException::class);
        new BasketMapper([
            'content' => [
                [
                    'articlename' => 'foo',
                    'ordernumber' => 10,
                    'tax'         => 200,
                    'tax_rate'    => 20,
                    'quantity'    => 1,
                    'price'       => 1000,
                ],
            ],
        ], 'EUR', $articles, $transaction);
    }

    public function testBasketArticleOutOfStock()
    {
        /** @var \sArticles|\PHPUnit_Framework_MockObject_MockObject $articles */
        $articles = $this->createMock(\sArticles::class);
        $articles->expects($this->once())->method('sGetProductByOrdernumber')->willReturn([
            'isAvailable' => true,
            'laststock'   => true,
            'instock'     => 1,
        ]);
        /** @var Transaction|\PHPUnit_Framework_MockObject_MockObject $transaction */
        $transaction = $this->createMock(Transaction::class);

        $this->expectException(InvalidBasketException::class);
        new BasketMapper([
            'content' => [
                [
                    'articlename' => 'foo',
                    'ordernumber' => 10,
                    'tax'         => 200,
                    'tax_rate'    => 20,
                    'quantity'    => 2,
                    'price'       => 1000,
                ],
            ],
        ], 'EUR', $articles, $transaction);
    }

    public function testInvalidBasketException()
    {
        /** @var \sArticles|\PHPUnit_Framework_MockObject_MockObject $articles */
        $articles = $this->createMock(\sArticles::class);
        /** @var Transaction|\PHPUnit_Framework_MockObject_MockObject $transaction */
        $transaction = $this->createMock(Transaction::class);

        $this->expectException(InvalidBasketException::class);
        new BasketMapper([], 'EUR', $articles, $transaction);
    }
}
