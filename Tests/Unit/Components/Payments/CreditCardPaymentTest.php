<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

namespace WirecardElasticEngine\Tests\Unit\Components\Payments;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Shopware\Models\Shop\Locale;
use Shopware\Models\Shop\Shop;
use Shopware\Models\Shop\Currency;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Wirecard\PaymentSdk\Config\Config;
use Wirecard\PaymentSdk\Config\CreditCardConfig;
use Wirecard\PaymentSdk\Config\PaymentMethodConfig;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Transaction\CreditCardTransaction;
use Wirecard\PaymentSdk\TransactionService;
use WirecardElasticEngine\Components\Actions\ErrorAction;
use WirecardElasticEngine\Components\Actions\ViewAction;
use WirecardElasticEngine\Components\Data\OrderSummary;
use WirecardElasticEngine\Components\Data\PaymentConfig;
use WirecardElasticEngine\Components\Mapper\BasketMapper;
use WirecardElasticEngine\Components\Mapper\UserMapper;
use WirecardElasticEngine\Components\Payments\Contracts\AdditionalViewAssignmentsInterface;
use WirecardElasticEngine\Components\Payments\Contracts\ProcessPaymentInterface;
use WirecardElasticEngine\Components\Payments\Contracts\ProcessReturnInterface;
use WirecardElasticEngine\Components\Payments\CreditCardPayment;
use WirecardElasticEngine\Components\Payments\PaypalPayment;
use WirecardElasticEngine\Components\Services\SessionManager;
use WirecardElasticEngine\Models\CreditCardVault;
use WirecardElasticEngine\Tests\Unit\PaymentTestCase;
use WirecardElasticEngine\WirecardElasticEngine;

class CreditCardPaymentTest extends PaymentTestCase
{
    /** @var CreditCardPayment */
    private $payment;

    public function setUp()
    {
        parent::setUp();

        $this->config->method('getByNamespace')->willReturnMap([
            [WirecardElasticEngine::NAME, 'wirecardElasticEngineCreditCardMerchantId', null, 'CCMAID'],
            [WirecardElasticEngine::NAME, 'wirecardElasticEngineCreditCardSecret', null, 'CCSecret'],
            [WirecardElasticEngine::NAME, 'wirecardElasticEngineCreditCardSslMaxLimit', null, '300'],
            [WirecardElasticEngine::NAME, 'wirecardElasticEngineCreditCardSslMaxLimitCurrency', null, 1],
            [WirecardElasticEngine::NAME, 'wirecardElasticEngineCreditCardThreeDMinLimit', null, '100'],
            [WirecardElasticEngine::NAME, 'wirecardElasticEngineCreditCardThreeDMinLimitCurrency', null, 'EUR'],
            [WirecardElasticEngine::NAME, 'wirecardElasticEngineCreditCardTransactionType', null, 'pay'],
            [WirecardElasticEngine::NAME, 'wirecardElasticEngineCreditCardEnableVault', null, true],
            [WirecardElasticEngine::NAME, 'wirecardElasticEngineCreditCardAllowAddressChanges', null, false],
            [WirecardElasticEngine::NAME, 'wirecardElasticEngineCreditCardThreeDUsageOnTokens', null, false],
        ]);

        $this->payment = new CreditCardPayment(
            $this->em,
            $this->config,
            $this->installer,
            $this->router,
            $this->eventManager
        );
    }

    public function testGetPaymentOptions()
    {
        $this->assertEquals('WirecardCreditCard', $this->payment->getLabel());
        $this->assertEquals('wirecard_elastic_engine_credit_card', $this->payment->getName());
        $this->assertEquals(0, $this->payment->getPosition());
        $this->assertPaymentOptions(
            $this->payment->getPaymentOptions(),
            'wirecard_elastic_engine_credit_card',
            'WirecardCreditCard',
            0
        );
    }

