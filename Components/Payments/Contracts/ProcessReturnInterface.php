<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

namespace WirecardElasticEngine\Components\Payments\Contracts;

use Wirecard\PaymentSdk\Response\Response;
use Wirecard\PaymentSdk\TransactionService;
use WirecardElasticEngine\Components\Services\SessionManager;

/**
 * @package WirecardElasticEngine\Components\Payments\Contracts
 *
 * @since   1.0.0
 */
interface ProcessReturnInterface
{
    /**
     * Payment specific return processing, called by the `ReturnHandler`. This method either returns a `Response` (which
     * is directly returned to the controller) or `null`. Returning `null` leads to the `TransactionService` taking
     * care of handling the response (via `handleResponse`) which is then returned to the controller.
     *
     * @param TransactionService                  $transactionService
     * @param \Enlight_Controller_Request_Request $request
     * @param SessionManager                      $sessionManager
     *
     * @return Response|null
     *
     * @since 1.0.0
     */
    public function processReturn(
        TransactionService $transactionService,
        \Enlight_Controller_Request_Request $request,
        SessionManager $sessionManager
    );
}
