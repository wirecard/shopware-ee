<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

namespace WirecardElasticEngine\Components\Payments\Contracts;

use Shopware\Models\Shop\Shop;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\TransactionService;
use WirecardElasticEngine\Components\Actions\Action;
use WirecardElasticEngine\Components\Data\OrderSummary;
use WirecardElasticEngine\Components\Services\PaymentHandler;

/**
 * @package WirecardElasticEngine\Components\Payments\Interfaces
 *
 * @since   1.0.0
 */
interface ProcessPaymentInterface
{
    /**
     * Payment specific processing. This method either returns an `Action` (which is directly returned to the Handler)
     * or `null`. Returning `null` leads to the handler executing the transaction via the `TransactionService`. In case
     * of returning an `Action` execution of the transaction (via the `TransactionService`) probably needs to get
     * called manually within this method.
     *
     * @see   PaymentHandler
     *
     * @param OrderSummary                        $orderSummary
     * @param TransactionService                  $transactionService
     * @param Shop                                $shop
     * @param Redirect                            $redirect
     * @param \Enlight_Controller_Request_Request $request
     * @param \sOrder                             $shopwareOrder
     *
     * @return Action|null
     *
     * @since 1.0.0
     */
    public function processPayment(
        OrderSummary $orderSummary,
        TransactionService $transactionService,
        Shop $shop,
        Redirect $redirect,
        \Enlight_Controller_Request_Request $request,
        \sOrder $shopwareOrder
    );
}
