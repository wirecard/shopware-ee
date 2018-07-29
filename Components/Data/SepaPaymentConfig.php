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
 * SEPA specific payment configuration.
 *
 * @package WirecardShopwareElasticEngine\Components\Data
 *
 * @since   1.0.0
 */
class SepaPaymentConfig extends PaymentConfig
{
    /**
     * @var bool
     */
    protected $showBic;

    /**
     * @var string
     */
    protected $creditorId;

    /**
     * @var string
     */
    protected $creditorName;

    /**
     * @var string
     */
    protected $creditorAddress;

    /**
     * @var string
     */
    protected $backendTransactionMaid;

    /**
     * @var string
     */
    protected $backendTransactionSecret;

    /**
     * @var string
     */
    protected $backendCreditorId;

    /**
     * @param bool $showBic
     *
     * @since 1.0.0
     */
    public function setShowBic($showBic)
    {
        $this->showBic = $showBic;
    }

    /**
     * @return bool if true, the BIC form field on checkout page will be displayed
     *
     * @since 1.0.0
     */
    public function showBic()
    {
        return (bool)$this->showBic;
    }

    /**
     * @param string
     *
     * @since 1.0.0
     */
    public function setCreditorId($creditorId)
    {
        $this->creditorId = $creditorId;
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function getCreditorId()
    {
        return $this->creditorId;
    }

    /**
     * @param string
     *
     * @since 1.0.0
     */
    public function setCreditorName($creditorName)
    {
        $this->creditorName = $creditorName;
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function getCreditorName()
    {
        return $this->creditorName;
    }

    /**
     * @param string
     *
     * @since 1.0.0
     */
    public function setCreditorAddress($creditorAddress)
    {
        $this->creditorAddress = $creditorAddress;
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function getCreditorAddress()
    {
        return $this->creditorAddress;
    }

    /**
     * @param string
     *
     * @since 1.0.0
     */
    public function setBackendTransactionMAID($backendTransactionMaid)
    {
        $this->backendTransactionMaid = $backendTransactionMaid;
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function getBackendTransactionMAID()
    {
        return $this->backendTransactionMaid;
    }

    /**
     * @param string
     *
     * @since 1.0.0
     */
    public function setBackendTransactionSecret($backendTransactionSecret)
    {
        $this->backendTransactionSecret = $backendTransactionSecret;
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function getBackendTransactionSecret()
    {
        return $this->backendTransactionSecret;
    }

    /**
     * @param string
     *
     * @since 1.0.0
     */
    public function setBackendCreditorId($backendCreditorId)
    {
        $this->backendCreditorId = $backendCreditorId;
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function getBackendCreditorId()
    {
        return $this->backendCreditorId;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return array_merge(
            parent::toArray(),
            [
                'showBic'                  => $this->showBic(),
                'creditorId'               => $this->getCreditorId(),
                'creditorName'             => $this->getCreditorName(),
                'creditorAddress'          => $this->getCreditorAddress(),
                'backendTransactionMaid'   => $this->getBackendTransactionMAID(),
                'backendCreditorId'        => $this->getBackendCreditorId()
            ]
        );
    }
}
