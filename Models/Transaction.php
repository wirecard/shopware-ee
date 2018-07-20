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
use Doctrine\ORM\Mapping as ORM;
use Wirecard\PaymentSdk\Exception\MalformedResponseException;
use Wirecard\PaymentSdk\Response\FormInteractionResponse;
use Wirecard\PaymentSdk\Response\InteractionResponse;
use Wirecard\PaymentSdk\Response\Response;
use Wirecard\PaymentSdk\Response\SuccessResponse;
use Wirecard\PaymentSdk\TransactionService;

/**
 * @ORM\Entity
 * @ORM\Table(name="wirecard_elastic_engine_transactions")
 */
class Transaction extends ModelEntity
{
    const TYPE_INITIAL_RESPONSE = 'initial-response';
    const TYPE_INITIAL_REQUEST = 'initial-request';
    const TYPE_BACKEND = 'backend';
    const TYPE_RETURN = 'return';
    const TYPE_INTERACTION = 'interaction';
    const TYPE_NOTIFY = 'notify';

    const STATE_OPEN = 'open';
    const STATE_CLOSED = 'closed';

    const NOTIFY_PAYMENT_STATUS = 'notify-payment-status';

    /**
     * @var int
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
     * @var string
     * @ORM\Column(name="payment_unique_id", type="string", nullable=true)
     */
    private $paymentUniqueId;

    /**
     * @var string
     * @ORM\Column(name="parent_transaction_id", type="string", nullable=true)
     */
    private $parentTransactionId;

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
     * @ORM\Column(name="provider_transaction_reference", type="string", nullable=true)
     */
    private $providerTransactionReference;

    /**
     * @var string
     * @ORM\Column(name="transaction_type", type="string", nullable=true)
     */
    private $transactionType;

    /**
     * @var string
     * @ORM\Column(name="payment_method", type="string", nullable=true)
     */
    private $paymentMethod;

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
     * @ORM\Column(name="response", type="array", nullable=true)
     */
    private $response;

    /**
     * @var array
     * @ORM\Column(name="request", type="array", nullable=true)
     */
    private $request;

    /**
     * @var string
     * @ORM\Column(name="type", type="string", nullable=true)
     */
    private $type;

    /**
     * @var string
     * @ORM\Column(name="request_id", type="string", nullable=true)
     */
    private $requestId;

    /**
     * @var \DateTime
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    private $createdAt;

    /**
     * @var string
     * @ORM\Column(name="state", type="string")
     */
    private $state;

    /**
     * @var string
     * @ORM\Column(name="basket_signature", type="string", nullable=true)
     */
    private $basketSignature;

    /**
     * @var int
     * @ORM\Column(name="payment_status", type="integer", nullable=true)
     */
    private $paymentStatus;

    public function __construct($type)
    {
        $this->type = $type;
        $this->setState(self::STATE_OPEN);
        $this->setCreatedAt(new \DateTime());
    }

    /**
     * @return int|null
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
     * @param string|null orderNumber
     */
    public function setOrderNumber($orderNumber)
    {
        $this->orderNumber = $orderNumber;
    }

    /**
     * @return string|null
     */
    public function getPaymentUniqueId()
    {
        return $this->paymentUniqueId;
    }

    /**
     * @param string|null $paymentUniqueId
     */
    public function setPaymentUniqueId($paymentUniqueId)
    {
        $this->paymentUniqueId = $paymentUniqueId;
    }

    /**
     * @return string|null
     */
    public function getParentTransactionId()
    {
        return $this->parentTransactionId;
    }

    /**
     * @return string
     */
    public function getTransactionId()
    {
        return $this->transactionId;
    }

    /**
     * @return string|null
     */
    public function getProviderTransactionId()
    {
        return $this->providerTransactionId;
    }

    /**
     * @return string|null
     */
    public function getProviderTransactionReference()
    {
        return $this->providerTransactionReference;
    }

    /**
     * @return string
     */
    public function getTransactionType()
    {
        return $this->transactionType;
    }

