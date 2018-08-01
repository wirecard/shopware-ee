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

    public function testCreateReturn()
    {
        $initialTransaction = $this->createMock(Transaction::class);
        $initialTransaction->expects($this->atLeastOnce())->method('getId')->willReturn(1);
        $initialTransaction->expects($this->atLeastOnce())->method('getOrderNumber')->willReturn('order-num');
        $initialTransaction->expects($this->atLeastOnce())->method('getPaymentUniqueId')
                           ->willReturn('parent-payUniqueId');

        $relatedTransaction = new Transaction(Transaction::TYPE_INTERACTION);
        $this->assertNull($relatedTransaction->getOrderNumber());
        $this->repo->expects($this->atLeastOnce())->method('findBy')->willReturn([
            $initialTransaction,
            $relatedTransaction,
        ]);

        $response = $this->createMock(InteractionResponse::class);
        $response->method('getRequestId')->willReturn('req-id');

        $transaction = $this->manager->createReturn($initialTransaction, $response);
        $this->assertInstanceOf(Transaction::class, $transaction);
        $this->assertEquals(Transaction::TYPE_RETURN, $transaction->getType());
        $this->assertEquals('parent-payUniqueId', $transaction->getPaymentUniqueId());
        $this->assertNull($transaction->getBasketSignature());
        $this->assertEquals('req-id', $transaction->getRequestId());
        $this->assertEquals('order-num', $transaction->getOrderNumber());
        $this->assertEquals(Transaction::STATE_OPEN, $transaction->getState());
        $this->assertEquals('order-num', $relatedTransaction->getOrderNumber());
    }

    public function testCreateNotify()
    {
        $initialTransaction = $this->createMock(Transaction::class);
        $initialTransaction->expects($this->atLeastOnce())->method('getOrderNumber')->willReturn('order-num');
        $initialTransaction->expects($this->atLeastOnce())->method('getPaymentUniqueId')
                           ->willReturn('parent-payUniqueId');

        $parentTransaction = new Transaction(Transaction::TYPE_NOTIFY);
        $this->repo->expects($this->atLeastOnce())->method('findOneBy')->willReturn($parentTransaction);

        $response = $this->createMock(InteractionResponse::class);
        $response->method('getRequestId')->willReturn('req-id');

        $backendService = $this->createMock(BackendService::class);
        $backendService->method('isFinal')->willReturn(true);

        $transaction = $this->manager->createNotify($initialTransaction, $response, $backendService);
        $this->assertInstanceOf(Transaction::class, $transaction);
        $this->assertEquals(Transaction::TYPE_NOTIFY, $transaction->getType());
        $this->assertEquals('parent-payUniqueId', $transaction->getPaymentUniqueId());
        $this->assertNull($transaction->getBasketSignature());
        $this->assertEquals('req-id', $transaction->getRequestId());
        $this->assertEquals('order-num', $transaction->getOrderNumber());
        $this->assertEquals(Transaction::STATE_CLOSED, $transaction->getState());
    }

    public function testCreateNotifyOpenState()
    {
        $initialTransaction = $this->createMock(Transaction::class);
        $parentTransaction  = new Transaction(Transaction::TYPE_NOTIFY);
        $this->repo->expects($this->atLeastOnce())->method('findOneBy')->willReturn($parentTransaction);
        $response       = $this->createMock(InteractionResponse::class);
        $backendService = $this->createMock(BackendService::class);
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

        $parentTransaction = new Transaction(Transaction::TYPE_NOTIFY);
        $this->repo->method('findOneBy')->willReturnOnConsecutiveCalls($initialTransaction, $parentTransaction);

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
