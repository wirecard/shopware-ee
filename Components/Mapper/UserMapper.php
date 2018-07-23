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
use WirecardShopwareElasticEngine\Exception\ArrayKeyNotFoundException;

class UserMapper extends ArrayMapper
{
    const CUSTOMER_NUMBER = 'customernumber';
    const FIRST_NAME = 'firstname';
    const LAST_NAME = 'lastname';
    const EMAIL = 'email';
    const BIRTHDAY = 'birthday';
    const BILLING_ADDRESS = 'billingaddress';
    const PHONE = 'phone';
    const ADDITIONAL = 'additional';
    const ADDITIONAL_USER = 'user';
    const ADDITIONAL_COUNTRY = 'country';
    const ADDITIONAL_COUNTRY_ISO = 'countryiso';
    const SHIPPING_ADDRESS_FIRST_NAME = 'firstname';
    const SHIPPING_ADDRESS_LAST_NAME = 'lastname';
    const BILLING_ADDRESS_CITY = 'city';
    const BILLING_ADDRESS_STREET = 'street';
    const BILLING_ADDRESS_ADDITIONAL = 'additionalAddressLine1';
    const SHIPPING_ADDRESS = 'shippingaddress';
    const BILLING_ADDRESS_ZIP = 'zipcode';
    const ADDITIONAL_COUNTRY_SHIPPING = 'countryShipping';
    const ADDITIONAL_COUNTRY_SHIPPING_COUNTRY_ISO = 'countryiso';
    const SHIPPING_ADDRESS_CITY = 'city';
    const SHIPPING_ADDRESS_STREET = 'street';
    const SHIPPING_ADDRESS_ZIP = 'zipcode';
    const SHIPPING_ADDRESS_ADDITIONAL = 'additionalAddressLine1';

    /**
     * @var string
     */
    protected $clientIp;

    /**
     * @var string
     */
    protected $locale;

    /**
     * UserMapper constructor.
     *
     * @param array  $shopwareUser
     * @param string $clientIp
     * @param string $locale
     */
    public function __construct(array $shopwareUser, $clientIp, $locale)
    {
        $this->arrayEntity = $shopwareUser;
        $this->clientIp    = $clientIp;
        $this->locale      = $locale;
    }

    /**
     * @return array
     */
    public function getShopwareUser()
    {
        return $this->arrayEntity;
    }

    /**
     * Returns a Wirecard AccountHolder object based on user data.
     *
     * @return AccountHolder
     * @throws ArrayKeyNotFoundException
     */
    public function getWirecardBillingAccountHolder()
    {
        $billingAccountHolder = new AccountHolder();
        $billingAccountHolder->setFirstName($this->getFirstName());
        $billingAccountHolder->setLastName($this->getLastName());
        $billingAccountHolder->setEmail($this->getEmail());
        $birthday = $this->getBirthday();
        if ($birthday) {
            $billingAccountHolder->setDateOfBirth($birthday);
        }
        $billingAccountHolder->setPhone($this->getPhone());
        $billingAccountHolder->setAddress($this->getWirecardBillingAddress());

        return $billingAccountHolder;
    }

    /**
     * Returns a Wirecard AccountHolder object based on shipping data.
     *
     * @return AccountHolder
     */
    public function getWirecardShippingAccountHolder()
    {
        $shippingAccountHolder = new AccountHolder();
        $shippingAccountHolder->setFirstName($this->getShippingFirstName());
        $shippingAccountHolder->setLastName($this->getShippingLastName());
        $shippingAccountHolder->setPhone($this->getShippingPhone());
        $shippingAccountHolder->setAddress($this->getWirecardShippingAddress());

        return $shippingAccountHolder;
    }

    /**
     * Returns a Wirecard Address object based on the billing address.
     *
     * @return Address
     * @throws ArrayKeyNotFoundException
     */
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

    /**
     * Returns a Wirecard Address object based on the shipping address.
     *
     * @return Address
     */
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

    /**
     * @return mixed
     * @throws ArrayKeyNotFoundException
     */
    public function getFirstName()
    {
        return $this->get([self::ADDITIONAL, self::ADDITIONAL_USER, self::FIRST_NAME]);
    }

    /**
     * @return string
     * @throws ArrayKeyNotFoundException
     */
    public function getLastName()
    {
        return $this->get([self::ADDITIONAL, self::ADDITIONAL_USER, self::LAST_NAME]);
    }

    /**
     * @return string|null
     */
    public function getCustomerNumber()
    {
        return $this->getOptional([self::ADDITIONAL, self::ADDITIONAL_USER, self::CUSTOMER_NUMBER]);
    }

    /**
     * @return string
     * @throws ArrayKeyNotFoundException
     */
    public function getEmail()
    {
        return $this->get([self::ADDITIONAL, self::ADDITIONAL_USER, self::EMAIL]);
    }

    /**
     * @return \DateTime|null
     */
    public function getBirthday()
    {
        $birthday = $this->getOptional([self::ADDITIONAL, self::ADDITIONAL_USER, self::BIRTHDAY]);
        return $birthday ? new \DateTime($birthday) : null;
    }

    /**
     * @return array
     * @throws ArrayKeyNotFoundException
     */
    public function getBillingAddress()
    {
        return $this->get(self::BILLING_ADDRESS);
    }

