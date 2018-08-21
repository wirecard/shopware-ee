<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

namespace WirecardElasticEngine\Tests\Unit\Components\Payments;

use Doctrine\Common\Collections\ArrayCollection;
use Shopware\Models\Dispatch\Dispatch;
use Shopware\Models\Order\Detail;
use Shopware\Models\Order\Order;
use Shopware\Models\Shop\Currency;
use Shopware\Models\Shop\Shop;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Wirecard\PaymentSdk\Config\Config;
use Wirecard\PaymentSdk\Config\PaymentMethodConfig;
use Wirecard\PaymentSdk\Entity\AccountHolder;
use Wirecard\PaymentSdk\Entity\Amount;
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
use WirecardElasticEngine\Components\Services\SessionManager;
use WirecardElasticEngine\Models\Transaction;
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
        $order  = $this->createMock(Order::class);
        $entity = $this->createMock(Transaction::class);

        $transaction = $this->payment->getBackendTransaction($order, null, $entity);
        $this->assertInstanceOf(RatepayInvoiceTransaction::class, $transaction);
        $this->assertNotSame($transaction, $this->payment->getTransaction());
        $this->assertNotSame($transaction, $this->payment->getBackendTransaction($order, null, $entity));
        $this->assertNull($transaction->getAmount());

        $details = new ArrayCollection();
        $detail  = new Detail();
        $detail->setPrice(40.30);
        $detail->setQuantity(2);
        $detail->setTaxRate(20);
        $detail->setArticleNumber('foo');
        $details->add($detail);

        $detail = new Detail();
        $detail->setPrice(20.10);
        $detail->setQuantity(1);
        $detail->setTaxRate(20);
        $detail->setArticleNumber('bar');
        $details->add($detail);

        $order->method('getDetails')->willReturn($details);
        $order->method('getCurrency')->willReturn('USD');
        $order->method('getInvoiceShipping')->willReturn(30.30);
        $order->method('getInvoiceShippingNet')->willReturn(25.25);
        $dispatch = new Dispatch();
        $dispatch->setName('dispatch');
        $order->method('getDispatch')->willReturn($dispatch);

        $entity->method('getBasket')->willReturn([
            'foo'      => ['quantity' => 2],
            'shipping' => ['quantity' => 1],
        ]);

        $transaction = $this->payment->getBackendTransaction($order, null, $entity);
        $this->assertEquals(110.9, $transaction->getAmount()->getValue());
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
        $orderSummary->method('getAmount')->willReturn(new Amount(0.0, 'EUR'));
        $orderSummary->method('getAdditionalPaymentData')->willReturn([
            'birthday' => [
                'year'  => '2000',
                'month' => '1',
                'day'   => '01',
            ],
        ]);
        $orderSummary->expects($this->atLeastOnce())->method('getPaymentUniqueId')->willReturn('123test');
        $userMapper    = $this->createMock(UserMapper::class);
        $accountHolder = new AccountHolder();
        $accountHolder->setPhone('123456');
        $userMapper->method('getWirecardBillingAccountHolder')->willReturn($accountHolder);
        $userMapper->method('getWirecardShippingAccountHolder')->willReturn($accountHolder);
        $orderSummary->expects($this->atLeastOnce())->method('getUserMapper')->willReturn($userMapper);

        $shop = $this->createMock(Shop::class);
        $shop->method('getCurrency')->willReturn(new Currency());

        $this->assertNull($this->payment->processPayment(
            $orderSummary,
            $this->createMock(TransactionService::class),
            $shop,
            $this->createMock(Redirect::class),
            $this->createMock(\Enlight_Controller_Request_Request::class),
            $this->createMock(\sOrder::class)
        ));
        $transaction = $this->payment->getTransaction();
        $transaction->setOperation(Operation::PAY);
        $this->assertEquals('123test', $transaction->getOrderNumber());
        $this->assertEquals([
            'date-of-birth' => '01-01-2000',
            'phone'         => '123456',
        ], $transaction->getAccountHolder()->mappedProperties());
    }

    public function testProcessPaymentWithConsumerBirthday()
    {
        $this->assertInstanceOf(ProcessPaymentInterface::class, $this->payment);

        $orderSummary = $this->createMock(OrderSummary::class);
        $orderSummary->method('getAmount')->willReturn(new Amount(0.0, 'EUR'));
        $orderSummary->expects($this->atLeastOnce())->method('getPaymentUniqueId')->willReturn('123test');
        $userMapper    = $this->createMock(UserMapper::class);
        $accountHolder = new AccountHolder();
        $accountHolder->setDateOfBirth(new \DateTime('1999-07-31'));
        $accountHolder->setPhone('123456');
        $userMapper->method('getWirecardBillingAccountHolder')->willReturn($accountHolder);
        $userMapper->method('getWirecardShippingAccountHolder')->willReturn($accountHolder);
        $orderSummary->expects($this->atLeastOnce())->method('getUserMapper')->willReturn($userMapper);

        $shop = $this->createMock(Shop::class);
        $shop->method('getCurrency')->willReturn(new Currency());

        $this->assertNull($this->payment->processPayment(
            $orderSummary,
            $this->createMock(TransactionService::class),
            $shop,
            $this->createMock(Redirect::class),
            $this->createMock(\Enlight_Controller_Request_Request::class),
            $this->createMock(\sOrder::class)
        ));
        $transaction = $this->payment->getTransaction();
        $transaction->setOperation(Operation::PAY);
        $this->assertEquals('123test', $transaction->getOrderNumber());
        $this->assertEquals([
            'date-of-birth' => '31-07-1999',
            'phone'         => '123456',
        ], $transaction->getAccountHolder()->mappedProperties());
    }

    public function testProcessPaymentWithoutConsumerBirthday()
    {
        $this->assertInstanceOf(ProcessPaymentInterface::class, $this->payment);

        $orderSummary = $this->createMock(OrderSummary::class);
        $orderSummary->method('getAmount')->willReturn(new Amount(0.0, 'EUR'));
        $orderSummary->expects($this->atLeastOnce())->method('getPaymentUniqueId')->willReturn('123test');
        $userMapper    = $this->createMock(UserMapper::class);
        $accountHolder = new AccountHolder();
        $accountHolder->setPhone('123456');
        $userMapper->method('getWirecardBillingAccountHolder')->willReturn($accountHolder);
        $userMapper->method('getWirecardShippingAccountHolder')->willReturn($accountHolder);
        $orderSummary->expects($this->atLeastOnce())->method('getUserMapper')->willReturn($userMapper);

        $shop = $this->createMock(Shop::class);
        $shop->method('getCurrency')->willReturn(new Currency());

        $action = $this->payment->processPayment(
            $orderSummary,
            $this->createMock(TransactionService::class),
            $shop,
            $this->createMock(Redirect::class),
            $this->createMock(\Enlight_Controller_Request_Request::class),
            $this->createMock(\sOrder::class)
        );
        $this->assertInstanceOf(ErrorAction::class, $action);
        $this->assertEquals(ErrorAction::PROCESSING_FAILED_WRONG_AGE, $action->getCode());
    }

    public function testProcessPaymentWithInvalidAmount()
    {
        $this->assertInstanceOf(ProcessPaymentInterface::class, $this->payment);

        $orderSummary = $this->createMock(OrderSummary::class);
        $orderSummary->method('getAmount')->willReturn(new Amount(10.0, 'EUR'));
        $orderSummary->expects($this->atLeastOnce())->method('getPaymentUniqueId')->willReturn('123test');
        $userMapper    = $this->createMock(UserMapper::class);
        $accountHolder = new AccountHolder();
        $accountHolder->setDateOfBirth(new \DateTime('1999-07-31'));
        $accountHolder->setPhone('123456');
        $userMapper->method('getWirecardBillingAccountHolder')->willReturn($accountHolder);
        $userMapper->method('getWirecardShippingAccountHolder')->willReturn($accountHolder);
        $orderSummary->expects($this->atLeastOnce())->method('getUserMapper')->willReturn($userMapper);

        $shop = $this->createMock(Shop::class);
        $shop->method('getCurrency')->willReturn(new Currency());

        $action = $this->payment->processPayment(
            $orderSummary,
            $this->createMock(TransactionService::class),
            $shop,
            $this->createMock(Redirect::class),
            $this->createMock(\Enlight_Controller_Request_Request::class),
            $this->createMock(\sOrder::class)
        );
        $this->assertInstanceOf(ErrorAction::class, $action);
        $this->assertEquals(ErrorAction::PROCESSING_FAILED_INVALID_AMOUNT, $action->getCode());
    }

    public function testProcessPaymentWithMissingPhone()
    {
        $this->assertInstanceOf(ProcessPaymentInterface::class, $this->payment);

        $orderSummary = $this->createMock(OrderSummary::class);
        $orderSummary->method('getAmount')->willReturn(new Amount(0.0, 'EUR'));
        $orderSummary->expects($this->atLeastOnce())->method('getPaymentUniqueId')->willReturn('123test');
        $userMapper    = $this->createMock(UserMapper::class);
        $accountHolder = new AccountHolder();
        $accountHolder->setDateOfBirth(new \DateTime('1999-07-31'));
        $userMapper->method('getWirecardBillingAccountHolder')->willReturn($accountHolder);
        $userMapper->method('getWirecardShippingAccountHolder')->willReturn($accountHolder);
        $orderSummary->expects($this->atLeastOnce())->method('getUserMapper')->willReturn($userMapper);

        $shop = $this->createMock(Shop::class);
        $shop->method('getCurrency')->willReturn(new Currency());

        $action = $this->payment->processPayment(
            $orderSummary,
            $this->createMock(TransactionService::class),
            $shop,
            $this->createMock(Redirect::class),
            $this->createMock(\Enlight_Controller_Request_Request::class),
            $this->createMock(\sOrder::class)
        );
        $this->assertInstanceOf(ErrorAction::class, $action);
        $this->assertEquals(ErrorAction::PROCESSING_FAILED_MISSING_PHONE, $action->getCode());
    }

    public function testCheckDisplayRestrictions()
    {
        $sessionManager = $this->createMock(SessionManager::class);

        $userMapper = $this->createMock(UserMapper::class);
        $userMapper->method('getBillingAddress')->willReturn(['countryId' => 2]);
        $userMapper->method('getShippingAddress')->willReturn(['countryId' => 2]);
        $userMapper->method('getBirthday')->willReturn(new \DateTime('2000-01-01'));
        $this->assertTrue($this->payment->checkDisplayRestrictions($userMapper, $sessionManager));
    }

    public function testGetAdditionalViewAssignments()
    {
        $sessionManager = $this->createMock(SessionManager::class);

        $this->assertInstanceOf(AdditionalViewAssignmentsInterface::class, $this->payment);
        $this->assertEquals([
            'method'   => 'wirecard_elastic_engine_ratepay_invoice',
            'showForm' => true,
        ], $this->payment->getAdditionalViewAssignments($sessionManager));
    }
}
