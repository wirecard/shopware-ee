<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

namespace WirecardElasticEngine\Tests\Unit\Components\Services;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Shopware\Components\Routing\RouterInterface;
use Shopware\Models\Order\Order;
use Shopware\Models\Order\Repository;
use Shopware\Models\Shop\Shop;
use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Response\InteractionResponse;
use Wirecard\PaymentSdk\Response\Response;
use Wirecard\PaymentSdk\Transaction\Transaction;
use Wirecard\PaymentSdk\TransactionService;
use WirecardElasticEngine\Components\Actions\Action;
use WirecardElasticEngine\Components\Actions\ErrorAction;
use WirecardElasticEngine\Components\Actions\RedirectAction;
use WirecardElasticEngine\Components\Data\OrderSummary;
use WirecardElasticEngine\Components\Data\PaymentConfig;
use WirecardElasticEngine\Components\Mapper\BasketMapper;
use WirecardElasticEngine\Components\Mapper\UserMapper;
use WirecardElasticEngine\Components\Payments\Contracts\ProcessPaymentInterface;
use WirecardElasticEngine\Components\Payments\PaymentInterface;
use WirecardElasticEngine\Components\Services\PaymentHandler;
use WirecardElasticEngine\Components\Services\TransactionManager;

class PaymentHandlerTest extends TestCase
{
    /** @var \Shopware_Components_Config|\PHPUnit_Framework_MockObject_MockObject */
    private $config;

    /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $em;

    /** @var RouterInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $router;

    /** @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $logger;

    /** @var OrderSummary|\PHPUnit_Framework_MockObject_MockObject */
    private $orderSummary;

    /** @var TransactionService|\PHPUnit_Framework_MockObject_MockObject */
    private $transactionService;

    /** @var Redirect|\PHPUnit_Framework_MockObject_MockObject */
    private $redirect;

    /** @var \Enlight_Controller_Request_Request|\PHPUnit_Framework_MockObject_MockObject */
    private $request;

    /** @var TransactionManager|\PHPUnit_Framework_MockObject_MockObject */
    private $transactionManager;

    /** @var \sOrder|\PHPUnit_Framework_MockObject_MockObject */
    private $shopwareOrder;

    /** @var PaymentHandler */
    private $handler;

