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

use Shopware\Bundle\PluginInstallerBundle\Service\InstallerService;
use Shopware\Models\Shop\Shop;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Wirecard\PaymentSdk\Config\CreditCardConfig;
use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Transaction\CreditCardTransaction;
use Wirecard\PaymentSdk\TransactionService;
use WirecardShopwareElasticEngine\Components\Actions\ViewAction;
use WirecardShopwareElasticEngine\Components\Data\OrderSummary;
use WirecardShopwareElasticEngine\Components\Data\PaymentConfig;

class CreditCardPayment extends Payment
{
    const PAYMETHOD_IDENTIFIER = 'wirecard_elastic_engine_credit_card';

    /**
     * @var CreditCardTransaction
     */
    private $transactionInstance;

    /**
     * @inheritdoc
     */
    public function getLabel()
    {
        return 'Wirecard Credit Card';
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
        return 0;
    }

    /**
     * @return CreditCardTransaction
     */
    public function getTransaction()
    {
        if (! $this->transactionInstance) {
            $this->transactionInstance = new CreditCardTransaction();
        }
        return $this->transactionInstance;
    }

    /**
     * @inheritdoc
     */
    public function getTransactionConfig(
        Shop $shop,
        ParameterBagInterface $parameterBag,
        InstallerService $installerService
    ) {
        $transactionConfig = parent::getTransactionConfig($shop, $parameterBag, $installerService);
        $paymentConfig     = $this->getPaymentConfig();
        $creditCardConfig  = new CreditCardConfig();

        if ($paymentConfig->getTransactionMAID() && $paymentConfig->getTransactionMAID() !== 'null') {
            $creditCardConfig->setSSLCredentials(
                $paymentConfig->getTransactionMAID(),
                $paymentConfig->getTransactionSecret()
            );
        }

        if ($paymentConfig->getThreeDMAID() && $paymentConfig->getThreeDMAID() !== 'null') {
            $creditCardConfig->setThreeDCredentials(
                $paymentConfig->getThreeDMAID(),
                $paymentConfig->getThreeDSecret()
            );
        }

        $creditCardConfig->addSslMaxLimit(
            $this->getLimit($shop, $paymentConfig->getThreeDMinLimit(), $paymentConfig->getThreeDMinLimitCurrency())
        );
        $creditCardConfig->addThreeDMinLimit(
            $this->getLimit(
                $shop,
                $paymentConfig->getThreeDSslMaxLimit(),
                $paymentConfig->getThreeDSslMaxLimitCurrency()
            )
        );

        $transactionConfig->add($creditCardConfig);
        $this->getTransaction()->setConfig($creditCardConfig);

        return $transactionConfig;
    }

    /**
     * @param Shop $shop
     * @param      $limitValue
     * @param      $limitCurrency
     *
     * @return Amount
     */
    private function getLimit(Shop $shop, $limitValue, $limitCurrency)
    {
        $factor = $this->getCurrencyConversionFactor($shop, strtolower($limitCurrency));
        return new Amount($limitValue * $factor, $shop->getCurrency()->getCurrency());
    }

    /**
     * @param Shop   $shop
     * @param string $limitCurrency
     *
     * @return float
     */
    private function getCurrencyConversionFactor(Shop $shop, $limitCurrency)
    {
        $shopCurrency = $shop->getCurrency();

        if ($limitCurrency && $limitCurrency !== 'null') {
            if (strtolower($shopCurrency->getCurrency()) !== $limitCurrency) {
                foreach ($shop->getCurrencies() as $currency) {
                    if (strtolower($currency->getCurrency()) === $limitCurrency) {
                        return $shopCurrency->getFactor() / $currency->getFactor();
                    }
                }
            }
        } elseif (! $shopCurrency->getDefault()) {
            return $shopCurrency->getFactor();
        }
        return 1.0;
    }

    /**
     * @inheritdoc
     */
    public function getPaymentConfig()
    {
        $paymentConfig = new PaymentConfig(
            $this->getPluginConfig('CreditCardServer'),
            $this->getPluginConfig('CreditCardHttpUser'),
            $this->getPluginConfig('CreditCardHttpPassword')
        );

        $paymentConfig->setTransactionMAID($this->getPluginConfig('CreditCardMerchantId'));
        $paymentConfig->setTransactionSecret($this->getPluginConfig('CreditCardSecret'));
        $paymentConfig->setTransactionType($this->getPluginConfig('CreditCardTransactionType'));

        $paymentConfig->setThreeDMAID($this->getPluginConfig('CreditCardThreeDMAID'));
        $paymentConfig->setThreeDSecret($this->getPluginConfig('CreditCardThreeDSecret'));
        $paymentConfig->setThreeDSslMaxLimit($this->getPluginConfig('CreditCardThreeDSslMaxLimit'));
        $paymentConfig->setThreeDSslMaxLimitCurrency($this->getPluginConfig('CreditCardThreeDSslMaxLimitCurrency'));
        $paymentConfig->setThreeDMinLimit($this->getPluginConfig('CreditCardThreeDMinLimit'));
        $paymentConfig->setThreeDMinLimitCurrency($this->getPluginConfig('CreditCardThreeDMinLimitCurrency'));

        return $paymentConfig;
    }

    /**
     * @inheritdoc
     */
    public function processPayment(
        OrderSummary $orderSummary,
        TransactionService $transactionService,
        Redirect $redirect,
        \Enlight_Controller_Request_Request $request
    ) {
        $transaction = $this->getTransaction();
        $transaction->setTermUrl($redirect->getSuccessUrl());

        $response = $transactionService->getCreditCardUiWithData(
            $transaction,
            $orderSummary->getPayment()->getPaymentConfig()->getTransactionType()
        );

        return new ViewAction('credit_card.tpl', [
            'wirecardUrl'         => $orderSummary->getPayment()->getPaymentConfig()->getBaseUrl(),
            'wirecardRequestData' => $response,
        ]);
    }
}
