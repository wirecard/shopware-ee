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
    protected $mandateText;

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
     * @param bool
     */
    public function setShowBic($showBic)
    {
        $this->showBic = $showBic;
    }

    /**
     * @return bool
     */
    public function showBic()
    {
        return (bool)$this->showBic;
    }

    /**
     * @param string
     */
    public function setCreditorId($creditorId)
    {
        $this->creditorId = $creditorId;
    }

    /**
     * @return string
     */
    public function getCreditorId()
    {
        return $this->creditorId;
    }

    /**
     * @param string
     */
    public function setCreditorName($creditorName)
    {
        $this->creditorName = $creditorName;
    }

    /**
     * @return string
     */
    public function getCreditorName()
    {
        return $this->creditorName;
    }

    /**
     * @param string
     */
    public function setCreditorAddress($creditorAddress)
    {
        $this->creditorAddress = $creditorAddress;
    }

    /**
     * @return string
     */
    public function getCreditorAddress()
    {
        return $this->creditorAddress;
    }

    /**
     * @param string
     */
    public function setMandateText($mandateText)
    {
        $this->mandateText = $mandateText;
    }

    /**
     * @return string
     */
    public function getMandateText()
    {
        return $this->mandateText;
    }

    /**
     * @param string
     */
    public function setBackendTransactionMAID($backendTransactionMaid)
    {
        $this->backendTransactionMaid = $backendTransactionMaid;
    }

    /**
     * @return string
     */
    public function getBackendTransactionMAID()
    {
        return $this->backendTransactionMaid;
    }

    /**
     * @param string
     */
    public function setBackendTransactionSecret($backendTransactionSecret)
    {
        $this->backendTransactionSecret = $backendTransactionSecret;
    }

    /**
     * @return string
     */
    public function getBackendTransactionSecret()
    {
        return $this->backendTransactionSecret;
    }

    /**
     * @param string
     */
    public function setBackendCreditorId($backendCreditorId)
    {
        $this->backendCreditorId = $backendCreditorId;
    }

    /**
     * @return string
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
                'mandateText'              => $this->getMandateText(),
                'backendTransactionMaid'   => $this->getBackendTransactionMAID(),
                'backendCreditorId'        => $this->getBackendCreditorId()
            ]
        );
    }
}
