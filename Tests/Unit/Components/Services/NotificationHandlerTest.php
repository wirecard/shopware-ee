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
use Shopware\Models\Order\Status;
use Shopware\Models\Shop\Shop;
use Wirecard\PaymentSdk\BackendService;
use Wirecard\PaymentSdk\Response\Response;
use Wirecard\PaymentSdk\Response\SuccessResponse;
use Wirecard\PaymentSdk\Transaction\Transaction;
use WirecardElasticEngine\Components\Services\NotificationHandler;
use WirecardElasticEngine\Components\Services\TransactionManager;
use WirecardElasticEngine\Models\Transaction as TransactionModel;

class NotificationHandlerTest extends TestCase
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

    /** @var \sOrder|\PHPUnit_Framework_MockObject_MockObject */
    private $shopwareOrder;

    /** @var NotificationHandler */
    private $handler;

    public function setUp()
    {
        $shopRepo = $this->createMock(\Shopware\Models\Shop\Repository::class);
        $shopRepo->method('getActiveDefault')->willReturn($this->createMock(Shop::class));

        $orderRepo     = $this->createMock(Repository::class);
        $paymentStatus = $this->createMock(Status::class);
        $paymentStatus->method('getId')->willReturn(Status::PAYMENT_STATE_OPEN);
        $order = new Order();
        $order->setPaymentStatus($paymentStatus);
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
        $this->shopwareOrder      = $this->createMock(\sOrder::class);

        $this->handler = new NotificationHandler(
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

        $transaction = $this->handler->handleResponse(
            $this->shopwareOrder,
            $response,
            $this->backendService
        );
        $this->assertNull($transaction);
    }

    public function testExecuteSuccessResponse()
    {
        $response = $this->createMock(SuccessResponse::class);
        $response->method('getData')->willReturn([]);
        $response->method('getTransactionId')->willReturn('foo-bar-id');
        $this->backendService->method('process')->willReturn($response);

        $initialTransactionEntity = $this->createMock(TransactionModel::class);
        $transactionEntity        = $this->createMock(TransactionModel::class);
        $this->transactionManager->expects($this->atLeastOnce())->method('getInitialTransaction')
                                 ->willReturn($initialTransactionEntity);
        $this->transactionManager->expects($this->atLeastOnce())->method('createNotify')
                                 ->willReturn($transactionEntity);

        $notifyTransaction = $this->handler->handleResponse(
            $this->shopwareOrder,
            $response,
            $this->backendService
        );
        $this->assertSame($transactionEntity, $notifyTransaction);
    }

    public function testShouldSendMail()
    {
        $this->assertFalse(NotificationHandler::shouldSendStatusMail(null));
        $this->assertFalse(NotificationHandler::shouldSendStatusMail(Status::PAYMENT_STATE_OPEN));
        $this->assertTrue(NotificationHandler::shouldSendStatusMail(Status::PAYMENT_STATE_COMPLETELY_PAID));
        $this->assertTrue(NotificationHandler::shouldSendStatusMail(Status::PAYMENT_STATE_THE_PROCESS_HAS_BEEN_CANCELLED));
    }
}
