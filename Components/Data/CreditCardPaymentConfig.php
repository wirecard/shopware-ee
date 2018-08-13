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
 * Credit Card specific payment configuration.
 *
 * @package WirecardElasticEngine\Components\Data
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
