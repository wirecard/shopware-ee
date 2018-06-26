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
use Wirecard\PaymentSdk\Config\PaymentMethodConfig;
use Wirecard\PaymentSdk\Transaction\PayPalTransaction;
use Wirecard\PaymentSdk\Transaction\Transaction as WirecardTransaction;

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
     * @inheritdoc
     */
    public function getConfig(array $configData)
    {
        $config = new Config($configData['baseUrl'], $configData['httpUser'], $configData['httpPass']);
        $paypalConfig = new PaymentMethodConfig(
            PayPalTransaction::NAME,
            $configData['transactionMAID'],
            $configData['transactionKey']
        );
        $config->add($paypalConfig);

        return $config;
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
    public function getConfigData()
    {
        $baseUrl = Shopware()->Config()->getByNamespace(
            'WirecardShopwareElasticEngine',
            'wirecardElasticEnginePaypalServer'
        );
        $httpUser = Shopware()->Config()->getByNamespace(
            'WirecardShopwareElasticEngine',
            'wirecardElasticEnginePaypalHttpUser'
        );
        $httpPass = Shopware()->Config()->getByNamespace(
            'WirecardShopwareElasticEngine',
            'wirecardElasticEnginePaypalHttpPassword'
        );

        $paypalMAID = Shopware()->Config()->getByNamespace(
            'WirecardShopwareElasticEngine',
            'wirecardElasticEnginePaypalMerchandId'
        );
        $paypalKey = Shopware()->Config()->getByNamespace(
            'WirecardShopwareElasticEngine',
            'wirecardElasticEnginePaypalSecret'
        );
        $transactionType = Shopware()->Config()->getByNamespace(
            'WirecardShopwareElasticEngine',
            'wirecardElasticEnginePaypalTransactionType'
        );

        $sendBasket = Shopware()->Config()->getByNamespace(
            'WirecardShopwareElasticEngine',
            'wirecardElasticEnginePaypalSendBasket'
        );
        $fraudPrevention = Shopware()->Config()->getByNamespace(
            'WirecardShopwareElasticEngine',
            'wirecardElasticEnginePaypalFraudPrevention'
        );

        $descriptor = Shopware()->Config()->getByNamespace(
            'WirecardShopwareElasticEngine',
            'wirecardElasticEnginePaypalDescriptor'
        );

        return array_merge(parent::getConfigData(), [
            'baseUrl'         => $baseUrl,
            'httpUser'        => $httpUser,
            'httpPass'        => $httpPass,
            'transactionMAID' => $paypalMAID,
            'transactionKey'  => $paypalKey,
            'transactionType' => $transactionType,
            'sendBasket'      => $sendBasket,
            'fraudPrevention' => $fraudPrevention,
            'descriptor'      => $descriptor
        ]);
    }

    /**
     * @inheritdoc
     */
    protected function addPaymentSpecificData(WirecardTransaction $transaction, array $paymentData, array $configData)
    {
        $orderDetail = $this->createBasketText($paymentData['basket'], $paymentData['currency']);

        if ($transaction instanceof PayPalTransaction) {
            $transaction->setOrderDetail($orderDetail);
        }

        return $transaction;
    }
}
