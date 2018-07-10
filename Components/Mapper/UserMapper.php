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
    const USER_FIRST_NAME = 'firstname';
    const USER_LAST_NAME = 'lastname';
    const USER_EMAIL = 'email';
    const USER_BIRTHDAY = 'birthday';
    const USER_BILLING_ADDRESS = 'billingaddress';
    const USER_PHONE = 'phone';
    const USER_ADDITIONAL = 'additional';
    const USER_ADDITIONAL_USER = 'user';
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
     * @param array $shopwareUser
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
        $billingAccountHolder->setDateOfBirth($this->getBirthday());
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
        return $this->getAdditionalUserDetail(self::USER_FIRST_NAME);
    }

    /**
     * @return string
     * @throws ArrayKeyNotFoundException
     */
    public function getLastName()
    {
        return $this->getAdditionalUserDetail(self::USER_LAST_NAME);
    }

    /**
     * @return string
     * @throws ArrayKeyNotFoundException
     */
    public function getEmail()
    {
        return $this->getAdditionalUserDetail(self::USER_EMAIL);
    }

    /**
     * @return \DateTime
     * @throws ArrayKeyNotFoundException
     */
    public function getBirthday()
    {
        return new \DateTime($this->getAdditionalUserDetailOptional(self::USER_BIRTHDAY));
    }

    /**
     * @return array
     * @throws ArrayKeyNotFoundException
     */
    public function getBillingAddress()
    {
        return $this->get(self::USER_BILLING_ADDRESS);
    }

    /**
     * @param $detail
     *
     * @return string|null
     * @throws ArrayKeyNotFoundException
     */
    private function getBillingAddressDetail($detail)
    {
        $billingAddress = $this->getBillingAddress();

        return isset($billingAddress[$detail]) ? $billingAddress[$detail] : null;
    }

    /**
     * @return string|null
     */
    public function getPhone()
    {
        return $this->getOptional(self::USER_PHONE);
    }

    /**
     * @return array
     * @throws ArrayKeyNotFoundException
     */
    public function getAdditional()
    {
        return $this->get(self::USER_ADDITIONAL);
    }

    /**
     * @param $detail
     *
     * @return mixed
     * @throws ArrayKeyNotFoundException
     */
    public function getAdditionalDetail($detail)
    {
        if (! isset($this->getAdditional()[$detail])) {
            throw new ArrayKeyNotFoundException("additional.${detail}", get_class($this), $this->getAdditional());
        }

        return $this->getAdditional()[$detail];
    }

    /**
     * @param string $detail
     *
     * @return string
     * @throws ArrayKeyNotFoundException
     */
    public function getAdditionalUserDetail($detail)
    {
        $userAdditionalDetails = $this->getAdditionalDetail(self::USER_ADDITIONAL_USER);

        if (! isset($userAdditionalDetails[$detail])) {
            throw new ArrayKeyNotFoundException("additional.user.${detail}", get_class($this), $userAdditionalDetails);
        }

        return $userAdditionalDetails[$detail];
    }

    /**
     * @param string $detail
     * @param mixed  $fallback
     *
     * @return null
     * @throws ArrayKeyNotFoundException
     */
    public function getAdditionalUserDetailOptional($detail, $fallback = null)
    {
        $userAdditionalDetails = $this->getAdditionalDetail(self::USER_ADDITIONAL_USER);

        if (! isset($userAdditionalDetails[$detail])) {
            return $fallback;
        }

        return $userAdditionalDetails[$detail];
    }

    /**
     * @return string|null
     */
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

    /**
     * @return string|null
     * @throws ArrayKeyNotFoundException
     */
    public function getBillingAddressCity()
    {
        return $this->getBillingAddressDetail(self::USER_BILLING_ADDRESS_CITY);
    }

    /**
     * @return string|null
     * @throws ArrayKeyNotFoundException
     */
    public function getBillingAddressStreet()
    {
        return $this->getBillingAddressDetail(self::USER_BILLING_ADDRESS_STREET);
    }

    /**
     * @return string|null
     * @throws ArrayKeyNotFoundException
     */
    public function getBillingAddressZip()
    {
        return $this->getBillingAddressDetail(self::USER_BILLING_ADDRESS_ZIP);
    }

    /**
     * @return string|null
     * @throws ArrayKeyNotFoundException
     */
    public function getBillingAddressAdditional()
    {
        return $this->getBillingAddressDetail(self::USER_BILLING_ADDRESS_ADDITIONAL);
    }

    /**
     * @return array
     */
    public function getShippingAddress()
    {
        return $this->getOptional(self::USER_SHIPPING_ADDRESS, []);
    }

    /**
     * @param $detail
     *
     * @return string|null
     */
    private function getShippingAddressDetail($detail)
    {
        $shippingAddress = $this->getShippingAddress();

        return isset($shippingAddress[$detail]) ? $shippingAddress[$detail] : null;
    }

    /**
     * @return string
     */
    public function getShippingFirstName()
    {
        return $this->getShippingAddressDetail(self::USER_SHIPPING_ADDRESS_FIRST_NAME);
    }

    /**
     * @return string
     */
    public function getShippingLastName()
    {
        return $this->getShippingAddressDetail(self::USER_SHIPPING_ADDRESS_LAST_NAME);
    }

    /**
     * @return string
     */
    public function getShippingPhone()
    {
        return $this->getShippingAddressDetail(self::USER_SHIPPING_ADDRESS_PHONE);
    }

    /**
     * @return string|null
     */
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

    /**
     * @return string|null
     */
    public function getShippingAddressCity()
    {
        return $this->getShippingAddressDetail(self::USER_SHIPPING_ADDRESS_CITY);
    }

    /**
     * @return string|null
     */
    public function getShippingAddressStreet()
    {
        return $this->getShippingAddressDetail(self::USER_SHIPPING_ADDRESS_STREET);
    }

    /**
     * @return string|null
     */
    public function getShippingAddressZip()
    {
        return $this->getShippingAddressDetail(self::USER_SHIPPING_ADDRESS_ZIP);
    }

    /**
     * @return string|null
     */
    public function getShippingAddressAdditional()
    {
        return $this->getShippingAddressDetail(self::USER_SHIPPING_ADDRESS_ADDITIONAL);
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
            'firstName'                 => $this->getFirstName(),
            'lastName'                  => $this->getLastName(),
            'email'                     => $this->getEmail(),
            'birthday'                  => $this->getBirthday(),
            'phone'                     => $this->getPhone(),
            'additional'                => $this->getAdditional(),
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
