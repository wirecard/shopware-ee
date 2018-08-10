<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

namespace WirecardElasticEngine\Tests\Unit\Components\Mapper;

use PHPUnit\Framework\TestCase;
use Wirecard\PaymentSdk\Entity\Basket;
use Wirecard\PaymentSdk\Transaction\Transaction;
use WirecardElasticEngine\Components\Mapper\BasketMapper;
use WirecardElasticEngine\Exception\InvalidBasketException;
use WirecardElasticEngine\Exception\NotAvailableBasketException;
use WirecardElasticEngine\Exception\OutOfStockBasketException;

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
        /** @var \Shopware_Components_Snippet_Manager|\PHPUnit_Framework_MockObject_MockObject $snippetManager */
        $snippetManager = $this->createMock(\Shopware_Components_Snippet_Manager::class);

        $basketArray = [
            'content' => [
                [
                    'articlename' => 'foo',
                    'ordernumber' => 10,
                    'tax'         => 150,
                    'tax_rate'    => 15,
                    'quantity'    => 1,
                    'price'       => 999.99999,
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
        $mapper      = new BasketMapper(
            $basketArray,
            'SIGNATURE',
            'EUR',
            $articles,
            $transaction,
            $snippetManager,
            null
        );
        $this->assertEquals('SIGNATURE', $mapper->getSignature());
        $this->assertEquals($basketArray, $mapper->getShopwareBasket());
        $this->assertEquals(implode("\n", [
            'foo - 10 - 999.99999 - EUR - 1 - 15%',
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
        $this->assertEquals($basketArray, $mapper->toArray());
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
        /** @var \Shopware_Components_Snippet_Manager|\PHPUnit_Framework_MockObject_MockObject $snippetManager */
        $snippetManager = $this->createMock(\Shopware_Components_Snippet_Manager::class);
        $snippet        = $this->createMock(\Enlight_Components_Snippet_Namespace::class);
        $snippet->expects($this->atLeastOnce())->method('get')->willReturn('Shipping Name');
        $snippetManager->expects($this->atLeastOnce())->method('getNamespace')->willReturn($snippet);

        $basketArray    = [
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
        $shippingMethod = [
            'name' => 'Shipping Description',
        ];
        $mapper         = new BasketMapper(
            $basketArray,
            'SIGNATURE',
            'USD',
            $articles,
            $transaction,
            $snippetManager,
            $shippingMethod
        );
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
                [
                    'name'           => 'Shipping Name',
                    'quantity'       => 1,
                    'amount'         => ['currency' => 'USD', 'value' => 10.0],
                    'description'    => 'Shipping Description',
                    'article-number' => 'shipping',
                    'tax-rate'       => 2,
                ],
            ],
        ], $mapper->getWirecardBasket()->mappedProperties());
        $this->assertEquals($basketArray, $mapper->toArray());
    }

    public function testBasketShipping()
    {
        /** @var \sArticles|\PHPUnit_Framework_MockObject_MockObject $articles */
        $articles = $this->createMock(\sArticles::class);
        /** @var Transaction|\PHPUnit_Framework_MockObject_MockObject $transaction */
        $transaction = $this->createMock(Transaction::class);
        /** @var \Shopware_Components_Snippet_Manager|\PHPUnit_Framework_MockObject_MockObject $snippetManager */
        $snippetManager = $this->createMock(\Shopware_Components_Snippet_Manager::class);
        $snippet        = $this->createMock(\Enlight_Components_Snippet_Namespace::class);
        $snippet->expects($this->atLeastOnce())->method('get')->willReturn('Shipping Name');
        $snippetManager->expects($this->atLeastOnce())->method('getNamespace')->willReturn($snippet);

        // sShippingcostsNet has no effect here. See comment in BasketMapper::createWirecardBasket
        $basketArray = [
            'content'               => [],
            'sShippingcostsWithTax' => 10,
            'sShippingcostsNet'     => 5,
            'sShippingcostsTax'     => 2,
        ];
        $mapper      = new BasketMapper(
            $basketArray,
            'SIGNATURE',
            'USD',
            $articles,
            $transaction,
            $snippetManager,
            null
        );
        $this->assertEquals($basketArray, $mapper->getShopwareBasket());
        $this->assertEquals(implode("\n", [
            'Shipping - shipping - 10 USD - 2',
        ]), $mapper->getBasketText());
        $this->assertInstanceOf(Basket::class, $mapper->getWirecardBasket());
        $this->assertEquals([
            'order-item' => [
                [
                    'name'           => 'Shipping Name',
                    'quantity'       => 1,
                    'amount'         => ['currency' => 'USD', 'value' => 10.0],
                    'description'    => 'Shipping Name',
                    'article-number' => 'shipping',
                    'tax-rate'       => 2,
                ],
            ],
        ], $mapper->getWirecardBasket()->mappedProperties());
        $this->assertEquals($basketArray, $mapper->toArray());
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
        /** @var \Shopware_Components_Snippet_Manager|\PHPUnit_Framework_MockObject_MockObject $snippetManager */
        $snippetManager = $this->createMock(\Shopware_Components_Snippet_Manager::class);

        $this->expectException(NotAvailableBasketException::class);
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
        ], 'SIGNATURE', 'EUR', $articles, $transaction, $snippetManager, []);
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
        /** @var \Shopware_Components_Snippet_Manager|\PHPUnit_Framework_MockObject_MockObject $snippetManager */
        $snippetManager = $this->createMock(\Shopware_Components_Snippet_Manager::class);

        $this->expectException(OutOfStockBasketException::class);
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
        ], 'SIGNATURE', 'EUR', $articles, $transaction, $snippetManager, null);
    }

    public function testInvalidBasketException()
    {
        /** @var \sArticles|\PHPUnit_Framework_MockObject_MockObject $articles */
        $articles = $this->createMock(\sArticles::class);
        /** @var Transaction|\PHPUnit_Framework_MockObject_MockObject $transaction */
        $transaction = $this->createMock(Transaction::class);
        /** @var \Shopware_Components_Snippet_Manager|\PHPUnit_Framework_MockObject_MockObject $snippetManager */
        $snippetManager = $this->createMock(\Shopware_Components_Snippet_Manager::class);

        $this->expectException(InvalidBasketException::class);
        new BasketMapper([], 'SIGNATURE', 'EUR', $articles, $transaction, $snippetManager, null);
    }

    /**
     * @dataProvider numberFormatProvider
     *
     * @param string       $expected
     * @param string|float $amount
     */
    public function testNumberFormat($expected, $amount)
    {
        $this->assertSame($expected, BasketMapper::numberFormat($amount));
    }

    public function numberFormatProvider()
    {
        return [
            ['0.00', 0],
            ['1.00', 1],
            ['-1.00', -1],
            ['1.10', 1.10],
            ['-1.10', -1.1],
            ['10000.50', 10000.5],
            ['10.99', 10.99],
            ['11.00', 10.99999999],
            ['11.00', 10.995],
            ['10.99', 10.9949],
            ['10.00', 10.0001],
            ['0.00', '0'],
            ['1.00', '1'],
            ['-1.00', '-1'],
            ['1.10', '1.10'],
            ['-1.10', '-1.1'],
            ['10000.50', '10000.5'],
            ['10.99', '10.99'],
            ['11.00', '10.99999999'],
            ['11.00', '10.995'],
            ['10.99', '10.9949'],
            ['10.00', '10.0001'],
        ];
    }
}
