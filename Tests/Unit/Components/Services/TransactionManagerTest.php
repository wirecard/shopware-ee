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
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Wirecard\PaymentSdk\BackendService;
use Wirecard\PaymentSdk\Response\InteractionResponse;
use Wirecard\PaymentSdk\Response\Response;
use Wirecard\PaymentSdk\Response\SuccessResponse;
use WirecardElasticEngine\Components\Data\OrderSummary;
use WirecardElasticEngine\Components\Mapper\BasketMapper;
use WirecardElasticEngine\Components\Services\TransactionManager;
use WirecardElasticEngine\Models\Transaction;

class TransactionManagerTest extends TestCase
{
    /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $em;

    /** @var EntityRepository|\PHPUnit_Framework_MockObject_MockObject */
    private $repo;

    /** @var TransactionManager */
    private $manager;

    public function setUp()
    {
        $this->repo = $this->createMock(EntityRepository::class);

        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->em->method('getRepository')->willReturnMap([
            [Transaction::class, $this->repo],
        ]);

        $this->manager = new TransactionManager($this->em);
    }

    public function testCreateInitial()
    {
        $basketMapper = $this->createMock(BasketMapper::class);
        $basketMapper->method('getSignature')->willReturn('basket-signature');

        $orderSummary = $this->createMock(OrderSummary::class);
        $orderSummary->method('getPaymentUniqueId')->willReturn('payUniqueId');
        $orderSummary->method('getBasketMapper')->willReturn($basketMapper);

        $response = $this->createMock(Response::class);
        $response->method('getRequestId')->willReturn('req-id');

        $transaction = $this->manager->createInitial($orderSummary, $response);
        $this->assertInstanceOf(Transaction::class, $transaction);
        $this->assertEquals(Transaction::TYPE_INITIAL_RESPONSE, $transaction->getType());
        $this->assertEquals('payUniqueId', $transaction->getPaymentUniqueId());
        $this->assertEquals('basket-signature', $transaction->getBasketSignature());
        $this->assertEquals('req-id', $transaction->getRequestId());
        $this->assertEquals(Transaction::STATE_OPEN, $transaction->getState());
        $this->assertNull($transaction->getOrderNumber());
    }

    public function testCreateInteraction()
    {
        $parentTransaction = new Transaction(Transaction::TYPE_INITIAL_REQUEST);
        $parentTransaction->setPaymentUniqueId('parent-payUniqueId');
        $parentTransaction->setOrderNumber('order-num');
        $this->repo->expects($this->atLeastOnce())->method('findOneBy')->willReturn($parentTransaction);

        $response = $this->createMock(InteractionResponse::class);
        $response->method('getRequestId')->willReturn('req-id');

        $transaction = $this->manager->createInteraction($response);
        $this->assertInstanceOf(Transaction::class, $transaction);
        $this->assertEquals(Transaction::TYPE_INTERACTION, $transaction->getType());
        $this->assertEquals('parent-payUniqueId', $transaction->getPaymentUniqueId());
        $this->assertNull($transaction->getBasketSignature());
        $this->assertEquals('req-id', $transaction->getRequestId());
        $this->assertEquals('order-num', $transaction->getOrderNumber());
        $this->assertEquals(Transaction::STATE_OPEN, $transaction->getState());
    }

    public function testUpdateReturn()
    {
        $initialTransaction = new Transaction(Transaction::TYPE_INITIAL_REQUEST);
        $initialTransaction->setOrderNumber('order-num');
        $initialTransaction->setPaymentUniqueId('parent-payUniqueId');
        $initialTransaction->setState(Transaction::STATE_OPEN);

        $response = $this->createMock(InteractionResponse::class);
        $response->method('getRequestId')->willReturn('req-id');

        $transaction = $this->manager->createReturn($initialTransaction, $response);

        $this->assertEquals(Transaction::TYPE_RETURN, $transaction->getType());
        $this->assertEquals(Transaction::STATE_OPEN, $transaction->getState());
    }

    public function testReturnAfterNotify()
    {
        $initialTransaction = new Transaction(Transaction::TYPE_NOTIFY);
        $initialTransaction->setState(Transaction::STATE_OPEN);

        $response = $this->createMock(SuccessResponse::class);
        $response->method('getRequestId')->willReturn('req-id');

        $transaction = $this->manager->createReturn($initialTransaction, $response);

        $this->assertEquals($initialTransaction->getType(), $transaction->getType());
        $this->assertEquals($initialTransaction->getState(), $transaction->getState());
    }

    public function testUpdatePaymentNotify()
    {
        $initialTransaction = new Transaction(Transaction::TYPE_RETURN);
        $initialTransaction->setState(Transaction::STATE_OPEN);

        $this->repo->expects($this->atLeastOnce())->method('findBy')->willReturn(null);

        $response = $this->createMock(SuccessResponse::class);
        $response->method('getRequestId')->willReturn('req-id');

        $backendService = $this->createMock(BackendService::class);

        $transaction = $this->manager->createNotify($initialTransaction, $response, $backendService);

        $this->assertEquals(Transaction::TYPE_NOTIFY, $transaction->getType());
        $this->assertEquals(Transaction::STATE_OPEN, $transaction->getState());
    }

