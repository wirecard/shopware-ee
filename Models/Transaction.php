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

namespace WirecardShopwareElasticEngine\Models;

use Shopware\Components\Model\ModelEntity;
use Shopware\Models\Order\Order;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="wirecard_elastic_engine_transaction")
 */
class Transaction extends ModelEntity
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="order_number", type="string", nullable=true)
     */
    private $orderNumber;

    /**
     * @var Order
     * @ORM\ManyToOne(targetEntity="Shopware\Models\Order\Order")
     * @ORM\JoinColumn(name="order_number", referencedColumnName="ordernumber")
     */
    private $order;

    /**
     * @var string
     * @ORM\Column(name="parent_transaction_id", type="string", nullable=false)
     */
    private $parentTransactionId;

    /**
     * @var string
     * @ORM\Column(name="transaction_id", type="string", nullable=false, unique=true)
     */
    private $transactionId;

    /**
     * @var string
     * @ORM\Column(name="provider_transaction_id", type="string", nullable=true)
     */
    private $providerTransactionId;

    /**
     * @var string
     * @ORM\Column(name="transaction_type", type="string", nullable=true)
     */
    private $transactionType;

    /**
     * @var float
     * @ORM\Column(name="amount", type="float", nullable=true)
     */
    private $amount;

    /**
     * @var string
     * @ORM\Column(name="currency", type="string", nullable=true)
     */
    private $currency;

    /**
     * @var array
     * @ORM\Column(name="return_response", type="array", nullable=true)
     */
    private $returnResponse;

    /**
     * @var array
     * @ORM\Column(name="notification_response", type="array", nullable=true)
     */
    private $notificationResponse;

    /**
     * @var \DateTime
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    private $createdAt;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getOrderNumber()
    {
        return $this->orderNumber;
    }

    /**
     * @param string orderNumber
     */
    public function setOrderNumber($orderNumber)
    {
        $this->orderNumber = $orderNumber;
    }

    /**
     * @return Order
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @param Order $order
     */
    public function setOrder($order)
    {
        $this->order = $order;
    }


    /**
     * @return string
     */
    public function getParentTransactionId()
    {
        return $this->parentTransactionId;
    }

    /**
     * @param string $parentTransactionId
     */
    public function setParentTransactionId($parentTransactionId)
    {
        $this->parentTransactionId = $parentTransactionId;
    }

    /**
     * @return string
     */
    public function getTransactionId()
    {
        return $this->transactionId;
    }

    /**
     * @param string $transactionId
     */
    public function setTransactionId($transactionId)
    {
        $this->transactionId = $transactionId;
    }

    /**
     * @return string
     */
    public function getProviderTransactionId()
    {
        return $this->providerTransactionId;
    }

    /**
     * @param string $providerTransactionId
     */
    public function setProviderTransactionId($providerTransactionId)
    {
        $this->providerTransactionId = $providerTransactionId;
    }

    /**
     * @return string
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
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param float $amount
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
    }

    /**
     * @return array
     */
    public function getReturnResponse()
    {
        return $this->returnResponse;
    }

    /**
     * @param string $returnResponse
     */
    public function setReturnResponse($returnResponse)
    {
        $this->returnResponse = $returnResponse;
    }

    /**
     * @return array
     */
    public function getNotificationResponse()
    {
        return $this->notificationResponse;
    }

    /**
     * @param string $notificationResponse
     */
    public function setNotificationResponse($notificationResponse)
    {
        $this->notificationResponse = $notificationResponse;
    }

    /**
     * @param \DateTime
     */
    public function setCreatedAt(\DateTime $createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }
}
