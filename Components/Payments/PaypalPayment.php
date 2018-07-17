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
use Wirecard\PaymentSdk\Config\PaymentMethodConfig;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Transaction\PayPalTransaction;
use Wirecard\PaymentSdk\TransactionService;
use WirecardShopwareElasticEngine\Components\Data\OrderSummary;
use WirecardShopwareElasticEngine\Components\Data\PaymentConfig;

class PaypalPayment extends Payment
{
    const PAYMETHOD_IDENTIFIER = 'wirecard_elastic_engine_paypal';

    /**
     * @var PayPalTransaction
     */
    private $transactionInstance;

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
        if (! $this->transactionInstance) {
            $this->transactionInstance = new PayPalTransaction();
        }
        return $this->transactionInstance;
    }

    /**
     * @inheritdoc
     */
    public function getTransactionConfig(Shop $shop, ParameterBagInterface $parameterBag, $selectedCurrency)
    {
        $config = parent::getTransactionConfig($shop, $parameterBag, $selectedCurrency);
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
            $this->getPluginConfig('PaypalServer'),
            $this->getPluginConfig('PaypalHttpUser'),
            $this->getPluginConfig('PaypalHttpPassword')
        );

        $paymentConfig->setTransactionMAID($this->getPluginConfig('PaypalMerchantId'));
        $paymentConfig->setTransactionSecret($this->getPluginConfig('PaypalSecret'));
        $paymentConfig->setTransactionOperation($this->getPluginConfig('PaypalTransactionType'));
        $paymentConfig->setSendBasket($this->getPluginConfig('PaypalSendBasket'));
        $paymentConfig->setFraudPrevention($this->getPluginConfig('PaypalFraudPrevention'));
        $paymentConfig->setSendDescriptor($this->getPluginConfig('PaypalDescriptor'));

        return $paymentConfig;
    }

    /**
     * @inheritdoc
     */
    public function processPayment(
        OrderSummary $orderSummary,
        TransactionService $transactionService,
        Shop $shop,
        Redirect $redirect,
        \Enlight_Controller_Request_Request $request,
        \sOrder $shopwareOrder
    ) {
        $transaction = $this->getTransaction();

        $transaction->setOrderDetail(sprintf(
            '%s - %.2f %s',
            $orderSummary->getOrderNumber(),
            $orderSummary->getAmount()->getValue(),
            $orderSummary->getAmount()->getCurrency()
        ));

        return null;
    }
}
