<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

namespace WirecardElasticEngine\Tests\Unit\Components\Payments;

use Doctrine\ORM\EntityRepository;
use Shopware\Models\Order\Order;
use Shopware\Models\Shop\Shop;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Wirecard\PaymentSdk\Config\Config;
use Wirecard\PaymentSdk\Config\PaymentMethodConfig;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Transaction\Operation;
use Wirecard\PaymentSdk\Transaction\PoiPiaTransaction;
use Wirecard\PaymentSdk\TransactionService;
use WirecardElasticEngine\Components\Data\OrderSummary;
use WirecardElasticEngine\Components\Data\PaymentConfig;
use WirecardElasticEngine\Components\Mapper\UserMapper;
use WirecardElasticEngine\Components\Payments\Contracts\AdditionalPaymentInformationInterface;
use WirecardElasticEngine\Components\Payments\Contracts\ProcessPaymentInterface;
use WirecardElasticEngine\Components\Payments\PaymentInAdvancePayment;
use WirecardElasticEngine\Models\Transaction;
use WirecardElasticEngine\Models\Transaction as TransactionModel;
use WirecardElasticEngine\Tests\Unit\PaymentTestCase;
use WirecardElasticEngine\WirecardElasticEngine;

class PaymentInAdvancePaymentTest extends PaymentTestCase
{
    /** @var PaymentInAdvancePayment */
    private $payment;

    public function setUp()
    {
        parent::setUp();

        $this->config->method('getByNamespace')->willReturnMap([
            [WirecardElasticEngine::NAME, 'wirecardElasticEnginePoiPiaMerchantId', null, 'MAID'],
            [WirecardElasticEngine::NAME, 'wirecardElasticEnginePoiPiaSecret', null, 'Secret'],
        ]);

        $this->payment = new PaymentInAdvancePayment(
            $this->em,
            $this->config,
            $this->installer,
            $this->router,
            $this->eventManager
        );
    }

    public function testGetPaymentOptions()
    {
        $this->assertEquals('WirecardPaymentInAdvance', $this->payment->getLabel());
        $this->assertEquals('wirecard_elastic_engine_pia', $this->payment->getName());
        $this->assertEquals(6, $this->payment->getPosition());
        $this->assertPaymentOptions(
            $this->payment->getPaymentOptions(),
            'wirecard_elastic_engine_pia',
            'WirecardPaymentInAdvance',
            6
        );
    }

    public function testGetTransaction()
    {
        $transaction = $this->payment->getTransaction();
        $this->assertInstanceOf(PoiPiaTransaction::class, $transaction);
        $this->assertSame($transaction, $this->payment->getTransaction());
    }

    public function testGetBackendTransaction()
    {
        $entity        = $this->createMock(TransactionModel::class);
        $paymentMethod = $entity->method('getPaymentMethod');
        $paymentMethod->willReturn(PoiPiaTransaction::NAME);
        $transactionType = $entity->method('getTransactionType');
        $transactionType->willReturn(null);

        $transaction = $this->payment->getBackendTransaction(new Order(), Operation::REFUND, $entity);
        $this->assertInstanceOf(PoiPiaTransaction::class, $transaction);
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

        $config = $this->payment->getTransactionConfig($parameters, 'EUR');

        $this->assertInstanceOf(Config::class, $config);
        $this->assertNull($config->getBaseUrl());
        $this->assertNull($config->getHttpUser());
        $this->assertNull($config->getHttpPassword());

        $sofortConfig = $config->get(PoiPiaTransaction::NAME);
        $this->assertInstanceOf(PaymentMethodConfig::class, $sofortConfig);
        $this->assertEquals('MAID', $sofortConfig->getMerchantAccountId());
        $this->assertEquals('Secret', $sofortConfig->getSecret());
        $this->assertEquals(PoiPiaTransaction::NAME, $sofortConfig->getPaymentMethodName());

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
        $this->assertEquals('authorization', $this->payment->getTransactionType());
    }

    public function testGetTransactionType()
    {
        /** @var \Shopware_Components_Config|\PHPUnit_Framework_MockObject_MockObject $config */
        $config  = $this->createMock(\Shopware_Components_Config::class);
        $payment = new PaymentInAdvancePayment(
            $this->em,
            $config,
            $this->installer,
            $this->router,
            $this->eventManager
        );
        $this->assertEquals('authorization', $payment->getTransactionType());
    }

    public function testProcessPayment()
    {
        $this->assertInstanceOf(ProcessPaymentInterface::class, $this->payment);

        $userMapper = $this->createMock(UserMapper::class);
        $userMapper->expects($this->once())->method('getLastName')->willReturn('lastname');
        $userMapper->expects($this->once())->method('getFirstName')->willReturn('firstname');

        $orderSummary = $this->createMock(OrderSummary::class);
        $orderSummary->expects($this->never())->method('getPaymentUniqueId');
        $orderSummary->expects($this->atLeastOnce())->method('getUserMapper')->willReturn($userMapper);
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
        $transaction->setOperation(Operation::RESERVE);
        $this->assertNull($transaction->getOrderNumber());
        $accountHolder = $transaction->getAccountHolder();
        $this->assertEquals([
            'last-name'  => 'lastname',
            'first-name' => 'firstname',
        ], $accountHolder->mappedProperties());
    }

    public function testAssignAdditionalPaymentInformation()
    {
        $this->assertInstanceOf(AdditionalPaymentInformationInterface::class, $this->payment);

        $view = $this->createMock(\Enlight_View_Default::class);
        $view->method('getAssign')->willReturnMap([
            ['sPayment', ['name' => PaymentInAdvancePayment::PAYMETHOD_IDENTIFIER]],
            ['sOrderNumber', 20001],
        ]);
        $repo = $this->createMock(EntityRepository::class);
        $repo->method('findOneBy')->willReturn($this->createMock(Transaction::class));
        $this->em->method('getRepository')->willReturn($repo);
        $view->expects($this->once())->method('assign')->with('wirecardElasticEngineBankData');
        $this->assertNull($this->payment->assignAdditionalPaymentInformation($view));
    }
}
