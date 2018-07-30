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

namespace WirecardShopwareElasticEngine\Tests\Unit\Components\Payments;

use Shopware\Models\Shop\Shop;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Wirecard\PaymentSdk\Config\Config;
use Wirecard\PaymentSdk\Config\PaymentMethodConfig;
use Wirecard\PaymentSdk\Transaction\PayPalTransaction;
use WirecardShopwareElasticEngine\Components\Data\PaymentConfig;
use WirecardShopwareElasticEngine\Components\Payments\PaypalPayment;
use WirecardShopwareElasticEngine\Exception\UnknownTransactionTypeException;
use WirecardShopwareElasticEngine\Tests\Unit\PaymentTestCase;
use WirecardShopwareElasticEngine\WirecardShopwareElasticEngine;

class PaypalPaymentTest extends PaymentTestCase
{
    /** @var PaypalPayment */
    private $payment;

    public function setUp()
    {
        parent::setUp();

        $this->config->method('getByNamespace')->willReturnMap([
            [WirecardShopwareElasticEngine::NAME, 'wirecardElasticEnginePaypalMerchantId', null, 'MAID'],
            [WirecardShopwareElasticEngine::NAME, 'wirecardElasticEnginePaypalSecret', null, 'Secret'],
        ]);

        $this->payment = new PaypalPayment(
            $this->em,
            $this->config,
            $this->installer,
            $this->router,
            $this->eventManager
        );
    }

    public function testGetPaymentOptions()
    {
        $this->assertEquals('WirecardPayPal', $this->payment->getLabel());
        $this->assertEquals('wirecard_elastic_engine_paypal', $this->payment->getName());
        $this->assertEquals(1, $this->payment->getPosition());
        $this->assertPaymentOptions(
            $this->payment->getPaymentOptions(),
            'wirecard_elastic_engine_paypal',
            'WirecardPayPal',
            1
        );
    }

    public function testGetTransaction()
    {
        $transaction = $this->payment->getTransaction();
        $this->assertInstanceOf(PayPalTransaction::class, $transaction);
        $this->assertSame($transaction, $this->payment->getTransaction());
    }

    public function testGetPaymentConfig()
    {
        $config = $this->payment->getPaymentConfig();

        $this->assertInstanceOf(PaymentConfig::class, $config);
        $this->assertNull($config->getBaseUrl());
        $this->assertNull($config->getHttpUser());
        $this->assertNull($config->getHttpPassword());
    }

    public function testGetTransactionConfig()
    {
        /** @var Shop|\PHPUnit_Framework_MockObject_MockObject $shop */
        /** @var ParameterBagInterface|\PHPUnit_Framework_MockObject_MockObject $parameters */

        $shop       = $this->createMock(Shop::class);
        $parameters = $this->createMock(ParameterBagInterface::class);
        $parameters->method('get')->willReturnMap([
            ['kernel.name', 'Shopware'],
            ['shopware.release.version', '__SW_VERSION__'],
        ]);

        $config = $this->payment->getTransactionConfig($shop, $parameters, 'EUR');

        $this->assertInstanceOf(Config::class, $config);
        $this->assertNull($config->getBaseUrl());
        $this->assertNull($config->getHttpUser());
        $this->assertNull($config->getHttpPassword());

        $paymentMethodConfig = $config->get(PayPalTransaction::NAME);
        $this->assertInstanceOf(PaymentMethodConfig::class, $paymentMethodConfig);
        $this->assertEquals('MAID', $paymentMethodConfig->getMerchantAccountId());
        $this->assertEquals('Secret', $paymentMethodConfig->getSecret());
        $this->assertEquals(PayPalTransaction::NAME, $paymentMethodConfig->getPaymentMethodName());
        $this->assertEquals([
            'headers' => [
                'shop-system-name'    => 'Shopware',
                'shop-system-version' => '__SW_VERSION__',
                'plugin-name'         => 'WirecardShopwareElasticEngine',
                'plugin-version'      => '__PLUGIN_VERSION__',
            ],
        ], $config->getShopHeader());
    }

    public function testGetTransactionTypeException()
    {
        $this->expectException(UnknownTransactionTypeException::class);
        $this->assertEquals('', $this->payment->getTransactionType());
    }

    public function testGetTransactionType()
    {
        /** @var \Shopware_Components_Config|\PHPUnit_Framework_MockObject_MockObject $config */
        $config = $this->createMock(\Shopware_Components_Config::class);
        $config->method('getByNamespace')->willReturnMap([
            [WirecardShopwareElasticEngine::NAME, 'wirecardElasticEnginePaypalTransactionType', null, 'pay'],
        ]);
        $payment = new PaypalPayment($this->em, $config, $this->installer, $this->router, $this->eventManager);
        $this->assertEquals('purchase', $payment->getTransactionType());

        $config = $this->createMock(\Shopware_Components_Config::class);
        $config->method('getByNamespace')->willReturnMap([
            [WirecardShopwareElasticEngine::NAME, 'wirecardElasticEnginePaypalTransactionType', null, 'reserve'],
        ]);
        $payment = new PaypalPayment($this->em, $config, $this->installer, $this->router, $this->eventManager);
        $this->assertEquals('authorization', $payment->getTransactionType());
    }
}
