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
use Wirecard\PaymentSdk\TransactionService;
use WirecardShopwareElasticEngine\Components\Data\OrderSummary;
use WirecardShopwareElasticEngine\Components\Payments\Payment;
use WirecardShopwareElasticEngine\Exception\ArrayKeyNotFoundException;

class PaymentHandler
{
    /**
     * @var Payment
     */
    protected $payment;

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
     * @param EntityManagerInterface      $em
     * @param LoggerInterface             $logger
     */
    public function __construct(\Shopware_Components_Config $config, EntityManagerInterface $em, LoggerInterface $logger)
    {
        $this->config = $config;
        $this->em     = $em;
        $this->logger = $logger;
    }

    /**
     * @throws ArrayKeyNotFoundException
     */
    public function execute()
    {
        $transaction  = $this->getPayment()->getTransaction();
        $orderSummary = $this->getOrderSummary();

        $transaction->setRedirect($orderSummary->getRedirect());
        $transaction->setAmount($orderSummary->getAmount());
        $transaction->setNotificationUrl(null);

        if ($this->getPayment()->getPaymentConfig()->sendBasket()) {
            $transaction->setBasket($orderSummary->getBasketMapper()->getWirecardBasket());
        }

        if ($this->getPayment()->getPaymentConfig()->hasFraudPrevention()) {
            $transaction->setIpAddress($orderSummary->getUserMapper()->getClientIp());
            $transaction->setAccountHolder($orderSummary->getUserMapper()->getWirecardBillingAccountHolder());
            $transaction->setShipping($orderSummary->getUserMapper()->getWirecardShippingAccountHolder());
            $transaction->setLocale($orderSummary->getUserMapper()->getLocale());
        }

        if ($this->getPayment()->getPaymentConfig()->sendDescriptor()) {
            $transaction->setDescriptor($this->getDescriptor(null));
        }

        $this->getPayment()->processPayment($this->getOrderSummary(), $this->getTransactionService());
    }

    /**
     * @param Payment $payment
     */
    public function setPayment(Payment $payment)
    {
        $this->payment = $payment;
    }

    /**
     * @return Payment
     */
    public function getPayment()
    {
        return $this->payment;
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
