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

use Wirecard\PaymentSdk\Response\FailureResponse;
use Wirecard\PaymentSdk\Response\FormInteractionResponse;
use Wirecard\PaymentSdk\Response\InteractionResponse;
use Wirecard\PaymentSdk\Response\Response;
use Wirecard\PaymentSdk\Response\SuccessResponse;
use Wirecard\PaymentSdk\TransactionService;
use WirecardShopwareElasticEngine\Components\Actions\Action;
use WirecardShopwareElasticEngine\Components\Actions\ErrorAction;
use WirecardShopwareElasticEngine\Components\Actions\RedirectAction;
use WirecardShopwareElasticEngine\Components\Actions\ViewAction;
use WirecardShopwareElasticEngine\Components\Payments\Payment;
use WirecardShopwareElasticEngine\Models\Transaction;

class ReturnHandler extends Handler
{
    /**
     * @param Payment                             $payment
     * @param TransactionService                  $transactionService
     * @param \Enlight_Controller_Request_Request $request
     *
     * @return Response
     */
    public function execute(
        Payment $payment,
        TransactionService $transactionService,
        \Enlight_Controller_Request_Request $request
    ) {
        $response = $payment->processReturn($transactionService, $request);

        if (! $response) {
            $response = $transactionService->handleResponse($request->getParams());
        }

        return $response;
    }

    /**
     * @param Response $response
     *
     * @return Action|ViewAction
     */
    public function handleResponse(Response $response)
    {
        if ($response instanceof FormInteractionResponse) {
            return $this->handleFormInteraction($response);
        }
        if ($response instanceof InteractionResponse) {
            return $this->handleInteraction($response);
        }
        return $this->handleFailure($response);
    }

    /**
     * @param FormInteractionResponse $response
     *
     * @return ViewAction
     */
    protected function handleFormInteraction(FormInteractionResponse $response)
    {
        $this->transactionManager->createInteraction($response);

        return new ViewAction('credit_card.tpl', [
            'threeDSecure' => true,
            'method'       => $response->getMethod(),
            'formFields'   => $response->getFormFields(),
            'url'          => $response->getUrl(),
        ]);
    }

    /**
     * @param SuccessResponse $response
     * @param Transaction     $initialTransaction
     *
     * @return Action
     */
    public function handleSuccess(SuccessResponse $response, Transaction $initialTransaction)
    {
        $this->sessionHandler->destroyDeviceFingerprintId();
        $this->transactionManager->createReturn($initialTransaction, $response);

        // `sUniqueID` should match the order temporaryId/paymentUniqueId to show proper information after redirect.
        return new RedirectAction($this->router->assemble([
            'module'     => 'frontend',
            'controller' => 'checkout',
            'action'     => 'finish',
            'sUniqueID'  => $initialTransaction->getPaymentUniqueId(),
        ]));
    }

    /**
     * @param InteractionResponse $response
     *
     * @return Action
     */
    protected function handleInteraction(InteractionResponse $response)
    {
        $this->transactionManager->createInteraction($response);

        return new RedirectAction($response->getRedirectUrl());
    }

    /**
     * @param FailureResponse|Response|mixed $response
     *
     * @return Action
     */
    protected function handleFailure($response)
    {
        $message = 'Unexpected response';
        $context = get_class($response);

        if ($response instanceof FailureResponse) {
            $message = 'Failure response';
        }
        if ($response instanceof Response) {
            $context = $response->getData();
        }

        $this->logger->error('Return handling failed: ' . $message, $context);
        return new ErrorAction(ErrorAction::FAILURE_RESPONSE, $message);
    }
}
