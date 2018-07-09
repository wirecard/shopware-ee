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
use Wirecard\PaymentSdk\Entity\Item;
use WirecardShopwareElasticEngine\Components\Mapper\BasketItemMapper;
use WirecardShopwareElasticEngine\Exception\InvalidBasketItemException;

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
                'description' => 'foobar',
            ],
        ];
        $mapper    = new BasketItemMapper($itemArray, 'EUR');

        $this->assertEquals($itemArray, $mapper->getShopwareItem());

        $item = $mapper->getWirecardItem();
        $this->assertInstanceOf(Item::class, $item);
        $this->assertEquals('foo', $mapper->getArticleName());
        $this->assertEquals(1, $mapper->getQuantity());
        $this->assertEquals('foobar', $mapper->getDescription());
        $this->assertEquals(['description' => 'foobar'], $mapper->getAdditionalDetails());
        $this->assertEquals(10, $mapper->getOrderNumber());
        $this->assertEquals(1000, $mapper->getPrice());
        $this->assertEquals(200, $mapper->getTax());
        $this->assertEquals(20, $mapper->getTaxRate());
    }

    public function testBasketPrices()
    {
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

        $itemArray['additional_details'] = [
            'description'   => 'foobar',
            'price_numeric' => 1000.51,
        ];

        $mapper = new BasketItemMapper($itemArray, 'EUR');
        $this->assertEquals(1000.51, $mapper->getPrice());

        $itemArray['additional_details']['prices'] = [['price_numeric' => 1000.52]];

        $mapper = new BasketItemMapper($itemArray, 'EUR');
        $this->assertEquals(1000.52, $mapper->getPrice());
    }

    public function testInvalidBasketItemException()
    {
        $this->expectException(InvalidBasketItemException::class);
        new BasketItemMapper([], 'EUR');
    }
}
