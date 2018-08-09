<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

namespace WirecardElasticEngine\Tests\Unit\Components\Payments;

use Shopware\Models\Shop\Shop;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Wirecard\PaymentSdk\Config\Config;
use Wirecard\PaymentSdk\Config\PaymentMethodConfig;
use Wirecard\PaymentSdk\Transaction\CreditCardTransaction;
use Wirecard\PaymentSdk\Transaction\MasterpassTransaction;
use Wirecard\PaymentSdk\Transaction\Operation;
use Wirecard\PaymentSdk\Transaction\Transaction;
use WirecardElasticEngine\Components\Data\PaymentConfig;
use WirecardElasticEngine\Components\Payments\MasterpassPayment;
use WirecardElasticEngine\Exception\UnknownTransactionTypeException;
use WirecardElasticEngine\Tests\Unit\PaymentTestCase;
use WirecardElasticEngine\WirecardElasticEngine;

class MasterpassPaymentTest extends PaymentTestCase
{
    /** @var MasterpassPayment */
    private $payment;

    public function setUp()
    {
        parent::setUp();

        $this->config->method('getByNamespace')->willReturnMap([
            [WirecardElasticEngine::NAME, 'wirecardElasticEngineMasterpassMerchantId', null, 'MAID'],
            [WirecardElasticEngine::NAME, 'wirecardElasticEngineMasterpassSecret', null, 'Secret'],
        ]);

        $this->payment = new MasterpassPayment(
            $this->em,
            $this->config,
            $this->installer,
            $this->router,
            $this->eventManager
        );
    }

    public function testGetPaymentOptions()
    {
        $this->assertEquals('WirecardMasterpass', $this->payment->getLabel());
        $this->assertEquals('wirecard_elastic_engine_masterpass', $this->payment->getName());
        $this->assertEquals(4, $this->payment->getPosition());
        $this->assertPaymentOptions(
            $this->payment->getPaymentOptions(),
            'wirecard_elastic_engine_masterpass',
            'WirecardMasterpass',
            4
        );
    }

    public function testGetTransaction()
    {
        $transaction = $this->payment->getTransaction();
        $this->assertInstanceOf(MasterpassTransaction::class, $transaction);
        $this->assertSame($transaction, $this->payment->getTransaction());
    }

    public function testGetBackendTransaction()
    {
        $masterpass  = MasterpassTransaction::NAME;
        $transaction = $this->payment->getBackendTransaction(Operation::CANCEL, $masterpass, null);
        $this->assertInstanceOf(MasterpassTransaction::class, $transaction);
        $this->assertNotSame($transaction, $this->payment->getTransaction());

        $this->assertNull($this->payment->getBackendTransaction(null, $masterpass, Transaction::TYPE_DEBIT));
        $this->assertNull($this->payment->getBackendTransaction(null, $masterpass, Transaction::TYPE_AUTHORIZATION));

        $creditcard  = CreditCardTransaction::NAME;
        $transaction = $this->payment->getBackendTransaction(null, $creditcard, Transaction::TYPE_DEBIT);
        $this->assertInstanceOf(MasterpassTransaction::class, $transaction);
        $transaction = $this->payment->getBackendTransaction(null, $creditcard, Transaction::TYPE_AUTHORIZATION);
        $this->assertInstanceOf(MasterpassTransaction::class, $transaction);
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

        $paymentMethodConfig = $config->get(MasterpassTransaction::NAME);
        $this->assertInstanceOf(PaymentMethodConfig::class, $paymentMethodConfig);
        $this->assertEquals('MAID', $paymentMethodConfig->getMerchantAccountId());
        $this->assertEquals('Secret', $paymentMethodConfig->getSecret());
        $this->assertEquals(MasterpassTransaction::NAME, $paymentMethodConfig->getPaymentMethodName());
        $this->assertEquals([
            'headers' => [
                'shop-system-name'    => 'Shopware',
                'shop-system-version' => '__SW_VERSION__',
                'plugin-name'         => 'WirecardElasticEngine',
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
            [WirecardElasticEngine::NAME, 'wirecardElasticEngineMasterpassTransactionType', null, 'pay'],
        ]);
        $payment = new MasterpassPayment($this->em, $config, $this->installer, $this->router, $this->eventManager);
        $this->assertEquals('purchase', $payment->getTransactionType());

        $config = $this->createMock(\Shopware_Components_Config::class);
        $config->method('getByNamespace')->willReturnMap([
            [WirecardElasticEngine::NAME, 'wirecardElasticEngineMasterpassTransactionType', null, 'reserve'],
        ]);
        $payment = new MasterpassPayment($this->em, $config, $this->installer, $this->router, $this->eventManager);
        $this->assertEquals('authorization', $payment->getTransactionType());
    }
}
