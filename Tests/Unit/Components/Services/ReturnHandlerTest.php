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
use Wirecard\PaymentSdk\Response\FailureResponse;
use Wirecard\PaymentSdk\Response\FormInteractionResponse;
use Wirecard\PaymentSdk\Response\InteractionResponse;
use Wirecard\PaymentSdk\Response\Response;
use Wirecard\PaymentSdk\Response\SuccessResponse;
use Wirecard\PaymentSdk\TransactionService;
use WirecardElasticEngine\Components\Actions\ErrorAction;
use WirecardElasticEngine\Components\Actions\RedirectAction;
use WirecardElasticEngine\Components\Actions\ViewAction;
use WirecardElasticEngine\Components\Payments\Payment;
use WirecardElasticEngine\Components\Services\ReturnHandler;
use WirecardElasticEngine\Components\Services\SessionManager;
use WirecardElasticEngine\Components\Services\TransactionManager;

class ReturnHandlerTest extends TestCase
{
    /** @var \Shopware_Components_Config|\PHPUnit_Framework_MockObject_MockObject */
    private $config;

    /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $em;

    /** @var RouterInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $router;

    /** @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $logger;

    /** @var TransactionManager|\PHPUnit_Framework_MockObject_MockObject */
    private $transactionManager;

    /** @var ReturnHandler */
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

        $this->transactionManager = $this->createMock(TransactionManager::class);

        $this->handler = new ReturnHandler(
            $this->em,
            $this->router,
            $this->logger,
            $this->config,
            $this->transactionManager
        );
    }

    public function testHandleRequest()
    {
        $payment = $this->createMock(Payment::class);

        $response           = $this->createMock(Response::class);
        $transactionService = $this->createMock(TransactionService::class);
        $transactionService->expects($this->once())->method('handleResponse')->willReturn($response);
        $request = $this->createMock(\Enlight_Controller_Request_Request::class);
        $request->method('getParams')->willReturn([]);

        $response = $this->handler->handleRequest(
            $payment,
            $transactionService,
            $request,
            $this->createMock(SessionManager::class)
        );
        $this->assertInstanceOf(Response::class, $response);
    }

    public function testHandleResponseFailure()
    {
        $response = $this->createMock(Response::class);
        $response->method('getData')->willReturn([]);

        /** @var ErrorAction $action */
        $action = $this->handler->handleResponse($response);
        $this->assertInstanceOf(ErrorAction::class, $action);
        $this->assertEquals(ErrorAction::FAILURE_RESPONSE, $action->getCode());

        $response = $this->createMock(FailureResponse::class);
        $response->method('getData')->willReturn([]);

        $action = $this->handler->handleResponse($response);
        $this->assertInstanceOf(ErrorAction::class, $action);
        $this->assertEquals(ErrorAction::FAILURE_RESPONSE, $action->getCode());
    }

    public function testHandleFormInteractionResponse()
    {
        $response = $this->createMock(FormInteractionResponse::class);

        /** @var ViewAction $action */
        $action = $this->handler->handleResponse($response);
        $this->assertInstanceOf(ViewAction::class, $action);
        $this->assertEquals([
            'threeDSecure' => true,
            'method'       => $response->getMethod(),
            'formFields'   => $response->getFormFields(),
            'url'          => $response->getUrl(),
        ], $action->getAssignments());
        $this->assertEquals('credit_card.tpl', $action->getTemplate());
    }

    public function testHandleInteractionResponse()
    {
        $response = $this->createMock(InteractionResponse::class);
        $response->method('getRedirectUrl')->willReturn('http://localhost/redirect');

        /** @var RedirectAction $action */
        $action = $this->handler->handleResponse($response);
        $this->assertInstanceOf(RedirectAction::class, $action);
        $this->assertEquals('http://localhost/redirect', $action->getUrl());
    }

    public function testHandleSuccess()
    {
        $this->router->method('assemble')->willReturn('http://localhost/success');

        $response           = $this->createMock(SuccessResponse::class);
        $initialTransaction = $this->createMock(\WirecardElasticEngine\Models\Transaction::class);

        /** @var RedirectAction $action */
        $action = $this->handler->handleSuccess($response, $initialTransaction);
        $this->assertInstanceOf(RedirectAction::class, $action);
        $this->assertEquals('http://localhost/success', $action->getUrl());
    }
}
