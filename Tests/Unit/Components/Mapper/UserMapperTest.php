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

namespace WirecardShopwareElasticEngine\Tests\Functional\Components\Payments;

use Shopware\Components\Test\Plugin\TestCase;
use WirecardShopwareElasticEngine\Components\Mapper\UserMapper;

class UserMapperTest extends TestCase
{
    protected $user = [
        'billingaddress' => [
            'city' => 'Footown',
            'street' => 'Barstreet',
            'zipcode' => 1337,
            'additionalAddressLine1' => 'Hodor'
        ],
        'shippingaddress' => [
            'city' => 'Shippingfootown',
            'street' => 'Shippingbarstreet',
            'zipcode' => 2710,
            'additionalAddressLine1' => 'Shodorpping'
        ],
        'additional' => [
            'countryShipping' => [
                'countryiso' => 'DE'
            ],
            'country' => [
                'countryiso' => 'AT'
            ]
        ]
    ];

    /**
     * @var UserMapper
     */
    protected $mapper;

    public function setUp()
    {
        $this->mapper = new UserMapper($this->user);
    }

    public function testGetBillingCity()
    {
        $this->assertEquals($this->user['billingaddress']['city'], $this->mapper->getBillingAddressCity());
    }

    public function testGetBillingStreet()
    {
        $this->assertEquals($this->user['billingaddress']['street'], $this->mapper->getBillingAddressStreet());
    }

    public function testGetBillingZip()
    {
        $this->assertEquals($this->user['billingaddress']['zipcode'], $this->mapper->getBillingAddressZip());
    }

    public function getBillingAdditionalAddress()
    {
        $this->assertEquals($this->user['billingaddress']['additionalAddressLine1'], $this->mapper->getBillingAddressAdditional());
    }

    public function testGetCountryIso()
    {
        $this->assertEquals($this->user['additional']['country']['countryiso'], $this->mapper->getCountryIso());
    }

    public function testGetShippingAddressCity()
    {
        $this->assertEquals($this->user['shippingaddress']['city'], $this->mapper->getShippingAddressCity());
    }

    public function testGetShippingAddressStreet()
    {
        $this->assertEquals($this->user['shippingaddress']['street'], $this->mapper->getShippingAddressStreet());
    }

    public function testGetShippingAddressZip()
    {
        $this->assertEquals($this->user['shippingaddress']['zipcode'], $this->mapper->getShippingAddressZip());
    }

    public function testGetShippingAddressAdditional()
    {
        $this->assertEquals($this->user['shippingaddress']['additionalAddressLine1'], $this->mapper->getShippingAddressAdditional());
    }

    public function testGetShippingAddressCountryIso()
    {
        $this->assertEquals($this->user['additional']['countryShipping']['countryiso'], $this->mapper->getShippingAddressCountryIso());
    }
}
