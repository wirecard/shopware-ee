<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

namespace WirecardElasticEngine\Components\Services;

use Doctrine\ORM\EntityManagerInterface;
use Wirecard\PaymentSdk\BackendService;
use Wirecard\PaymentSdk\Entity\Basket;
use Wirecard\PaymentSdk\Exception\MalformedResponseException;
use Wirecard\PaymentSdk\Response\FormInteractionResponse;
use Wirecard\PaymentSdk\Response\InteractionResponse;
use Wirecard\PaymentSdk\Response\Response;
use Wirecard\PaymentSdk\Response\SuccessResponse;
use Wirecard\PaymentSdk\Transaction\PoiPiaTransaction;
use WirecardElasticEngine\Components\Data\OrderSummary;
use WirecardElasticEngine\Exception\InitialTransactionNotFoundException;
use WirecardElasticEngine\Models\Transaction;

/**
 * @package WirecardElasticEngine\Components\Services
 *
 * @since   1.0.0
 */
class TransactionManager
{
    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @param EntityManagerInterface $em
     *
     * @since 1.0.0
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @param OrderSummary $orderSummary
     * @param Response $response
     *
     * @return Transaction|null
     *
     * @since 1.0.0
     */
    public function createInitial(OrderSummary $orderSummary, Response $response)
    {
        $transaction = new Transaction(Transaction::TYPE_INITIAL_RESPONSE);
        $transaction->setPaymentUniqueId($orderSummary->getPaymentUniqueId());
        $transaction->setBasketSignature($orderSummary->getBasketMapper()->getSignature());
        $transaction->setResponse($response);

        // It is possible that the notification arrived before the initial transaction has been created
        $notify = $this->em->getRepository(Transaction::class)
                           ->findOneBy([
                               'requestId' => $response->getRequestId(),
                               'type'      => Transaction::TYPE_NOTIFY
                           ]);
        if ($notify) {
            $transaction->setPaymentStatus($notify->getPaymentStatus());
        }

        return $this->persist($transaction);
    }

    /**
     * @param InteractionResponse|FormInteractionResponse $response
     *
     * @return Transaction|null
     *
     * @since 1.0.0
     */
    public function createInteraction($response)
    {
        $parentTransaction = $this->em->getRepository(Transaction::class)
                                      ->findOneBy(['requestId' => $response->getRequestId()]);

        $transaction = new Transaction(Transaction::TYPE_INTERACTION);
        if ($parentTransaction) {
            $transaction->setPaymentUniqueId($parentTransaction->getPaymentUniqueId());
            $transaction->setOrderNumber($parentTransaction->getOrderNumber());
        }
        $transaction->setResponse($response);

        return $this->persist($transaction);
    }

    /**
     * @param Transaction $initialTransaction
     * @param Response $response
     * @param string $statusMessage
     *
     * @return Transaction|null
     *
     * @since 1.0.0
     */
    public function createReturn(Transaction $initialTransaction, Response $response, $statusMessage = null)
    {
        $transactions = $this->em->getRepository(Transaction::class)
                                 ->findBy(['paymentUniqueId' => $initialTransaction->getPaymentUniqueId()]);
        foreach ($transactions as $transaction) {
            if ($transaction->getId() !== $initialTransaction->getId()) {
                $transaction->setOrderNumber($initialTransaction->getOrderNumber());
            }
        }

        $transaction = new Transaction(Transaction::TYPE_RETURN);
        $transaction->setPaymentUniqueId($initialTransaction->getPaymentUniqueId());
        $transaction->setOrderNumber($initialTransaction->getOrderNumber());
        $transaction->setResponse($response);
        $transaction->setStatusMessage($statusMessage);

        return $this->persist($transaction);
    }

    /**
     * @param Transaction $initialTransaction
     * @param Response $response
     * @param BackendService $backendService
     *
     * @return Transaction
     *
     * @since 1.0.0
     */
    public function createNotify(Transaction $initialTransaction, Response $response, BackendService $backendService)
    {
        $transaction = new Transaction(Transaction::TYPE_NOTIFY);
        $transaction->setPaymentUniqueId($initialTransaction->getPaymentUniqueId());
        $transaction->setPaymentStatus($initialTransaction->getPaymentStatus());
        $transaction->setOrderNumber($initialTransaction->getOrderNumber());
        $transaction->setResponse($response);

        // POI/PIA: Set status message, if PTRID of initial transaction and notification transaction do not match
        $expectReference = $initialTransaction->getProviderTransactionReference();
        $actualReference = $transaction->getProviderTransactionReference();
        if ($transaction->getPaymentMethod() === PoiPiaTransaction::NAME && $expectReference !== $actualReference) {
            $transaction->setStatusMessage(
                "Provider Transaction Reference ID mismatch: " .
                ($expectReference ? ("Expected '$expectReference', got '$actualReference'") : "'$actualReference'")
            );
        }

        $transaction = $this->persist($transaction);

        $parentTransaction = $this->em->getRepository(Transaction::class)->findOneBy([
            'transactionId' => $transaction->getParentTransactionId(),
            'type'          => Transaction::TYPE_NOTIFY,
        ]);

        if ($parentTransaction && $backendService->isFinal($response->getTransactionType())) {
            $transaction->setState(Transaction::STATE_CLOSED);
            $this->em->flush();
        }

        return $transaction;
    }

