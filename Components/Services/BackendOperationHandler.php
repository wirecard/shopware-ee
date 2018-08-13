<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

namespace WirecardElasticEngine\Components\Services;

use Wirecard\PaymentSdk\BackendService;
use Wirecard\PaymentSdk\Entity\Status;
use Wirecard\PaymentSdk\Response\FailureResponse;
use Wirecard\PaymentSdk\Response\SuccessResponse;
use Wirecard\PaymentSdk\Transaction\Operation;
use Wirecard\PaymentSdk\Transaction\Transaction;
use WirecardElasticEngine\Components\Actions\Action;
use WirecardElasticEngine\Components\Actions\ErrorAction;
use WirecardElasticEngine\Components\Actions\ViewAction;

/**
 * Responsible for handling backend operations. Backend operations should be retrieved from `retrieveBackendOperations`
 * from the `BackendService` and directly passed to this handler.
 *
 * @package WirecardElasticEngine\Components\Services
 *
 * @since   1.0.0
 */
class BackendOperationHandler extends Handler
{
    /**
     * Executes the backend operation and returns a proper `Action`.
     *
     * @see   Operation Possible operations
     *
     * @param Transaction    $transaction
     * @param BackendService $transactionService
     * @param string         $operation
     *
     * @return Action
     *
     * @since 1.0.0
     */
    public function execute(
        Transaction $transaction,
        BackendService $transactionService,
        $operation
    ) {
        try {
            $response = $transactionService->process($transaction, $operation);

            if ($response instanceof SuccessResponse) {
                $this->transactionManager->createBackend($response);

                return new ViewAction(null, [
                    'success'       => true,
                    'transactionId' => $response->getTransactionId(),
                ]);
            }
        } catch (\Exception $e) {
            $this->logger->error('Transaction service process failed: ' . $e->getMessage());
            return new ErrorAction(ErrorAction::PROCESSING_FAILED, 'Transaction processing failed');
        }

        $message = 'BackendOperationFailedUnknownResponse';
        if ($response instanceof FailureResponse) {
            $errors = [];
            foreach ($response->getStatusCollection() as $status) {
                /** @var Status $status */
                $errors[] = $status->getDescription();
            }
            $message = join("\n", $errors);
        }

        $this->logger->error('Backend operation failed', $response->getData());
        return new ErrorAction(ErrorAction::BACKEND_OPERATION_FAILED, $message);
    }
}
