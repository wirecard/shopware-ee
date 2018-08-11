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
use Wirecard\PaymentSdk\Entity\AccountHolder;
use Wirecard\PaymentSdk\Entity\Basket;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Transaction\Operation;
use Wirecard\PaymentSdk\Transaction\RatepayInvoiceTransaction;
use Wirecard\PaymentSdk\TransactionService;
use WirecardElasticEngine\Components\Actions\ErrorAction;
use WirecardElasticEngine\Components\Data\OrderSummary;
use WirecardElasticEngine\Components\Data\PaymentConfig;
use WirecardElasticEngine\Components\Mapper\UserMapper;
use WirecardElasticEngine\Components\Payments\Contracts\AdditionalViewAssignmentsInterface;
use WirecardElasticEngine\Components\Payments\Contracts\ProcessPaymentInterface;
use WirecardElasticEngine\Components\Payments\RatepayInvoicePayment;
use WirecardElasticEngine\Tests\Unit\PaymentTestCase;
use WirecardElasticEngine\WirecardElasticEngine;

class RatepayInvoicePaymentTest extends PaymentTestCase
{
    /** @var RatepayInvoicePayment */
    private $payment;

    public function setUp()
    {
        parent::setUp();

        $this->config->method('getByNamespace')->willReturnMap([
            [WirecardElasticEngine::NAME, 'wirecardElasticEngineRatepayInvoiceMerchantId', null, 'MAID'],
            [WirecardElasticEngine::NAME, 'wirecardElasticEngineRatepayInvoiceSecret', null, 'Secret'],
            [WirecardElasticEngine::NAME, 'wirecardElasticEngineRatepayInvoiceFraudPrevention', null, false],
            [WirecardElasticEngine::NAME, 'wirecardElasticEngineRatepayInvoiceAcceptedCurrencies', null, [1]],
            [WirecardElasticEngine::NAME, 'wirecardElasticEngineRatepayInvoiceShippingCountries', null, [2]],
            [WirecardElasticEngine::NAME, 'wirecardElasticEngineRatepayInvoiceBillingCountries', null, [2]],
        ]);

        $this->payment = new RatepayInvoicePayment(
            $this->em,
            $this->config,
            $this->installer,
            $this->router,
            $this->eventManager
        );
    }

    public function testGetPaymentOptions()
    {
        $this->assertEquals('WirecardRatepayInvoice', $this->payment->getLabel());
        $this->assertEquals('wirecard_elastic_engine_ratepay_invoice', $this->payment->getName());
        $this->assertEquals(2, $this->payment->getPosition());
        $this->assertPaymentOptions(
            $this->payment->getPaymentOptions(),
            'wirecard_elastic_engine_ratepay_invoice',
            'WirecardRatepayInvoice',
            2
        );
    }

    public function testGetTransaction()
    {
        $transaction = $this->payment->getTransaction();
        $this->assertInstanceOf(RatepayInvoiceTransaction::class, $transaction);
        $this->assertSame($transaction, $this->payment->getTransaction());
    }

    public function testGetBackendTransaction()
    {
        $order = new Order();
        $order->setInvoiceAmount(123.98);
        $order->setCurrency('USD');

        $transaction = $this->payment->getBackendTransaction($order, null, RatepayInvoiceTransaction::NAME);
        $this->assertInstanceOf(RatepayInvoiceTransaction::class, $transaction);
        $this->assertNotSame($transaction, $this->payment->getTransaction());
        $this->assertNotSame($transaction, $this->payment->getBackendTransaction($order, null, null));

        $transaction = $this->payment->getBackendTransaction($order, null, null);
        $this->assertEquals(123.98, $transaction->getAmount()->getValue());
        $this->assertEquals('USD', $transaction->getAmount()->getCurrency());
        $basket = $transaction->getBasket();
        $this->assertInstanceOf(Basket::class, $basket);
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

        $sofortConfig = $config->get(RatepayInvoiceTransaction::NAME);
        $this->assertInstanceOf(PaymentMethodConfig::class, $sofortConfig);
        $this->assertEquals('MAID', $sofortConfig->getMerchantAccountId());
        $this->assertEquals('Secret', $sofortConfig->getSecret());
        $this->assertEquals(RatepayInvoiceTransaction::NAME, $sofortConfig->getPaymentMethodName());

        $this->assertEquals([
            'headers' => [
                'shop-system-name'    => 'Shopware',
                'shop-system-version' => '__SW_VERSION__',
                'plugin-name'         => 'WirecardElasticEngine',
                'plugin-version'      => '__PLUGIN_VERSION__',
            ],
        ], $config->getShopHeader());
    }

    public function testGetTransactionType()
    {
        $this->assertEquals('authorization', $this->payment->getTransactionType());
    }

