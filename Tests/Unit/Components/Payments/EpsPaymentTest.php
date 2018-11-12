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
use Wirecard\PaymentSdk\Transaction\EpsTransaction;
use Wirecard\PaymentSdk\Transaction\Operation;
use Wirecard\PaymentSdk\Transaction\SepaCreditTransferTransaction;
use Wirecard\PaymentSdk\Transaction\Transaction;
use Wirecard\PaymentSdk\TransactionService;
use WirecardElasticEngine\Components\Data\OrderSummary;
use WirecardElasticEngine\Components\Data\PaymentConfig;
use WirecardElasticEngine\Components\Payments\Contracts\AdditionalViewAssignmentsInterface;
use WirecardElasticEngine\Components\Payments\Contracts\ProcessPaymentInterface;
use WirecardElasticEngine\Components\Payments\EpsPayment;
use WirecardElasticEngine\Components\Services\SessionManager;
use WirecardElasticEngine\Tests\Unit\PaymentTestCase;
use WirecardElasticEngine\WirecardElasticEngine;

class EpsPaymentTest extends PaymentTestCase
{
    /** @var EpsPayment */
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

        $this->payment = new EpsPayment(
            $this->em,
            $this->config,
            $this->installer,
            $this->router,
            $this->eventManager
        );
    }

    public function testGetPaymentOptions()
    {
        $this->assertEquals('WirecardEPS', $this->payment->getLabel());
        $this->assertEquals('wirecard_elastic_engine_eps', $this->payment->getName());
        $this->assertEquals(3, $this->payment->getPosition());
        $this->assertPaymentOptions(
            $this->payment->getPaymentOptions(),
            'wirecard_elastic_engine_eps',
            'WirecardEPS',
            3
        );
    }

    public function testGetTransaction()
    {
        $transaction = $this->payment->getTransaction();
        $this->assertInstanceOf(EpsTransaction::class, $transaction);
        $this->assertSame($transaction, $this->payment->getTransaction());
    }

    public function testGetBackendTransaction()
    {
        $entity        = $this->createMock(\WirecardElasticEngine\Models\Transaction::class);
        $paymentMethod = $entity->method('getPaymentMethod');
        $paymentMethod->willReturn(EpsPayment::NAME);
        $transactionType = $entity->method('getTransactionType');
        $transactionType->willReturn(null);

        $order       = new Order();
        $transaction = $this->payment->getBackendTransaction($order, Operation::REFUND, $entity);
        $this->assertInstanceOf(EpsPayment::class, $transaction);
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

        $epsConfig = $config->get(EpsTransaction::NAME);
        $this->assertInstanceOf(PaymentMethodConfig::class, $epsConfig);
        $this->assertEquals('MAID', $epsConfig->getMerchantAccountId());
        $this->assertEquals('Secret', $epsConfig->getSecret());
        $this->assertEquals(EpsTransaction::NAME, $epsConfig->getPaymentMethodName());

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
            [WirecardElasticEngine::NAME, 'wirecardElasticEngineEpsTransactionType', null, 'pay'],
        ]);
        $payment = new EpsPayment($this->em, $config, $this->installer, $this->router, $this->eventManager);
        $this->assertEquals('purchase', $payment->getTransactionType());

        $payment = new EpsPayment($this->em, $config, $this->installer, $this->router, $this->eventManager);
        $this->assertEquals('purchase', $payment->getTransactionType());
    }

    public function testProcessPayment()
    {
        $this->assertInstanceOf(ProcessPaymentInterface::class, $this->payment);

        $orderSummary = $this->createMock(OrderSummary::class);
        $orderSummary->method('getAdditionalPaymentData')->willReturn([
            'epsBic' => 'BWFBATW1XXX',
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

        $this->assertInstanceOf(AdditionalViewAssignmentsInterface::class, $this->payment);
        $this->assertEquals([
            'method'     => 'wirecard_elastic_engine_eps'
        ], $this->payment->getAdditionalViewAssignments($sessionManager));
    }
}
