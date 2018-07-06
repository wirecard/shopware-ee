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

use Psr\Log\LoggerInterface;
use Wirecard\PaymentSdk\TransactionService;
use WirecardShopwareElasticEngine\Components\Data\PaymentData;
use WirecardShopwareElasticEngine\Components\Payments\Payment;

class PaymentProcessor
{
    /**
     * @var Payment
     */
    protected $payment;

    /**
     * @var PaymentData
     */
    protected $paymentData;

    /**
     * @var TransactionService
     */
    protected $transactionService;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function execute()
    {
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
     * @param PaymentData $paymentData
     */
    public function setPaymentData(PaymentData $paymentData)
    {
        $this->paymentData = $paymentData;
    }

    /**
     * @return mixed
     */
    public function getPaymentData()
    {
        return $this->getPaymentData();
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
}
