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
use Wirecard\PaymentSdk\Entity\AccountHolder;
use Wirecard\PaymentSdk\Entity\Address;
use WirecardShopwareElasticEngine\Components\Mapper\UserMapper;
use WirecardShopwareElasticEngine\Exception\ArrayKeyNotFoundException;

class UserMapperTest extends TestCase
{
    protected $user = [
        'additional'      => [
            'user'            => [
                'firstname' => 'First Name',
                'lastname'  => 'Last Name',
                'email'     => 'test@example.com',
                'birthday'  => '1.1.1990',
            ],
            'countryShipping' => [
                'countryiso' => 'DE',
            ],
            'country'         => [
                'countryiso' => 'AT',
            ],
        ],
        'billingaddress'  => [
            'phone'                  => '+43123456789',
            'city'                   => 'Footown',
            'street'                 => 'Barstreet',
            'zipcode'                => 1337,
            'additionalAddressLine1' => 'Hodor',
        ],
        'shippingaddress' => [
            'firstname'              => 'First Shipping',
            'lastname'               => 'Last Shipping',
            'phone'                  => '+43987654321',
            'city'                   => 'Shippingfootown',
            'street'                 => 'Shippingbarstreet',
            'zipcode'                => 2710,
            'additionalAddressLine1' => 'Shodorpping',
        ],
    ];

    protected $clientIp = '127.0.0.1';

    protected $locale = 'de_DE';

    /**
     * @var UserMapper
     */
    protected $mapper;

    public function setUp()
    {
        $this->mapper = new UserMapper($this->user, $this->clientIp, $this->locale);
    }

    public function testGetShopwareUser()
    {
        $this->assertEquals($this->user, $this->mapper->getShopwareUser());
    }

    public function testGetWirecardBillingAccountHolderWithAllFields()
    {
        $this->assertEquals('First Name', $this->mapper->getFirstName());
        $this->assertEquals('Last Name', $this->mapper->getLastName());
        $this->assertEquals('test@example.com', $this->mapper->getEmail());
        $this->assertEquals('+43123456789', $this->mapper->getPhone());
        $this->assertEquals(new \DateTime('1.1.1990'), $this->mapper->getBirthday());

        $account = $this->mapper->getWirecardBillingAccountHolder();
        $this->assertInstanceOf(AccountHolder::class, $account);
        $this->assertEquals([
            'first-name'    => $this->mapper->getFirstName(),
            'last-name'     => $this->mapper->getLastName(),
            'email'         => $this->mapper->getEmail(),
            'date-of-birth' => $this->mapper->getBirthday()->format('d-m-Y'),
            'phone'         => $this->mapper->getPhone(),
            'address'       => [
                'street1'     => 'Barstreet',
                'city'        => 'Footown',
                'country'     => 'AT',
                'postal-code' => 1337,
                'street2'     => 'Hodor',
            ],
        ], $account->mappedProperties());
    }

    public function testGetWirecardBillingAccountHolderWithOptionals()
    {
        $mapper  = new UserMapper([
            'additional'     => [
                'user'    => [
                    'firstname' => 'First Name',
                    'lastname'  => 'Last Name',
                    'email'     => 'test@example.com',
                ],
                'country' => [
                    'countryiso' => 'AT',
                ],
            ],
            'billingaddress' => [
                'city'   => 'Footown',
                'street' => 'Barstreet',
            ],
        ], '', '');
        $account = $mapper->getWirecardBillingAccountHolder();
        $this->assertInstanceOf(AccountHolder::class, $account);
        $this->assertEquals([
            'first-name' => 'First Name',
            'last-name'  => 'Last Name',
            'email'      => 'test@example.com',
            'address'    => [
                'street1' => 'Barstreet',
                'city'    => 'Footown',
                'country' => 'AT',
            ],
        ], $account->mappedProperties());
    }

    public function testGetWirecardBillingAddress()
    {
        $address = $this->mapper->getWirecardBillingAddress();
        $this->assertInstanceOf(Address::class, $address);
        $this->assertEquals([
            'street1'     => 'Barstreet',
            'city'        => 'Footown',
            'country'     => 'AT',
            'postal-code' => 1337,
            'street2'     => 'Hodor',
        ], $address->mappedProperties());
    }

