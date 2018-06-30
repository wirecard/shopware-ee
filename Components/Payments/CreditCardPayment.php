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
use Wirecard\PaymentSdk\Transaction\Transaction as WirecardTransaction;
use Wirecard\PaymentSdk\TransactionService;

class CreditCardPayment extends Payment
{
    const PAYMETHOD_IDENTIFIER = 'wirecard_elastic_engine_credit_card';

    private $paymentData;
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

    /**
     * @inheritdoc
     */
    public function getConfig(array $configData)
    {
        $config = new Config($configData['baseUrl'], $configData['httpUser'], $configData['httpPass']);

        $creditCardConfig = new CreditCardConfig(
            $configData['transactionMAID'],
            $configData['transactionKey']
        );

        if ($configData['transaction3dsMAID'] !== '' &&
            $configData['transaction3dsMAID'] !== 'null') {
            $creditCardConfig->setThreeDCredentials(
                $configData['transaction3dsMAID'],
                $configData['transaction3dsKey']
            );
        }

        $creditCardConfig->addSslMaxLimit(
            new Amount(
                $configData['3dsOnly'],
                $this->paymentData['currency']
            )
        );

        $creditCardConfig->addThreeDMinLimit(
            new Amount(
                $configData['3dsAttempt'],
                $this->paymentData['currency']
            )
        );

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

        $threeDsAttempt = Shopware()->Config()->getByNamespace(
            'WirecardShopwareElasticEngine',
            'wirecardElasticEngineCreditCard3dsAttempt'
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
            '3dsAttempt'         => $threeDsAttempt
         ]);
    }

    /**
     * @inheritdoc
     */
    protected function addPaymentSpecificData(WirecardTransaction $transaction, array $paymentData, array $configData)
    {
        // TODO add transaction data or delete function
        $transaction->setTermUrl($paymentData['returnUrl']);
        return $transaction;
    }

    public function getRequestDataForIframe(array $paymentData) {
        $this->paymentData = $paymentData;
        $transaction = $this->createTransaction($paymentData);

        $configData = $this->getConfigData();

        $transaction->setConfig($this->creditCardConfig);
        $transactionService = new TransactionService($this->paymentConfig, Shopware()->PluginLogger());

        return $transactionService->getCreditCardUiWithData(
            $transaction,
            $configData['transactionType'],
            Shopware()->Locale()->getLanguage()
        );
    }
}