    public function testGetTransaction()
    {
        $transaction = $this->payment->getTransaction();
        $this->assertInstanceOf(CreditCardTransaction::class, $transaction);
        $this->assertSame($transaction, $this->payment->getTransaction());
    }

    public function testGetPaymentConfig()
    {
        $config = $this->payment->getPaymentConfig();

        $this->assertInstanceOf(PaymentConfig::class, $config);
        $this->assertNull($config->getBaseUrl());
        $this->assertNull($config->getHttpUser());
        $this->assertNull($config->getHttpPassword());
        $this->assertTrue($config->isVaultEnabled());
        $this->assertFalse($config->allowAddressChanges());
        $this->assertFalse($config->useThreeDOnTokens());
    }

    public function testGetTransactionConfig()
    {
        /** @var ParameterBagInterface|\PHPUnit_Framework_MockObject_MockObject $parameters */

        $parameters = $this->createMock(ParameterBagInterface::class);
        $parameters->method('get')->willReturnMap([
            ['kernel.name', 'Shopware'],
            ['shopware.release.version', '__SW_VERSION__'],
        ]);

        /** @var \PHPUnit_Framework_MockObject_MockObject $currencyModelMock */
        $currencyModelMock = $this->createMock(Currency::class);
        $currencyModelMock->method('getCurrency')->willReturn('EUR');

        /** @var \PHPUnit_Framework_MockObject_MockObject $repoMock */
        $repoMock = $this->createMock(EntityRepository::class);
        $repoMock->expects($this->once())->method('find')->with(1)->willReturn($currencyModelMock);

        $this->em->method('getRepository')->willReturn($repoMock);

        $config = $this->payment->getTransactionConfig($parameters, 'EUR');

        $this->assertInstanceOf(Config::class, $config);
        $this->assertNull($config->getBaseUrl());
        $this->assertNull($config->getHttpUser());
        $this->assertNull($config->getHttpPassword());

        /** @var CreditCardConfig $paymentMethodConfig */
        $paymentMethodConfig = $config->get(CreditCardTransaction::NAME);

        $this->assertInstanceOf(PaymentMethodConfig::class, $paymentMethodConfig);
        $this->assertEquals('CCMAID', $paymentMethodConfig->getMerchantAccountId());
        $this->assertEquals('CCSecret', $paymentMethodConfig->getSecret());
        $this->assertEquals(CreditCardTransaction::NAME, $paymentMethodConfig->getPaymentMethodName());
        $this->assertEquals([
            'headers' => [
                'shop-system-name'    => 'Shopware',
                'shop-system-version' => '__SW_VERSION__',
                'plugin-name'         => 'WirecardElasticEngine',
                'plugin-version'      => '__PLUGIN_VERSION__',
            ],
        ], $config->getShopHeader());
        $this->assertEquals(300, $paymentMethodConfig->getSslMaxLimit('EUR'));
        $this->assertEquals(100, $paymentMethodConfig->getThreeDMinLimit('EUR'));
    }

    public function testGetTransactionTypeException()
    {
        $this->assertEquals('purchase', $this->payment->getTransactionType());
    }

    public function testGetTransactionType()
    {
        /** @var \Shopware_Components_Config|\PHPUnit_Framework_MockObject_MockObject $config */
        $config = $this->createMock(\Shopware_Components_Config::class);
        $config->method('getByNamespace')->willReturnMap([
            [WirecardElasticEngine::NAME, 'wirecardElasticEnginePaypalTransactionType', null, 'pay'],
        ]);
        $payment = new PaypalPayment($this->em, $config, $this->installer, $this->router, $this->eventManager);
        $this->assertEquals('purchase', $payment->getTransactionType());

        $config = $this->createMock(\Shopware_Components_Config::class);
        $config->method('getByNamespace')->willReturnMap([
            [WirecardElasticEngine::NAME, 'wirecardElasticEnginePaypalTransactionType', null, 'reserve'],
        ]);
        $payment = new PaypalPayment($this->em, $config, $this->installer, $this->router, $this->eventManager);
        $this->assertEquals('authorization', $payment->getTransactionType());
    }

