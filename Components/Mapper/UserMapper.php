<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

namespace WirecardElasticEngine\Components\Mapper;

use Wirecard\PaymentSdk\Entity\AccountHolder;
use Wirecard\PaymentSdk\Entity\Address;
use WirecardElasticEngine\Exception\ArrayKeyNotFoundException;

/**
 * Represents a Shopware user as object.
 *
 * @package WirecardElasticEngine\Components\Mapper
 *
 * @since   1.0.0
 */
class UserMapper extends ArrayMapper
{
    const CUSTOMER_NUMBER = 'customernumber';
    const USER_ID = 'id';
    const FIRST_NAME = 'firstname';
    const LAST_NAME = 'lastname';
    const EMAIL = 'email';
    const BIRTHDAY = 'birthday';
    const PHONE = 'phone';
    const SHIPPING_ADDRESS = 'shippingaddress';
    const SHIPPING_ADDRESS_ID = 'id';
    const BILLING_ADDRESS = 'billingaddress';
    const ADDRESS_FIRST_NAME = 'firstname';
    const ADDRESS_LAST_NAME = 'lastname';
    const ADDRESS_CITY = 'city';
    const ADDRESS_STREET = 'street';
    const ADDRESS_ZIP = 'zipcode';
    const ADDRESS_ADDITIONAL = 'additionalAddressLine1';
    const ADDRESS_ADDITIONAL2 = 'additionalAddressLine2';
    const ADDRESS_COUNTRY_ID = 'countryId';
    const ADDRESS_STATE_ID = 'stateId';
    const ADDITIONAL = 'additional';
    const ADDITIONAL_USER = 'user';
    const ADDITIONAL_COUNTRY = 'country';
    const ADDITIONAL_COUNTRY_ISO = 'countryiso';
    const ADDITIONAL_COUNTRY_SHIPPING = 'countryShipping';
    const ADDITIONAL_COUNTRY_SHIPPING_COUNTRY_ISO = 'countryiso';
    const ADDITIONAL_STATE = 'state';
    const ADDITIONAL_STATE_ISO = 'shortcode';
    const ADDITIONAL_STATE_SHIPPING = 'stateShipping';
    const ADDITIONAL_STATE_SHIPPING_ISO = 'shortcode';

    /**
     * @var string
     */
    protected $clientIp;

    /**
     * @var string
     */
    protected $locale;

    /**
     * @param array $shopwareUser
     * @param string $clientIp
     * @param string $locale
     *
     * @since 1.0.0
     */
    public function __construct(array $shopwareUser, $clientIp, $locale)
    {
        $this->arrayEntity = $shopwareUser;
        $this->clientIp    = $clientIp;
        $this->locale      = $locale;
    }

    /**
     * @return array
     *
     * @since 1.0.0
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
     *
     * @since 1.0.0
     */
    public function getWirecardBillingAccountHolder()
    {
        $billingAccountHolder = new AccountHolder();
        $billingAccountHolder->setCrmId($this->getCustomerNumber());
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
     *
     * @since 1.0.0
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
     *
     * @since 1.0.0
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
        $billingAddress->setStreet3($this->getBillingAddressAdditional2());

        if (strlen($this->getStateIso())) {
            $billingAddress->setState($this->getStateIso());
        }

        return $billingAddress;
    }

    /**
     * Returns a Wirecard Address object based on the shipping address.
     *
     * @return Address
     *
     * @since 1.0.0
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
        $shippingAddress->setStreet3($this->getShippingAddressAdditional2());

        if (strlen($this->getShippingAddressStateIso())) {
            $shippingAddress->setState($this->getShippingAddressStateIso());
        }

        return $shippingAddress;
    }

    /**
     * @return mixed
     * @throws ArrayKeyNotFoundException
     *
     * @since 1.1.0
     */
    public function getUserId()
    {
        return $this->get([self::ADDITIONAL, self::ADDITIONAL_USER, self::USER_ID]);
    }

    /**
     * @return mixed
     * @throws ArrayKeyNotFoundException
     *
     * @since 1.0.0
     */
    public function getFirstName()
    {
        return $this->get([self::ADDITIONAL, self::ADDITIONAL_USER, self::FIRST_NAME]);
    }

