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

namespace WirecardShopwareElasticEngine\Components\Mapper;

use Wirecard\PaymentSdk\Entity\AccountHolder;
use Wirecard\PaymentSdk\Entity\Address;

class UserMapper extends Mapper
{
    const USER_FIRST_NAME = 'firstname';
    const USER_LAST_NAME = 'lastname';
    const USER_EMAIL = 'email';
    const USER_BIRTHDAY = 'birthday';
    const USER_BILLING_ADDRESS = 'billingaddress';
    const USER_PHONE = 'phone';
    const USER_ADDITIONAL = 'additional';
    const USER_ADDITIONAL_COUNTRY = 'country';
    const USER_ADDITIONAL_COUNTRY_ISO = 'countryiso';
    const USER_SHIPPING_ADDRESS_FIRST_NAME = 'firstname';
    const USER_SHIPPING_ADDRESS_LAST_NAME = 'lastname';
    const USER_SHIPPING_ADDRESS_PHONE = 'phone';
    const USER_BILLING_ADDRESS_CITY = 'city';
    const USER_BILLING_ADDRESS_STREET = 'street';
    const USER_BILLING_ADDRESS_ADDITIONAL = 'additionalAddressLine1';
    const USER_SHIPPING_ADDRESS = 'shippingaddress';
    const USER_BILLING_ADDRESS_ZIP = 'zipcode';
    const USER_ADDITIONAL_COUNTRY_SHIPPING = 'countryShipping';
    const USER_ADDITIONAL_COUNTRY_SHIPPING_COUNTRY_ISO = 'countryiso';
    const USER_SHIPPING_ADDRESS_CITY = 'city';
    const USER_SHIPPING_ADDRESS_STREET = 'street';
    const USER_SHIPPING_ADDRESS_ZIP = 'zipcode';
    const USER_SHIPPING_ADDRESS_ADDITIONAL = 'additionalAddressLine1';

    /**
     * UserMapper constructor.
     *
     * @param array $shopwareUser
     */
    public function __construct(array $shopwareUser)
    {
        $this->shopwareArrayEntity = $shopwareUser;
    }

    public function getShopwareUser()
    {
        return $this->shopwareArrayEntity;
    }

    public function getWirecardBillingAccountHolder()
    {
        $billingAccountHolder = new AccountHolder();
        $billingAccountHolder->setFirstName($this->getFirstName());
        $billingAccountHolder->setLastName($this->getLastName());
        $billingAccountHolder->setEmail($this->getEmail());
        $billingAccountHolder->setDateOfBirth($this->getBirthday());
        $billingAccountHolder->setPhone($this->getPhone());
        $billingAccountHolder->setAddress($this->getWirecardBillingAddress());
    }

    public function getWirecardShippingAccountHolder()
    {
        $shippingAccountHolder = new AccountHolder();
        $shippingAccountHolder->setFirstName($this->getShippingFirstName());
        $shippingAccountHolder->setLastName($this->getShippingLastName());
        $shippingAccountHolder->setPhone($this->getPhone());
        $shippingAccountHolder->setAddress($this->getWirecardShippingAddress());

        return $shippingAccountHolder;
    }

    public function getWirecardBillingAddress()
    {
        $billingAddress = new Address(
            $this->getCountryIso(),
            $this->getBillingAddressCity(),
            $this->getBillingAddressStreet()
        );

        $billingAddress->setPostalCode($this->getBillingAddressZip());
        $billingAddress->setStreet2($this->getBillingAddressAdditional());

        return $billingAddress;
    }

    public function getWirecardShippingAddress()
    {
        $shippingAddress = new Address(
            $this->getShippingAddressCountryIso(),
            $this->getShippingAddressCity(),
            $this->getShippingAddressStreet()
        );

        $shippingAddress->setPostalCode($this->getShippingAddressZip());
        $shippingAddress->setStreet2($this->getShippingAddressAdditional());

        return $shippingAddress;
    }