    public function testProcessPayment()
    {
        $requestData = file_get_contents(__DIR__ . '/testdata/creditcard-requestdata.json');

        $this->assertInstanceOf(ProcessPaymentInterface::class, $this->payment);

        $orderSummary = $this->createMock(OrderSummary::class);
        $orderSummary->method('getPayment')->willReturn($this->payment);
        $orderSummary->method('getPaymentUniqueId')->willReturn('1532501234exxxf');
        $orderSummary->method('getBasketMapper')->willReturn($this->createMock(BasketMapper::class));
        $transactionService = $this->createMock(TransactionService::class);
        $transactionService->method('getCreditCardUiWithData')->willReturn($requestData);
        $shop = $this->createMock(Shop::class);
        $shop->method('getLocale')->willReturn(new Locale());
        $redirect = $this->createMock(Redirect::class);
        $request  = $this->createMock(\Enlight_Controller_Request_Request::class);
        $order    = $this->createMock(\sOrder::class);

        $action = $this->payment->processPayment(
            $orderSummary,
            $transactionService,
            $shop,
            $redirect,
            $request,
            $order
        );
        $this->assertInstanceOf(ViewAction::class, $action);
        $this->assertEquals('credit_card.tpl', $action->getTemplate());
        $this->assertEquals([
            'wirecardUrl'         => null,
            'wirecardRequestData' => $requestData,
            'url'                 => null,
        ], $action->getAssignments());
    }

    public function testProcessPaymentWithVaultEnabled()
    {
        $orderSummary = $this->createMock(OrderSummary::class);
        $orderSummary->method('getAdditionalPaymentData')->willReturn(['token' => '2']);
        $userMapper = $this->createMock(UserMapper::class);
        $userMapper->expects($this->atLeastOnce())->method('getUserId')->willReturn(1);
        $userMapper->expects($this->atLeastOnce())->method('getBillingAddress')->willReturn([
            'city'    => 'Footown',
            'street'  => 'Barstreet',
            'zipcode' => 1337,
        ]);
        $userMapper->expects($this->atLeastOnce())->method('getShippingAddress')->willReturn([
            'city'    => 'ShipFootown',
            'street'  => 'ShipBarstreet',
            'zipcode' => 1338,
        ]);
        $orderSummary->method('getUserMapper')->willReturn($userMapper);

        $repo            = $this->createMock(EntityRepository::class);
        $creditCardVault = new CreditCardVault();
        $creditCardVault->setToken('FOOTOKEN321');
        $lastUsed = $creditCardVault->getLastUsed();
        $repo->expects($this->once())->method('findOneBy')->with([
            'userId'                  => 1,
            'id'                      => '2',
            'bindBillingAddressHash'  => '5958eb93c0b510df3961d03ff1bf3975',
            'bindShippingAddressHash' => 'e8269246ec003988d43a212a960f0518',
        ])->willReturn($creditCardVault);
        $this->em->method('getRepository')->willReturn($repo);

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
        $this->assertNotSame($lastUsed, $creditCardVault->getLastUsed());
    }

