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
 * Sofort. specific payment configuration.
 *
 * @package WirecardElasticEngine\Components\Data
 *
 * @since   1.0.0
 */
class SofortPaymentConfig extends PaymentConfig
{
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
     * Set SEPA Credit Transfer creditor id
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
                'backendTransactionMaid'   => $this->getBackendTransactionMAID(),
                'backendCreditorId'        => $this->getBackendCreditorId()
            ]
        );
    }
}
