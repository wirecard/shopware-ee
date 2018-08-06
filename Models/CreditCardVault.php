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

namespace WirecardElasticEngine\Models;

use Shopware\Components\Model\ModelEntity;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="wirecard_elastic_engine_credit_card_vault")
 *
 * @since 1.0.0
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
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->setLastUsed(new \DateTime());
    }

    /**
     * @return int|null
     *
     * @since 1.0.0
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     *
     * @since 1.0.0
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     *
     * @since 1.0.0
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param string
     *
     * @since 1.0.0
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function getMaskedAccountNumber()
    {
        return $this->maskedAccountNumber;
    }

    /**
     * @param string $masked
     *
     * @since 1.0.0
     */
    public function setMaskedAccountNumber($maskedAccountNumber)
    {
        $this->maskedAccountNumber = $maskedAccountNumber;
    }

    /**
     * @return \DateTime
     *
     * @since 1.0.0
     */
    public function getLastUsed()
    {
        return $this->lastUsed;
    }

    /**
     * @param \DateTime $lastUsed
     *
     * @since 1.0.0
     */
    public function setLastUsed(\DateTime $lastUsed)
    {
        $this->lastUsed = $lastUsed;
    }

    /**
     * @return array
     *
     * @since 1.0.0
     */
    public function toArray()
    {
        return [
            'id'                  => $this->getId(),
            'userId'              => $this->getUserId(),
            'token'               => $this->getToken(),
            'maskedAccountNumber' => $this->getMaskedAccountNumber(),
            'lastUsed'            => $this->getLastUsed(),
        ];
    }
}
