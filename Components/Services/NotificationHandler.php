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

use Shopware\Models\Order\Order;
use Shopware\Models\Order\Status;
use Wirecard\PaymentSdk\BackendService;
use Wirecard\PaymentSdk\Response\FailureResponse;
use Wirecard\PaymentSdk\Response\Response;
use Wirecard\PaymentSdk\Response\SuccessResponse;
use WirecardShopwareElasticEngine\Models\Transaction;

/**
 * Handles notification responses. Notification responses are server-to-server, meaning you must NEVER access session
 * data in here.
 * Additionally notifications are the "source of truth", hence they are responsible for setting - respectively
 * updating - the payment status.
 *
 * @package WirecardShopwareElasticEngine\Components\Services
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
     * @return bool
     * @throws \WirecardShopwareElasticEngine\Exception\InitialTransactionNotFoundException
     *
     * @since 1.0.0
     */
    public function handleResponse(\sOrder $shopwareOrder, Response $notification, BackendService $backendService)
    {
        if ($notification instanceof SuccessResponse) {
            $initialTransaction = $this->handleSuccess($shopwareOrder, $notification, $backendService);

            $this->transactionManager->createNotify($initialTransaction, $notification, $backendService);

            return true;
        }

        if ($notification instanceof FailureResponse) {
            $this->logger->error("Failure response", $notification->getData());
            return false;
        }

        $this->logger->error("Unexpected notification response", [
            'class'    => get_class($notification),
            'response' => $notification->getData(),
        ]);
        return false;
    }

    /**
     * @param \sOrder         $shopwareOrder
     * @param SuccessResponse $notification
     * @param BackendService  $backendService
     *
     * @return Transaction
     * @throws \WirecardShopwareElasticEngine\Exception\InitialTransactionNotFoundException
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
        $this->logger->debug("NotificationHandler::handleSuccess: flushed initial transaction " .
                             "{$initialTransaction->getId()} with payment status $paymentStatusId");

        /** @var Order $order */
        $order = $this->em->getRepository(Order::class)->findOneBy([
            'temporaryId' => $initialTransaction->getPaymentUniqueId(),
        ]);

        // if we already have an order, we can update the payment status directly
        if ($order) {
            $this->logger->debug('NotificationHandler::handleSuccess: order found, save payment status '
                                 . $paymentStatusId);
            $this->savePaymentStatus($shopwareOrder, $order, $paymentStatusId);
            return $initialTransaction;
        }
        $this->logger->debug('NotificationHandler::handleSuccess: no order');

        // otherwise, lets save the payment status to the initial transaction (see returnAction)
        $this->em->refresh($initialTransaction);
        $this->logger->debug('NotificationHandler::handleSuccess: refreshed initial transaction');

        // check again if order exists and try to update payment status
        $order = $this->em->getRepository(Order::class)->findOneBy([
            'temporaryId' => $initialTransaction->getPaymentUniqueId(),
        ]);
        $this->logger->debug('NotificationHandler::handleSuccess: order: ' . ($order ? ' found' : ' not found'));
        if ($order) {
            $this->savePaymentStatus($shopwareOrder, $order, $paymentStatusId);
        }

        $this->logger->debug('NotificationHandler::handleSuccess: finished');
        return $initialTransaction;
    }

    /**
     * @param \sOrder $shopwareOrder
     * @param Order   $order
     * @param int     $paymentStatusId
     */
    private function savePaymentStatus(\sOrder $shopwareOrder, Order $order, $paymentStatusId)
    {
        $shopwareOrder->setPaymentStatus($order->getId(), $paymentStatusId, false);
        return;
    }

    /**
     * @param BackendService $backendService
     * @param Response       $notification
     *
     * @return int
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
