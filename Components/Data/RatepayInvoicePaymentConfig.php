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
     * @var float minAmount
     */
    protected $minAmount;

    /**
     * @var float maxAmount;
     */
    protected $maxAmount;

    /**
     * @var array acceptedCurrencies
     */
    protected $acceptedCurrencies;

    /**
     * @var array shippingCountries
     */
    protected $shippingCountries;

    /**
     * @var array billingCountries
     */
    protected $billingCountries;

    /**
     * @var bool differentBillingShipping
     */
    protected $differentBillingShipping;

    /**
     * @return float
     *
     * @since 1.0.0
     */
    public function getMinAmount()
    {
        return $this->minAmount;
    }

    /**
     * @param float $minAmount
     *
     * @since 1.0.0
     */
    public function setMinAmount($minAmount)
    {
        $this->minAmount = $minAmount;
    }

    /**
     * @return float
     *
     * @since 1.0.0
     */
    public function getMaxAmount()
    {
        return $this->maxAmount;
    }

    /**
     * @param float $maxAmount
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
    public function setAcceptedCurrencies(array $acceptedCurrencies)
    {
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
    public function setShippingCountries(array $shippingCountries)
    {
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
    public function setBillingCountries(array $billingCountries)
    {
        $this->billingCountries = $billingCountries;
    }

    /**
     * @return bool
     *
     * @since 1.0.0
     */
    public function isAllowedDifferentBillingShipping()
    {
        return $this->differentBillingShipping;
    }

    /**
     * @param bool $differentBillingShipping
     */
    public function setDifferentBillingShipping($differentBillingShipping)
    {
        $this->differentBillingShipping = $differentBillingShipping;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return array_merge(
            parent::toArray(),
            [
                'minAmount'                => $this->getMinAmount(),
                'maxAmount'                => $this->getMaxAmount(),
                'acceptedCurrencies'       => $this->getAcceptedCurrencies(),
                'shippingCountries'        => $this->getShippingCountries(),
                'billingCountries'         => $this->getBillingCountries(),
                'differentBillingShipping' => $this->isAllowedDifferentBillingShipping(),
            ]
        );
    }
}