    /**
     * Create a backend transaction and set the state of the parent transaction to "closed" if the requested-amount
     * has been reached.
     *
     * @param SuccessResponse $response
     *
     * @return Transaction|null
     * @throws InitialTransactionNotFoundException
     *
     * @since 1.0.0
     */
    public function createBackend(SuccessResponse $response)
    {
        $initialTransaction = $this->getInitialTransaction($response);

        $transaction = new Transaction(Transaction::TYPE_BACKEND);
        $transaction->setPaymentUniqueId($initialTransaction->getPaymentUniqueId());
        $transaction->setOrderNumber($initialTransaction->getOrderNumber());
        $transaction->setResponse($response);
        $transaction = $this->persist($transaction);

        $repo               = $this->em->getRepository(Transaction::class);
        $parentTransactions = $repo->findBy([
            'transactionId' => $transaction->getParentTransactionId(),
            'type'          => Transaction::TYPE_NOTIFY,
        ]);
        if (! count($parentTransactions)) {
            return $transaction;
        }

        $childTransactions = $repo->findBy([
            'parentTransactionId' => $transaction->getParentTransactionId(),
            'type'                => Transaction::TYPE_BACKEND,
        ]);
        foreach ($parentTransactions as $parentTransaction) {
            $totalAmount = (float)$parentTransaction->getAmount();
            foreach ($childTransactions as $childTransaction) {
                $totalAmount -= (float)$childTransaction->getAmount();
            }
            if ($totalAmount <= 0) {
                $parentTransaction->setState(Transaction::STATE_CLOSED);
                $this->em->flush();
            }
        }

        return $transaction;
    }

    /**
     * Calculate remaining amount for backend operations
     *
     * @param Transaction $transaction
     *
     * @return float
     *
     * @since 1.1.0
     */
    public function getRemainingAmount(Transaction $transaction)
    {
        $totalAmount       = (float)$transaction->getAmount();
        $childTransactions = $this->em->getRepository(Transaction::class)->findBy([
            'parentTransactionId' => $transaction->getTransactionId(),
            'type'                => Transaction::TYPE_BACKEND,
        ]);
        foreach ($childTransactions as $childTransaction) {
            $totalAmount -= (float)$childTransaction->getAmount();
        }
        return $totalAmount;
    }

    /**
     * Decide if there is rest amount for payment state definition
     *
     * @param string $restAmount
     * @param string|null $orderNumber
     *
     * @return boolean
     *
     * @since 1.4.0
     */
    public function isRestAmount($restAmount, $orderNumber)
    {
        $isRestAmount = false;
        $childTransactions = $this->em->getRepository(Transaction::class)->findBy([
            'orderNumber' => $orderNumber,
            'type'        => Transaction::TYPE_BACKEND,
        ]);

        if (!empty($childTransactions)) {
            foreach ($childTransactions as $childTransaction) {
                $restAmount += $this->getRestAmount($childTransaction);
            }
            if ($restAmount > 0) {
                $isRestAmount = true;
            }
        }

        return $isRestAmount;
    }

    /**
     * Get rest amount from transaction
     *
     * @param $transaction
     * @return float
     *
     * @since 1.4.0
     */
    private function getRestAmount($transaction)
    {
        $amount = ((float)$transaction->getAmount()) * -1;
        if (in_array($transaction->getTransactionType(), Transaction::TYPES_WITH_REST_AMOUNT)) {
            $amount = (float)$transaction->getAmount();
        }
        return $amount;
    }