    /**
     * @return string
     * @throws ArrayKeyNotFoundException
     *
     * @since 1.0.0
     */
    public function getLastName()
    {
        return $this->get([self::ADDITIONAL, self::ADDITIONAL_USER, self::LAST_NAME]);
    }

    /**
     * @return string|null
     *
     * @since 1.0.0
     */
    public function getCustomerNumber()
    {
        return $this->getOptional([self::ADDITIONAL, self::ADDITIONAL_USER, self::CUSTOMER_NUMBER]);
    }

    /**
     * @return string
     * @throws ArrayKeyNotFoundException
     *
     * @since 1.0.0
     */
    public function getEmail()
    {
        return $this->get([self::ADDITIONAL, self::ADDITIONAL_USER, self::EMAIL]);
    }

    /**
     * @return \DateTime|null
     *
     * @since 1.0.0
     */
    public function getBirthday()
    {
        $birthday = $this->getOptional([self::ADDITIONAL, self::ADDITIONAL_USER, self::BIRTHDAY]);

        return $birthday ? new \DateTime($birthday) : null;
    }

    /**
     * @return array
     * @throws ArrayKeyNotFoundException
     *
     * @since 1.0.0
     */
    public function getBillingAddress()
    {
        return $this->get(self::BILLING_ADDRESS);
    }

    /**
     * @return string|null
     *
     * @since 1.0.0
     */
    public function getPhone()
    {
        return $this->getOptional([self::BILLING_ADDRESS, self::PHONE]);
    }

    /**
     * @return string|null
     *
     * @since 1.0.0
     */
    public function getCountryIso()
    {
        return $this->getOptional([self::ADDITIONAL, self::ADDITIONAL_COUNTRY, self::ADDITIONAL_COUNTRY_ISO]);
    }

    /**
     * @return string|null
     *
     * @since 1.2.0
     */
    public function getStateIso()
    {
        return $this->getOptional([self::ADDITIONAL, self::ADDITIONAL_STATE, self::ADDITIONAL_STATE_ISO]);
    }

    /**
     * @return string|null
     * @throws ArrayKeyNotFoundException
     *
     * @since 1.0.0
     */
    public function getBillingAddressCity()
    {
        $address = $this->getBillingAddress();

        return isset($address[self::ADDRESS_CITY]) ? $address[self::ADDRESS_CITY] : null;
    }

    /**
     * @return string|null
     * @throws ArrayKeyNotFoundException
     *
     * @since 1.0.0
     */
    public function getBillingAddressStreet()
    {
        $address = $this->getBillingAddress();

        return isset($address[self::ADDRESS_STREET]) ? $address[self::ADDRESS_STREET] : null;
    }

    /**
     * @return string|null
     * @throws ArrayKeyNotFoundException
     *
     * @since 1.0.0
     */
    public function getBillingAddressZip()
    {
        $address = $this->getBillingAddress();

        return isset($address[self::ADDRESS_ZIP]) ? $address[self::ADDRESS_ZIP] : null;
    }

    /**
     * @return string|null
     * @throws ArrayKeyNotFoundException
     *
     * @since 1.0.0
     */
    public function getBillingAddressAdditional()
    {
        $address = $this->getBillingAddress();

        return isset($address[self::ADDRESS_ADDITIONAL]) ? $address[self::ADDRESS_ADDITIONAL] : null;
    }

    /**
     * @return string|null
     *
     * @since 1.4.0
     */
    public function getBillingAddressAdditional2()
    {
        return $this->getOptional([self::BILLING_ADDRESS, self::ADDRESS_ADDITIONAL2]);
    }

    /**
     * @return array
     *
     * @since 1.0.0
     */
    public function getShippingAddress()
    {
        return $this->getOptional(self::SHIPPING_ADDRESS, []);
    }

    /**
     * @return string|null
     *
     * @since 1.4.0
     */
    public function getShippingAddressId()
    {
        return $this->getOptional([self::SHIPPING_ADDRESS, self::SHIPPING_ADDRESS_ID]);
    }

