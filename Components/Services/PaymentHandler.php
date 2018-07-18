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
use Shopware\Models\Shop\Shop;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Response\FailureResponse;
use Wirecard\PaymentSdk\Response\FormInteractionResponse;
use Wirecard\PaymentSdk\Response\InteractionResponse;
use Wirecard\PaymentSdk\Response\SuccessResponse;
use Wirecard\PaymentSdk\TransactionService;
use WirecardShopwareElasticEngine\Components\Actions\Action;
use WirecardShopwareElasticEngine\Components\Actions\ErrorAction;
use WirecardShopwareElasticEngine\Components\Actions\RedirectAction;
use WirecardShopwareElasticEngine\Components\Actions\ViewAction;
use WirecardShopwareElasticEngine\Components\Data\OrderSummary;
use WirecardShopwareElasticEngine\Exception\ArrayKeyNotFoundException;
use WirecardShopwareElasticEngine\Exception\OrderNotFoundException;
use WirecardShopwareElasticEngine\Models\Transaction;

class PaymentHandler extends Handler
{
    /**
     * @param OrderSummary                        $orderSummary
     * @param TransactionService                  $transactionService
     * @param Redirect                            $redirect
     * @param string                              $notificationUrl
     * @param \Enlight_Controller_Request_Request $request
     * @param \sOrder                             $shopwareOrder
     *
     * @return Action
     * @throws ArrayKeyNotFoundException
     * @throws OrderNotFoundException
     */
    public function execute(
        OrderSummary $orderSummary,
        TransactionService $transactionService,
        Redirect $redirect,
        $notificationUrl,
        \Enlight_Controller_Request_Request $request,
        \sOrder $shopwareOrder
    ) {
        $this->prepareTransaction($orderSummary, $redirect, $notificationUrl);

        $payment     = $orderSummary->getPayment();
        $transaction = $payment->getTransaction();

        try {
            $action = $payment->processPayment(
                $orderSummary,
                $transactionService,
                $this->em->getRepository(Shop::class)->getActiveDefault(),
                $redirect,
                $request,
                $shopwareOrder
            );

            if ($action !== null) {
                return $action;
            }

            $response = $transactionService->process(
                $transaction,
                $payment->getPaymentConfig()->getTransactionOperation()
            );
        } catch (\Exception $e) {
            $this->logger->error('Transaction service process failed: ' . $e->getMessage());
            return new ErrorAction(ErrorAction::PROCESSING_FAILED, 'Transaction processing failed');
        }

        $this->logger->debug('Payment processing execution', [
            'summary'  => $orderSummary->toArray(),
            'response' => $response->getData(),
        ]);

        if ($response instanceof FormInteractionResponse) {
            return new ViewAction('payment_redirect.tpl', [
                'method'       => $response->getMethod(),
                'formFields'   => $response->getFormFields(),
                'url'          => $response->getUrl(),
            ]);
        }

        switch (true) {
            case $response instanceof SuccessResponse:
            case $response instanceof InteractionResponse:
                $this->updateOrder($response->getTransactionId(), $orderSummary);
                $this->transactionFactory->create(
                    $orderSummary->getOrderNumber(),
                    $response,
                    Transaction::TYPE_INITIAL
                );

                return new RedirectAction($response->getRedirectUrl());

            case $response instanceof FailureResponse:
                $this->logger->error('Failure response', $response->getData());

                return new ErrorAction(ErrorAction::FAILURE_RESPONSE, 'Failure response');

            default:
                $this->logger->error('Processing failed', $response->getData());

                return new ErrorAction(ErrorAction::PROCESSING_FAILED, 'Payment processing failed');
        }
    }

    /**
     * Updates the shopware order by setting the proper transaction ID.
     *
     * @param string       $transactionId
     * @param OrderSummary $orderSummary
     *
     * @throws OrderNotFoundException
     */
    private function updateOrder($transactionId, OrderSummary $orderSummary)
    {
        $order = $this->em->getRepository(Order::class)
                          ->findOneBy([
                              'number' => $orderSummary->getOrderNumber(),
                          ]);

        if (! $order) {
            throw new OrderNotFoundException($orderSummary->getOrderNumber(), $transactionId);
        }

        $order->setTransactionId($transactionId);

        $this->em->flush();
    }

    /**
     * Prepares the transaction for being sent to Wirecard by adding specific (e.g. amount) and optional (e.g. fraud
     * prevention data) data to the `Transaction` object of the payment.
     *
     * @param OrderSummary $orderSummary
     * @param Redirect     $redirect
     * @param string       $notificationUrl
     *
     * @throws ArrayKeyNotFoundException
     */
    private function prepareTransaction(OrderSummary $orderSummary, Redirect $redirect, $notificationUrl)
    {
        $payment       = $orderSummary->getPayment();
        $paymentConfig = $payment->getPaymentConfig();
        $transaction   = $payment->getTransaction();
        $orderNumber   = $this->getOrderNumberForTransaction($orderSummary->getOrderNumber());

        $transaction->setRedirect($redirect);
        $transaction->setAmount($orderSummary->getAmount());
        $transaction->setNotificationUrl($notificationUrl);
        $transaction->setOrderNumber($orderNumber);

        if ($paymentConfig->sendBasket()) {
            $transaction->setBasket($orderSummary->getBasketMapper()->getWirecardBasket());
        }

        if ($paymentConfig->hasFraudPrevention()) {
            $transaction->setIpAddress($orderSummary->getUserMapper()->getClientIp());
            $transaction->setAccountHolder($orderSummary->getUserMapper()->getWirecardBillingAccountHolder());
            $transaction->setShipping($orderSummary->getUserMapper()->getWirecardShippingAccountHolder());
            $transaction->setLocale($orderSummary->getUserMapper()->getLocale());
        }

        if ($paymentConfig->sendDescriptor()) {
            $transaction->setDescriptor($this->getDescriptor($orderNumber));
        }
    }

    /**
     * Returns the descriptor sent to Wirecard. Change to your own needs.
     *
     * @param $orderNumber
     *
     * @return string
     */
    protected function getDescriptor($orderNumber)
    {
        $shopName = substr($this->shopwareConfig->get('shopName'), 0, 9);
        return "${shopName} ${orderNumber}";
    }
}
