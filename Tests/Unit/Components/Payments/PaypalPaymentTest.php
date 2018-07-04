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

namespace WirecardShopwareElasticEngine\Tests\Functional\Components\Payments;

use Wirecard\PaymentSdk\Config\Config;
use Wirecard\PaymentSdk\Config\PaymentMethodConfig;
use Wirecard\PaymentSdk\Transaction\PayPalTransaction;
use WirecardShopwareElasticEngine\Components\Payments\Payment;
use WirecardShopwareElasticEngine\Components\Payments\PaymentInterface;
use WirecardShopwareElasticEngine\Components\Payments\PaypalPayment;
use WirecardShopwareElasticEngine\Tests\Unit\PaymentTestCase;

class PaypalPaymentTest extends PaymentTestCase
{
    /** @var PaymentInterface */
    protected $payment;

    public function setUp()
    {
        $this->payment = new PaypalPayment();
    }

    public function testGetPaymentOptions()
    {
        $this->assertPaymentOptions($this->payment->getPaymentOptions(), 'wirecard_elastic_engine_paypal', 'Wirecard PayPal');
    }

    public function testGetTransaction()
    {
        $this->assertInstanceOf(PayPalTransaction::class, $this->payment->getTransaction());
    }

    public function testGetConfig()
    {
        $configData = $this->payment->getConfigData();
        $config = $this->payment->getConfig($configData);

        $this->assertInstanceOf(Config::class, $config);
        $this->assertSame($configData['baseUrl'], $config->getBaseUrl());
        $this->assertSame($configData['httpUser'], $config->getHttpUser());
        $this->assertSame($configData['httpPass'], $config->getHttpPassword());
        $this->assertInstanceOf(PaymentMethodConfig::class, $config->get(PayPalTransaction::NAME));
        $this->assertSame($configData['transactionMAID'], $config->get(PayPalTransaction::NAME)->getMerchantAccountId());
        $this->assertSame($configData['transactionKey'], $config->get(PayPalTransaction::NAME)->getSecret());
    }

    public function testGetConfigData()
    {
        $this->assertConfigData([
            'baseUrl'         => 'https://api-test.wirecard.com',
            'httpUser'        => '70000-APITEST-AP',
            'httpPass'        => 'qD2wzQ_hrc!8',
            'transactionMAID' => '2a0e9351-24ed-4110-9a1b-fd0fee6bec26',
            'transactionKey'  => 'dbc5a498-9a66-43b9-bf1d-a618dd399684',
            'transactionType' => Payment::TRANSACTION_TYPE_PURCHASE,
            'sendBasket'      => true,
            'fraudPrevention' => true,
            'descriptor'      => true
        ], $this->payment->getConfigData());
    }
}