    /**
     * @return string|null
     */
    public function getPhone()
    {
        return $this->getOptional([self::BILLING_ADDRESS, self::PHONE]);
    }

    /**
     * @return string|null
     */
    public function getCountryIso()
    {
        return $this->getOptional([self::ADDITIONAL, self::ADDITIONAL_COUNTRY, self::ADDITIONAL_COUNTRY_ISO]);
    }

    /**
     * @return string|null
     * @throws ArrayKeyNotFoundException
     */
    public function getBillingAddressCity()
    {
        $address = $this->getBillingAddress();
        return isset($address[self::BILLING_ADDRESS_CITY]) ? $address[self::BILLING_ADDRESS_CITY] : null;
    }

    /**
     * @return string|null
     * @throws ArrayKeyNotFoundException
     */
    public function getBillingAddressStreet()
    {
        $address = $this->getBillingAddress();
        return isset($address[self::BILLING_ADDRESS_STREET]) ? $address[self::BILLING_ADDRESS_STREET] : null;
    }

    /**
     * @return string|null
     * @throws ArrayKeyNotFoundException
     */
    public function getBillingAddressZip()
    {
        $address = $this->getBillingAddress();
        return isset($address[self::BILLING_ADDRESS_ZIP]) ? $address[self::BILLING_ADDRESS_ZIP] : null;
    }

    /**
     * @return string|null
     * @throws ArrayKeyNotFoundException
     */
    public function getBillingAddressAdditional()
    {
        $address = $this->getBillingAddress();
        return isset($address[self::BILLING_ADDRESS_ADDITIONAL]) ? $address[self::BILLING_ADDRESS_ADDITIONAL] : null;
    }

    /**
     * @return array
     */
    public function getShippingAddress()
    {
        return $this->getOptional(self::SHIPPING_ADDRESS, []);
    }

    /**
     * @return string|null
     */
    public function getShippingFirstName()
    {
        return $this->getOptional([self::SHIPPING_ADDRESS, self::SHIPPING_ADDRESS_FIRST_NAME]);
    }

    /**
     * @return string|null
     */
    public function getShippingLastName()
    {
        return $this->getOptional([self::SHIPPING_ADDRESS, self::SHIPPING_ADDRESS_LAST_NAME]);
    }

    /**
     * @return string|null
     */
    public function getShippingPhone()
    {
        return $this->getOptional([self::SHIPPING_ADDRESS, self::PHONE]);
    }

    /**
     * @return string|null
     */
    public function getShippingAddressCountryIso()
    {
        return $this->getOptional([
            self::ADDITIONAL,
            self::ADDITIONAL_COUNTRY_SHIPPING,
            self::ADDITIONAL_COUNTRY_SHIPPING_COUNTRY_ISO,
        ]);
    }

    /**
     * @return string|null
     */
    public function getShippingAddressCity()
    {
        return $this->getOptional([self::SHIPPING_ADDRESS, self::SHIPPING_ADDRESS_CITY]);
    }

    /**
     * @return string|null
     */
    public function getShippingAddressStreet()
    {
        return $this->getOptional([self::SHIPPING_ADDRESS, self::SHIPPING_ADDRESS_STREET]);
    }

    /**
     * @return string|null
     */
    public function getShippingAddressZip()
    {
        return $this->getOptional([self::SHIPPING_ADDRESS, self::SHIPPING_ADDRESS_ZIP]);
    }

    /**
     * @return string|null
     */
    public function getShippingAddressAdditional()
    {
        return $this->getOptional([self::SHIPPING_ADDRESS, self::SHIPPING_ADDRESS_ADDITIONAL]);
    }

    /**
     * @return string
     */
    public function getClientIp()
    {
        return $this->clientIp;
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @return array
     * @throws ArrayKeyNotFoundException
     */
    public function toArray()
    {
        return [
            'customerNumber'            => $this->getCustomerNumber(),
            'firstName'                 => $this->getFirstName(),
            'lastName'                  => $this->getLastName(),
            'email'                     => $this->getEmail(),
            'birthday'                  => $this->getBirthday(),
            'phone'                     => $this->getPhone(),
            'countryIso'                => $this->getCountryIso(),
            'billingAddressCity'        => $this->getBillingAddressCity(),
            'billingAddressStreet'      => $this->getBillingAddressStreet(),
            'billingAddressZip'         => $this->getBillingAddressZip(),
            'billingAddressAdditional'  => $this->getBillingAddressAdditional(),
            'shippingFirstName'         => $this->getShippingFirstName(),
            'shippingLastName'          => $this->getShippingLastName(),
            'shippingPhone'             => $this->getShippingPhone(),
            'shippingCountryIso'        => $this->getShippingAddressCountryIso(),
            'shippingAddressCity'       => $this->getShippingAddressCity(),
            'shippingAddressStreet'     => $this->getShippingAddressStreet(),
            'shippingAddressZip'        => $this->getShippingAddressZip(),
            'shippingAddressAdditional' => $this->getShippingAddressAdditional(),
            'clientIp'                  => $this->getClientIp(),
            'locale'                    => $this->getLocale(),
        ];
    }
}
