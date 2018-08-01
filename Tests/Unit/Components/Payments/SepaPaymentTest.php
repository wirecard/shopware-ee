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

namespace WirecardElasticEngine\Tests\Unit\Components\Payments;

use Shopware\Models\Shop\Shop;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Wirecard\PaymentSdk\Config\Config;
use Wirecard\PaymentSdk\Config\PaymentMethodConfig;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Transaction\Operation;
use Wirecard\PaymentSdk\Transaction\SepaCreditTransferTransaction;
use Wirecard\PaymentSdk\Transaction\SepaDirectDebitTransaction;
use Wirecard\PaymentSdk\TransactionService;
use WirecardElasticEngine\Components\Data\OrderSummary;
use WirecardElasticEngine\Components\Data\PaymentConfig;
use WirecardElasticEngine\Components\Payments\Contracts\AdditionalViewAssignmentsInterface;
use WirecardElasticEngine\Components\Payments\Contracts\ProcessPaymentInterface;
use WirecardElasticEngine\Components\Payments\SepaPayment;
use WirecardElasticEngine\Exception\InsufficientDataException;
use WirecardElasticEngine\Exception\UnknownTransactionTypeException;
use WirecardElasticEngine\Tests\Unit\PaymentTestCase;
use WirecardElasticEngine\WirecardElasticEngine;

class SepaPaymentTest extends PaymentTestCase
{
    /** @var SepaPayment */
    private $payment;

    public function setUp()
    {
        parent::setUp();

        $this->config->method('getByNamespace')->willReturnMap([
            [WirecardElasticEngine::NAME, 'wirecardElasticEngineSepaMerchantId', null, 'DD-MAID'],
            [WirecardElasticEngine::NAME, 'wirecardElasticEngineSepaSecret', null, 'DD-Secret'],
            [WirecardElasticEngine::NAME, 'wirecardElasticEngineSepaBackendMerchantId', null, 'CT-MAID'],
            [WirecardElasticEngine::NAME, 'wirecardElasticEngineSepaBackendSecret', null, 'CT-Secret'],
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
        $parameters->method('get')->willReturnMap([
            ['kernel.name', 'Shopware'],
            ['shopware.release.version', '__SW_VERSION__'],
        ]);

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
            [WirecardElasticEngine::NAME, 'wirecardElasticEngineSepaTransactionType', null, 'pay'],
        ]);
        $payment = new SepaPayment($this->em, $config, $this->installer, $this->router, $this->eventManager);
        $this->assertEquals('purchase', $payment->getTransactionType());

        $config = $this->createMock(\Shopware_Components_Config::class);
        $config->method('getByNamespace')->willReturnMap([
            [WirecardElasticEngine::NAME, 'wirecardElasticEngineSepaTransactionType', null, 'reserve'],
        ]);
        $payment = new SepaPayment($this->em, $config, $this->installer, $this->router, $this->eventManager);
        $this->assertEquals('authorization', $payment->getTransactionType());
    }

    public function testProcessPayment()
    {
        $this->assertInstanceOf(ProcessPaymentInterface::class, $this->payment);

        $orderSummary = $this->createMock(OrderSummary::class);
        $orderSummary->method('getAdditionalPaymentData')->willReturn([
            'sepaConfirmMandate' => 'confirmed',
            'sepaIban'           => 'I-B-A-N',
            'sepaFirstName'      => 'Firstname',
            'sepaLastName'       => 'Lastname',
        ]);
        $orderSummary->method('getPaymentUniqueId')->willReturn('1532501234exxxf');
        $transactionService = $this->createMock(TransactionService::class);
        $shop               = $this->createMock(Shop::class);
        $redirect           = $this->createMock(Redirect::class);
        $request            = $this->createMock(\Enlight_Controller_Request_Request::class);
        $order              = $this->createMock(\sOrder::class);

        $this->assertNull($this->payment->processPayment(
            $orderSummary,
            $transactionService,
            $shop,
            $redirect,
            $request,
            $order
        ));
        $transaction = $this->payment->getTransaction();
        $transaction->setOperation(Operation::PAY);
        $this->assertArraySubset([
            'account-holder'   => [
                'last-name'  => 'Lastname',
                'first-name' => 'Firstname',
            ],
            'transaction-type' => 'debit',
            'bank-account'     => [
                'iban' => 'I-B-A-N',
            ],
            'mandate'          => [
                'mandate-id'  => '-exxxf-1532501234',
                'signed-date' => date('Y-m-d'),
            ],
        ], $transaction->mappedProperties());
    }

    public function testProcessPaymentInsufficientDataException()
    {
        $this->assertInstanceOf(ProcessPaymentInterface::class, $this->payment);

        $orderSummary       = $this->createMock(OrderSummary::class);
        $transactionService = $this->createMock(TransactionService::class);
        $shop               = $this->createMock(Shop::class);
        $redirect           = $this->createMock(Redirect::class);
        $request            = $this->createMock(\Enlight_Controller_Request_Request::class);
        $order              = $this->createMock(\sOrder::class);

        $this->expectException(InsufficientDataException::class);
        $this->payment->processPayment(
            $orderSummary,
            $transactionService,
            $shop,
            $redirect,
            $request,
            $order
        );
    }

    public function testGetAdditionalViewAssignments()
    {
        $this->assertInstanceOf(AdditionalViewAssignmentsInterface::class, $this->payment);
        $this->assertEquals([
            'method'          => 'wirecard_elastic_engine_sepa',
            'showBic'         => false,
            'creditorId'      => null,
            'creditorName'    => null,
            'creditorAddress' => null,
        ], $this->payment->getAdditionalViewAssignments());
    }
}
