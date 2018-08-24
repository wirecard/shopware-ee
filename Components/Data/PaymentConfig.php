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
 * Basic payment configuration class, which stores general data like http user, api url, merchant account id (maid), ...
 *
 * @package WirecardElasticEngine\Components\Data
 *
 * @since   1.0.0
 */
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
    protected $transactionOperation;

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
     * @param string $baseUrl
     * @param string $httpUser
     * @param string $httpPassword
     *
     * @since 1.0.0
     */
    public function __construct($baseUrl, $httpUser, $httpPassword)
    {
        $this->baseUrl      = $baseUrl;
        $this->httpUser     = $httpUser;
        $this->httpPassword = $httpPassword;
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function getHttpUser()
    {
        return $this->httpUser;
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function getHttpPassword()
    {
        return $this->httpPassword;
    }

    /**
     * @return string|null
     *
     * @since 1.0.0
     */
    public function getTransactionMAID()
    {
        return $this->transactionMAID;
    }

    /**
     * @param string $transactionMAID
     *
     * @since 1.0.0
     */
    public function setTransactionMAID($transactionMAID)
    {
        $this->transactionMAID = $transactionMAID;
    }

    /**
     * @return string|null
     *
     * @since 1.0.0
     */
    public function getTransactionSecret()
    {
        return $this->transactionSecret;
    }

    /**
     * @param string $transactionSecret
     *
     * @since 1.0.0
     */
    public function setTransactionSecret($transactionSecret)
    {
        $this->transactionSecret = $transactionSecret;
    }

    /**
     * @return string|null
     *
     * @since 1.0.0
     */
    public function getTransactionOperation()
    {
        return $this->transactionOperation;
    }

    /**
     * @param string $transactionOperation
     *
     * @since 1.0.0
     */
    public function setTransactionOperation($transactionOperation)
    {
        $this->transactionOperation = $transactionOperation;
    }

    /**
     * @return bool
     *
     * @since 1.0.0
     */
    public function sendBasket()
    {
        return (bool)$this->sendBasket;
    }

    /**
     * @param bool $sendBasket
     *
     * @since 1.0.0
     */
    public function setSendBasket($sendBasket)
    {
        $this->sendBasket = $sendBasket;
    }

    /**
     * @return bool
     *
     * @since 1.0.0
     */
    public function hasFraudPrevention()
    {
        return (bool)$this->fraudPrevention;
    }

    /**
     * @param bool $fraudPrevention
     *
     * @since 1.0.0
     */
    public function setFraudPrevention($fraudPrevention)
    {
        $this->fraudPrevention = $fraudPrevention;
    }

    /**
     * @return bool
     *
     * @since 1.0.0
     */
    public function sendDescriptor()
    {
        return (bool)$this->sendDescriptor;
    }

    /**
     * @param bool $sendDescriptor
     *
     * @since 1.0.0
     */
    public function setSendDescriptor($sendDescriptor)
    {
        $this->sendDescriptor = $sendDescriptor;
    }

    /**
     * @return array
     *
     * @since 1.0.0
     */
    public function toArray()
    {
        return [
            'baseUrl'              => $this->getBaseUrl(),
            'transactionMAID'      => $this->getTransactionMAID(),
            'transactionOperation' => $this->getTransactionOperation(),
            'sendBasket'           => $this->sendBasket(),
            'fraudPrevention'      => $this->hasFraudPrevention(),
            'sendDescriptor'       => $this->sendDescriptor(),
        ];
    }
}
