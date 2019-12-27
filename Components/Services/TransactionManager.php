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
     * @param Response     $response
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
            ->findOneBy(['requestId' => $response->getRequestId(), 'type' => Transaction::TYPE_NOTIFY]);
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
        $initialTransaction = $this->em->getRepository(Transaction::class)
            ->findOneBy(['requestId' => $response->getRequestId()]);

        return $this->updateTransaction($initialTransaction, $response, Transaction::TYPE_INTERACTION);
    }

    /**
     * @param Transaction $initialTransaction
     * @param Response    $response
     * @param string      $statusMessage
     *
     * @return Transaction|null
     *
     * @since 1.0.0
     */
    public function createReturn(Transaction $initialTransaction, Response $response, $statusMessage = null)
    {
        if ($initialTransaction->getType() === Transaction::TYPE_NOTIFY) {
            return $initialTransaction;
        }

        return $this->updateTransaction(
            $initialTransaction,
            $response,
            Transaction::TYPE_RETURN,
            $statusMessage
        );
    }

    /**
     * @param Transaction    $initialTransaction
     * @param Response       $response
     * @param BackendService $backendService
     *
     * @return Transaction
     *
     * @since 1.0.0
     */
    public function createNotify(Transaction $initialTransaction, Response $response, BackendService $backendService)
    {
        // Update backend transaction with notification - if exists
        $backendTransaction = $this->updateBackendOnNotify($initialTransaction, $response, $backendService);
        if ($backendTransaction) {
            return $backendTransaction;
        }

        // Update initial notification
        $initialTransaction = $this->updateTransaction($initialTransaction, $response, Transaction::TYPE_NOTIFY);

        // POI/PIA: Set status message, if PTRID of initial transaction and notification transaction do not match
        $expectReference = $initialTransaction->getProviderTransactionReference();
        $actualReference = $initialTransaction->getProviderTransactionReference();
        if ($initialTransaction->getPaymentMethod() === PoiPiaTransaction::NAME &&
            $expectReference !== $actualReference) {
            $initialTransaction->setStatusMessage(
                "Provider Transaction Reference ID mismatch: " .
                ($expectReference ? ("Expected '$expectReference', got '$actualReference'") : "'$actualReference'")
            );
        }
        $this->em->flush();

        $parentTransaction = $this->em->getRepository(Transaction::class)->findOneBy([
            'transactionId' => $initialTransaction->getParentTransactionId(),
            'type'          => Transaction::TYPE_NOTIFY,
        ]);

        if ($parentTransaction && $backendService->isFinal($response->getTransactionType())) {
            $initialTransaction->setState(Transaction::STATE_CLOSED);
            $this->em->flush();
        }

        return $initialTransaction;
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
            'orderNumber'         => $transaction->getOrderNumber(),
        ]);
        foreach ($childTransactions as $childTransaction) {
            if ($childTransaction !== $transaction) {
                $totalAmount -= (float)$childTransaction->getAmount();
            }
        }
        return $totalAmount;
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
     * @param Transaction $initialTransaction
     * @return array
     */
    private function getTransactions(Transaction $initialTransaction)
    {
        return (array)$this->em->getRepository(Transaction::class)
            ->findBy(['paymentUniqueId' => $initialTransaction->getPaymentUniqueId()]);
    }

    /**
     * @param Transaction $transaction
     * @param Response $response
     * @param $type
     * @param null $statusMessage
     * @return Transaction
     */
    private function updateTransaction(Transaction $transaction, Response $response, $type, $statusMessage = null)
    {
        $transaction->setType($type);
        $transaction->setResponse($response);
        $transaction->setUpdatedAt(new \DateTime());
        if ($statusMessage) {
            $transaction->setStatusMessage($statusMessage);
        }
        $this->em->flush();

        return $transaction;
    }

    /**
     * @param $initialTransaction
     * @param $response
     * @param $backendService
     * @return bool|Transaction
     * @throws \Exception
     */
    private function updateBackendOnNotify($initialTransaction, $response, $backendService)
    {
        // Get all transactions with the same payment unique id
        $transactions = $this->getTransactions($initialTransaction);
        foreach ($transactions as $transaction) {
            // Update if is not initial transaction and backend already exists
            if ($transaction !== $initialTransaction && $transaction->getType() === Transaction::TYPE_BACKEND) {
                if ($backendService->isFinal($response->getTransactionType())) {
                    $transaction->setState(Transaction::STATE_CLOSED);
                }
                $transaction->setType(Transaction::TYPE_NOTIFY);
                $transaction->setResponse($response);
                $transaction->setUpdatedAt(new \DateTime());
                $this->em->flush();
                return $transaction;
            }
        }
        return false;
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
            if ($transaction) {
                return $transaction;
            }
        } else {
            $transaction = $this->em->getRepository(Transaction::class)
                ->findOneBy(['requestId' => $response->getRequestId()]);
            return $transaction;
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