    public function getFirstName()
    {
        return $this->getKey(self::USER_FIRST_NAME);
    }

    public function getLastName()
    {
        return $this->getKey(self::USER_LAST_NAME);
    }

    public function getEmail()
    {
        return $this->getKey(self::USER_EMAIL);
    }

    public function getBirthday()
    {
        return $this->getKey(self::USER_BIRTHDAY);
    }

    public function getBillingAddress()
    {
        return $this->getKey(self::USER_BILLING_ADDRESS);
    }

    public function getBillingAddressDetail($detail)
    {
        $billingAddress = $this->getBillingAddress();

        return isset($billingAddress[$detail]) ? $billingAddress[$detail] : null;
    }

    public function getPhone()
    {
        return $this->getKey(self::USER_PHONE, false);
    }

    public function getAdditional()
    {
        return $this->getKey(self::USER_ADDITIONAL, false, []);
    }

    public function getCountryIso()
    {
        $additional = $this->getAdditional();

        if (isset(
            $additional,
            $additional[self::USER_ADDITIONAL_COUNTRY],
            $additional[self::USER_ADDITIONAL_COUNTRY][self::USER_ADDITIONAL_COUNTRY_ISO]
        )) {
            return $additional[self::USER_ADDITIONAL_COUNTRY][self::USER_ADDITIONAL_COUNTRY_ISO];
        }

        return null;
    }

    public function getBillingAddressCity()
    {
        return $this->getBillingAddressDetail(self::USER_BILLING_ADDRESS_CITY);
    }

    public function getBillingAddressStreet()
    {
        return $this->getBillingAddressDetail(self::USER_BILLING_ADDRESS_STREET);
    }

    public function getBillingAddressZip()
    {
        return $this->getBillingAddressDetail(self::USER_BILLING_ADDRESS_ZIP);
    }

    public function getBillingAddressAdditional()
    {
        return $this->getBillingAddressDetail(self::USER_BILLING_ADDRESS_ADDITIONAL);
    }

    public function getShippingAddress()
    {
        return $this->getKey(self::USER_SHIPPING_ADDRESS, false, []);
    }

    public function getShippingAddressDetail($detail)
    {
        $shippingAddress = $this->getShippingAddress();

        return isset($shippingAddress[$detail]) ? $shippingAddress[$detail] : null;
    }

    public function getShippingFirstName()
    {
        return $this->getShippingAddressDetail(self::USER_SHIPPING_ADDRESS_FIRST_NAME);
    }

    public function getShippingLastName()
    {
        return $this->getShippingAddressDetail(self::USER_SHIPPING_ADDRESS_LAST_NAME);
    }

    public function getShippingPhone()
    {
        return $this->getShippingAddressDetail(self::USER_SHIPPING_ADDRESS_PHONE);
    }

    public function getShippingAddressCountryIso()
    {
        $additional = $this->getAdditional();

        if (isset(
            $additional,
            $additional[self::USER_ADDITIONAL_COUNTRY_SHIPPING],
            $additional[self::USER_ADDITIONAL_COUNTRY_SHIPPING][self::USER_ADDITIONAL_COUNTRY_SHIPPING_COUNTRY_ISO]
        )) {
            return $additional[self::USER_ADDITIONAL_COUNTRY_SHIPPING][self::USER_ADDITIONAL_COUNTRY_SHIPPING_COUNTRY_ISO];
        }

        return null;
    }

    public function getShippingAddressCity()
    {
        return $this->getShippingAddressDetail(self::USER_SHIPPING_ADDRESS_CITY);
    }

    public function getShippingAddressStreet()
    {
        return $this->getShippingAddressDetail(self::USER_SHIPPING_ADDRESS_STREET);
    }

    public function getShippingAddressZip()
    {
        return $this->getShippingAddressDetail(self::USER_SHIPPING_ADDRESS_ZIP);
    }

    public function getShippingAddressAdditional()
    {
        return $this->getShippingAddressDetail(self::USER_SHIPPING_ADDRESS_ADDITIONAL);
    }
}
