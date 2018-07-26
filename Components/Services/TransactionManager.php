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

namespace WirecardShopwareElasticEngine\Components\Services;

use Doctrine\ORM\EntityManagerInterface;
use Wirecard\PaymentSdk\BackendService;
use Wirecard\PaymentSdk\Exception\MalformedResponseException;
use Wirecard\PaymentSdk\Response\FormInteractionResponse;
use Wirecard\PaymentSdk\Response\InteractionResponse;
use Wirecard\PaymentSdk\Response\Response;
use Wirecard\PaymentSdk\Response\SuccessResponse;
use WirecardShopwareElasticEngine\Components\Data\OrderSummary;
use WirecardShopwareElasticEngine\Exception\InitialTransactionNotFoundException;
use WirecardShopwareElasticEngine\Models\Transaction;

/**
 * @package WirecardShopwareElasticEngine\Components\Services
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
     *
     * @return Transaction|null
     *
     * @since 1.0.0
     */
    public function createReturn(Transaction $initialTransaction, Response $response)
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

        $repo              = $this->em->getRepository(Transaction::class);
        $parentTransaction = $repo->findOneBy([
            'transactionId' => $transaction->getParentTransactionId(),
            'type'          => Transaction::TYPE_NOTIFY,
        ]);

        if (! $parentTransaction) {
            return $transaction;
        }

        $childTransactions = $repo->findBy([
            'parentTransactionId' => $transaction->getParentTransactionId(),
            'transactionType'     => $transaction->getTransactionType(),
        ]);

        $totalAmount = (float)$parentTransaction->getAmount();

        foreach ($childTransactions as $childTransaction) {
            $totalAmount -= (float)$childTransaction->getAmount();
        }

        if ($totalAmount <= 0) {
            $parentTransaction->setState(Transaction::STATE_CLOSED);
            $this->em->flush();
        }
        return $transaction;
    }

    /**
     * @param Transaction $transaction
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