    public function setUp()
    {
        $shopRepo = $this->createMock(\Shopware\Models\Shop\Repository::class);
        $shopRepo->method('getActiveDefault')->willReturn($this->createMock(Shop::class));

        $orderRepo = $this->createMock(Repository::class);
        $order     = new Order();
        $orderRepo->method('findOneBy')->willReturn($order);

        $this->config = $this->createMock(\Shopware_Components_Config::class);
        $this->em     = $this->createMock(EntityManagerInterface::class);
        $this->em->method('getRepository')->willReturnMap([
            [Shop::class, $shopRepo],
            [Order::class, $orderRepo],
        ]);
        $this->router = $this->createMock(RouterInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->orderSummary       = $this->createMock(OrderSummary::class);
        $this->transactionService = $this->createMock(TransactionService::class);
        $this->redirect           = $this->createMock(Redirect::class);
        $this->transactionManager = $this->createMock(TransactionManager::class);
        $this->request            = $this->createMock(\Enlight_Controller_Request_Request::class);
        $this->shopwareOrder      = $this->createMock(\sOrder::class);

        $this->handler = new PaymentHandler(
            $this->em,
            $this->router,
            $this->logger,
            $this->config,
            $this->transactionManager
        );
    }

    public function testExecute()
    {
        $transaction = $this->createMock(Transaction::class);
        $transaction->expects($this->never())->method('setBasket');
        $transaction->expects($this->never())->method('setIpAddress');
        $transaction->expects($this->never())->method('setAccountHolder');
        $transaction->expects($this->never())->method('setShipping');
        $transaction->expects($this->never())->method('setLocale');
        $transaction->expects($this->never())->method('setDescriptor');
        $transaction->expects($this->atLeastOnce())->method('setNotificationUrl')->with('https://localhost/notify');

        $paymentConfig = $this->createMock(PaymentConfig::class);
        $paymentConfig->method('sendBasket')->willReturn(false);
        $paymentConfig->method('hasFraudPrevention')->willReturn(false);
        $paymentConfig->method('sendDescriptor')->willReturn(false);

        $payment = $this->createMock(PaymentInterface::class);
        $payment->method('getTransaction')->willReturn($transaction);
        $payment->method('getPaymentConfig')->willReturn($paymentConfig);

        $response = $this->createMock(Response::class);
        $response->method('getData')->willReturn([]);
        $this->transactionService->method('process')->willReturn($response);

        $this->orderSummary->method('getPayment')->willReturn($payment);

        /** @var ErrorAction $action */
        $action = $this->handler->execute(
            $this->orderSummary,
            $this->transactionService,
            $this->redirect,
            'https://localhost/notify',
            $this->request,
            $this->shopwareOrder
        );
        $this->assertInstanceOf(ErrorAction::class, $action);
        $this->assertEquals(ErrorAction::PROCESSING_FAILED, $action->getCode());
    }

    public function testExecuteCustomActionAndPaymentConfig()
    {
        $this->config->method('get')->willReturnMap([['shopName', null, 'WirecardShopware']]);

        $transaction = $this->createMock(Transaction::class);
        $transaction->expects($this->atLeastOnce())->method('setBasket');
        $transaction->expects($this->atLeastOnce())->method('setIpAddress');
        $transaction->expects($this->atLeastOnce())->method('setAccountHolder');
        $transaction->expects($this->atLeastOnce())->method('setShipping');
        $transaction->expects($this->atLeastOnce())->method('setLocale');
        $transaction->expects($this->atLeastOnce())->method('setDescriptor');
        $transaction->expects($this->atLeastOnce())->method('setNotificationUrl')->with('https://localhost/notify');

        $paymentConfig = $this->createMock(PaymentConfig::class);
        $paymentConfig->method('sendBasket')->willReturn(true);
        $paymentConfig->method('hasFraudPrevention')->willReturn(true);
        $paymentConfig->method('sendDescriptor')->willReturn(true);

        $builder = new \PHPUnit_Framework_MockObject_MockBuilder(
            $this,
            [PaymentInterface::class, ProcessPaymentInterface::class]
        );
        $payment = $builder->getMock();
        $payment->method('getTransaction')->willReturn($transaction);
        $payment->method('getPaymentConfig')->willReturn($paymentConfig);
        $customAction = $this->createMock(Action::class);
        $payment->method('processPayment')->willReturn($customAction);

        $basketMapper = $this->createMock(BasketMapper::class);
        $userMapper   = $this->createMock(UserMapper::class);

        $this->orderSummary->method('getPayment')->willReturn($payment);
        $this->orderSummary->method('getBasketMapper')->willReturn($basketMapper);
        $this->orderSummary->method('getUserMapper')->willReturn($userMapper);

        $action = $this->handler->execute(
            $this->orderSummary,
            $this->transactionService,
            $this->redirect,
            'https://localhost/notify',
            $this->request,
            $this->shopwareOrder
        );
        $this->assertEquals($customAction, $action);
    }

    public function testExecuteRedirectAction()
    {
        $payment = $this->createMock(PaymentInterface::class);
        $payment->method('getTransaction')->willReturn($this->createMock(Transaction::class));
        $payment->method('getPaymentConfig')->willReturn($this->createMock(PaymentConfig::class));

        $basketMapper = $this->createMock(BasketMapper::class);
        $userMapper   = $this->createMock(UserMapper::class);

        $this->orderSummary->method('getPayment')->willReturn($payment);
        $this->orderSummary->method('getBasketMapper')->willReturn($basketMapper);
        $this->orderSummary->method('getUserMapper')->willReturn($userMapper);
        $this->orderSummary->method('getAmount')->willReturn(new Amount(100, 'EUR'));
        $response = $this->createMock(InteractionResponse::class);
        $response->method('getRedirectUrl')->willReturn('https://localhost/redirect');
        $response->method('getData')->willReturn([]);
        $this->transactionService->method('process')->willReturn($response);

        /** @var RedirectAction $action */
        $action = $this->handler->execute(
            $this->orderSummary,
            $this->transactionService,
            $this->redirect,
            'https://localhost/notify',
            $this->request,
            $this->shopwareOrder
        );
        $this->assertInstanceOf(RedirectAction::class, $action);
        $this->assertEquals('https://localhost/redirect', $action->getUrl());
    }
}
