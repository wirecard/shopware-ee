<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

namespace WirecardElasticEngine\Components\Services;

use Shopware\Models\Shop\Shop;
use Wirecard\PaymentSdk\Entity\CustomField;
use Wirecard\PaymentSdk\Entity\CustomFieldCollection;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Response\FailureResponse;
use Wirecard\PaymentSdk\Response\FormInteractionResponse;
use Wirecard\PaymentSdk\Response\InteractionResponse;
use Wirecard\PaymentSdk\Response\SuccessResponse;
use Wirecard\PaymentSdk\TransactionService;
use WirecardElasticEngine\Components\Actions\Action;
use WirecardElasticEngine\Components\Actions\ErrorAction;
use WirecardElasticEngine\Components\Actions\RedirectAction;
use WirecardElasticEngine\Components\Actions\ViewAction;
use WirecardElasticEngine\Components\Data\OrderSummary;
use WirecardElasticEngine\Components\Payments\Contracts\ProcessPaymentInterface;
use WirecardElasticEngine\Exception\ArrayKeyNotFoundException;

/**
 * Responsible for handling the payment. Payments may implement their own way of handling payments by implementing
 * the `ProcessPaymentInterface` interface.
 * Ultimately a proper `Action` is returned to the controller.
 *
 * @package WirecardElasticEngine\Components\Services
 *
 * @since   1.0.0
 */
class PaymentHandler extends Handler
{
    /**
     * Executes the payment process.
     *
     * @param OrderSummary                        $orderSummary
     * @param TransactionService                  $transactionService
     * @param Redirect                            $redirect
     * @param string                              $notificationUrl
     * @param \Enlight_Controller_Request_Request $request
     * @param \sOrder                             $shopwareOrder
     *
     * @return Action
     * @throws ArrayKeyNotFoundException
     *
     * @since 1.0.0
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

        $payment = $orderSummary->getPayment();

        try {
            if ($payment instanceof ProcessPaymentInterface) {
                $action = $payment->processPayment(
                    $orderSummary,
                    $transactionService,
                    Shopware()->Shop(),
                    $redirect,
                    $request,
                    $shopwareOrder
                );

                if ($action) {
                    return $action;
                }
            }

            $response = $transactionService->process(
                $payment->getTransaction(),
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
            $this->transactionManager->createInitial($orderSummary, $response);
            return new ViewAction('payment_redirect.tpl', [
                'wirecardUrl' => $orderSummary->getPayment()->getPaymentConfig()->getBaseUrl(),
                'method'      => $response->getMethod(),
                'formFields'  => $response->getFormFields(),
                'url'         => $response->getUrl(),
            ]);
        }
        if ($response instanceof SuccessResponse || $response instanceof InteractionResponse) {
            $this->transactionManager->createInitial($orderSummary, $response);
            return new RedirectAction($response->getRedirectUrl());
        }
        if ($response instanceof FailureResponse) {
            $this->logger->error('Failure response', $response->getData());
            return new ErrorAction(ErrorAction::FAILURE_RESPONSE, 'Failure response');
        }
        return new ErrorAction(ErrorAction::PROCESSING_FAILED, 'Payment processing failed');
    }

    /**
     * Prepares the transaction for being sent to Wirecard by adding specific (e.g. amount) and optional (e.g. fraud
     * prevention data) data to the `Transaction` object of the payment.
     * Keep in mind that the transaction returned by the payment is ALWAYS the same instance, hence we don't need to
     * return the transaction here.
     *
     * @param OrderSummary $orderSummary
     * @param Redirect     $redirect
     * @param string       $notificationUrl
     *
     * @throws ArrayKeyNotFoundException
     *
     * @since 1.0.0
     */
    private function prepareTransaction(OrderSummary $orderSummary, Redirect $redirect, $notificationUrl)
    {
        $payment       = $orderSummary->getPayment();
        $paymentConfig = $payment->getPaymentConfig();
        $transaction   = $payment->getTransaction();

        $transaction->setRedirect($redirect);
        $transaction->setAmount($orderSummary->getAmount());
        $transaction->setNotificationUrl($notificationUrl);
        $transaction->setOrderNumber($orderSummary->getPaymentUniqueId());

        $customFields = new CustomFieldCollection();
        $customFields->add(new CustomField('payment-unique-id', $orderSummary->getPaymentUniqueId()));
        $transaction->setCustomFields($customFields);

        if ($paymentConfig->sendBasket() || $paymentConfig->hasFraudPrevention()) {
            $transaction->setBasket($orderSummary->getBasketMapper()->getWirecardBasket());
        }

        if ($paymentConfig->hasFraudPrevention()) {
            $transaction->setIpAddress($orderSummary->getUserMapper()->getClientIp());
            $transaction->setConsumerId($orderSummary->getUserMapper()->getCustomerNumber());
            $transaction->setAccountHolder($orderSummary->getUserMapper()->getWirecardBillingAccountHolder());
            $transaction->setShipping($orderSummary->getUserMapper()->getWirecardShippingAccountHolder());
            $transaction->setLocale($orderSummary->getUserMapper()->getLocale());
            $transaction->setDevice($orderSummary->getWirecardDevice());
        }

        if ($paymentConfig->sendDescriptor()) {
            $transaction->setDescriptor($this->getDescriptor($orderSummary->getPaymentUniqueId()));
        }
    }

    /**
     * Returns the descriptor sent to Wirecard. Change to your own needs.
     *
     * @param $orderNumber
     *
     * @return string
     *
     * @since 1.0.0
     */
    protected function getDescriptor($orderNumber)
    {
        $shopName = substr($this->shopwareConfig->get('shopName'), 0, 9);
        return substr($shopName . ' ' . $orderNumber, 0, 20);
    }
}