    /**
     * Calculate remaining amount for backend operations
     *
     * @param Transaction $transaction
     * @param Basket|null $basket
     *
     * @return array
     *
     * @since 1.1.0
     */
    public function getRemainingBasket(Transaction $transaction, Basket $basket = null)
    {
        if (! $basket) {
            return null;
        }

        $remainingBasket = [];
        /** @var \Wirecard\PaymentSdk\Entity\Item $item */
        foreach ($basket->getIterator() as $item) {
            $remainingBasket[$item->getArticleNumber()] = $item->mappedProperties();
        }

        $childTransactions = $this->em->getRepository(Transaction::class)->findBy([
            'parentTransactionId' => $transaction->getTransactionId(),
            'type'                => Transaction::TYPE_BACKEND,
        ]);
        foreach ($childTransactions as $childTransaction) {
            if (empty($childTransaction->getBasket())) {
                continue;
            }
            foreach ($childTransaction->getBasket() as $articleNumber => $item) {
                if (! isset($remainingBasket[$articleNumber])) {
                    continue;
                }
                $remainingBasket[$articleNumber]['quantity'] -= $item['quantity'];
            }
        }
        return $remainingBasket;
    }

    /**
     * @param Transaction $transaction
     *
     * @return Transaction
     *
     * @since 1.0.0
     */
    private function persist(Transaction $transaction)
    {
        $this->em->persist($transaction);
        $this->em->flush();
        return $transaction;
    }

    /**
     * Try to find notification via paymentUniqueId from initial transaction
     *
     * @param Transaction $initialTransaction
     *
     * @return Transaction|null
     *
     * @since 1.1.0
     */
    public function findNotificationTransaction(Transaction $initialTransaction)
    {
        return $this->em->getRepository(Transaction::class)
                        ->findOneBy([
                            'paymentUniqueId' => $initialTransaction->getPaymentUniqueId(),
                            'type'            => Transaction::TYPE_NOTIFY,
                        ]);
    }

    /**
     * Find and return the initial transaction entity related to the given response.
     *
     * @param SuccessResponse $response
     *
     * @return Transaction
     * @throws InitialTransactionNotFoundException
     *
     * @since 1.0.0
     */
    public function getInitialTransaction(SuccessResponse $response)
    {
        try {
            // first try to get paymentUniqueId from response field 'order-number'
            $paymentUniqueId = $response->findElement('order-number');
        } catch (MalformedResponseException $e) {
            // response doesn't contain 'order-number', try to get paymentUniqueId from custom field 'payment-unique-id'
            $customFields    = $response->getCustomFields();
            $paymentUniqueId = $customFields->get('payment-unique-id');
        }

        if ($paymentUniqueId) {
            $transaction = $this->findParentTransactionBy('paymentUniqueId', $paymentUniqueId);
            if ($transaction && $transaction->isInitial()) {
                return $transaction;
            }
        }

        // still no initial transaction found: try to find it recursively via parent-transaction-id or requestId
        $transaction = $this->findInitialTransaction($response->getParentTransactionId(), $response->getRequestId());
        if (! $transaction) {
            throw new InitialTransactionNotFoundException($response);
        }
        return $transaction;
    }

    /**
     * @param string|null      $parentTransactionId
     * @param string|null      $requestId
     * @param Transaction|null $previousTransaction
     *
     * @return Transaction|null
     *
     * @since 1.1.0 Added $previousTransaction
     * @since 1.0.0
     */
    private function findInitialTransaction($parentTransactionId, $requestId, Transaction $previousTransaction = null)
    {
        if ($parentTransactionId
            && ($transaction = $this->findParentTransactionBy('transactionId', $parentTransactionId))
        ) {
            return $this->returnInitialTransaction($transaction, $previousTransaction);
        }
        if ($requestId && ($transaction = $this->findParentTransactionBy('requestId', $requestId))) {
            return $this->returnInitialTransaction($transaction, $previousTransaction);
        }
        return null;
    }

    /**
     * @param string $criteria
     * @param mixed  $value
     *
     * @return Transaction|null
     *
     * @since 1.1.0
     */
    private function findParentTransactionBy($criteria, $value)
    {
        $repo        = $this->em->getRepository(Transaction::class);
        $transaction = $repo->findOneBy([$criteria => $value, 'type' => Transaction::TYPES_INITIAL]);
        return $transaction ?: $repo->findOneBy([$criteria => $value]);
    }

    /**
     * @param Transaction      $transaction
     * @param Transaction|null $previousTransaction
     *
     * @return Transaction|null
     *
     * @since 1.1.0 Added $previousTransaction
     * @since 1.0.0
     */
    private function returnInitialTransaction(Transaction $transaction, Transaction $previousTransaction = null)
    {
        if (! $transaction->getParentTransactionId() && $transaction->isInitial()) {
            return $transaction;
        }
        // loop detection
        if ($transaction === $previousTransaction) {
            return null;
        }
        return $this->findInitialTransaction(
            $transaction->getParentTransactionId(),
            $transaction->getRequestId(),
            $transaction
        );
    }
}
