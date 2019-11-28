<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

namespace WirecardElasticEngine\Tests\Unit\Components\Payments;

use Shopware\Models\Order\Order;
use Shopware\Models\Shop\Shop;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Wirecard\PaymentSdk\Config\Config;
use Wirecard\PaymentSdk\Config\PaymentMethodConfig;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Transaction\Operation;
use Wirecard\PaymentSdk\Transaction\SepaCreditTransferTransaction;
use Wirecard\PaymentSdk\Transaction\SepaDirectDebitTransaction;
use Wirecard\PaymentSdk\Transaction\Transaction;
use Wirecard\PaymentSdk\TransactionService;
use WirecardElasticEngine\Components\Data\OrderSummary;
use WirecardElasticEngine\Components\Data\PaymentConfig;
use WirecardElasticEngine\Components\Payments\Contracts\AdditionalViewAssignmentsInterface;
use WirecardElasticEngine\Components\Payments\Contracts\ProcessPaymentInterface;
use WirecardElasticEngine\Components\Payments\SepaPayment;
use WirecardElasticEngine\Components\Services\SessionManager;
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
        $entity        = $this->createMock(\WirecardElasticEngine\Models\Transaction::class);
        $paymentMethod = $entity->method('getPaymentMethod');
        $paymentMethod->willReturn(SepaDirectDebitTransaction::NAME);
        $transactionType = $entity->method('getTransactionType');
        $transactionType->willReturn(null);

        $order       = new Order();
        $transaction = $this->payment->getBackendTransaction($order, Operation::REFUND, $entity);
        $this->assertInstanceOf(SepaDirectDebitTransaction::class, $transaction);
        $this->assertNotSame($transaction, $this->payment->getTransaction());
        $this->assertNotSame($transaction, $this->payment->getBackendTransaction($order, Operation::REFUND, $entity));

        $transactionType->willReturn(Transaction::TYPE_CREDIT);
        $transaction = $this->payment->getBackendTransaction($order, Operation::CREDIT, $entity);
        $this->assertInstanceOf(SepaCreditTransferTransaction::class, $transaction);
        $transaction = $this->payment->getBackendTransaction($order, null, $entity);
        $this->assertInstanceOf(SepaCreditTransferTransaction::class, $transaction);
        $transactionType->willReturn(null);
        $transaction = $this->payment->getBackendTransaction($order, Operation::CREDIT, $entity);
        $this->assertInstanceOf(SepaCreditTransferTransaction::class, $transaction);

        $paymentMethod->willReturn(SepaCreditTransferTransaction::NAME);
        $transaction = $this->payment->getBackendTransaction($order, Operation::REFUND, $entity);
        $this->assertInstanceOf(SepaCreditTransferTransaction::class, $transaction);

        $transactionType->willReturn(Transaction::TYPE_CREDIT);
        $transaction = $this->payment->getBackendTransaction($order, null, $entity);
        $this->assertInstanceOf(SepaCreditTransferTransaction::class, $transaction);
        $transaction = $this->payment->getBackendTransaction($order, Operation::CREDIT, $entity);
        $this->assertInstanceOf(SepaCreditTransferTransaction::class, $transaction);
        $transactionType->willReturn(null);
        $transaction = $this->payment->getBackendTransaction($order, null, $entity);
        $this->assertInstanceOf(SepaCreditTransferTransaction::class, $transaction);
        $paymentMethod->willReturn(null);
        $transaction = $this->payment->getBackendTransaction($order, Operation::CREDIT, $entity);
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

        $config = $this->payment->getTransactionConfig($parameters, 'EUR');

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
                'mandate-id'  => '-1532501234',
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
        $sessionManager = $this->createMock(SessionManager::class);

        $this->assertInstanceOf(AdditionalViewAssignmentsInterface::class, $this->payment);
        $this->assertEquals([
            'method'          => 'wirecard_elastic_engine_sepa',
            'showBic'         => false,
            'creditorId'      => null,
            'creditorName'    => null,
            'creditorAddress' => null,
        ], $this->payment->getAdditionalViewAssignments($sessionManager));
    }
}
