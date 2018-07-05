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

use Wirecard\PaymentSdk\Config\Config;
use Wirecard\PaymentSdk\Config\CreditCardConfig;
use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Transaction\CreditCardTransaction;
use Wirecard\PaymentSdk\Transaction\Reservable;
use Wirecard\PaymentSdk\Transaction\Transaction as WirecardTransaction;
use Wirecard\PaymentSdk\TransactionService;

class CreditCardPayment extends Payment
{
    const PAYMETHOD_IDENTIFIER = 'wirecard_elastic_engine_credit_card';

    private $creditCardConfig;
    private $paymentConfig;

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

    public function getPosition()
    {
        return 0;
    }

    /**
     * @inheritdoc
     */
    public function getConfig(array $configData)
    {
        $config = new Config($configData['baseUrl'], $configData['httpUser'], $configData['httpPass']);

        $creditCardConfig = new CreditCardConfig();

        if ($configData['transactionMAID'] !== '' &&
            $configData['transactionKey'] !== 'null') {
            $creditCardConfig->setSSLCredentials(
                $configData['transactionMAID'],
                $configData['transactionKey']
            );
        }

        if ($configData['transaction3dsMAID'] !== '' &&
            $configData['transaction3dsMAID'] !== 'null') {
            $creditCardConfig->setThreeDCredentials(
                $configData['transaction3dsMAID'],
                $configData['transaction3dsKey']
            );
        }

        $threeDsOnly = $configData['3dsOnly'];
        $threeDsOnlyCurrency = $configData['3dsOnlyCurrency'];
        $threeDsAttempt = $configData['3dsAttempt'];
        $threeDsAttemptCurrency = $configData['3dsAttemptCurrency'];
        $shop = Shopware()->Container()->get('shop');

        if ($shop) {
            if ($threeDsOnlyCurrency) {
                if( $shop->getCurrency()->getCurrency() !== $threeDsOnlyCurrency) {
                    foreach ($shop->getCurrencies() as $currency) {
                        if ($currency->getCurrency() === $threeDsOnlyCurrency) {
                            $factorOld = $currency->getFactor();
                            $threeDsOnly /= $factorOld;
                            $factorNew = $shop->getCurrency()->getFactor();
                            $threeDsOnly *= $factorNew;
                            break;
                        }
                    }
                }
            } else {
                if (!$shop->getCurrency()->getDefault()) {
                    $factor = $shop->getCurrency()->getFactor();
                    $threeDsOnly *= $factor;
                }
            }

            if ($threeDsAttemptCurrency) {
                if( $shop->getCurrency()->getCurrency() !== $threeDsAttemptCurrency) {
                    foreach ($shop->getCurrencies() as $currency) {
                        if ($currency->getCurrency() === $threeDsAttemptCurrency) {
                            $factorOld = $currency->getFactor();
                            $threeDsAttempt /= $factorOld;
                            $factorNew = $shop->getCurrency()->getFactor();
                            $threeDsAttempt *= $factorNew;
                            break;
                        }
                    }
                }
            } else {
                if (!$shop->getCurrency()->getDefault()) {
                    $factor = $shop->getCurrency()->getFactor();
                    $threeDsAttempt *= $factor;
                }
            }

            $currency = $shop->getCurrency()->getCurrency();

            $creditCardConfig->addSslMaxLimit(
                new Amount(
                    $threeDsOnly,
                    $currency
                )
            );

            $creditCardConfig->addThreeDMinLimit(
                new Amount(
                    $threeDsAttempt,
                    $currency
                )
            );
        }

        $this->creditCardConfig = $creditCardConfig;

        $config->add($this->creditCardConfig);

        $this->paymentConfig = $config;

        return $this->paymentConfig;
    }

    /**
     * @return CreditCardTransaction
     */
    public function getTransaction()
    {
        return new CreditCardTransaction();
    }