    /**
     * @return string|null
     *
     * @since 1.0.0
     */
    public function getShippingFirstName()
    {
        return $this->getOptional([self::SHIPPING_ADDRESS, self::ADDRESS_FIRST_NAME]);
    }

    /**
     * @return string|null
     *
     * @since 1.0.0
     */
    public function getShippingLastName()
    {
        return $this->getOptional([self::SHIPPING_ADDRESS, self::ADDRESS_LAST_NAME]);
    }

    /**
     * @return string|null
     *
     * @since 1.0.0
     */
    public function getShippingPhone()
    {
        return $this->getOptional([self::SHIPPING_ADDRESS, self::PHONE]);
    }

    /**
     * @return string|null
     *
     * @since 1.0.0
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
     *
     * @since 1.2.0
     */
    public function getShippingAddressStateIso()
    {
        return $this->getOptional([
            self::ADDITIONAL,
            self::ADDITIONAL_STATE_SHIPPING,
            self::ADDITIONAL_STATE_SHIPPING_ISO,
        ]);
    }

    /**
     * @return string|null
     *
     * @since 1.0.0
     */
    public function getShippingAddressCity()
    {
        return $this->getOptional([self::SHIPPING_ADDRESS, self::ADDRESS_CITY]);
    }

    /**
     * @return string|null
     *
     * @since 1.0.0
     */
    public function getShippingAddressStreet()
    {
        return $this->getOptional([self::SHIPPING_ADDRESS, self::ADDRESS_STREET]);
    }

    /**
     * @return string|null
     *
     * @since 1.0.0
     */
    public function getShippingAddressZip()
    {
        return $this->getOptional([self::SHIPPING_ADDRESS, self::ADDRESS_ZIP]);
    }

    /**
     * @return string|null
     *
     * @since 1.0.0
     */
    public function getShippingAddressAdditional()
    {
        return $this->getOptional([self::SHIPPING_ADDRESS, self::ADDRESS_ADDITIONAL]);
    }

    /**
     * @return string|null
     *
     * @since 1.4.0
     */
    public function getShippingAddressAdditional2()
    {
        return $this->getOptional([self::SHIPPING_ADDRESS, self::ADDRESS_ADDITIONAL2]);
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function getClientIp()
    {
        return $this->clientIp;
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @return array
     * @throws ArrayKeyNotFoundException
     *
     * @since 1.0.0
     */
    public function toArray()
    {
        return [
            'customerNumber'             => $this->getCustomerNumber(),
            'firstName'                  => $this->getFirstName(),
            'lastName'                   => $this->getLastName(),
            'email'                      => $this->getEmail(),
            'birthday'                   => $this->getBirthday(),
            'phone'                      => $this->getPhone(),
            'countryIso'                 => $this->getCountryIso(),
            'stateIso'                   => $this->getStateIso(),
            'billingAddressCity'         => $this->getBillingAddressCity(),
            'billingAddressStreet'       => $this->getBillingAddressStreet(),
            'billingAddressZip'          => $this->getBillingAddressZip(),
            'billingAddressAdditional'   => $this->getBillingAddressAdditional(),
            'billingAddressAdditional2'  => $this->getBillingAddressAdditional2(),
            'shippingFirstName'          => $this->getShippingFirstName(),
            'shippingLastName'           => $this->getShippingLastName(),
            'shippingPhone'              => $this->getShippingPhone(),
            'shippingCountryIso'         => $this->getShippingAddressCountryIso(),
            'shippingStateIso'           => $this->getShippingAddressStateIso(),
            'shippingAddressCity'        => $this->getShippingAddressCity(),
            'shippingAddressStreet'      => $this->getShippingAddressStreet(),
            'shippingAddressZip'         => $this->getShippingAddressZip(),
            'shippingAddressAdditional'  => $this->getShippingAddressAdditional(),
            'shippingAddressAdditional2' => $this->getShippingAddressAdditional2(),
            'clientIp'                   => $this->getClientIp(),
            'locale'                     => $this->getLocale(),
        ];
    }
}
