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

namespace WirecardShopwareElasticEngine\Components\Data;

/**
 * Credit Card specific payment configuration.
 *
 * @package WirecardShopwareElasticEngine\Components\Data
 *
 * @since   1.0.0
 */
class CreditCardPaymentConfig extends PaymentConfig
{
    /**
     * @var string
     */
    protected $threeDMAID;

    /**
     * @var string
     */
    protected $threeDSecret;

    /**
     * @var float
     */
    protected $threeDMinLimit;

    /**
     * @var string
     */
    protected $threeDMinLimitCurrency;

    /**
     * @var float
     */
    protected $sslMaxLimit;

    /**
     * @var string
     */
    protected $sslMaxLimitCurrency;

    /**
     * @return string If "null", ThreeD credentials are disabled
     *
     * @since 1.0.0
     */
    public function getThreeDMAID()
    {
        return $this->threeDMAID;
    }

    /**
     * @param string $threeDMAID
     *
     * @since 1.0.0
     */
    public function setThreeDMAID($threeDMAID)
    {
        $this->threeDMAID = $threeDMAID;
    }

    /**
     * @return string|float If "null", ThreeDMinLimit is disabled
     *
     * @since 1.0.0
     */
    public function getThreeDMinLimit()
    {
        return $this->threeDMinLimit;
    }

    /**
     * @param string|float $threeDMinLimit
     *
     * @since 1.0.0
     */
    public function setThreeDMinLimit($threeDMinLimit)
    {
        $this->threeDMinLimit = $threeDMinLimit;
    }

    /**
     * @return string If "null", the shop default currency will be used
     *
     * @since 1.0.0
     */
    public function getThreeDMinLimitCurrency()
    {
        return $this->threeDMinLimitCurrency;
    }

    /**
     * @param string $threeDMinLimitCurrency
     *
     * @since 1.0.0
     */
    public function setThreeDMinLimitCurrency($threeDMinLimitCurrency)
    {
        $this->threeDMinLimitCurrency = $threeDMinLimitCurrency;
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function getThreeDSecret()
    {
        return $this->threeDSecret;
    }

    /**
     * @param string $threeDSecret
     *
     * @since 1.0.0
     */
    public function setThreeDSecret($threeDSecret)
    {
        $this->threeDSecret = $threeDSecret;
    }

    /**
     * @return string|float If "null", SslMaxLimit is disabled
     *
     * @since 1.0.0
     */
    public function getSslMaxLimit()
    {
        return $this->sslMaxLimit;
    }

    /**
     * @param string|float $sslMaxLimit
     *
     * @since 1.0.0
     */
    public function setSslMaxLimit($sslMaxLimit)
    {
        $this->sslMaxLimit = $sslMaxLimit;
    }

    /**
     * @return string If "null", the shop default currency will be used
     *
     * @since 1.0.0
     */
    public function getSslMaxLimitCurrency()
    {
        return $this->sslMaxLimitCurrency;
    }

    /**
     * @param string $sslMaxLimitCurrency
     *
     * @since 1.0.0
     */
    public function setSslMaxLimitCurrency($sslMaxLimitCurrency)
    {
        $this->sslMaxLimitCurrency = $sslMaxLimitCurrency;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return array_merge(
            parent::toArray(),
            [
                'threeDMAID'             => $this->getThreeDMAID(),
                'threeDMinLimit'         => $this->getThreeDMinLimit(),
                'threeDMinLimitCurrency' => $this->getThreeDMinLimitCurrency(),
                'sslMaxLimit'            => $this->getSslMaxLimit(),
                'sslMaxLimitCurrency'    => $this->getSslMaxLimitCurrency(),
            ]
        );
    }
}