    /**
     * @return string
     */
    public function getPaymentMethod()
    {
        return $this->paymentMethod;
    }

    /**
     * @param string $paymentMethod
     */
    private function setPaymentMethod($paymentMethod)
    {
        $this->paymentMethod = $paymentMethod;
    }

    /**
     * @return float|null
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @return string|null
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @return array
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param Response $response
     */
    public function setResponse(Response $response)
    {
        $this->requestId       = $response->getRequestId();
        $this->transactionType = $response->getTransactionType();

        if ($response instanceof SuccessResponse) {
            $this->transactionId                = $response->getTransactionId();
            $this->parentTransactionId          = $response->getParentTransactionId();
            $this->providerTransactionId        = $response->getProviderTransactionId();
            $this->providerTransactionReference = $response->getProviderTransactionReference();
            $this->setPaymentMethod($response->getPaymentMethod());
        } elseif ($response instanceof InteractionResponse || $response instanceof FormInteractionResponse) {
            $this->transactionId = $response->getTransactionId();
        }

        if ($response->getRequestedAmount()) {
            $this->currency = $response->getRequestedAmount()->getCurrency();
            $this->amount   = $response->getRequestedAmount()->getValue();
        }

        if (! $this->getPaymentUniqueId()) {
            try {
                $this->setPaymentUniqueId($response->findElement('order-number'));
            } catch (MalformedResponseException $e) {
            }
        }

        $this->response = $response->getData();
    }

    /**
     * @return array|null
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param array $request
     */
    public function setRequest(array $request)
    {
        if (isset($request[TransactionService::REQUEST_ID])) {
            $this->requestId = $request[TransactionService::REQUEST_ID];
        }
        if (isset($request['transaction_type'])) {
            $this->transactionType = $request['transaction_type'];
        }
        if (isset($request['requested_amount'])) {
            $this->amount = $request['requested_amount'];
        }
        if (isset($request['requested_amount_currency'])) {
            $this->currency = $request['requested_amount_currency'];
        }
        if (isset($request['payment_method'])) {
            $this->setPaymentMethod($request['payment_method']);
        }

        $this->request = $request;
    }

    /**
     * @return string|null
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string|null
     */
    public function getRequestId()
    {
        return $this->requestId;
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

    /**
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param string $state
     */
    public function setState($state)
    {
        $this->state = $state;
    }

    /**
     * @return string|null
     */
    public function getBasketSignature()
    {
        return $this->basketSignature;
    }

    /**
     * @param string|null $basketSignature
     */
    public function setBasketSignature($basketSignature)
    {
        $this->basketSignature = $basketSignature;
    }

    /**
     * @return int|null
     */
    public function getPaymentStatus()
    {
        return $this->paymentStatus;
    }

    /**
     * @param int $paymentStatusId
     */
    public function setPaymentStatus($paymentStatusId)
    {
        $this->paymentStatus = $paymentStatusId;
    }

    public function isInitial()
    {
        return $this->getType() === self::TYPE_INITIAL_REQUEST || $this->getType() === self::TYPE_INITIAL_RESPONSE;
    }

    public function toArray()
    {
        return [
            'id'                           => $this->getId(),
            'orderNumber'                  => $this->getOrderNumber(),
            'paymentUniqueId'              => $this->getPaymentUniqueId(),
            'transactionType'              => $this->getTransactionType(),
            'paymentMethod'                => $this->getPaymentMethod(),
            'transactionId'                => $this->getTransactionId(),
            'parentTransactionId'          => $this->getParentTransactionId(),
            'providerTransactionId'        => $this->getProviderTransactionId(),
            'providerTransactionReference' => $this->getProviderTransactionReference(),
            'requestId'                    => $this->getRequestId(),
            'type'                         => $this->getType(),
            'amount'                       => $this->getAmount(),
            'currency'                     => $this->getCurrency(),
            'createdAt'                    => $this->getCreatedAt(),
            'response'                     => $this->getResponse(),
            'request'                      => $this->getRequest(),
            'state'                        => $this->getState(),
        ];
    }
}
