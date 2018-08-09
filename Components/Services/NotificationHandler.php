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
     * @param Response       $notification
     * @param BackendService $backendService
     *
     * @return Transaction|null
     * @throws \WirecardElasticEngine\Exception\InitialTransactionNotFoundException
     *
     * @since 1.0.0
     */
    public function handleResponse(\sOrder $shopwareOrder, Response $notification, BackendService $backendService)
    {
        if ($notification instanceof SuccessResponse) {
            $initialTransaction = $this->handleSuccess($shopwareOrder, $notification, $backendService);

            return $this->transactionManager->createNotify($initialTransaction, $notification, $backendService);
        }

        if ($notification instanceof FailureResponse) {
            $this->logger->error("Failure response", $notification->getData());
            return null;
        }

        $this->logger->error("Unexpected notification response", [
            'class'    => get_class($notification),
            'response' => $notification->getData(),
        ]);
        return null;
    }

    /**
     * @param \sOrder         $shopwareOrder
     * @param SuccessResponse $notification
     * @param BackendService  $backendService
     *
     * @return Transaction
     * @throws \WirecardElasticEngine\Exception\InitialTransactionNotFoundException
     *
     * @since 1.0.0
     */
    protected function handleSuccess(
        \sOrder $shopwareOrder,
        SuccessResponse $notification,
        BackendService $backendService
    ) {
        $this->logger->info('Incoming success notification', $notification->getData());

        $paymentStatusId    = $this->getPaymentStatusId($backendService, $notification);
        $initialTransaction = $this->transactionManager->getInitialTransaction($notification);
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
            $this->savePaymentStatus($shopwareOrder, $order, $paymentStatusId);
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
            $this->savePaymentStatus($shopwareOrder, $order, $paymentStatusId);
        }

        return $initialTransaction;
    }

    /**
     * @param \sOrder $shopwareOrder
     * @param Order   $order
     * @param int     $paymentStatusId
     *
     * @since 1.0.0
     */
    private function savePaymentStatus(\sOrder $shopwareOrder, Order $order, $paymentStatusId)
    {
        $shopwareOrder->setPaymentStatus(
            $order->getId(),
            $paymentStatusId,
            self::shouldSendStatusMail($paymentStatusId)
        );
    }

    /**
     * Status mails should be sent if the payment is finalized.
     *
     * @param int $paymentStatusId
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public static function shouldSendStatusMail($paymentStatusId)
    {
        return in_array($paymentStatusId, [
            Status::PAYMENT_STATE_COMPLETELY_PAID,
            Status::PAYMENT_STATE_THE_PROCESS_HAS_BEEN_CANCELLED,
        ]);
    }

    /**
     * @param BackendService $backendService
     * @param Response       $notification
     *
     * @return int
     *
     * @since 1.0.0
     */
    private function getPaymentStatusId($backendService, $notification)
    {
        if ($notification->getTransactionType() === 'check-payer-response') {
            return Status::PAYMENT_STATE_OPEN;
        }
        switch ($backendService->getOrderState($notification->getTransactionType())) {
            case BackendService::TYPE_AUTHORIZED:
                return Status::PAYMENT_STATE_RESERVED;
            case BackendService::TYPE_CANCELLED:
                return Status::PAYMENT_STATE_THE_PROCESS_HAS_BEEN_CANCELLED;
            case BackendService::TYPE_PROCESSING:
                return Status::PAYMENT_STATE_COMPLETELY_PAID;
            case BackendService::TYPE_REFUNDED:
                return Status::PAYMENT_STATE_RE_CREDITING;
            default:
                return Status::PAYMENT_STATE_OPEN;
        }
    }
}