    public function testUpdateFinalBackendNotify()
    {
        $initialTransaction = new Transaction(Transaction::TYPE_NOTIFY);
        $initialTransaction->setState(Transaction::STATE_OPEN);

        $backendTransaction = new Transaction(Transaction::TYPE_BACKEND);
        $backendTransaction->setState(Transaction::STATE_OPEN);
        $transactions = array(
            $backendTransaction
        );
        $this->repo->expects($this->atLeastOnce())->method('findBy')->willReturn($transactions);

        $response = $this->createMock(SuccessResponse::class);
        $response->method('getRequestId')->willReturn('req-id');

        $backendService = $this->createMock(BackendService::class);
        $backendService->method('isFinal')->willReturn(true);

        $transaction = $this->manager->createNotify($initialTransaction, $response, $backendService);

        $this->assertEquals(Transaction::TYPE_NOTIFY, $transaction->getType());
        $this->assertEquals(Transaction::STATE_CLOSED, $transaction->getState());
    }

    public function testUpdateBackendNotify()
    {
        $initialTransaction = new Transaction(Transaction::TYPE_NOTIFY);
        $initialTransaction->setState(Transaction::STATE_OPEN);

        $backendTransaction = new Transaction(Transaction::TYPE_BACKEND);
        $backendTransaction->setState(Transaction::STATE_OPEN);
        $transactions = array(
            $backendTransaction
        );
        $this->repo->expects($this->atLeastOnce())->method('findBy')->willReturn($transactions);

        $response = $this->createMock(SuccessResponse::class);
        $response->method('getRequestId')->willReturn('req-id');

        $backendService = $this->createMock(BackendService::class);
        // isFinal will return false if it is e.g. Capture
        $backendService->method('isFinal')->willReturn(false);

        $transaction = $this->manager->createNotify($initialTransaction, $response, $backendService);

        $this->assertEquals(Transaction::TYPE_NOTIFY, $transaction->getType());
        $this->assertEquals(Transaction::STATE_OPEN, $transaction->getState());
    }

    public function testCreateBackend()
    {
        $initialTransaction = $this->createMock(Transaction::class);
        $initialTransaction->expects($this->atLeastOnce())->method('isInitial')->willReturn(true);
        $initialTransaction->expects($this->atLeastOnce())->method('getOrderNumber')->willReturn('order-num');
        $initialTransaction->expects($this->atLeastOnce())->method('getPaymentUniqueId')
                           ->willReturn('parent-payUniqueId');

        $parentTransaction = $this->createMock(Transaction::class);
        $parentTransaction->method('getAmount')->willReturn(55.99);
        $parentTransaction->expects($this->never())->method('setState');
        $this->repo->expects($this->once())->method('findOneBy')->willReturn($initialTransaction);
        $this->repo->expects($this->atLeast(2))->method('findBy')
                   ->willReturnOnConsecutiveCalls([$parentTransaction]);

        $response = $this->createMock(SuccessResponse::class);
        $response->method('findElement')->willReturn('response-payUniqueId');
        $response->method('getRequestId')->willReturn('req-id');

        $transaction = $this->manager->createBackend($response);
        $this->assertInstanceOf(Transaction::class, $transaction);
        $this->assertEquals(Transaction::TYPE_BACKEND, $transaction->getType());
        $this->assertEquals('parent-payUniqueId', $transaction->getPaymentUniqueId());
        $this->assertNull($transaction->getBasketSignature());
        $this->assertEquals('req-id', $transaction->getRequestId());
        $this->assertEquals('order-num', $transaction->getOrderNumber());
        $this->assertEquals(Transaction::STATE_OPEN, $transaction->getState());
    }

    public function testCreateBackendAndCloseParentTransaction()
    {
        $initialTransaction = $this->createMock(Transaction::class);
        $initialTransaction->expects($this->atLeastOnce())->method('isInitial')->willReturn(true);
        $initialTransaction->expects($this->atLeastOnce())->method('getOrderNumber')->willReturn('order-num');
        $initialTransaction->expects($this->atLeastOnce())->method('getPaymentUniqueId')
                           ->willReturn('parent-payUniqueId');

        $childTransaction = $this->createMock(Transaction::class);
        $childTransaction->method('getAmount')->willReturn(35.99);
        $childTransaction2 = $this->createMock(Transaction::class);
        $childTransaction2->method('getAmount')->willReturn(20);

        $parentTransaction = $this->createMock(Transaction::class);
        $parentTransaction->method('getAmount')->willReturn(55.99);
        $parentTransaction->expects($this->once())->method('setState')->with(Transaction::STATE_CLOSED);
        $this->repo->expects($this->once())->method('findOneBy')->willReturn($initialTransaction);
        $this->repo->expects($this->atLeast(2))->method('findBy')
                   ->willReturnOnConsecutiveCalls([$parentTransaction], [$childTransaction, $childTransaction2]);

        $response = $this->createMock(SuccessResponse::class);
        $response->method('findElement')->willReturn('response-payUniqueId');
        $response->method('getRequestId')->willReturn('req-id');

        $transaction = $this->manager->createBackend($response);
        $this->assertInstanceOf(Transaction::class, $transaction);
        $this->assertEquals(Transaction::TYPE_BACKEND, $transaction->getType());
        $this->assertEquals('parent-payUniqueId', $transaction->getPaymentUniqueId());
        $this->assertNull($transaction->getBasketSignature());
        $this->assertEquals('req-id', $transaction->getRequestId());
        $this->assertEquals('order-num', $transaction->getOrderNumber());
        $this->assertEquals(Transaction::STATE_OPEN, $transaction->getState());
    }
}
