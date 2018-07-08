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

use Wirecard\PaymentSdk\Config\PaymentMethodConfig;
use Wirecard\PaymentSdk\Transaction\PayPalTransaction;
use Wirecard\PaymentSdk\Transaction\Transaction;
use Wirecard\PaymentSdk\TransactionService;
use WirecardShopwareElasticEngine\Components\Data\OrderDetails;
use WirecardShopwareElasticEngine\Components\Data\PaymentConfig;

class PaypalPayment extends Payment
{
    const PAYMETHOD_IDENTIFIER = 'wirecard_elastic_engine_paypal';

    /**
     * @inheritdoc
     */
    public function getLabel()
    {
        return 'Wirecard PayPal';
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return self::PAYMETHOD_IDENTIFIER;
    }

    /**
     * @return int
     */
    public function getPosition()
    {
        return 1;
    }

    /**
     * @return PayPalTransaction
     */
    public function getTransaction()
    {
        return new PayPalTransaction();
    }

    /**
     * @inheritdoc
     */
    public function getTransactionConfig()
    {
        $config = parent::getTransactionConfig();
        $config->add(new PaymentMethodConfig(
            PayPalTransaction::NAME,
            $this->getPaymentConfig()->getTransactionMAID(),
            $this->getPaymentConfig()->getTransactionSecret()
        ));

        return $config;
    }


    /**
     * @inheritdoc
     */
    public function getPaymentConfig()
    {
        $paymentConfig = new PaymentConfig(
            $this->getPluginConfig('wirecardElasticEnginePaypalServer'),
            $this->getPluginConfig('wirecardElasticEnginePaypalHttpUser'),
            $this->getPluginConfig('wirecardElasticEnginePaypalHttpPassword')
        );

        $paymentConfig->setTransactionMAID(
            $this->getPluginConfig('wirecardElasticEnginePaypalMerchantId')
        );

        $paymentConfig->setTransactionSecret(
            $this->getPluginConfig('wirecardElasticEnginePaypalSecret')
        );

        $paymentConfig->setTransactionType(
            $this->getPluginConfig('wirecardElasticEnginePaypalTransactionType')
        );

        $paymentConfig->setSendBasket(
            $this->getPluginConfig('wirecardElasticEnginePaypalSendBasket')
        );

        $paymentConfig->setFraudPrevention(
            $this->getPluginConfig('wirecardElasticEnginePaypalFraudPrevention')
        );

        $paymentConfig->setSendDescriptor(
            $this->getPluginConfig('wirecardElasticEnginePaypalDescriptor')
        );

        return $paymentConfig;
    }

    public function processPayment(OrderDetails $orderDetails, TransactionService $transactionService)
    {
        $transaction = $this->getTransaction();

        $transaction->setRedirect($orderDetails->getRedirect());
        $transaction->setAmount($orderDetails->getAmount());
        $transaction->setNotificationUrl(null);
        $transaction->setAccountHolder($orderDetails->getUserMapper()->getWirecardBillingAccountHolder());
        $transaction->setShipping($orderDetails->getUserMapper()->getWirecardShippingAccountHolder());

        $transaction->setOrderDetail($orderDetails->getBasketMapper()->getBasketText());
    }
}
