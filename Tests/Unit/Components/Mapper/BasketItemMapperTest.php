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
use Wirecard\PaymentSdk\Entity\Item;
use WirecardElasticEngine\Components\Mapper\BasketItemMapper;
use WirecardElasticEngine\Exception\InvalidBasketItemException;

class BasketItemMapperTest extends TestCase
{
    public function testBasket()
    {
        $itemArray = [
            'articlename'        => 'foo',
            'ordernumber'        => 10,
            'tax'                => 200,
            'tax_rate'           => 20,
            'quantity'           => 1,
            'price'              => 1000,
            'additional_details' => [
                'description' => 'foobar ✅',
            ],
        ];
        $mapper    = new BasketItemMapper($itemArray, 'EUR');

        $this->assertEquals($itemArray, $mapper->getShopwareItem());

        $item = $mapper->getWirecardItem();
        $this->assertInstanceOf(Item::class, $item);
        $this->assertEquals('foo', $mapper->getArticleName());
        $this->assertEquals(1, $mapper->getQuantity());
        $this->assertEquals('foobar', $mapper->getDescription());
        $this->assertEquals(10, $mapper->getArticleNumber());
        $this->assertEquals(1000, $mapper->getPrice());
        $this->assertEquals(200, $mapper->getTax());
        $this->assertEquals(20, $mapper->getTaxRate());

        $this->assertEquals([
            'name'           => 'foo',
            'quantity'       => 1,
            'amount'         => ['currency' => 'EUR', 'value' => 1000.0],
            'description'    => 'foobar',
            'article-number' => 10,
            'tax-rate'       => 20,
        ], $item->mappedProperties());
    }

    public function testBasketPrices()
    {
        // default behaviour
        $itemArray = [
            'articlename' => 'foo',
            'ordernumber' => 10,
            'tax'         => 200,
            'tax_rate'    => 20,
            'quantity'    => 1,
            'price'       => '1000,50',
        ];

        $mapper = new BasketItemMapper($itemArray, 'EUR');
        $this->assertEquals(1000.50, $mapper->getPrice());
        $this->assertEquals([
            'name'           => 'foo',
            'quantity'       => 1,
            'amount'         => ['currency' => 'EUR', 'value' => 1000.5],
            'description'    => '',
            'article-number' => 10,
            'tax-rate'       => 20,
        ], $mapper->getWirecardItem()->mappedProperties());

        // if the numeric price defined, use it
        $itemArray['priceNumeric'] = 1000.51;
        $mapper = new BasketItemMapper($itemArray, 'EUR');
        $this->assertEquals(1000.51, $mapper->getPrice());
        $this->assertEquals([
            'name'           => 'foo',
            'quantity'       => 1,
            'amount'         => ['currency' => 'EUR', 'value' => 1000.51],
            'description'    => '',
            'article-number' => 10,
            'tax-rate'       => 20,
        ], $mapper->getWirecardItem()->mappedProperties());

        // DON'T use THIS price, in case of bulk prices here stands the wrong price
        $itemArray['additional_details'] = [
            'description'   => 'foobar',
            'price_numeric' => 1000.52,
        ];

        $mapper = new BasketItemMapper($itemArray, 'EUR');
        $this->assertEquals(1000.51, $mapper->getPrice());
        $this->assertEquals([
            'name'           => 'foo',
            'quantity'       => 1,
            'amount'         => ['currency' => 'EUR', 'value' => 1000.51],
            'description'    => 'foobar',
            'article-number' => 10,
            'tax-rate'       => 20,
        ], $mapper->getWirecardItem()->mappedProperties());

        // ONE detail price: take it
        $itemArray['additional_details']['prices'] = [['price_numeric' => 1000.53]];

        $mapper = new BasketItemMapper($itemArray, 'USD');
        $this->assertEquals(1000.53, $mapper->getPrice());
        $this->assertEquals([
            'name'           => 'foo',
            'quantity'       => 1,
            'amount'         => ['currency' => 'USD', 'value' => 1000.53],
            'description'    => 'foobar',
            'article-number' => 10,
            'tax-rate'       => 20,
        ], $mapper->getWirecardItem()->mappedProperties());

        // MORE detail prices (bulk prices): ignore ALL because it is not clear which one is the right one
        $itemArray['additional_details']['prices'][] = ['price_numeric' => 1000.54];
        $mapper = new BasketItemMapper($itemArray, 'USD');
        $this->assertEquals(1000.51, $mapper->getPrice());
        $this->assertEquals([
            'name'           => 'foo',
            'quantity'       => 1,
            'amount'         => ['currency' => 'USD', 'value' => 1000.51],
            'description'    => 'foobar',
            'article-number' => 10,
            'tax-rate'       => 20,
        ], $mapper->getWirecardItem()->mappedProperties());
    }

    public function testInvalidBasketItemException()
    {
        $this->expectException(InvalidBasketItemException::class);
        new BasketItemMapper([], 'EUR');
    }
}
