<?php
/**
 * Shop System Plugins - Terms of Use
 *
 * The plugins offered are provided free of charge by Wirecard AG and are explicitly not part
 * of the Wirecard AG range of products and services.
 *
 * They have been tested and approved for full functionality in the standard configuration
 * (status on delivery) of the corresponding shop system. They are under General Public
 * License version 3 (GPLv3) and can be used, developed and passed on to third parties under
 * the same terms.
 *
 * However, Wirecard AG does not provide any guarantee or accept any liability for any errors
 * occurring when used in an enhanced, customized shop system configuration.
 *
 * Operation in an enhanced, customized configuration is at your own risk and requires a
 * comprehensive test phase by the user of the plugin.
 *
 * Customers use the plugins at their own risk. Wirecard AG does not guarantee their full
 * functionality neither does Wirecard AG assume liability for any disadvantages related to
 * the use of the plugins. Additionally, Wirecard AG does not guarantee the full functionality
 * for customized shop systems or installed plugins of other vendors of plugins within the same
 * shop system.
 *
 * Customers are responsible for testing the plugin's functionality before starting productive
 * operation.
 *
 * By installing the plugin into the shop system the customer agrees to these terms of use.
 * Please do not use the plugin if you do not agree to these terms of use!
 */

namespace WirecardShopwareElasticEngine\Tests\Unit\Components\Services;

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
use WirecardShopwareElasticEngine\Components\Actions\ErrorAction;
use WirecardShopwareElasticEngine\Components\Actions\ViewAction;
use WirecardShopwareElasticEngine\Components\Services\BackendOperationHandler;
use WirecardShopwareElasticEngine\Components\Services\TransactionManager;
use WirecardShopwareElasticEngine\Models\Transaction as TransactionModel;

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

        $transactionEntity = $this->createMock(TransactionModel::class);
        $this->transactionManager->method('create')->willReturn($transactionEntity);

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
