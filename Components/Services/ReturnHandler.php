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
use Wirecard\PaymentSdk\Response\SuccessResponse;
use Wirecard\PaymentSdk\TransactionService;
use WirecardShopwareElasticEngine\Components\Actions\Action;
use WirecardShopwareElasticEngine\Components\Actions\ErrorAction;
use WirecardShopwareElasticEngine\Components\Actions\RedirectAction;
use WirecardShopwareElasticEngine\Components\Actions\ViewAction;
use WirecardShopwareElasticEngine\Components\Payments\Payment;
use WirecardShopwareElasticEngine\Exception\OrderNotFoundException;
use WirecardShopwareElasticEngine\Models\Transaction;

class ReturnHandler extends Handler
{
    /**
     * @param Payment                             $payment
     * @param TransactionService                  $transactionService
     * @param \Enlight_Controller_Request_Request $request
     *
     * @return Action
     * @throws OrderNotFoundException
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

        switch (true) {
            case $response instanceof FormInteractionResponse:
                return $this->handleFormInteraction($response);

            case $response instanceof SuccessResponse:
                return $this->handleSuccess($response);

            case $response instanceof FailureResponse:
            default:
                return $this->handleFailure($response);
        }
    }

    /**
     * @param FormInteractionResponse $response
     *
     * @return ViewAction
     */
    protected function handleFormInteraction(FormInteractionResponse $response)
    {
        try {
            $order = $this->getOrderFromResponse($response);
            $this->transactionFactory->create($order->getNumber(), $response, Transaction::TYPE_RETURN);
        } catch (OrderNotFoundException $e) {
            $this->logger->notice('Could not create transaction for FormInteractionResponse: ' . $e->getMessage());
        }

        return new ViewAction('credit_card.tpl', [
            'threeDSecure' => true,
            'method'       => $response->getMethod(),
            'formFields'   => $response->getFormFields(),
            'url'          => $response->getUrl(),
        ]);
    }

    /**
     * @param SuccessResponse $response
     *
     * @return Action
     * @throws OrderNotFoundException
     */
    protected function handleSuccess(SuccessResponse $response)
    {
        $order = $this->getOrderFromResponse($response);

        // TemporaryID is set to the order number, since the returned `RedirectAction` will contain this ID
        // as `sUniqueID` to get information about what order has been processed and show proper information.
        $order->setTemporaryId($order->getNumber());

        $this->transactionFactory->create($order->getNumber(), $response, Transaction::TYPE_RETURN);

        return new RedirectAction($this->router->assemble([
            'module'     => 'frontend',
            'controller' => 'checkout',
            'action'     => 'finish',
            'sUniqueID'  => $order->getTemporaryId(),
        ]));
    }

    /**
     * @param FailureResponse $response
     *
     * @return Action
     */
    protected function handleFailure(FailureResponse $response)
    {
        $this->logger->error('Return handling failed', $response->getData());

        return new ErrorAction(ErrorAction::FAILURE_RESPONSE, 'Failure response');
    }
}
