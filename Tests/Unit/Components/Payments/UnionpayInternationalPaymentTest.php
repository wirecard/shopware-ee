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
use Shopware\Models\Shop\Locale;
use Shopware\Models\Shop\Shop;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Wirecard\PaymentSdk\Config\Config;
use Wirecard\PaymentSdk\Config\PaymentMethodConfig;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Transaction\UpiTransaction;
use Wirecard\PaymentSdk\TransactionService;
use WirecardElasticEngine\Components\Actions\ViewAction;
use WirecardElasticEngine\Components\Data\OrderSummary;
use WirecardElasticEngine\Components\Data\PaymentConfig;
use WirecardElasticEngine\Components\Mapper\BasketMapper;
use WirecardElasticEngine\Components\Payments\Contracts\ProcessPaymentInterface;
use WirecardElasticEngine\Components\Payments\Contracts\ProcessReturnInterface;
use WirecardElasticEngine\Components\Payments\PaypalPayment;
use WirecardElasticEngine\Components\Payments\UnionpayInternationalPayment;
use WirecardElasticEngine\Components\Services\SessionManager;
use WirecardElasticEngine\Tests\Unit\PaymentTestCase;
use WirecardElasticEngine\WirecardElasticEngine;

class UnionpayInternationalPaymentTest extends PaymentTestCase
{
    /** @var UnionpayInternationalPayment */
    private $payment;

    public function setUp()
    {
        parent::setUp();

        $this->config->method('getByNamespace')->willReturnMap([
            [WirecardElasticEngine::NAME, 'wirecardElasticEngineUnionpayInternationalMerchantId', null, 'UpiMAID'],
            [WirecardElasticEngine::NAME, 'wirecardElasticEngineUnionpayInternationalSecret', null, 'UpiSecret'],
            [WirecardElasticEngine::NAME, 'wirecardElasticEngineUnionpayInternationalTransactionType', null, 'pay'],
        ]);

        $this->payment = new UnionpayInternationalPayment(
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
        $this->assertEquals('WirecardUnionPayInternational', $this->payment->getLabel());
        $this->assertEquals('wirecard_elastic_engine_unionpay_international', $this->payment->getName());
        $this->assertEquals(10, $this->payment->getPosition());
        $this->assertPaymentOptions(
            $this->payment->getPaymentOptions(),
            'wirecard_elastic_engine_unionpay_international',
            'WirecardUnionPayInternational',
            10
        );
    }

    public function testGetTransaction()
    {
        $transaction = $this->payment->getTransaction();
        $this->assertInstanceOf(UpiTransaction::class, $transaction);
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

        $this->em->method('getRepository')->willReturn($this->createMock(EntityRepository::class));

        $config = $this->payment->getTransactionConfig($shop, $parameters, 'EUR');

        $this->assertInstanceOf(Config::class, $config);
        $this->assertNull($config->getBaseUrl());
        $this->assertNull($config->getHttpUser());
        $this->assertNull($config->getHttpPassword());

        $paymentMethodConfig = $config->get(UpiTransaction::NAME);
        $this->assertInstanceOf(PaymentMethodConfig::class, $paymentMethodConfig);
        $this->assertEquals('UpiMAID', $paymentMethodConfig->getMerchantAccountId());
        $this->assertEquals('UpiSecret', $paymentMethodConfig->getSecret());
        $this->assertEquals(UpiTransaction::NAME, $paymentMethodConfig->getPaymentMethodName());
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
        $this->assertEquals('purchase', $this->payment->getTransactionType());
    }

    public function testGetTransactionType()
    {
        /** @var \Shopware_Components_Config|\PHPUnit_Framework_MockObject_MockObject $config */
        $config = $this->createMock(\Shopware_Components_Config::class);
        $config->method('getByNamespace')->willReturnMap([
            [WirecardElasticEngine::NAME, 'wirecardElasticEnginePaypalTransactionType', null, 'pay'],
        ]);
        $payment = new PaypalPayment(
            $this->em,
            $config,
            $this->installer,
            $this->router,
            $this->eventManager,
            $this->snippetManager
        );
        $this->assertEquals('purchase', $payment->getTransactionType());

        $config = $this->createMock(\Shopware_Components_Config::class);
        $config->method('getByNamespace')->willReturnMap([
            [WirecardElasticEngine::NAME, 'wirecardElasticEnginePaypalTransactionType', null, 'reserve'],
        ]);
        $payment = new PaypalPayment(
            $this->em,
            $config,
            $this->installer,
            $this->router,
            $this->eventManager,
            $this->snippetManager
        );
        $this->assertEquals('authorization', $payment->getTransactionType());
    }

    public function testProcessPayment()
    {
        $requestData = file_get_contents(__DIR__ . '/testdata/upi-requestdata.json');

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

    public function testProcessReturn()
    {
        $this->assertInstanceOf(ProcessReturnInterface::class, $this->payment);

        $transactionService = $this->createMock(TransactionService::class);
        $transactionService->expects($this->once())->method('processJsResponse');
        $request = $this->createMock(\Enlight_Controller_Request_Request::class);
        $request->method('getParams')->willReturn([
            'jsresponse' => 1,
        ]);
        $sessionManager = $this->createMock(SessionManager::class);

        $repo = $this->createMock(EntityRepository::class);
        $this->em->method('getRepository')->willReturn($repo);

        $response = $this->payment->processReturn($transactionService, $request, $sessionManager);
        $this->assertNull($response);
    }
}
