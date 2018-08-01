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
