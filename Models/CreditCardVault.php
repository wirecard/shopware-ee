<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

namespace WirecardElasticEngine\Models;

use Shopware\Components\Model\ModelEntity;
use Doctrine\ORM\Mapping as ORM;

/**
 * @package WirecardElasticEngine\Models
 *
 * @ORM\Entity
 * @ORM\Table(name="wirecard_elastic_engine_credit_card_vault")
 *
 * The credit card vault model holds credit card tokens for one-click checkout.
 *
 * @since   1.1.0
 */
class CreditCardVault extends ModelEntity
{
    /**
     * @var int
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var int
     * @ORM\Column(name="user_id", type="string", nullable=false)
     */
    private $userId;

    /**
     * @var string
     * @ORM\Column(name="token", type="string", nullable=false)
     */
    private $token;

    /**
     * @var string
     * @ORM\Column(name="masked_account_number", type="string", nullable=false)
     */
    private $maskedAccountNumber;

    /**
     * @var \DateTime
     * @ORM\Column(name="last_used", type="datetime", nullable=false)
     */
    private $lastUsed;

    /**
     * @var array
     * @ORM\Column(name="bind_billing_address", type="array", nullable=false)
     */
    private $bindBillingAddress;

    /**
     * @var string
     * @ORM\Column(name="bind_billing_address_hash", type="string", nullable=false)
     */
    private $bindBillingAddressHash;

    /**
     * @var array
     * @ORM\Column(name="bind_shipping_address", type="array", nullable=false)
     */
    private $bindShippingAddress;

    /**
     * @var string
     * @ORM\Column(name="bind_shipping_address_hash", type="string", nullable=false)
     */
    private $bindShippingAddressHash;

    /**
     * @var array
     * @ORM\Column(name="additional_data", type="array", nullable=true)
     */
    private $additionalData;

    /**
     * @since 1.1.0
     */
    public function __construct()
    {
        $this->setLastUsed(new \DateTime());
    }

    /**
     * @return int|null
     *
     * @since 1.1.0
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     *
     * @since 1.1.0
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     *
     * @since 1.1.0
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    /**
     * @return string
     *
     * @since 1.1.0
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param string
     *
     * @since 1.1.0
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * @return string
     *
     * @since 1.1.0
     */
    public function getMaskedAccountNumber()
    {
        return $this->maskedAccountNumber;
    }

    /**
     * @param string $maskedAccountNumber
     *
     * @since 1.1.0
     */
    public function setMaskedAccountNumber($maskedAccountNumber)
    {
        $this->maskedAccountNumber = $maskedAccountNumber;
    }

    /**
     * @return \DateTime
     *
     * @since 1.1.0
     */
    public function getLastUsed()
    {
        return $this->lastUsed;
    }

    /**
     * @param \DateTime $lastUsed
     *
     * @since 1.1.0
     */
    public function setLastUsed(\DateTime $lastUsed)
    {
        $this->lastUsed = $lastUsed;
    }

    /**
     * @return array
     *
     * @since 1.1.0
     */
    public function getBindBillingAddress()
    {
        return $this->bindBillingAddress;
    }

    /**
     * @param array $bindBillingAddress
     *
     * @since 1.1.0
     */
    public function setBindBillingAddress(array $bindBillingAddress)
    {
        $this->bindBillingAddress = $bindBillingAddress;
    }

    /**
     * @return string
     *
     * @since 1.1.0
     */
    public function getBindBillingAddressHash()
    {
        return $this->bindBillingAddressHash;
    }

    /**
     * @param string $bindBillingAddressHash
     *
     * @since 1.1.0
     */
    public function setBindBillingAddressHash($bindBillingAddressHash)
    {
        $this->bindBillingAddressHash = $bindBillingAddressHash;
    }

    /**
     * @return array
     *
     * @since 1.1.0
     */
    public function getBindShippingAddress()
    {
        return $this->bindShippingAddress;
    }

    /**
     * @param array $bindShippingAddress
     *
     * @since 1.1.0
     */
    public function setBindShippingAddress(array $bindShippingAddress)
    {
        $this->bindShippingAddress = $bindShippingAddress;
    }

    /**
     * @return string
     *
     * @since 1.1.0
     */
    public function getBindShippingAddressHash()
    {
        return $this->bindShippingAddressHash;
    }

    /**
     * @param string $bindShippingAddressHash
     *
     * @since 1.1.0
     */
    public function setBindShippingAddressHash($bindShippingAddressHash)
    {
        $this->bindShippingAddressHash = $bindShippingAddressHash;
    }

    /**
     * @return array
     *
     * @since 1.1.0
     */
    public function getAdditionalData()
    {
        return $this->additionalData;
    }

    /**
     * @param array $additionalData
     *
     * @since 1.1.0
     */
    public function setAdditionalData(array $additionalData)
    {
        $this->additionalData = $additionalData;
    }

    /**
     * @return array
     *
     * @since 1.1.0
     */
    public function toArray()
    {
        return [
            'id'                      => $this->getId(),
            'userId'                  => $this->getUserId(),
            'token'                   => $this->getToken(),
            'maskedAccountNumber'     => $this->getMaskedAccountNumber(),
            'lastUsed'                => $this->getLastUsed()->format(\DateTime::W3C),
            'bindBillingAddress'      => $this->getBindBillingAddress(),
            'bindBillingAddressHash'  => $this->getBindBillingAddressHash(),
            'bindShippingAddress'     => $this->getBindShippingAddress(),
            'bindShippingAddressHash' => $this->getBindShippingAddressHash(),
            'additionalData'          => $this->getAdditionalData(),
        ];
    }
}
