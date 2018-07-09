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

class PaymentConfig
{
    /**
     * @var string
     */
    protected $baseUrl;

    /**
     * @var string
     */
    protected $httpUser;

    /**
     * @var string
     */
    protected $httpPassword;

    /**
     * @var string
     */
    protected $transactionMAID;

    /**
     * @var string
     */
    protected $transactionSecret;

    /**
     * @var string
     */
    protected $transactionType;

    /**
     * @var bool
     */
    protected $sendBasket;

    /**
     * @var bool
     */
    protected $fraudPrevention;

    /**
     * @var bool
     */
    protected $sendDescriptor;

    /**
     * PaymentConfig constructor.
     *
     * @param string $baseUrl
     * @param string $httpUser
     * @param string $httpPassword
     */
    public function __construct($baseUrl, $httpUser, $httpPassword)
    {
        $this->baseUrl      = $baseUrl;
        $this->httpUser     = $httpUser;
        $this->httpPassword = $httpPassword;
    }

    /**
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * @return string
     */
    public function getHttpUser()
    {
        return $this->httpUser;
    }

    /**
     * @return string
     */
    public function getHttpPassword()
    {
        return $this->httpPassword;
    }

    /**
     * @return string|null
     */
    public function getTransactionMAID()
    {
        return $this->transactionMAID;
    }

    /**
     * @param string $transactionMAID
     */
    public function setTransactionMAID($transactionMAID)
    {
        $this->transactionMAID = $transactionMAID;
    }

    /**
     * @return string|null
     */
    public function getTransactionSecret()
    {
        return $this->transactionSecret;
    }

    /**
     * @param string $transactionSecret
     */
    public function setTransactionSecret($transactionSecret)
    {
        $this->transactionSecret = $transactionSecret;
    }

    /**
     * @return string|null
     */
    public function getTransactionType()
    {
        return $this->transactionType;
    }

    /**
     * @param string $transactionType
     */
    public function setTransactionType($transactionType)
    {
        $this->transactionType = $transactionType;
    }

    /**
     * @return bool
     */
    public function sendBasket()
    {
        return (bool)$this->sendBasket;
    }

    /**
     * @param bool $sendBasket
     */
    public function setSendBasket($sendBasket)
    {
        $this->sendBasket = $sendBasket;
    }

    /**
     * @return bool
     */
    public function hasFraudPrevention()
    {
        return (bool)$this->fraudPrevention;
    }

    /**
     * @param bool $fraudPrevention
     */
    public function setFraudPrevention($fraudPrevention)
    {
        $this->fraudPrevention = $fraudPrevention;
    }

    /**
     * @return bool
     */
    public function sendDescriptor()
    {
        return (bool)$this->sendDescriptor;
    }

    /**
     * @param bool $sendDescriptor
     */
    public function setSendDescriptor($sendDescriptor)
    {
        $this->sendDescriptor = $sendDescriptor;
    }
}
