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
use Wirecard\PaymentSdk\Entity\IdealBic;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Transaction\IdealTransaction;
use Wirecard\PaymentSdk\Transaction\Operation;
use Wirecard\PaymentSdk\Transaction\SepaCreditTransferTransaction;
use Wirecard\PaymentSdk\Transaction\Transaction;
use Wirecard\PaymentSdk\TransactionService;
use WirecardElasticEngine\Components\Data\OrderSummary;
use WirecardElasticEngine\Components\Data\PaymentConfig;
use WirecardElasticEngine\Components\Payments\Contracts\AdditionalViewAssignmentsInterface;
use WirecardElasticEngine\Components\Payments\Contracts\ProcessPaymentInterface;
use WirecardElasticEngine\Components\Payments\IdealPayment;
use WirecardElasticEngine\Components\Services\SessionManager;
use WirecardElasticEngine\Tests\Unit\PaymentTestCase;
use WirecardElasticEngine\WirecardElasticEngine;

class IdealPaymentTest extends PaymentTestCase
{
    /** @var IdealPayment */
    private $payment;

    public function setUp()
    {
        parent::setUp();

        $this->config->method('getByNamespace')->willReturnMap([
            [WirecardElasticEngine::NAME, 'wirecardElasticEngineIdealMerchantId', null, 'MAID'],
            [WirecardElasticEngine::NAME, 'wirecardElasticEngineIdealSecret', null, 'Secret'],
            [WirecardElasticEngine::NAME, 'wirecardElasticEngineSepaBackendMerchantId', null, 'CT-MAID'],
            [WirecardElasticEngine::NAME, 'wirecardElasticEngineSepaBackendSecret', null, 'CT-Secret'],
        ]);

        $this->payment = new IdealPayment(
            $this->em,
            $this->config,
            $this->installer,
            $this->router,
            $this->eventManager,
            $this->snippetManager
        );
    }

    public function testGetPaymentOptions()
    {
        $this->assertEquals('WirecardiDEAL', $this->payment->getLabel());
        $this->assertEquals('wirecard_elastic_engine_ideal', $this->payment->getName());
        $this->assertEquals(3, $this->payment->getPosition());
        $this->assertPaymentOptions(
            $this->payment->getPaymentOptions(),
            'wirecard_elastic_engine_ideal',
            'WirecardiDEAL',
            3
        );
    }

    public function testGetTransaction()
    {
        $transaction = $this->payment->getTransaction();
        $this->assertInstanceOf(IdealTransaction::class, $transaction);
        $this->assertSame($transaction, $this->payment->getTransaction());
    }

    public function testGetBackendTransaction()
    {
        $entity        = $this->createMock(\WirecardElasticEngine\Models\Transaction::class);
        $paymentMethod = $entity->method('getPaymentMethod');
        $paymentMethod->willReturn(IdealTransaction::NAME);
        $transactionType = $entity->method('getTransactionType');
        $transactionType->willReturn(null);

        $order       = new Order();
        $transaction = $this->payment->getBackendTransaction($order, Operation::REFUND, $entity);
        $this->assertInstanceOf(IdealTransaction::class, $transaction);
        $this->assertNotSame($transaction, $this->payment->getTransaction());
        $transactionType->willReturn(null);
        $this->assertNotSame($transaction, $this->payment->getBackendTransaction($order, Operation::REFUND, $entity));

        $transactionType->willReturn(Transaction::TYPE_CREDIT);
        $transaction = $this->payment->getBackendTransaction($order, null, $entity);
        $this->assertInstanceOf(SepaCreditTransferTransaction::class, $transaction);
        $transactionType->willReturn(null);
        $transaction = $this->payment->getBackendTransaction($order, Operation::CREDIT, $entity);
        $this->assertInstanceOf(SepaCreditTransferTransaction::class, $transaction);

        $transaction = $this->payment->getBackendTransaction($order, Operation::CANCEL, $entity);
        $this->assertInstanceOf(SepaCreditTransferTransaction::class, $transaction);

        $paymentMethod->willReturn(SepaCreditTransferTransaction::NAME);
        $transaction = $this->payment->getBackendTransaction($order, Operation::REFUND, $entity);
        $this->assertInstanceOf(SepaCreditTransferTransaction::class, $transaction);

        $transactionType->willReturn(Transaction::TYPE_CREDIT);
        $transaction = $this->payment->getBackendTransaction($order, Operation::CREDIT, $entity);
        $this->assertInstanceOf(SepaCreditTransferTransaction::class, $transaction);

        $transactionType->willReturn(null);
        $transaction = $this->payment->getBackendTransaction($order, Operation::CANCEL, $entity);
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

        $sofortConfig = $config->get(IdealTransaction::NAME);
        $this->assertInstanceOf(PaymentMethodConfig::class, $sofortConfig);
        $this->assertEquals('MAID', $sofortConfig->getMerchantAccountId());
        $this->assertEquals('Secret', $sofortConfig->getSecret());
        $this->assertEquals(IdealTransaction::NAME, $sofortConfig->getPaymentMethodName());

        $sofortCreditTransferConfig = $config->get(SepaCreditTransferTransaction::NAME);
        $this->assertInstanceOf(PaymentMethodConfig::class, $sofortCreditTransferConfig);
        $this->assertEquals('CT-MAID', $sofortCreditTransferConfig->getMerchantAccountId());
        $this->assertEquals('CT-Secret', $sofortCreditTransferConfig->getSecret());
        $this->assertEquals(SepaCreditTransferTransaction::NAME, $sofortCreditTransferConfig->getPaymentMethodName());
        $this->assertEquals([
            'headers' => [
                'shop-system-name'    => 'Shopware',
                'shop-system-version' => '__SW_VERSION__',
                'plugin-name'         => 'WirecardElasticEngine',
                'plugin-version'      => '__PLUGIN_VERSION__',
            ],
        ], $config->getShopHeader());
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
            [WirecardElasticEngine::NAME, 'wirecardElasticEngineIdealTransactionType', null, 'pay'],
        ]);
        $payment = new IdealPayment(
            $this->em,
            $config,
            $this->installer,
            $this->router,
            $this->eventManager,
            $this->snippetManager
        );
        $this->assertEquals('purchase', $payment->getTransactionType());

        $payment = new IdealPayment(
            $this->em,
            $config,
            $this->installer,
            $this->router,
            $this->eventManager,
            $this->snippetManager
        );
        $this->assertEquals('purchase', $payment->getTransactionType());
    }

    public function testProcessPayment()
    {
        $this->assertInstanceOf(ProcessPaymentInterface::class, $this->payment);

        $orderSummary = $this->createMock(OrderSummary::class);
        $orderSummary->method('getAdditionalPaymentData')->willReturn([
            'idealBank' => 'INGBNL2A',
        ]);
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
        $this->assertNull($transaction->getOrderNumber());
    }

    public function testGetAdditionalViewAssignments()
    {
        $sessionManager = $this->createMock(SessionManager::class);

        $idealBic = new \ReflectionClass(IdealBic::class);

        $this->assertInstanceOf(AdditionalViewAssignmentsInterface::class, $this->payment);
        $this->assertEquals([
            'method'     => 'wirecard_elastic_engine_ideal',
            'idealBanks' => $idealBic->getConstants(),
        ], $this->payment->getAdditionalViewAssignments($sessionManager));
    }
}
