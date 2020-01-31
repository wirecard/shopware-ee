<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

namespace WirecardElasticEngine\Components\Services;

use Shopware\Models\Order\Order;
use Shopware\Models\Order\Status;
use Wirecard\PaymentSdk\BackendService;
use Wirecard\PaymentSdk\Response\FailureResponse;
use Wirecard\PaymentSdk\Response\Response;
use Wirecard\PaymentSdk\Response\SuccessResponse;
use Wirecard\PaymentSdk\Transaction\PoiPiaTransaction;
use WirecardElasticEngine\Exception\InitialTransactionNotFoundException;
use WirecardElasticEngine\Models\Transaction;

/**
 * Handles notification responses. Notification responses are server-to-server, meaning you must NEVER access session
 * data in here.
 * Additionally notifications are the "source of truth", hence they are responsible for setting - respectively
 * updating - the payment status.
 *
 * @package WirecardElasticEngine\Components\Services
 *
 * @since   1.0.0
 */
class NotificationHandler extends Handler
{
    /**
     * Handles a notification response.
     *
     * @param \sOrder        $shopwareOrder
     * @param Response       $response
     * @param BackendService $backendService
     *
     * @return Transaction|null
     * @throws \WirecardElasticEngine\Exception\InitialTransactionNotFoundException
     * @since 1.0.0
     */
    public function handleResponse(\sOrder $shopwareOrder, Response $response, BackendService $backendService)
    {
        if ($response instanceof SuccessResponse) {
            $initialTransaction = $this->handleSuccess($shopwareOrder, $response, $backendService);

            return $this->transactionManager->createNotify($initialTransaction, $response, $backendService);
        }

        if ($response instanceof FailureResponse) {
            $this->logger->error("Failure response", $response->getData());
            return null;
        }

        $this->logger->error("Unexpected notification response", [
            'class'    => get_class($response),
            'response' => $response->getData(),
        ]);
        return null;
    }

    /**
     * @param \sOrder         $shopwareOrder
     * @param SuccessResponse $response
     * @param BackendService  $backendService
     *
     * @return Transaction
     * @since 1.0.0
     */
    protected function handleSuccess(
        \sOrder $shopwareOrder,
        SuccessResponse $response,
        BackendService $backendService
    ) {
        $this->logger->info('Incoming success notification', $response->getData());
        $transactionType = $response->getTransactionType();
        $paymentStatusId = $this->getPaymentStatusId($backendService, $response);
        try {
            $initialTransaction = $this->transactionManager->getInitialTransaction($response);
        } catch (InitialTransactionNotFoundException $exception) {
            // POI/PIA: initial transaction not found: create unassigned notify transaction
            if ($response->getPaymentMethod() === PoiPiaTransaction::NAME) {
                $this->logger->info(
                    "No matching transaction for " . PoiPiaTransaction::NAME
                    . " payment with PTRID '{$response->getProviderTransactionReference()}' found"
                );
            }
            $initialTransaction = new Transaction(Transaction::TYPE_INITIAL_RESPONSE);
            $initialTransaction->setPaymentStatus($paymentStatusId);
            $this->logger->info("Notification arrived before initial transaction");
            return $initialTransaction;
        }

        // POI/PIA: Set payment status "review necessary", if PTRID of initial transaction and notification do not match
        if ($response->getPaymentMethod() === PoiPiaTransaction::NAME
            && $response->getProviderTransactionReference() !== $initialTransaction->getProviderTransactionReference()
        ) {
            $paymentStatusId = Status::PAYMENT_STATE_REVIEW_NECESSARY;
        }

        if ($paymentStatusId === Status::PAYMENT_STATE_OPEN) {
            return $initialTransaction;
        }
        $initialTransaction->setPaymentStatus($paymentStatusId);
        $this->em->flush();

        /** @var Order $order */
        $order = $this->em->getRepository(Order::class)->findOneBy([
            'temporaryId' => $initialTransaction->getPaymentUniqueId(),
        ]);

        // if we already have an order, we can update the payment status directly
        if ($order) {
            $this->logger->debug("Order {$order->getNumber()} already exists, update payment status $paymentStatusId");
            $this->savePaymentStatus($shopwareOrder, $order, $paymentStatusId, $transactionType);
            if (! $initialTransaction->getOrderNumber() && $order->getNumber()) {
                $initialTransaction->setOrderNumber($order->getNumber());
            }
            return $initialTransaction;
        }

        // otherwise, lets save the payment status to the initial transaction (see returnAction)
        $this->em->refresh($initialTransaction);

        // check again if order exists and try to update payment status
        $order = $this->em->getRepository(Order::class)->findOneBy([
            'temporaryId' => $initialTransaction->getPaymentUniqueId(),
        ]);
        if ($order) {
            $this->logger->debug("Order {$order->getNumber()} found, update payment status $paymentStatusId");
            $this->savePaymentStatus($shopwareOrder, $order, $paymentStatusId, $transactionType);
        }

        return $initialTransaction;
    }

    /**
     * @param \sOrder $shopwareOrder
     * @param Order $order
     * @param int $paymentStatusId
     *
     * @param $transactionType
     *
     * @since 1.0.0
     */
    private function savePaymentStatus(\sOrder $shopwareOrder, Order $order, $paymentStatusId, $transactionType)
    {
        $shopwareOrder->setPaymentStatus(
            $order->getId(),
            $paymentStatusId,
            self::shouldSendStatusMail($paymentStatusId, $transactionType)
        );
    }

    /**
     * Status mails should be sent if the payment is finalized.
     *
     * @param int $paymentStatusId
     *
     * @param string|null $transactionType
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public static function shouldSendStatusMail($paymentStatusId, $transactionType = null)
    {
        $paymentStatuses = [
            Status::PAYMENT_STATE_COMPLETELY_PAID,
            Status::PAYMENT_STATE_THE_PROCESS_HAS_BEEN_CANCELLED,
        ];
        return (in_array($paymentStatusId, $paymentStatuses))&&
               (!in_array($transactionType, Transaction::TYPES_EMAIL_BLOCK));
    }

    /**
     * @param BackendService $backendService
     * @param Response $response
     *
     * @return int
     *
     * @since 1.0.0
     */
    private function getPaymentStatusId($backendService, $response)
    {
        $transaction = $this->transactionManager->getInitialTransaction($response);
        $transactionAmount = $transaction->getAmount();
        $isRestAmount = $this->transactionManager->isRestAmount(
            $transactionAmount,
            $transaction->getOrderNumber()
        );
        if ($response->getTransactionType() === Transaction::TYPE_CHECK_PAYER_RESPONSE) {
            return Status::PAYMENT_STATE_OPEN;
        }
        switch ($backendService->getOrderState($response->getTransactionType())) {
            case BackendService::TYPE_AUTHORIZED:
                return Status::PAYMENT_STATE_RESERVED;
            case BackendService::TYPE_CANCELLED:
                return Status::PAYMENT_STATE_THE_PROCESS_HAS_BEEN_CANCELLED;
            case BackendService::TYPE_PROCESSING:
                if ($isRestAmount) {
                    return Status::PAYMENT_STATE_PARTIALLY_PAID;
                } else {
                    return Status::PAYMENT_STATE_COMPLETELY_PAID;
                }
            case BackendService::TYPE_REFUNDED:
                return Status::PAYMENT_STATE_RE_CREDITING;
            default:
                return Status::PAYMENT_STATE_OPEN;
        }
    }
}