    public function testProcessPaymentWithVaultNotFoundError()
    {
        $orderSummary = $this->createMock(OrderSummary::class);
        $orderSummary->method('getAdditionalPaymentData')->willReturn(['token' => 'FOOTOKEN123']);
        $userMapper = $this->createMock(UserMapper::class);
        $userMapper->method('getUserId')->willReturn(1);
        $userMapper->method('getBillingAddress')->willReturn([
            'city'                   => 'Footown',
            'street'                 => 'Barstreet',
            'zipcode'                => 1337,
            'additionalAddressLine1' => 'Hodor',
        ]);
        $userMapper->method('getShippingAddress')->willReturn([
            'city'                   => 'ShipFootown',
            'street'                 => 'ShipBarstreet',
            'zipcode'                => 1338,
            'additionalAddressLine1' => 'ShipHodor',
        ]);
        $orderSummary->method('getUserMapper')->willReturn($userMapper);

        $repo = $this->createMock(EntityRepository::class);
        $this->em->method('getRepository')->willReturn($repo);

        $transactionService = $this->createMock(TransactionService::class);
        $shop               = $this->createMock(Shop::class);
        $redirect           = $this->createMock(Redirect::class);
        $request            = $this->createMock(\Enlight_Controller_Request_Request::class);
        $order              = $this->createMock(\sOrder::class);

        $action = $this->payment->processPayment(
            $orderSummary,
            $transactionService,
            $shop,
            $redirect,
            $request,
            $order
        );
        $this->assertInstanceOf(ErrorAction::class, $action);
        $this->assertEquals('no valid credit card for token found', $action->getMessage());
        $this->assertEquals(1, $action->getCode());
    }

    public function testProcessReturn()
    {
        $this->assertInstanceOf(ProcessReturnInterface::class, $this->payment);

        $transactionService = $this->createMock(TransactionService::class);
        $transactionService->expects($this->once())->method('getTransactionByTransactionId');
        $transactionService->expects($this->once())->method('processJsResponse');
        $request = $this->createMock(\Enlight_Controller_Request_Request::class);
        $request->method('getParams')->willReturn([
            'jsresponse'            => 1,
            'token_id'              => 'footoken123',
            'transaction_id'        => '1',
            'masked_account_number' => '1234****9876',
        ]);
        $sessionManager = $this->createMock(SessionManager::class);
        $sessionManager->expects($this->atLeastOnce())->method('getPaymentData')->willReturn(['saveToken' => true]);
        $sessionManager->method('getOrderBillingAddress')->willReturn([]);
        $sessionManager->method('getOrderShippingAddress')->willReturn([]);

        $repo = $this->createMock(EntityRepository::class);
        $this->em->method('getRepository')->willReturn($repo);

        $response = $this->payment->processReturn($transactionService, $request, $sessionManager);
        $this->assertNull($response);
    }

    public function testAdditionalViewAssignments()
    {
        $this->assertInstanceOf(AdditionalViewAssignmentsInterface::class, $this->payment);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('select')->willReturnSelf();
        $qb->method('from')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('orderBy')->willReturnSelf();
        $query = $this->createMock(AbstractQuery::class);
        $qb->method('getQuery')->willReturn($query);
        $this->em->method('createQueryBuilder')->willReturn($qb);

        $sessionManager = $this->createMock(SessionManager::class);
        $sessionManager->method('getOrderBillingAddress')->willReturn([]);
        $sessionManager->method('getOrderShippingAddress')->willReturn([]);

        $this->assertEquals([
            'method'       => CreditCardPayment::PAYMETHOD_IDENTIFIER,
            'vaultEnabled' => true,
            'savedCards'   => [],
        ], $this->payment->getAdditionalViewAssignments($sessionManager));

        $creditCardVault = new CreditCardVault();
        $creditCardVault->setToken('FOOTOKER321');
        $creditCardVault->setMaskedAccountNumber('4444****8888');
        $creditCardVault->setAdditionalData(['add']);
        $query->method('getResult')->willReturn([$creditCardVault]);

        $this->assertEquals([
            'method'       => CreditCardPayment::PAYMETHOD_IDENTIFIER,
            'vaultEnabled' => true,
            'savedCards'   => [
                [
                    'token'               => null,
                    'maskedAccountNumber' => '4444****8888',
                    'additionalData'      => ['add'],
                    'acceptedCriteria'    => false,
                ],
            ],
        ], $this->payment->getAdditionalViewAssignments($sessionManager));
    }
}
