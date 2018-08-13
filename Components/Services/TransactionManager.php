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
use Wirecard\PaymentSdk\Exception\MalformedResponseException;
use Wirecard\PaymentSdk\Response\FormInteractionResponse;
use Wirecard\PaymentSdk\Response\InteractionResponse;
use Wirecard\PaymentSdk\Response\Response;
use Wirecard\PaymentSdk\Response\SuccessResponse;
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
     * @param Response    $response
     * @param string      $statusMessage
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
        $transaction = new Transaction(Transaction::TYPE_NOTIFY);
        $transaction->setPaymentUniqueId($initialTransaction->getPaymentUniqueId());
        $transaction->setOrderNumber($initialTransaction->getOrderNumber());
        $transaction->setResponse($response);
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
            'transactionType'     => $transaction->getTransactionType(),
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
            $transaction = $this->em->getRepository(Transaction::class)
                                    ->findOneBy(['paymentUniqueId' => $paymentUniqueId]);
            if ($transaction && $transaction->isInitial()) {
                return $transaction;
            }
        }

        // still no initial transaction found: try to find it recursively via parent-transaction-id and/or requestId
        $transaction = $this->findInitialTransaction($response->getParentTransactionId(), $response->getRequestId());
        if (! $transaction) {
            throw new InitialTransactionNotFoundException($response);
        }
        return $transaction;
    }

    /**
     * @param string|null $parentTransactionId
     * @param string|null $requestId
     *
     * @return Transaction|null
     *
     * @since 1.0.0
     */
    private function findInitialTransaction($parentTransactionId, $requestId)
    {
        $repo = $this->em->getRepository(Transaction::class);
        if ($parentTransactionId) {
            $transaction = $repo->findOneBy(['transactionId' => $parentTransactionId]);
            if ($transaction) {
                return $this->returnInitialTransaction($transaction);
            }
        }
        if (! $requestId || ! ($transaction = $repo->findOneBy(['requestId' => $requestId]))) {
            return null;
        }
        return $this->returnInitialTransaction($transaction);
    }

    /**
     * @param Transaction $transaction
     *
     * @return Transaction|null
     *
     * @since 1.0.0
     */
    private function returnInitialTransaction(Transaction $transaction)
    {
        if (! $transaction->getParentTransactionId() && $transaction->isInitial()) {
            return $transaction;
        }
        return $this->findInitialTransaction($transaction->getParentTransactionId(), $transaction->getRequestId());
    }
}
