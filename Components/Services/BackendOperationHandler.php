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

use Wirecard\PaymentSdk\BackendService;
use Wirecard\PaymentSdk\Entity\Status;
use Wirecard\PaymentSdk\Response\FailureResponse;
use Wirecard\PaymentSdk\Response\SuccessResponse;
use Wirecard\PaymentSdk\Transaction\Transaction;
use WirecardShopwareElasticEngine\Components\Actions\Action;
use WirecardShopwareElasticEngine\Components\Actions\ErrorAction;
use WirecardShopwareElasticEngine\Components\Actions\ViewAction;

class BackendOperationHandler extends Handler
{
    /**
     * @param Transaction    $transaction
     * @param BackendService $transactionService
     * @param string         $operation
     *
     * @return Action
     * @throws \WirecardShopwareElasticEngine\Exception\OrderNotFoundException
     */
    public function execute(
        Transaction $transaction,
        BackendService $transactionService,
        $operation
    ) {
        try {
            $response = $transactionService->process($transaction, $operation);
        } catch (\Exception $e) {
            $this->logger->error('Transaction service process failed: ' . $e->getMessage());
            return new ErrorAction(ErrorAction::PROCESSING_FAILED, 'Transaction processing failed');
        }

        if ($response instanceof SuccessResponse) {
            $this->transactionFactory->create(
                $this->getOrderFromResponse($response)->getNumber(),
                $response,
                \WirecardShopwareElasticEngine\Models\Transaction::TYPE_BACKEND
            );

            return new ViewAction(null, [
                'success'       => true,
                'transactionId' => $response->getTransactionId(),
            ]);
        }

        if ($response instanceof FailureResponse) {
            $this->logger->error('Backend operation failed', $response->getData());

            $errors = [];
            foreach ($response->getStatusCollection() as $status) {
                /** @var Status $status */
                $errors[] = $status->getDescription();
            }

            return new ErrorAction(ErrorAction::BACKEND_OPERATION_FAILED, join("\n", $errors));
        }

        $this->logger->error('Backend operation failed', $response->getData());
        return new ErrorAction(ErrorAction::BACKEND_OPERATION_FAILED, 'BackendOperationFailedUnknownResponse');
    }
}
