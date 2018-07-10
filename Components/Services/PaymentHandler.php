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

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Response\FailureResponse;
use Wirecard\PaymentSdk\Response\InteractionResponse;
use Wirecard\PaymentSdk\TransactionService;
use WirecardShopwareElasticEngine\Components\Actions\Action;
use WirecardShopwareElasticEngine\Components\Actions\RedirectAction;
use WirecardShopwareElasticEngine\Components\Data\OrderSummary;
use WirecardShopwareElasticEngine\Exception\ArrayKeyNotFoundException;
use WirecardShopwareElasticEngine\Models\OrderNumberAssignment;

class PaymentHandler
{
    /**
     * @var OrderSummary
     */
    protected $orderSummary;

    /**
     * @var TransactionService
     */
    protected $transactionService;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var \Shopware_Components_Config
     */
    protected $config;

    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @param \Shopware_Components_Config $config
     * @param EntityManagerInterface $em
     * @param LoggerInterface $logger
     */
    public function __construct(
        \Shopware_Components_Config $config,
        EntityManagerInterface $em,
        LoggerInterface $logger
    ) {
        $this->config = $config;
        $this->em     = $em;
        $this->logger = $logger;
    }

    /**
     * @param OrderSummary $orderSummary
     * @param TransactionService $transactionService
     * @param Redirect $redirect
     * @param string $notificationUrl
     *
     * @return Action
     * @throws ArrayKeyNotFoundException
     */
    public function execute(
        OrderSummary $orderSummary,
        TransactionService $transactionService,
        Redirect $redirect,
        $notificationUrl
    ) {
        $this->prepareTransaction($orderSummary, $redirect, $notificationUrl);

        $payment     = $orderSummary->getPayment();
        $transaction = $payment->getTransaction();

        $action = $payment->processPayment($orderSummary, $transactionService);

        if ($action !== null) {
            return $action;
        }

        $response = $transactionService->process(
            $transaction,
            $payment->getPaymentConfig()->getTransactionType()
        );

        $this->logger->debug('Payment processing execution', [
            'summary'  => $orderSummary->toArray(),
            'response' => $response,
        ]);

        switch (true) {
            case $response instanceof InteractionResponse:
                return new RedirectAction($response->getRedirectUrl());

            case $response instanceof FailureResponse:
                // todo: handle failure
                exit();

            default:
                // todo: throw exception
                return null;
        }
    }

    /**
     * Prepares the transaction for being sent to Wirecard by adding specific (e.g. amount) and optional (e.g. fraud
     * prevention data) data to the `Transaction` object of the payment.
     *
     * @param OrderSummary $orderSummary
     * @param Redirect $redirect
     * @param string $notificationUrl
     *
     * @throws ArrayKeyNotFoundException
     */
    private function prepareTransaction(OrderSummary $orderSummary, Redirect $redirect, $notificationUrl)
    {
        $orderNumberAssignment = $this->createOrderNumberAssignment();

        $payment       = $orderSummary->getPayment();
        $paymentConfig = $payment->getPaymentConfig();
        $transaction   = $payment->getTransaction();

        $transaction->setRedirect($redirect);
        $transaction->setAmount($orderSummary->getAmount());
        $transaction->setNotificationUrl($notificationUrl);
        $transaction->setOrderNumber($orderNumberAssignment->getId());

        if ($paymentConfig->sendBasket()) {
            $transaction->setBasket($orderSummary->getBasketMapper()->getWirecardBasket());
        }

        if ($paymentConfig->hasFraudPrevention()) {
            $transaction->setIpAddress($orderSummary->getUserMapper()->getClientIp());
            $transaction->setAccountHolder($orderSummary->getUserMapper()->getWirecardBillingAccountHolder());
            $transaction->setShipping($orderSummary->getUserMapper()->getWirecardShippingAccountHolder());
            $transaction->setLocale($orderSummary->getUserMapper()->getLocale());
        }

        if ($paymentConfig->sendDescriptor() && ! in_array(getenv('SHOPWARE_ENV'), ['dev', 'development'])) {
            $transaction->setDescriptor($this->getDescriptor($orderNumberAssignment->getId()));
        }
    }

    /**
     * Creates an order number assignment.
     */
    private function createOrderNumberAssignment()
    {
        $orderNumberAssignment = new OrderNumberAssignment();

        $this->em->persist($orderNumberAssignment);
        $this->em->flush();

        return $orderNumberAssignment;
    }

    /**
     * Returns the descriptor sent to Wirecard. Change to your own needs.
     * Keep in mind that the descriptor is ignored when shopware runs in development mode (see SHOPWARE_ENV of your
     * `.htaccess` or your apache configuration).
     *
     * @param $orderNumber
     *
     * @return string
     */
    protected function getDescriptor($orderNumber)
    {
        $shopName = $this->config->get('shopName');
        return "${shopName} ${orderNumber}";
    }
}
