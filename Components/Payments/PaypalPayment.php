<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

namespace WirecardElasticEngine\Components\Payments;

use Shopware\Models\Shop\Shop;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Wirecard\PaymentSdk\Config\PaymentMethodConfig;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Transaction\PayPalTransaction;
use Wirecard\PaymentSdk\TransactionService;
use WirecardElasticEngine\Components\Data\OrderSummary;
use WirecardElasticEngine\Components\Data\PaymentConfig;
use WirecardElasticEngine\Components\Payments\Contracts\ProcessPaymentInterface;

/**
 * @package WirecardElasticEngine\Components\Payments
 *
 * @since   1.0.0
 */
class PaypalPayment extends Payment implements ProcessPaymentInterface
{
    const PAYMETHOD_IDENTIFIER = 'wirecard_elastic_engine_paypal';

    /**
     * @var PayPalTransaction
     */
    private $transactionInstance;

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'WirecardPayPal';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::PAYMETHOD_IDENTIFIER;
    }

    /**
     * {@inheritdoc}
     */
    public function getPosition()
    {
        return 5;
    }

    /**
     * @return PayPalTransaction
     *
     * @since 1.0.0
     */
    public function getTransaction()
    {
        if (! $this->transactionInstance) {
            $this->transactionInstance = new PayPalTransaction();
        }
        return $this->transactionInstance;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
            $orderSummary->getPaymentUniqueId(),
            $orderSummary->getAmount()->getValue(),
            $orderSummary->getAmount()->getCurrency()
        ));

        return null;
    }
}
