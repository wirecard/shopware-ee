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

namespace WirecardShopwareElasticEngine\Components\Payments;

use Shopware\Models\Shop\Shop;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Wirecard\PaymentSdk\Config\Config;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Response\Response;
use Wirecard\PaymentSdk\TransactionService;
use WirecardShopwareElasticEngine\Components\Actions\Action;
use WirecardShopwareElasticEngine\Components\Data\OrderSummary;
use WirecardShopwareElasticEngine\Components\Data\PaymentConfig;
use WirecardShopwareElasticEngine\Components\Services\PaymentHandler;

interface PaymentInterface
{
    /**
     * @return string
     */
    public function getLabel();

    /**
     * @return string
     */
    public function getName();

    /**
     * @return int
     */
    public function getPosition();

    /**
     * Returns the config (in form of an array) for registering payments in Shopware.
     *
     * @return array
     */
    public function getPaymentOptions();

    /**
     * Returns payment specific transaction object.
     *
     * @return \Wirecard\PaymentSdk\Transaction\Transaction
     */
    public function getTransaction();

    /**
     * Returns the transaction type from `getPaymentOptions`.
     *
     * @return string
     */
    public function getTransactionType();

    /**
     * Returns transaction config.
     *
     * @param Shop                  $shop
     * @param ParameterBagInterface $parameterBag
     *
     * @return Config
     */
    public function getTransactionConfig(Shop $shop, ParameterBagInterface $parameterBag);

    /**
     * Returns payment specific configuration.
     *
     * @return PaymentConfig
     */
    public function getPaymentConfig();

    /**
     * Returns payment specific settings for additional payment form fields
     *
     * @return Array|null
     */
    public function getAdditionalFormFields();

    /**
     * Payment specific processing. This method either returns an `Action` (which is directly returned to the handler)
     * or `null`. Returning `null` leads to the handler executing the transaction via the `TransactionService`. In case
     * of returning an `Action` execution of the transaction (via the `TransactionService`) probably needs to get
     * called manually within this method.
     *
     * @see PaymentHandler
     *
     * @param OrderSummary                        $orderSummary
     * @param TransactionService                  $transactionService
     * @param Shop                                $shop
     * @param Redirect                            $redirect
     * @param \Enlight_Controller_Request_Request $request
     * @param \sOrder                             $shopwareOrder
     *
     * @return Action|null
     */
    public function processPayment(
        OrderSummary $orderSummary,
        TransactionService $transactionService,
        Shop $shop,
        Redirect $redirect,
        \Enlight_Controller_Request_Request $request,
        \sOrder $shopwareOrder
    );

    /**
     * @param TransactionService                  $transactionService
     * @param \Enlight_Controller_Request_Request $request
     *
     * @return Response
     */
    public function processReturn(
        TransactionService $transactionService,
        \Enlight_Controller_Request_Request $request
    );
}
