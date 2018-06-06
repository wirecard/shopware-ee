<?php
/**
 * Shop System Plugins - Terms of Use
 * The plugins offered are provided free of charge by Wirecard AG and are explicitly not part
 * of the Wirecard AG range of products and services.
 * They have been tested and approved for full functionality in the standard configuration
 * (status on delivery) of the corresponding shop system. They are under General Public
 * License version 3 (GPLv3) and can be used, developed and passed on to third parties under
 * the same terms.
 * However, Wirecard AG does not provide any guarantee or accept any liability for any errors
 * occurring when used in an enhanced, customized shop system configuration.
 * Operation in an enhanced, customized configuration is at your own risk and requires a
 * comprehensive test phase by the user of the plugin.
 * Customers use the plugins at their own risk. Wirecard AG does not guarantee their full
 * functionality neither does Wirecard AG assume liability for any disadvantages related to
 * the use of the plugins. Additionally, Wirecard AG does not guarantee the full functionality
 * for customized shop systems or installed plugins of other vendors of plugins within the same
 * shop system.
 * Customers are responsible for testing the plugin's functionality before starting productive
 * operation.
 * By installing the plugin into the shop system the customer agrees to these terms of use.
 * Please do not use the plugin if you do not agree to these terms of use!
 */

namespace WirecardShopwareElasticEngine\Components\Payments;

use Wirecard\PaymentSdk\Config\Config;
use Wirecard\PaymentSdk\Config\PaymentMethodConfig;
use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Response\InteractionResponse;
use Wirecard\PaymentSdk\Transaction\PayPalTransaction;
use Wirecard\PaymentSdk\TransactionService;

class PaypalPayment extends Payment
{
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
        return 'wirecard_elastic_engine_paypal';
    }

    /**
     * @inheritdoc
     */
    public function getPaymentOptions()
    {
        return [
            'name'                  => $this->getName(),
            'description'           => $this->getLabel(),
            'action'                => 'WirecardElasticEnginePayment',
            'active'                => 0,
            'position'              => 0,
            'additionalDescription' => '',
        ];
    }

    public function processPayment(array $paymentData)
    {
        $configData = $this->getConfigData();
        $config = new Config($configData['baseUrl'], $configData['httpUser'], $configData['httpPass']);
        $paypalConfig = new PaymentMethodConfig(PayPalTransaction::NAME, $configData['paypalMAID'], $configData['paypalKey']);
        $config->add($paypalConfig);

        $amount = new Amount($paymentData['amount'], $paymentData['currency']);

        $redirectUrls = new Redirect($paymentData['returnUrl'], $paymentData['cancleUrl']);
        $notificationUrl = $paymentData['notifyUrl'];

        $transaction = new PayPalTransaction();
        $transaction->setNotificationUrl($notificationUrl);
        $transaction->setRedirect($redirectUrls);
        $transaction->setAmount($amount);
 
        $basket = $this->createBasket($transaction, $paymentData['basket'], $paymentData['currency']);
        $transaction->setBasket($basket);

        $transactionService = new TransactionService($config);
        
        $response = null;
        if ($configData['transactionType'] == 'authorization') {
            $response = $transactionService->reserve($transaction);
        } elseif ($configData['transactionType'] == 'purchase') {
            $response = $transactionService->pay($transaction);
        } else {
            // TODO error handling
        }

        if ($response instanceof InteractionResponse) {
            return $response->getRedirectUrl();
        }

        var_dump($response);
        exit();
    }

    protected function getConfigData()
    {
        $baseUrl = Shopware()->Config()->getByNamespace('WirecardShopwareElasticEngine', 'wirecardElasticEngineServer');
        $httpUser = Shopware()->Config()->getByNamespace('WirecardShopwareElasticEngine', 'wirecardElasticEnginePaypalHttpUser');
        $httpPass = Shopware()->Config()->getByNamespace('WirecardShopwareElasticEngine', 'wirecardElasticEnginePaypalHttpPassword');

        $paypalMAID = Shopware()->Config()->getByNamespace('WirecardShopwareElasticEngine', 'wirecardElasticEnginePaypalMerchandId');
        $paypalKey = Shopware()->Config()->getByNamespace('WirecardShopwareElasticEngine', 'wirecardElasticEngineSecret');
        $transactionType = Shopware()->Config()->getByNamespace('WirecardShopwareElasticEngine', 'wirecardElasticEngineTransactionType');

        return [
            'baseUrl'         => $baseUrl,
            'httpUser'        => $httpUser,
            'httpPass'        => $httpPass,
            'paypalMAID'      => $paypalMAID,
            'paypalKey'       => $paypalKey,
            'transactionType' => $transactionType
        ];
    }
}