    public function testProcessPayment()
    {
        $this->assertInstanceOf(ProcessPaymentInterface::class, $this->payment);

        $orderSummary = $this->createMock(OrderSummary::class);
        $orderSummary->method('getAdditionalPaymentData')->willReturn([
            'birthday' => [
                'year'  => '2000',
                'month' => '1',
                'day'   => '01',
            ],
        ]);
        $orderSummary->expects($this->atLeastOnce())->method('getPaymentUniqueId')->willReturn('123test');
        $userMapper = $this->createMock(UserMapper::class);
        $userMapper->method('getWirecardBillingAccountHolder')->willReturn(new AccountHolder());
        $orderSummary->expects($this->atLeastOnce())->method('getUserMapper')->willReturn($userMapper);

        $this->assertNull($this->payment->processPayment(
            $orderSummary,
            $this->createMock(TransactionService::class),
            $this->createMock(Shop::class),
            $this->createMock(Redirect::class),
            $this->createMock(\Enlight_Controller_Request_Request::class),
            $this->createMock(\sOrder::class)
        ));
        $transaction = $this->payment->getTransaction();
        $transaction->setOperation(Operation::PAY);
        $this->assertEquals('123test', $transaction->getOrderNumber());
        $this->assertEquals([
            'date-of-birth' => '01-01-2000',
        ], $transaction->getAccountHolder()->mappedProperties());
    }

    public function testProcessPaymentWithConsumerBirthday()
    {
        $this->assertInstanceOf(ProcessPaymentInterface::class, $this->payment);

        $orderSummary = $this->createMock(OrderSummary::class);
        $orderSummary->expects($this->atLeastOnce())->method('getPaymentUniqueId')->willReturn('123test');
        $userMapper    = $this->createMock(UserMapper::class);
        $accountHolder = new AccountHolder();
        $accountHolder->setDateOfBirth(new \DateTime('1999-07-31'));
        $userMapper->method('getWirecardBillingAccountHolder')->willReturn($accountHolder);
        $orderSummary->expects($this->atLeastOnce())->method('getUserMapper')->willReturn($userMapper);

        $this->assertNull($this->payment->processPayment(
            $orderSummary,
            $this->createMock(TransactionService::class),
            $this->createMock(Shop::class),
            $this->createMock(Redirect::class),
            $this->createMock(\Enlight_Controller_Request_Request::class),
            $this->createMock(\sOrder::class)
        ));
        $transaction = $this->payment->getTransaction();
        $transaction->setOperation(Operation::PAY);
        $this->assertEquals('123test', $transaction->getOrderNumber());
        $this->assertEquals([
            'date-of-birth' => '31-07-1999',
        ], $transaction->getAccountHolder()->mappedProperties());
    }

    public function testProcessPaymentWithoutConsumerBirthday()
    {
        $this->assertInstanceOf(ProcessPaymentInterface::class, $this->payment);

        $orderSummary = $this->createMock(OrderSummary::class);
        $orderSummary->expects($this->atLeastOnce())->method('getPaymentUniqueId')->willReturn('123test');
        $userMapper = $this->createMock(UserMapper::class);
        $userMapper->method('getWirecardBillingAccountHolder')->willReturn(new AccountHolder());
        $orderSummary->expects($this->atLeastOnce())->method('getUserMapper')->willReturn($userMapper);

        $action = $this->payment->processPayment(
            $orderSummary,
            $this->createMock(TransactionService::class),
            $this->createMock(Shop::class),
            $this->createMock(Redirect::class),
            $this->createMock(\Enlight_Controller_Request_Request::class),
            $this->createMock(\sOrder::class)
        );
        $this->assertInstanceOf(ErrorAction::class, $action);
        $this->assertEquals(ErrorAction::PROCESSING_FAILED_WRONG_AGE, $action->getCode());
    }

    public function testCheckDisplayRestrictions()
    {
        $userMapper = $this->createMock(UserMapper::class);
        $userMapper->method('getBillingAddress')->willReturn(['countryId' => 2]);
        $userMapper->method('getShippingAddress')->willReturn(['countryId' => 2]);
        $userMapper->method('getBirthday')->willReturn(new \DateTime('2000-01-01'));
        $this->assertTrue($this->payment->checkDisplayRestrictions($userMapper));
    }

    public function testGetAdditionalViewAssignments()
    {
        $this->markTestIncomplete('Complete test after merge and SessionManager is available!');

        $this->assertInstanceOf(AdditionalViewAssignmentsInterface::class, $this->payment);
        $this->assertEquals([
            'method'   => 'wirecard_elastic_engine_ratepay_invoice',
            'showForm' => true,
        ], $this->payment->getAdditionalViewAssignments());
    }
}
