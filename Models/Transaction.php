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
 * @ORM\Table(name="wirecard_elastic_engine_transactions")
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
     * @ORM\OneToOne(targetEntity="Shopware\Models\Order\Order")
     * @ORM\JoinColumn(name="order_number", referencedColumnName="ordernumber")
     */
    private $order;

    /**
     * @var string
     * @ORM\Column(name="transaction_id", type="string", nullable=true)
     */
    private $transactionId;

    /**
     * @var string
     * @ORM\Column(name="provider_transaction_id", type="string", nullable=true)
     */
    private $providerTransactionId;

    /**
     * @var string
     * @ORM\Column(name="return_response", type="text", nullable=true)
     */
    private $returnResponse;

    /**
     * @var string
     * @ORM\Column(name="notification_response", type="text", nullable=true)
     */
    private $notificationResponse;

    /**
     * @var string
     * @ORM\Column(name="payment_status", type="string", nullable=true)
     */
    private $paymentStatus;

    /**
     * @var string
     * @ORM\Column(name="basket_signature", type="string", nullable=true)
     */
    private $basketSignature;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string|null
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
     * @return Order|null
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @param Order $order
     */
    public function setOrder(Order $order)
    {
        $this->order = $order;
    }

    /**
     * @return string|null
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
     * @return string|null
     */
    public function getProviderTransactionId()
    {
        return $this->providerTransactionId;
    }

    /**
     * @param $providerTransactionId
     */
    public function setProviderTransactionId($providerTransactionId)
    {
        $this->providerTransactionId = $providerTransactionId;
    }

    /**
     * @return string|null
     */
    public function getReturnResponse()
    {
        return $this->returnResponse;
    }

    /**
     * @param $returnResponse
     */
    public function setReturnResponse($returnResponse)
    {
        $this->returnResponse = $returnResponse;
    }

    /**
     * @return string|null
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
     * @return string|null
     */
    public function getPaymentStatus()
    {
        return $this->paymentStatus;
    }

    /**
     * @param string $paymentStatus
     */
    public function setPaymentStatus($paymentStatus)
    {
        $this->paymentStatus = $paymentStatus;
    }

    /**
     * @return string|null
     */
    public function getBasketSignature()
    {
        return $this->basketSignature;
    }
    
    /**
     * @param string $basketSignature
     */
    public function setBasketSignature($basketSignature)
    {
        $this->basketSignature = $basketSignature;
    }
}
