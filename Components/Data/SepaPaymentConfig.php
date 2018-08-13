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
 * SEPA specific payment configuration.
 *
 * @package WirecardElasticEngine\Components\Data
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
     * Set SEPA Credit Transfer transaction merchant account ID
     *
     * @param string
     *
     * @since 1.0.0
     */
    public function setBackendTransactionMAID($backendTransactionMaid)
    {
        $this->backendTransactionMaid = $backendTransactionMaid;
    }

    /**
     * Get SEPA Credit Transfer transaction merchant account ID
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function getBackendTransactionMAID()
    {
        return $this->backendTransactionMaid;
    }

    /**
     * Set SEPA Credit Transfer transaction secret
     *
     * @param string
     *
     * @since 1.0.0
     */
    public function setBackendTransactionSecret($backendTransactionSecret)
    {
        $this->backendTransactionSecret = $backendTransactionSecret;
    }

    /**
     * Get SEPA Credit Transfer transaction secret
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function getBackendTransactionSecret()
    {
        return $this->backendTransactionSecret;
    }

    /**
     * set SEPA Credit Transfer creditor id
     *
     * @param string
     *
     * @since 1.0.0
     */
    public function setBackendCreditorId($backendCreditorId)
    {
        $this->backendCreditorId = $backendCreditorId;
    }

    /**
     * Get SEPA Credit Transfer creditor id
     *
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
                'showBic'                => $this->showBic(),
                'creditorId'             => $this->getCreditorId(),
                'creditorName'           => $this->getCreditorName(),
                'creditorAddress'        => $this->getCreditorAddress(),
                'backendTransactionMaid' => $this->getBackendTransactionMAID(),
                'backendCreditorId'      => $this->getBackendCreditorId(),
            ]
        );
    }
}
