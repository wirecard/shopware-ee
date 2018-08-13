<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

namespace WirecardElasticEngine\Components\Data;

/**
 * Guaranteed Invoice by Wirecard / Ratepay specific payment configuration.
 *
 * @package WirecardElasticEngine\Components\Data
 *
 * @since   1.0.0
 */
class RatepayInvoicePaymentConfig extends PaymentConfig
{
    /**
     * @var float
     */
    protected $minAmount;

    /**
     * @var float
     */
    protected $maxAmount;

    /**
     * @var array
     */
    protected $acceptedCurrencies;

    /**
     * @var array
     */
    protected $shippingCountries;

    /**
     * @var array
     */
    protected $billingCountries;

    /**
     * @var bool
     */
    protected $allowDifferentBillingShipping;

    /**
     * @return string|float
     *
     * @since 1.0.0
     */
    public function getMinAmount()
    {
        return $this->minAmount;
    }

    /**
     * @param string|float $minAmount
     *
     * @since 1.0.0
     */
    public function setMinAmount($minAmount)
    {
        $this->minAmount = $minAmount;
    }

    /**
     * @return string|float
     *
     * @since 1.0.0
     */
    public function getMaxAmount()
    {
        return $this->maxAmount;
    }

    /**
     * @param string|float $maxAmount
     *
     * @since 1.0.0
     */
    public function setMaxAmount($maxAmount)
    {
        $this->maxAmount = $maxAmount;
    }

    /**
     * @return array
     *
     * @since 1.0.0
     */
    public function getAcceptedCurrencies()
    {
        return $this->acceptedCurrencies;
    }

    /**
     * @param array $acceptedCurrencies
     *
     * @since 1.0.0
     */
    public function setAcceptedCurrencies($acceptedCurrencies)
    {
        if (! is_array($acceptedCurrencies)) {
            $acceptedCurrencies = [];
        }
        $this->acceptedCurrencies = $acceptedCurrencies;
    }

    /**
     * @return array
     *
     * @since 1.0.0
     */
    public function getShippingCountries()
    {
        return $this->shippingCountries;
    }

    /**
     * @param array $shippingCountries
     *
     * @since 1.0.0
     */
    public function setShippingCountries($shippingCountries)
    {
        if (! is_array($shippingCountries)) {
            $shippingCountries = [];
        }
        $this->shippingCountries = $shippingCountries;
    }

    /**
     * @return array
     *
     * @since 1.0.0
     */
    public function getBillingCountries()
    {
        return $this->billingCountries;
    }

    /**
     * @param array $billingCountries
     *
     * @since 1.0.0
     */
    public function setBillingCountries($billingCountries)
    {
        if (! is_array($billingCountries)) {
            $billingCountries = [];
        }
        $this->billingCountries = $billingCountries;
    }

    /**
     * @return bool
     *
     * @since 1.0.0
     */
    public function isAllowedDifferentBillingShipping()
    {
        return $this->allowDifferentBillingShipping;
    }

    /**
     * @param bool $allowDifferentBillingShipping
     */
    public function setAllowDifferentBillingShipping($allowDifferentBillingShipping)
    {
        $this->allowDifferentBillingShipping = $allowDifferentBillingShipping;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return array_merge(
            parent::toArray(),
            [
                'minAmount'                     => $this->getMinAmount(),
                'maxAmount'                     => $this->getMaxAmount(),
                'acceptedCurrencies'            => $this->getAcceptedCurrencies(),
                'shippingCountries'             => $this->getShippingCountries(),
                'billingCountries'              => $this->getBillingCountries(),
                'allowDifferentBillingShipping' => $this->isAllowedDifferentBillingShipping(),
            ]
        );
    }
}
