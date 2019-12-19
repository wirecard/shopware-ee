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
use Wirecard\PaymentSdk\Exception\MalformedResponseException;
use Wirecard\PaymentSdk\Response\FormInteractionResponse;
use Wirecard\PaymentSdk\Response\InteractionResponse;
use Wirecard\PaymentSdk\Response\Response;
use Wirecard\PaymentSdk\Response\SuccessResponse;
use Wirecard\PaymentSdk\TransactionService;
use WirecardElasticEngine\Components\Services\TransactionManager;

/**
 * @package WirecardElasticEngine\Models
 *
 * @ORM\Entity
 * @ORM\Table(name="wirecard_elastic_engine_transactions")
 *
 * Every time a request or response to/from the Wirecard servers is done a new transaction is created in the database.
 * For internal purposes transactions are separated in "types" (don't confuse with the real transaction type!), which
 * are shown as "Source" in the browser.
 * Transactions are mainly created respectively updated by the `TransactionManager`.
 *
 * @see     TransactionManager
 *
 * @since   1.0.0
 */
class Transaction extends ModelEntity
{
    const TYPE_INITIAL_RESPONSE = 'initial-response';
    const TYPE_INITIAL_REQUEST = 'initial-request';
    const TYPES_INITIAL = ['initial-response', 'initial-request'];
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
     * @var array
     * @ORM\Column(name="basket", type="array", nullable=true)
     */
    private $basket;

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
     * @var \DateTime
     * @ORM\Column(name="updated_at", type="datetime", nullable=false)
     */
    private $updatedAt;

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

    /**
     * @var string
     * @ORM\Column(name="status_message", type="text", nullable=true)
     */
    private $statusMessage;

    /**
     * @param string $type Transaction type, see Model constants for available types
     *
     * @since 1.0.0
     */
    public function __construct($type)
    {
        $this->type = $type;
        $this->state = self::STATE_OPEN;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    /**
     * @param $type
     *
     * @since 1.4.0
     */
    public function setType($type)
    {
        $this->type = $type;
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
     * @return string|null
     *
     * @since 1.0.0
     */
    public function getOrderNumber()
    {
        return $this->orderNumber;
    }

    /**
     * @param string|null orderNumber
     *
     * @since 1.0.0
     */
    public function setOrderNumber($orderNumber)
    {
        $this->orderNumber = $orderNumber;
    }

    /**
     * @return string|null
     *
     * @since 1.0.0
     */
    public function getPaymentUniqueId()
    {
        return $this->paymentUniqueId;
    }

    /**
     * @param string|null $paymentUniqueId
     *
     * @since 1.0.0
     */
    public function setPaymentUniqueId($paymentUniqueId)
    {
        $this->paymentUniqueId = $paymentUniqueId;
    }

    /**
     * @return string|null
     *
     * @since 1.0.0
     */
    public function getParentTransactionId()
    {
        return $this->parentTransactionId;
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function getTransactionId()
    {
        return $this->transactionId;
    }

    /**
     * @return string|null
     *
     * @since 1.0.0
     */
    public function getProviderTransactionId()
    {
        return $this->providerTransactionId;
    }

    /**
     * @return string|null
     *
     * @since 1.0.0
     */
    public function getProviderTransactionReference()
    {
        return $this->providerTransactionReference;
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function getTransactionType()
    {
        return $this->transactionType;
    }

    /**
     * Return payment method. If null, fallback to first payment method in response data.
     * (PaymentSDK only provides getPaymentMethod() in SuccessResponse class)
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function getPaymentMethod()
    {
        if ($this->paymentMethod) {
            return $this->paymentMethod;
        }
        return isset($this->response['payment-methods.0.name']) ? $this->response['payment-methods.0.name'] : null;
    }

    /**
     * @param string $paymentMethod
     *
     * @since 1.0.0
     */
    private function setPaymentMethod($paymentMethod)
    {
        $this->paymentMethod = $paymentMethod;
    }

    /**
     * @return float|null
     *
     * @since 1.0.0
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @return string|null
     *
     * @since 1.0.0
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @return array
     *
     * @since 1.0.0
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param Response $response
     *
     * @since 1.0.0
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
                // the response does not contain an 'order-number'
            }
        }

        $this->response = $response->getData();

        if ($response->getBasket()) {
            $this->basket = [];
            /** @var \Wirecard\PaymentSdk\Entity\Item $item */
            foreach ($response->getBasket()->getIterator() as $item) {
                $this->basket[$item->getArticleNumber()] = $item->mappedProperties();
            }
        }

        // provider-transaction-reference-id can also be present in responses besides SuccessResponse, but we dont have
        // a getter there.
        if (! $this->providerTransactionReference && isset($this->response['provider-transaction-reference-id'])) {
            $this->providerTransactionReference = $this->response['provider-transaction-reference-id'];
        }
    }

    /**
     * @return array|null
     *
     * @since 1.0.0
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param array $request
     *
     * @since 1.0.0
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
     * @return array|null
     *
     * @since 1.1.0
     */
    public function getBasket()
    {
        return $this->basket;
    }

    /**
     * @return string|null
     *
     * @since 1.0.0
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string|null
     *
     * @since 1.0.0
     */
    public function getRequestId()
    {
        return $this->requestId;
    }

    /**
     * @param \DateTime
     *
     * @since 1.0.0
     */
    public function setCreatedAt(\DateTime $createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return \DateTime
     *
     * @since 1.0.0
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime
     *
     * @since 1.0.0
     */
    public function setUpdatedAt(\DateTime $updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * @return \DateTime
     *
     * @since 1.0.0
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param string $state
     *
     * @since 1.0.0
     */
    public function setState($state)
    {
        $this->state = $state;
    }

    /**
     * @return string|null
     *
     * @since 1.0.0
     */
    public function getBasketSignature()
    {
        return $this->basketSignature;
    }

    /**
     * @param string|null $basketSignature
     *
     * @since 1.0.0
     */
    public function setBasketSignature($basketSignature)
    {
        $this->basketSignature = $basketSignature;
    }

    /**
     * @return int|null
     *
     * @since 1.0.0
     */
    public function getPaymentStatus()
    {
        return $this->paymentStatus;
    }

    /**
     * @param int $paymentStatusId
     *
     * @since 1.0.0
     */
    public function setPaymentStatus($paymentStatusId)
    {
        $this->paymentStatus = $paymentStatusId;
    }

    /**
     * @return string|null
     *
     * @since 1.0.0
     */
    public function getStatusMessage()
    {
        return $this->statusMessage;
    }

    /**
     * @param string|null $statusMessage
     *
     * @since 1.0.0
     */
    public function setStatusMessage($statusMessage)
    {
        $this->statusMessage = $statusMessage;
    }

    /**
     * @return bool
     *
     * @since 1.0.0
     */
    public function isInitial()
    {
        return $this->getType() === self::TYPE_INITIAL_REQUEST || $this->getType() === self::TYPE_INITIAL_RESPONSE;
    }

    /**
     * @return array
     *
     * @since 1.0.0
     */
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
            'createdAt'                    => $this->getCreatedAt()->format(\DateTime::W3C),
            'updatedAt'                    => $this->getUpdatedAt()->format(\DateTime::W3C),
            'response'                     => $this->getResponse(),
            'request'                      => $this->getRequest(),
            'state'                        => $this->getState(),
            'statusMessage'                => $this->getStatusMessage(),
        ];
    }
}
