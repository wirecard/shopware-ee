<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

namespace WirecardElasticEngine\Exception;

use Wirecard\PaymentSdk\Response\SuccessResponse;

/**
 * Thrown when the `TransactionManager` fails to find the initial transaction from a response.
 *
 * @see     \WirecardElasticEngine\Components\Services\TransactionManager::findInitialTransaction()
 *
 * @package WirecardElasticEngine\Exception
 *
 * @since   1.0.0
 */
class InitialTransactionNotFoundException extends \Exception
{
    /**
     * @param SuccessResponse $response
     *
     * @since 1.0.0
     */
    public function __construct(SuccessResponse $response)
    {
        parent::__construct(
            "Initial transaction for transaction (" .
            "Id '{$response->getTransactionId()}', " .
            "ParentId '{$response->getParentTransactionId()}', " .
            "RequestId '{$response->getRequestId()}'" .
            ") not found: " . json_encode($response->getData())
        );
    }
}
