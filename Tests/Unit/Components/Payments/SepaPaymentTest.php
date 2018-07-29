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
use Wirecard\PaymentSdk\Transaction\SepaDirectDebitTransaction;
use WirecardShopwareElasticEngine\Components\Data\PaymentConfig;
use WirecardShopwareElasticEngine\Components\Payments\SepaPayment;
use WirecardShopwareElasticEngine\Exception\UnknownTransactionTypeException;
use WirecardShopwareElasticEngine\Tests\Unit\PaymentTestCase;
use WirecardShopwareElasticEngine\WirecardShopwareElasticEngine;

class SepaPaymentTest extends PaymentTestCase
{
    /** @var SepaPayment */
    private $payment;

    public function setUp()
    {
        parent::setUp();

        $this->config->method('getByNamespace')->willReturnMap([
            [WirecardShopwareElasticEngine::NAME, 'wirecardElasticEngineSepaMerchantId', null, 'DD-MAID'],
            [WirecardShopwareElasticEngine::NAME, 'wirecardElasticEngineSepaSecret', null, 'DD-Secret'],
            [WirecardShopwareElasticEngine::NAME, 'wirecardElasticEngineSepaBackendMerchantId', null, 'CT-MAID'],
            [WirecardShopwareElasticEngine::NAME, 'wirecardElasticEngineSepaBackendSecret', null, 'CT-Secret'],
        ]);

        $this->payment = new SepaPayment(
            $this->em,
            $this->config,
            $this->installer,
            $this->router,
            $this->eventManager
        );
    }

    public function testGetPaymentOptions()
    {
        $this->assertEquals('WirecardSEPADirectDebit', $this->payment->getLabel());
        $this->assertEquals('wirecard_elastic_engine_sepa', $this->payment->getName());
        $this->assertEquals(8, $this->payment->getPosition());
        $this->assertPaymentOptions(
            $this->payment->getPaymentOptions(),
            'wirecard_elastic_engine_sepa',
            'WirecardSEPADirectDebit',
            8
        );
    }

    public function testGetTransaction()
    {
        $transaction = $this->payment->getTransaction();
        $this->assertInstanceOf(SepaDirectDebitTransaction::class, $transaction);
        $this->assertSame($transaction, $this->payment->getTransaction());
    }

    public function testGetBackendTransaction()
    {
        $transaction = $this->payment->getBackendTransaction(Operation::REFUND, SepaDirectDebitTransaction::NAME);
        $this->assertInstanceOf(SepaDirectDebitTransaction::class, $transaction);
        $this->assertNotSame($transaction, $this->payment->getTransaction());
        $this->assertNotSame($transaction, $this->payment->getBackendTransaction(
            Operation::REFUND,
            SepaDirectDebitTransaction::NAME
        ));

        $transaction = $this->payment->getBackendTransaction(Operation::CREDIT, SepaDirectDebitTransaction::NAME);
        $this->assertInstanceOf(SepaCreditTransferTransaction::class, $transaction);

        $transaction = $this->payment->getBackendTransaction(Operation::REFUND, SepaCreditTransferTransaction::NAME);
        $this->assertInstanceOf(SepaCreditTransferTransaction::class, $transaction);

        $transaction = $this->payment->getBackendTransaction(Operation::CREDIT, SepaCreditTransferTransaction::NAME);
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

        $sepaDirectDebitConfig = $config->get(SepaDirectDebitTransaction::NAME);
        $this->assertInstanceOf(PaymentMethodConfig::class, $sepaDirectDebitConfig);
        $this->assertEquals('DD-MAID', $sepaDirectDebitConfig->getMerchantAccountId());
        $this->assertEquals('DD-Secret', $sepaDirectDebitConfig->getSecret());
        $this->assertEquals(SepaDirectDebitTransaction::NAME, $sepaDirectDebitConfig->getPaymentMethodName());

        $sepaCreditTransferConfig = $config->get(SepaCreditTransferTransaction::NAME);
        $this->assertInstanceOf(PaymentMethodConfig::class, $sepaCreditTransferConfig);
        $this->assertEquals('CT-MAID', $sepaCreditTransferConfig->getMerchantAccountId());
        $this->assertEquals('CT-Secret', $sepaCreditTransferConfig->getSecret());
        $this->assertEquals(SepaCreditTransferTransaction::NAME, $sepaCreditTransferConfig->getPaymentMethodName());
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
            [WirecardShopwareElasticEngine::NAME, 'wirecardElasticEngineSepaTransactionType', null, 'pay'],
        ]);
        $payment = new SepaPayment($this->em, $config, $this->installer, $this->router, $this->eventManager);
        $this->assertEquals('purchase', $payment->getTransactionType());

        $config = $this->createMock(\Shopware_Components_Config::class);
        $config->method('getByNamespace')->willReturnMap([
            [WirecardShopwareElasticEngine::NAME, 'wirecardElasticEngineSepaTransactionType', null, 'reserve'],
        ]);
        $payment = new SepaPayment($this->em, $config, $this->installer, $this->router, $this->eventManager);
        $this->assertEquals('authorization', $payment->getTransactionType());
    }
}