    public function testGetWirecardShippingAccountHolderWithAllFields()
    {
        $this->assertEquals('First Shipping', $this->mapper->getShippingFirstName());
        $this->assertEquals('Last Shipping', $this->mapper->getShippingLastName());
        $this->assertEquals('+43987654321', $this->mapper->getShippingPhone());

        $account = $this->mapper->getWirecardShippingAccountHolder();
        $this->assertInstanceOf(AccountHolder::class, $account);
        $this->assertEquals([
            'first-name' => $this->mapper->getShippingFirstName(),
            'last-name'  => $this->mapper->getShippingLastName(),
            'phone'      => $this->mapper->getShippingPhone(),
            'address'    => [
                'street1'     => 'Shippingbarstreet',
                'city'        => 'Shippingfootown',
                'country'     => 'DE',
                'postal-code' => 2710,
                'street2'     => 'Shodorpping',
            ],
        ], $account->mappedProperties());
    }

    public function testGetWirecardShippingAccountHolderWithOptionals()
    {
        $mapper  = new UserMapper([
            'additional'      => [
                'countryShipping' => [
                    'countryiso' => 'DE',
                ],
            ],
            'shippingaddress' => [
                'firstname' => 'First Shipping',
                'lastname'  => 'Last Shipping',
                'city'      => 'Shippingfootown',
                'street'    => 'Shippingbarstreet',
            ],
        ], '', '');
        $account = $mapper->getWirecardShippingAccountHolder();
        $this->assertInstanceOf(AccountHolder::class, $account);
        $this->assertEquals([
            'first-name' => 'First Shipping',
            'last-name'  => 'Last Shipping',
            'address'    => [
                'street1' => 'Shippingbarstreet',
                'city'    => 'Shippingfootown',
                'country' => 'DE',
            ],
        ], $account->mappedProperties());
    }

    public function testGetWirecardShippingAccountHolderEmpty()
    {
        $mapper  = new UserMapper([], '', '');
        $account = $mapper->getWirecardShippingAccountHolder();
        $this->assertInstanceOf(AccountHolder::class, $account);
        $this->assertEquals([
            'address' => [
                'street1' => null,
                'city'    => null,
                'country' => null,
            ],
        ], $account->mappedProperties());
    }

    public function testGetWirecardShippingAddress()
    {
        $address = $this->mapper->getWirecardShippingAddress();
        $this->assertInstanceOf(Address::class, $address);
        $this->assertEquals([
            'street1'     => 'Shippingbarstreet',
            'city'        => 'Shippingfootown',
            'country'     => 'DE',
            'postal-code' => 2710,
            'street2'     => 'Shodorpping',
        ], $address->mappedProperties());
    }

    public function testGetBillingAddress()
    {
        $this->assertEquals($this->user['billingaddress'], $this->mapper->getBillingAddress());

        $this->expectException(ArrayKeyNotFoundException::class);
        $mapper = new UserMapper([], '', '');
        $mapper->getBillingAddress();
    }

    public function testGetBillingCity()
    {
        $this->assertEquals($this->user['billingaddress']['city'], $this->mapper->getBillingAddressCity());

        $this->expectException(ArrayKeyNotFoundException::class);
        $mapper = new UserMapper([], '', '');
        $mapper->getBillingAddressCity();
    }

    public function testGetBillingStreet()
    {
        $this->assertEquals($this->user['billingaddress']['street'], $this->mapper->getBillingAddressStreet());

        $this->expectException(ArrayKeyNotFoundException::class);
        $mapper = new UserMapper([], '', '');
        $mapper->getBillingAddressStreet();
    }

    public function testGetBillingZip()
    {
        $this->assertEquals($this->user['billingaddress']['zipcode'], $this->mapper->getBillingAddressZip());

        $this->expectException(ArrayKeyNotFoundException::class);
        $mapper = new UserMapper([], '', '');
        $mapper->getBillingAddressZip();
    }

