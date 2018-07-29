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
use Wirecard\PaymentSdk\Transaction\Operation;
use Wirecard\PaymentSdk\Transaction\SepaCreditTransferTransaction;
use Wirecard\PaymentSdk\Transaction\SofortTransaction;
use WirecardShopwareElasticEngine\Components\Data\PaymentConfig;
use WirecardShopwareElasticEngine\Components\Payments\SofortPayment;
use WirecardShopwareElasticEngine\Tests\Unit\PaymentTestCase;
use WirecardShopwareElasticEngine\WirecardShopwareElasticEngine;

class SofortPaymentTest extends PaymentTestCase
{
    /** @var SofortPayment */
    private $payment;

    public function setUp()
    {
        parent::setUp();

        $this->config->method('getByNamespace')->willReturnMap([
            [WirecardShopwareElasticEngine::NAME, 'wirecardElasticEngineSofortMerchantId', null, 'MAID'],
            [WirecardShopwareElasticEngine::NAME, 'wirecardElasticEngineSofortSecret', null, 'Secret'],
            [WirecardShopwareElasticEngine::NAME, 'wirecardElasticEngineSepaBackendMerchantId', null, 'CT-MAID'],
            [WirecardShopwareElasticEngine::NAME, 'wirecardElasticEngineSepaBackendSecret', null, 'CT-Secret'],
        ]);

        $this->payment = new SofortPayment(
            $this->em,
            $this->config,
            $this->installer,
            $this->router,
            $this->eventManager
        );
    }

    public function testGetPaymentOptions()
    {
        $this->assertEquals('WirecardSofort', $this->payment->getLabel());
        $this->assertEquals('wirecard_elastic_engine_sofort', $this->payment->getName());
        $this->assertEquals(9, $this->payment->getPosition());
        $this->assertPaymentOptions(
            $this->payment->getPaymentOptions(),
            'wirecard_elastic_engine_sofort',
            'WirecardSofort',
            9
        );
    }

    public function testGetTransaction()
    {
        $transaction = $this->payment->getTransaction();
        $this->assertInstanceOf(SofortTransaction::class, $transaction);
        $this->assertSame($transaction, $this->payment->getTransaction());
    }

    public function testGetBackendTransaction()
    {
        $transaction = $this->payment->getBackendTransaction(Operation::REFUND, SofortTransaction::NAME);
        $this->assertInstanceOf(SofortTransaction::class, $transaction);
        $this->assertNotSame($transaction, $this->payment->getTransaction());
        $this->assertNotSame($transaction, $this->payment->getBackendTransaction(
            Operation::REFUND,
            SofortTransaction::NAME
        ));

        $transaction = $this->payment->getBackendTransaction(Operation::CREDIT, SofortTransaction::NAME);
        $this->assertInstanceOf(SepaCreditTransferTransaction::class, $transaction);

        $transaction = $this->payment->getBackendTransaction(Operation::CANCEL, SofortTransaction::NAME);
        $this->assertInstanceOf(SepaCreditTransferTransaction::class, $transaction);

        $transaction = $this->payment->getBackendTransaction(Operation::REFUND, SepaCreditTransferTransaction::NAME);
        $this->assertInstanceOf(SepaCreditTransferTransaction::class, $transaction);

        $transaction = $this->payment->getBackendTransaction(Operation::CREDIT, SepaCreditTransferTransaction::NAME);
        $this->assertInstanceOf(SepaCreditTransferTransaction::class, $transaction);

        $transaction = $this->payment->getBackendTransaction(Operation::CANCEL, SepaCreditTransferTransaction::NAME);
        $this->assertInstanceOf(SepaCreditTransferTransaction::class, $transaction);
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

        $config = $this->payment->getTransactionConfig($shop, $parameters, 'EUR');

        $this->assertInstanceOf(Config::class, $config);
        $this->assertNull($config->getBaseUrl());
        $this->assertNull($config->getHttpUser());
        $this->assertNull($config->getHttpPassword());

        $sofortConfig = $config->get(SofortTransaction::NAME);
        $this->assertInstanceOf(PaymentMethodConfig::class, $sofortConfig);
        $this->assertEquals('MAID', $sofortConfig->getMerchantAccountId());
        $this->assertEquals('Secret', $sofortConfig->getSecret());
        $this->assertEquals(SofortTransaction::NAME, $sofortConfig->getPaymentMethodName());

        $sofortCreditTransferConfig = $config->get(SepaCreditTransferTransaction::NAME);
        $this->assertInstanceOf(PaymentMethodConfig::class, $sofortCreditTransferConfig);
        $this->assertEquals('CT-MAID', $sofortCreditTransferConfig->getMerchantAccountId());
        $this->assertEquals('CT-Secret', $sofortCreditTransferConfig->getSecret());
        $this->assertEquals(SepaCreditTransferTransaction::NAME, $sofortCreditTransferConfig->getPaymentMethodName());
    }

    public function testGetTransactionTypePurchase()
    {
        $this->assertEquals('purchase', $this->payment->getTransactionType());
    }

    public function testGetTransactionType()
    {
        /** @var \Shopware_Components_Config|\PHPUnit_Framework_MockObject_MockObject $config */
        $config = $this->createMock(\Shopware_Components_Config::class);
        $config->method('getByNamespace')->willReturnMap([
            [WirecardShopwareElasticEngine::NAME, 'wirecardElasticEngineSofortTransactionType', null, 'pay'],
        ]);
        $payment = new SofortPayment($this->em, $config, $this->installer, $this->router, $this->eventManager);
        $this->assertEquals('purchase', $payment->getTransactionType());

        $payment = new SofortPayment($this->em, $config, $this->installer, $this->router, $this->eventManager);
        $this->assertEquals('purchase', $payment->getTransactionType());
    }
}
