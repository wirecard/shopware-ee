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
use Wirecard\PaymentSdk\Response\FailureResponse;
use Wirecard\PaymentSdk\Response\Response;
use Wirecard\PaymentSdk\Response\SuccessResponse;
use WirecardShopwareElasticEngine\Exception\OrderNotFoundException;
use WirecardShopwareElasticEngine\Exception\ParentTransactionNotFoundException;
use WirecardShopwareElasticEngine\Models\Transaction;

class NotificationHandler extends Handler
{
    /**
     * @param \sOrder  $shopwareOrder
     * @param Response $notification
     */
    public function execute(\sOrder $shopwareOrder, Response $notification)
    {
        switch (true) {
            case $notification instanceof SuccessResponse:
                return $this->handleSuccess($shopwareOrder, $notification);

            case $notification instanceof FailureResponse:
            default:
                return $this->handleFailure($notification);
        }
    }

    /**
     * @param \sOrder         $shopwareOrder
     * @param SuccessResponse $notification
     */
    protected function handleSuccess(\sOrder $shopwareOrder, SuccessResponse $notification)
    {
        $parentTransactionId = $notification->getParentTransactionId();
        $orderNumber         = $this->getOrderNumberFromResponse($notification);

        $this->logger->info('Incoming notification', $notification->getData());

        $order = $this->em
            ->getRepository(Order::class)
            ->findOneBy([
                'number' => $orderNumber,
            ]);

        if (! $order) {
            $this->logger->error("Order (${orderNumber}) in notification not found", $notification->getData());
            throw new OrderNotFoundException($orderNumber, $notification->getTransactionId());
        }

        $parentTransaction = $this->em
            ->getRepository(Transaction::class)
            ->findOneBy([
                'transactionId' => $parentTransactionId,
            ]);

        if (! $parentTransaction) {
            $this->logger->error("Parent transaction in notification not found", $notification->getData());
            throw new ParentTransactionNotFoundException(
                $notification->getParentTransactionId(),
                $notification->getTransactionId()
            );
        }

        $this->transactionFactory->create($orderNumber, $notification);

        if ($order->getPaymentStatus() !== Status::PAYMENT_STATE_OPEN) {
            return ;
        }

        switch($notification->getTransactionType()) {
            case \Wirecard\PaymentSdk\Transaction\Transaction::TYPE_DEBIT:
            case \Wirecard\PaymentSdk\Transaction\Transaction::TYPE_PURCHASE:
                $orderState = Status::PAYMENT_STATE_COMPLETELY_PAID;
                break;

            case \Wirecard\PaymentSdk\Transaction\Transaction::TYPE_AUTHORIZATION:
                $orderState = Status::PAYMENT_STATE_RESERVED;
                break;

            default:
                $orderState = Status::PAYMENT_STATE_OPEN;
                break;
        }

        $shopwareOrder->setPaymentStatus($order->getId(), $orderState, true);
    }

    /**
     * @param FailureResponse $notification
     */
    protected function handleFailure(FailureResponse $notification)
    {
        $this->logger->error("Failure response", $notification->getData());
    }
}