    public function getBillingAddressAdditional()
    {
        $this->assertEquals($this->user['billingaddress']['additionalAddressLine1'], $this->mapper->getBillingAddressAdditional());

        $this->expectException(ArrayKeyNotFoundException::class);
        $mapper = new UserMapper([], '', '');
        $mapper->getBillingAddressAdditional();
    }

    public function testGetCountryIso()
    {
        $this->assertEquals($this->user['additional']['country']['countryiso'], $this->mapper->getCountryIso());
    }

    public function testGetShippingAddress()
    {
        $this->assertEquals($this->user['shippingaddress'], $this->mapper->getShippingAddress());
        $this->assertEquals($this->user['shippingaddress']['city'], $this->mapper->getShippingAddressCity());
        $this->assertEquals($this->user['shippingaddress']['street'], $this->mapper->getShippingAddressStreet());
        $this->assertEquals($this->user['shippingaddress']['zipcode'], $this->mapper->getShippingAddressZip());
        $this->assertEquals($this->user['shippingaddress']['additionalAddressLine1'], $this->mapper->getShippingAddressAdditional());
        $this->assertEquals($this->user['additional']['countryShipping']['countryiso'], $this->mapper->getShippingAddressCountryIso());
    }

    public function testGetClientIp()
    {
        $this->assertEquals($this->clientIp, $this->mapper->getClientIp());
    }

    public function testGetLocale()
    {
        $this->assertEquals($this->locale, $this->mapper->getLocale());
    }

    public function testToArray()
    {
        $this->assertEquals([
            'firstName'                 => 'First Name',
            'lastName'                  => 'Last Name',
            'email'                     => 'test@example.com',
            'birthday'                  => new \DateTime('1990-01-01'),
            'phone'                     => '+43123456789',
            'countryIso'                => 'AT',
            'billingAddressCity'        => 'Footown',
            'billingAddressStreet'      => 'Barstreet',
            'billingAddressZip'         => 1337,
            'billingAddressAdditional'  => 'Hodor',
            'shippingFirstName'         => 'First Shipping',
            'shippingLastName'          => 'Last Shipping',
            'shippingPhone'             => '+43987654321',
            'shippingCountryIso'        => 'DE',
            'shippingAddressCity'       => 'Shippingfootown',
            'shippingAddressStreet'     => 'Shippingbarstreet',
            'shippingAddressZip'        => 2710,
            'shippingAddressAdditional' => 'Shodorpping',
            'clientIp'                  => '127.0.0.1',
            'locale'                    => 'de_DE',
        ], $this->mapper->toArray());
    }

    public function testNullGetters()
    {
        $mapper = new UserMapper([], '', '');
        $this->assertNull($mapper->getBirthday());
        $this->assertNull($mapper->getCountryIso());
        $this->assertNull($mapper->getShippingAddressCity());
        $this->assertNull($mapper->getShippingAddressStreet());
        $this->assertNull($mapper->getShippingAddressZip());
        $this->assertNull($mapper->getShippingAddressAdditional());
        $this->assertNull($mapper->getShippingAddressCountryIso());

        $mapper = new UserMapper([
            'billingaddress'  => [],
            'shippingaddress' => [],
            'additional'      => [],
        ], '', '');
        $this->assertNull($mapper->getBillingAddressCity());
        $this->assertNull($mapper->getBillingAddressStreet());
        $this->assertNull($mapper->getBillingAddressZip());
        $this->assertNull($mapper->getBillingAddressAdditional());
        $this->assertNull($mapper->getCountryIso());
        $this->assertNull($mapper->getShippingAddressCity());
        $this->assertNull($mapper->getShippingAddressStreet());
        $this->assertNull($mapper->getShippingAddressZip());
        $this->assertNull($mapper->getShippingAddressAdditional());
        $this->assertNull($mapper->getShippingAddressCountryIso());

        $mapper = new UserMapper([
            'additional' => ['countryShipping' => [], 'country' => []],
        ], '', '');
        $this->assertNull($mapper->getCountryIso());
        $this->assertNull($mapper->getShippingAddressCountryIso());
    }
}