    /**
     * @inheritdoc
     */
    public function getConfigData()
    {
        $baseUrl = Shopware()->Config()->getByNamespace(
            'WirecardShopwareElasticEngine',
            'wirecardElasticEngineCreditCardServer'
        );
        $httpUser = Shopware()->Config()->getByNamespace(
            'WirecardShopwareElasticEngine',
            'wirecardElasticEngineCreditCardHttpUser'
        );
        $httpPass = Shopware()->Config()->getByNamespace(
            'WirecardShopwareElasticEngine',
            'wirecardElasticEngineCreditCardHttpPassword'
        );

        $creditCardMAID = Shopware()->Config()->getByNamespace(
            'WirecardShopwareElasticEngine',
            'wirecardElasticEngineCreditCardMerchandId'
        );

        $creditCardKey = Shopware()->Config()->getByNamespace(
            'WirecardShopwareElasticEngine',
            'wirecardElasticEngineCreditCardSecret'
        );

        $creditCard3dsMAID = Shopware()->Config()->getByNamespace(
            'WirecardShopwareElasticEngine',
            'wirecardElasticEngineCreditCard3dsMerchandId'
        );

        $creditCard3dsKey = Shopware()->Config()->getByNamespace(
            'WirecardShopwareElasticEngine',
            'wirecardElasticEngineCreditCard3dsSecret'
        );

        $transactionType = Shopware()->Config()->getByNamespace(
            'WirecardShopwareElasticEngine',
            'wirecardElasticEngineCreditCardTransactionType'
        );

        $threeDsOnly = Shopware()->Config()->getByNamespace(
            'WirecardShopwareElasticEngine',
            'wirecardElasticEngineCreditCard3dsOnly'
        );

        $threeDsOnlyCurrency = Shopware()->Config()->getByNamespace(
            'WirecardShopwareElasticEngine',
            'wirecardElasticEngineCreditCard3dsOnlyCurrency'
        );

        $threeDsAttempt = Shopware()->Config()->getByNamespace(
            'WirecardShopwareElasticEngine',
            'wirecardElasticEngineCreditCard3dsAttempt'
        );

        $threeDsAttemptCurrency = Shopware()->Config()->getByNamespace(
            'WirecardShopwareElasticEngine',
            'wirecardElasticEngineCreditCard3dsAttemptCurrency'
        );

        return array_merge(parent::getConfigData(), [
            'baseUrl'            => $baseUrl,
            'httpUser'           => $httpUser,
            'httpPass'           => $httpPass,
            'transactionMAID'    => $creditCardMAID,
            'transactionKey'     => $creditCardKey,
            'transaction3dsMAID' => $creditCard3dsMAID,
            'transaction3dsKey'  => $creditCard3dsKey,
            'transactionType'    => $transactionType,
            '3dsOnly'            => $threeDsOnly,
            '3dsOnlyCurrency'    => $threeDsOnlyCurrency,
            '3dsAttempt'         => $threeDsAttempt,
            '3dsAttemptCurrency' => $threeDsAttemptCurrency
         ]);
    }

    /**
     * @inheritdoc
     */
    protected function addPaymentSpecificData(WirecardTransaction $transaction, array $paymentData, array $configData)
    {
        if ($transaction instanceof CreditCardTransaction) {
            $transaction->setTermUrl($paymentData['returnUrl']);
        }
        return $transaction;
    }

    /**
     * Get request data as json string
     *
     * @param array $paymentData
     * @return string
     */
    public function getRequestDataForIframe(array $paymentData)
    {
        $transaction = $this->createTransaction($paymentData);

        $configData = $this->getConfigData();
        if ($transaction instanceof CreditCardTransaction) {
            $transaction->setConfig($this->creditCardConfig);
        }

        $transactionType = WirecardTransaction::TYPE_PURCHASE;
        if ($configData['transactionType'] === parent::TRANSACTION_TYPE_AUTHORIZATION
            && $transaction instanceof Reservable) {
            $transactionType = WirecardTransaction::TYPE_AUTHORIZATION;
        }

        $transactionService = new TransactionService($this->paymentConfig, Shopware()->PluginLogger());

        return $transactionService->getCreditCardUiWithData(
            $transaction,
            $transactionType,
            Shopware()->Locale()->getLanguage()
        );
    }
}
