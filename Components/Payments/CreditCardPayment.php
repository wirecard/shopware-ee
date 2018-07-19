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

use Shopware\Models\Shop\Currency;
use Shopware\Models\Shop\Shop;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Wirecard\PaymentSdk\Config\CreditCardConfig;
use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Transaction\CreditCardTransaction;
use Wirecard\PaymentSdk\TransactionService;
use WirecardShopwareElasticEngine\Components\Actions\ViewAction;
use WirecardShopwareElasticEngine\Components\Data\OrderSummary;
use WirecardShopwareElasticEngine\Components\Data\CreditCardPaymentConfig;
use WirecardShopwareElasticEngine\Models\Transaction;

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
    public function getTransactionConfig(Shop $shop, ParameterBagInterface $parameterBag, $selectedCurrency)
    {
        $transactionConfig = parent::getTransactionConfig($shop, $parameterBag, $selectedCurrency);
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
            $this->getLimit(
                $selectedCurrency,
                $paymentConfig->getThreeDSslMaxLimit(),
                $paymentConfig->getThreeDSslMaxLimitCurrency()
            )
        );
        $creditCardConfig->addThreeDMinLimit(
            $this->getLimit(
                $selectedCurrency,
                $paymentConfig->getThreeDMinLimit(),
                $paymentConfig->getThreeDMinLimitCurrency()
            )
        );

        $transactionConfig->add($creditCardConfig);
        $this->getTransaction()->setConfig($creditCardConfig);

        return $transactionConfig;
    }

    /**
     * @param string       $selectedCurrency
     * @param float|string $limitValue
     * @param string       $limitCurrency
     *
     * @return Amount
     * @throws \Enlight_Event_Exception
     */
    private function getLimit($selectedCurrency, $limitValue, $limitCurrency)
    {
        $limit  = new Amount($limitValue, strtoupper($limitCurrency));
        $factor = $this->getCurrencyConversionFactor(strtoupper($selectedCurrency), $limit);

        $factor = Shopware()->Events()->filter(
            'WirecardShopwareElasticEngine_CreditCardPayment_getLimitCurrencyConversionFactor',
            $factor,
            [
                'subject' => $this,
                'limit'   => $limit,
            ]
        );

        return new Amount($limit->getValue() * $factor, $selectedCurrency);
    }

    /**
     * Return conversion factor from currently selected currency to limit currency of the plugin configuration.
     * If no limit currency has been set, the default currency of the shopware installation is used as fallback.
     *
     * @param string $selectedCurrency
     * @param Amount $limit
     *
     * @return float
     */
    private function getCurrencyConversionFactor($selectedCurrency, Amount $limit)
    {
        if ($limit->getCurrency() === $selectedCurrency) {
            return 1.0;
        }

        $selectedFactor = 1.0;
        $limitFactor    = 1.0;
        $repo           = $this->em->getRepository(Currency::class);
        $currency       = $repo->findOneBy(['currency' => $selectedCurrency]);

        // Get factor of the selected currency (if it is the default currency, use factor 1.0)
        if ($currency && ! $currency->getDefault()) {
            $selectedFactor = $currency->getFactor();
        }

        // Check if limit currency has been configured
        if ($limit->getCurrency() && $limit->getCurrency() !== 'NULL') {
            // Get factor of the limit currency (if it is the default currency, use factor 1.0)
            $limitCurrency = $repo->findOneBy(['currency' => $limit->getCurrency()]);
            if ($limitCurrency && ! $limitCurrency->getDefault()) {
                $limitFactor = $limitCurrency->getFactor();
            }
        }
        if (! $selectedFactor) {
            $selectedFactor = 1.0;
        }
        if (! $limitFactor) {
            $limitFactor = 1.0;
        }
        return $selectedFactor / $limitFactor;
    }

    /**
     * Returns payment specific configuration.
     *
     * @return CreditCardPaymentConfig
     */
    public function getPaymentConfig()
    {
        $paymentConfig = new CreditCardPaymentConfig(
            $this->getPluginConfig('CreditCardServer'),
            $this->getPluginConfig('CreditCardHttpUser'),
            $this->getPluginConfig('CreditCardHttpPassword')
        );

        $paymentConfig->setTransactionMAID($this->getPluginConfig('CreditCardMerchantId'));
        $paymentConfig->setTransactionSecret($this->getPluginConfig('CreditCardSecret'));
        $paymentConfig->setTransactionOperation($this->getPluginConfig('CreditCardTransactionType'));

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
        Shop $shop,
        Redirect $redirect,
        \Enlight_Controller_Request_Request $request,
        \sOrder $shopwareOrder
    ) {
        $transaction = $this->getTransaction();
        $transaction->setTermUrl($redirect->getSuccessUrl());

        $requestData = $transactionService->getCreditCardUiWithData(
            $transaction,
            $orderSummary->getPayment()->getTransactionType(),
            $shop->getLocale()->getLocale()
        );

        $transaction = new Transaction(Transaction::TYPE_INITIAL_REQUEST);
        $transaction->setPaymentUniqueId($orderSummary->getPaymentUniqueId());
        $transaction->setBasketSignature($orderSummary->getBasketMapper()->getSignature());
        $transaction->setRequest(json_decode($requestData, true));
        $this->em->persist($transaction);
        $this->em->flush();

        return new ViewAction('credit_card.tpl', [
            'wirecardUrl'         => $orderSummary->getPayment()->getPaymentConfig()->getBaseUrl(),
            'wirecardRequestData' => $requestData,
            'url'                 => $this->router->assemble([
                'action' => 'return',
                'method' => CreditCardPayment::PAYMETHOD_IDENTIFIER,
            ]),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function processReturn(
        TransactionService $transactionService,
        \Enlight_Controller_Request_Request $request
    ) {
        $params = $request->getParams();
        if (! empty($params['jsresponse'])) {
            return $transactionService->processJsResponse($request->getParams(), $this->router->assemble([
                'action' => 'return',
                'method' => CreditCardPayment::PAYMETHOD_IDENTIFIER,
            ]));
        }

        return null;
    }
}
