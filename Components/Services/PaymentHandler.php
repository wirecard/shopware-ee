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
     * @var OrderNumberAssignment
     */
    protected $orderNumberAssignment;

    /**
     * @param \Shopware_Components_Config $config
     * @param EntityManagerInterface      $em
     * @param LoggerInterface             $logger
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
     * @param Redirect $redirect
     * @param string   $notificationUrl
     *
     * @return Action
     * @throws ArrayKeyNotFoundException
     */
    public function execute(Redirect $redirect, $notificationUrl)
    {
        $this->prepareTransaction($redirect, $notificationUrl);

        $transaction = $this->getOrderSummary()->getPayment()->getTransaction();

        $action = $this->getOrderSummary()
                       ->getPayment()
                       ->processPayment($this->getOrderSummary(), $this->getTransactionService());

        $this->logger->debug('Payment processing execution', $this->getOrderSummary()->toArray());

        if ($action !== null) {
            return $action;
        }

        $transactionService = $this->getTransactionService();
        $response           = $transactionService->process(
            $transaction,
            $this->getOrderSummary()->getPayment()->getPaymentConfig()->getTransactionType()
        );

        switch (true) {
            case $response instanceof InteractionResponse:
                return new RedirectAction($response->getRedirectUrl());

            default:
                // todo: throw exception
                return null;
        }
    }

    /**
     * Prepares the transaction for being sent to Wirecard by adding specific (e.g. amount) and optional (e.g. fraud
     * prevention data) data to the `Transaction` object of the payment.
     *
     * @param Redirect $redirect
     * @param string   $notificationUrl
     *
     * @throws ArrayKeyNotFoundException
     */
    private function prepareTransaction(Redirect $redirect, $notificationUrl)
    {
        $this->createOrderNumberAssignment();

        $orderSummary          = $this->getOrderSummary();
        $transaction           = $orderSummary->getPayment()->getTransaction();

        $transaction->setRedirect($redirect);
        $transaction->setAmount($orderSummary->getAmount());
        $transaction->setNotificationUrl($notificationUrl);
        $transaction->setOrderNumber($this->orderNumberAssignment->getId());

        if ($orderSummary->getPayment()->getPaymentConfig()->sendBasket()) {
            $transaction->setBasket($orderSummary->getBasketMapper()->getWirecardBasket());
        }

        if ($orderSummary->getPayment()->getPaymentConfig()->hasFraudPrevention()) {
            $transaction->setIpAddress($orderSummary->getUserMapper()->getClientIp());
            $transaction->setAccountHolder($orderSummary->getUserMapper()->getWirecardBillingAccountHolder());
            $transaction->setShipping($orderSummary->getUserMapper()->getWirecardShippingAccountHolder());
            $transaction->setLocale($orderSummary->getUserMapper()->getLocale());
        }

        if ($orderSummary->getPayment()->getPaymentConfig()->sendDescriptor()) {
            $transaction->setDescriptor($this->getDescriptor($this->orderNumberAssignment->getId()));
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

        $this->orderNumberAssignment = $orderNumberAssignment;
    }

    /**
     * @param OrderSummary $orderSummary
     */
    public function setOrderSummary(OrderSummary $orderSummary)
    {
        $this->orderSummary = $orderSummary;
    }

    /**
     * @return OrderSummary
     */
    public function getOrderSummary()
    {
        return $this->orderSummary;
    }

    /**
     * @param TransactionService $transactionService
     */
    public function setTransactionService(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    /**
     * @return TransactionService
     */
    public function getTransactionService()
    {
        return $this->transactionService;
    }

    /**
     * Returns the descriptor sent to Wirecard. Change to your own needs.
     *
     * @param $orderNumber
     *
     * @return string
     */
    public function getDescriptor($orderNumber)
    {
        $shopName = $this->config->get('shopName');
        return "${shopName} ${orderNumber}";
    }
}
