<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

namespace WirecardElasticEngine\Components\Services;

use Wirecard\PaymentSdk\Response\FailureResponse;
use Wirecard\PaymentSdk\Response\FormInteractionResponse;
use Wirecard\PaymentSdk\Response\InteractionResponse;
use Wirecard\PaymentSdk\Response\Response;
use Wirecard\PaymentSdk\Response\SuccessResponse;
use Wirecard\PaymentSdk\TransactionService;
use WirecardElasticEngine\Components\Actions\Action;
use WirecardElasticEngine\Components\Actions\ErrorAction;
use WirecardElasticEngine\Components\Actions\RedirectAction;
use WirecardElasticEngine\Components\Actions\ViewAction;
use WirecardElasticEngine\Components\Payments\Contracts\ProcessReturnInterface;
use WirecardElasticEngine\Components\Payments\Payment;
use WirecardElasticEngine\Models\Transaction;

/**
 * Responsible for handling return actions. Payments may implement their own way of handling returns by implementing
 * the `ProcessReturnInterface` interface.
 *
 * @package WirecardElasticEngine\Components\Services
 *
 * @since   1.0.0
 */
class ReturnHandler extends Handler
{
    /**
     * @param Payment                             $payment
     * @param TransactionService                  $transactionService
     * @param \Enlight_Controller_Request_Request $request
     * @param SessionManager                      $sessionManager
     *
     * @return Response
     *
     * @since 1.0.0
     */
    public function handleRequest(
        Payment $payment,
        TransactionService $transactionService,
        \Enlight_Controller_Request_Request $request,
        SessionManager $sessionManager
    ) {
        if ($payment instanceof ProcessReturnInterface) {
            $response = $payment->processReturn($transactionService, $request, $sessionManager);
            if ($response) {
                return $response;
            }
        }

        return $transactionService->handleResponse($request->getParams());
    }

    /**
     * @param Response $response
     *
     * @return Action
     *
     * @since 1.0.0
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
     *
     * @since 1.0.0
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
     * @param string          $statusMessage
     *
     * @return Action
     *
     * @since 1.0.0
     */
    public function handleSuccess(SuccessResponse $response, Transaction $initialTransaction, $statusMessage = null)
    {
        $this->transactionManager->createReturn($initialTransaction, $response, $statusMessage);

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
     *
     * @since 1.0.0
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
     *
     * @since 1.0.0
     */
    protected function handleFailure($response)
    {
        $message = 'Unexpected response';
        $context = [get_class($response)];

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
