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
use Wirecard\PaymentSdk\BackendService;
use Wirecard\PaymentSdk\Response\Response;
use Wirecard\PaymentSdk\Response\SuccessResponse;
use Wirecard\PaymentSdk\Transaction\Operation;
use Wirecard\PaymentSdk\Transaction\Transaction;
use WirecardElasticEngine\Components\Actions\ErrorAction;
use WirecardElasticEngine\Components\Actions\ViewAction;
use WirecardElasticEngine\Components\Services\BackendOperationHandler;
use WirecardElasticEngine\Components\Services\TransactionManager;
use WirecardElasticEngine\Models\Transaction as TransactionModel;

class BackendOperationHandlerTest extends TestCase
{
    /** @var \Shopware_Components_Config|\PHPUnit_Framework_MockObject_MockObject */
    private $config;

    /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $em;

    /** @var RouterInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $router;

    /** @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $logger;

    /** @var BackendService|\PHPUnit_Framework_MockObject_MockObject */
    private $backendService;

    /** @var TransactionManager|\PHPUnit_Framework_MockObject_MockObject */
    private $transactionManager;

    /** @var BackendOperationHandler */
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
        $this->em     = $this->createMock(EntityManagerInterface::class);
        $this->em->method('getRepository')->willReturnMap([
            [Shop::class, $shopRepo],
            [Order::class, $orderRepo],
            [TransactionModel::class, $this->createMock(Repository::class)],
        ]);
        $this->router = $this->createMock(RouterInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->backendService     = $this->createMock(BackendService::class);
        $this->transactionManager = $this->createMock(TransactionManager::class);

        $this->handler = new BackendOperationHandler(
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
        $transaction->expects($this->never())->method('setNotificationUrl');

        $response = $this->createMock(Response::class);
        $response->expects($this->atLeastOnce())->method('getData')->willReturn([]);
        $this->backendService->method('process')->willReturn($response);

        /** @var ErrorAction $action */
        $action = $this->handler->execute(
            $transaction,
            $this->backendService,
            Operation::CANCEL
        );
        $this->assertInstanceOf(ErrorAction::class, $action);
        $this->assertEquals(ErrorAction::BACKEND_OPERATION_FAILED, $action->getCode());
    }

    public function testExecuteSuccessResponse()
    {
        $transaction = $this->createMock(Transaction::class);

        $response = $this->createMock(SuccessResponse::class);
        $response->method('getData')->willReturn([]);
        $response->method('getTransactionId')->willReturn('foo-bar-id');
        $this->backendService->method('process')->willReturn($response);

        $transactionEntity        = $this->createMock(TransactionModel::class);
        $this->transactionManager->expects($this->atLeastOnce())->method('createBackend')
                                 ->willReturn($transactionEntity);

        /** @var ViewAction $action */
        $action = $this->handler->execute(
            $transaction,
            $this->backendService,
            Operation::CANCEL
        );
        $this->assertInstanceOf(ViewAction::class, $action);
        $this->assertNull($action->getTemplate());
        $this->assertEquals([
            'success'       => true,
            'transactionId' => 'foo-bar-id',
        ], $action->getAssignments());
    }
}
