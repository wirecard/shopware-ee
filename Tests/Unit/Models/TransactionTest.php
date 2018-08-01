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

namespace WirecardElasticEngine\Tests\Unit\Models;

use Shopware\Models\Order\Status;
use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Exception\MalformedResponseException;
use Wirecard\PaymentSdk\Response\FormInteractionResponse;
use Wirecard\PaymentSdk\Response\InteractionResponse;
use Wirecard\PaymentSdk\Response\Response;
use Wirecard\PaymentSdk\Response\SuccessResponse;
use Wirecard\PaymentSdk\TransactionService;
use WirecardElasticEngine\Components\Payments\Payment;
use WirecardElasticEngine\Models\Transaction;
use WirecardElasticEngine\Tests\Unit\ModelTestCase;

class TransactionTest extends ModelTestCase
{
    /**
     * @var Transaction
     */
    protected $model;

    public function getModel()
    {
        return new Transaction(Transaction::TYPE_INITIAL_RESPONSE);
    }

    public function testGetId()
    {
        $this->assertNull($this->model->getId());
    }

    public function testSettersAndGetters()
    {
        $this->assertTrue($this->model->isInitial());
        $this->assertGetterAndSetter('orderNumber', 1337);
        $this->assertGetterAndSetter('basketSignature', 'unique-signature');
        $this->assertGetterAndSetter('paymentStatus', Status::PAYMENT_STATE_COMPLETELY_PAID);
        $this->assertGetterAndSetter('state', Transaction::STATE_CLOSED, Transaction::STATE_OPEN);
        $this->assertGetterAndSetter('createdAt', new \DateTime(), $this->model->getCreatedAt());
        $this->assertGetterAndSetter('statusMessage', 'error');

        $this->assertEquals([
            'id'                           => null,
            'orderNumber'                  => 1337,
            'paymentUniqueId'              => null,
            'paymentMethod'                => null,
            'transactionType'              => null,
            'transactionId'                => null,
            'parentTransactionId'          => null,
            'providerTransactionId'        => null,
            'providerTransactionReference' => null,
            'requestId'                    => null,
            'type'                         => Transaction::TYPE_INITIAL_RESPONSE,
            'amount'                       => null,
            'currency'                     => null,
            'createdAt'                    => $this->model->getCreatedAt()->format(\DateTime::W3C),
            'state'                        => Transaction::STATE_CLOSED,
            'response'                     => null,
            'request'                      => null,
            'statusMessage'                => 'error',
        ], $this->model->toArray());
    }

    public function testDefaults()
    {
        $transaction = new Transaction(Transaction::TYPE_INITIAL_RESPONSE);
        $this->assertNull($transaction->getOrderNumber());
        $this->assertNull($transaction->getParentTransactionId());
        $this->assertNull($transaction->getTransactionType());
        $this->assertNull($transaction->getTransactionId());
        $this->assertNull($transaction->getProviderTransactionId());
        $this->assertNull($transaction->getProviderTransactionReference());
        $this->assertNull($transaction->getRequestId());
        $this->assertNull($transaction->getAmount());
        $this->assertNull($transaction->getBasketSignature());
        $this->assertNull($transaction->getPaymentStatus());
        $this->assertNull($transaction->getCurrency());
        $this->assertEquals(Transaction::TYPE_INITIAL_RESPONSE, $transaction->getType());
        $this->assertEquals(Transaction::STATE_OPEN, $transaction->getState());
        $this->assertNotNull($transaction->getCreatedAt());
        $this->assertNull($transaction->getResponse());
        $this->assertTrue($transaction->isInitial());
        $this->assertNull($transaction->getStatusMessage());
    }

    public function testWithResponse()
    {
        $transaction = new Transaction(Transaction::TYPE_INITIAL_RESPONSE);
        $this->assertEquals(Transaction::TYPE_INITIAL_RESPONSE, $transaction->getType());
        $this->assertEquals(Transaction::STATE_OPEN, $transaction->getState());
        $this->assertTrue($transaction->isInitial());

        /** @var Response|\PHPUnit_Framework_MockObject_MockObject $response */
        $response = $this->createMock(Response::class);
        $response->expects($this->atLeastOnce())->method('getRequestId')->willReturn('req-id');
        $response->expects($this->atLeastOnce())->method('getTransactionType')->willReturn('trans-type');
        $response->expects($this->atLeastOnce())->method('getRequestedAmount')->willReturn(null);
        $response->expects($this->atLeastOnce())->method('getData')->willReturn([]);
        $transaction->setResponse($response);
        $this->assertEquals([], $transaction->getResponse());

        $this->assertNull($transaction->getPaymentUniqueId());
        $transaction->setPaymentUniqueId('payunique-id');
        $this->assertEquals('payunique-id', $transaction->getPaymentUniqueId());

        $this->assertEquals([
            'id'                           => null,
            'orderNumber'                  => null,
            'paymentUniqueId'              => 'payunique-id',
            'paymentMethod'                => null,
            'transactionType'              => 'trans-type',
            'transactionId'                => null,
            'parentTransactionId'          => null,
            'providerTransactionId'        => null,
            'providerTransactionReference' => null,
            'requestId'                    => 'req-id',
            'type'                         => Transaction::TYPE_INITIAL_RESPONSE,
            'amount'                       => null,
            'currency'                     => null,
            'createdAt'                    => $transaction->getCreatedAt()->format(\DateTime::W3C),
            'state'                        => Transaction::STATE_OPEN,
            'response'                     => [],
            'request'                      => null,
            'statusMessage'                => null,
        ], $transaction->toArray());
    }

    public function testWithSuccessResponse()
    {
        $transaction = new Transaction(Transaction::TYPE_RETURN);
        $this->assertEquals(Transaction::TYPE_RETURN, $transaction->getType());
        $this->assertEquals(Transaction::STATE_OPEN, $transaction->getState());
        $this->assertFalse($transaction->isInitial());

        /** @var SuccessResponse|\PHPUnit_Framework_MockObject_MockObject $response */
        $response = $this->createMock(SuccessResponse::class);
        $response->expects($this->atLeastOnce())->method('getRequestId')->willReturn('req-id');
        $response->expects($this->atLeastOnce())->method('getTransactionType')
                 ->willReturn(Payment::TRANSACTION_TYPE_PURCHASE);
        $response->expects($this->atLeastOnce())->method('getTransactionId')->willReturn('trans-id');
        $response->expects($this->atLeastOnce())->method('getProviderTransactionId')->willReturn('provider-id');
        $response->expects($this->atLeastOnce())->method('getProviderTransactionReference')->willReturn('provider-ref');
        $response->expects($this->atLeastOnce())->method('getParentTransactionId')->willReturn('parent-id');
        $response->expects($this->atLeastOnce())->method('getPaymentMethod')->willReturn('paymethod');
        $response->expects($this->atLeastOnce())->method('findElement')->willReturn('order-num');
        $response->expects($this->atLeastOnce())->method('getRequestedAmount')->willReturn(new Amount(1.23, 'USD'));
        $response->expects($this->atLeastOnce())->method('getData')->willReturn([
            'transaction-id' => 'trans-id',
            'request-id'     => 'req-id',
        ]);
        $transaction->setResponse($response);
        $this->assertEquals([
            'transaction-id' => 'trans-id',
            'request-id'     => 'req-id',
        ], $transaction->getResponse());

        $transaction->setOrderNumber(1337);

        $this->assertEquals([
            'id'                           => null,
            'orderNumber'                  => 1337,
            'paymentUniqueId'              => 'order-num',
            'paymentMethod'                => 'paymethod',
            'transactionType'              => Payment::TRANSACTION_TYPE_PURCHASE,
            'transactionId'                => 'trans-id',
            'parentTransactionId'          => 'parent-id',
            'providerTransactionId'        => 'provider-id',
            'providerTransactionReference' => 'provider-ref',
            'requestId'                    => 'req-id',
            'type'                         => Transaction::TYPE_RETURN,
            'amount'                       => 1.23,
            'currency'                     => 'USD',
            'createdAt'                    => $transaction->getCreatedAt()->format(\DateTime::W3C),
            'state'                        => Transaction::STATE_OPEN,
            'response'                     => [
                'transaction-id' => 'trans-id',
                'request-id'     => 'req-id',
            ],
            'request'                      => null,
            'statusMessage'                => null,
        ], $transaction->toArray());
    }

    public function testWithInteractionResponse()
    {
        $transaction = new Transaction(Transaction::TYPE_RETURN);
        $this->assertEquals(Transaction::TYPE_RETURN, $transaction->getType());
        $this->assertEquals(Transaction::STATE_OPEN, $transaction->getState());
        $this->assertFalse($transaction->isInitial());

        $this->assertNull($transaction->getPaymentUniqueId());
        $transaction->setPaymentUniqueId('payunique-id');
        $this->assertEquals('payunique-id', $transaction->getPaymentUniqueId());

        /** @var InteractionResponse|\PHPUnit_Framework_MockObject_MockObject $response */
        $response = $this->createMock(InteractionResponse::class);
        $response->expects($this->atLeastOnce())->method('getRequestId')->willReturn('req-id');
        $response->expects($this->atLeastOnce())->method('getTransactionType')->willReturn('trans-type');
        $response->expects($this->atLeastOnce())->method('getTransactionId')->willReturn('trans-id');
        $response->expects($this->atLeastOnce())->method('getRequestedAmount')->willReturn(null);
        $response->expects($this->never())->method('findElement')->willReturn('order-num');
        $response->expects($this->atLeastOnce())->method('getData')->willReturn([]);
        $transaction->setResponse($response);
        $this->assertEquals([], $transaction->getResponse());

        $this->assertEquals([
            'id'                           => null,
            'orderNumber'                  => null,
            'paymentUniqueId'              => 'payunique-id',
            'paymentMethod'                => null,
            'transactionType'              => 'trans-type',
            'transactionId'                => 'trans-id',
            'parentTransactionId'          => null,
            'providerTransactionId'        => null,
            'providerTransactionReference' => null,
            'requestId'                    => 'req-id',
            'type'                         => Transaction::TYPE_RETURN,
            'amount'                       => null,
            'currency'                     => null,
            'createdAt'                    => $transaction->getCreatedAt()->format(\DateTime::W3C),
            'state'                        => Transaction::STATE_OPEN,
            'response'                     => [],
            'request'                      => null,
            'statusMessage'                => null,
        ], $transaction->toArray());
    }


    public function testWithFormInteractionResponse()
    {
        $transaction = new Transaction(Transaction::TYPE_RETURN);
        $this->assertEquals(Transaction::TYPE_RETURN, $transaction->getType());
        $this->assertEquals(Transaction::STATE_OPEN, $transaction->getState());
        $this->assertFalse($transaction->isInitial());

        /** @var FormInteractionResponse|\PHPUnit_Framework_MockObject_MockObject $response */
        $response = $this->createMock(FormInteractionResponse::class);
        $response->expects($this->atLeastOnce())->method('getRequestId')->willReturn('req-id');
        $response->expects($this->atLeastOnce())->method('getTransactionType')->willReturn('trans-type');
        $response->expects($this->atLeastOnce())->method('getTransactionId')->willReturn('trans-id');
        $response->expects($this->atLeastOnce())->method('getRequestedAmount')->willReturn(null);
        $response->expects($this->atLeastOnce())->method('findElement')
                 ->willThrowException(new MalformedResponseException());
        $response->expects($this->atLeastOnce())->method('getData')->willReturn([]);
        $transaction->setResponse($response);
        $this->assertEquals([], $transaction->getResponse());

        $this->assertNull($transaction->getPaymentUniqueId());
        $this->assertEquals([
            'id'                           => null,
            'orderNumber'                  => null,
            'paymentUniqueId'              => null,
            'paymentMethod'                => null,
            'transactionType'              => 'trans-type',
            'transactionId'                => 'trans-id',
            'parentTransactionId'          => null,
            'providerTransactionId'        => null,
            'providerTransactionReference' => null,
            'requestId'                    => 'req-id',
            'type'                         => Transaction::TYPE_RETURN,
            'amount'                       => null,
            'currency'                     => null,
            'createdAt'                    => $transaction->getCreatedAt()->format(\DateTime::W3C),
            'state'                        => Transaction::STATE_OPEN,
            'response'                     => [],
            'request'                      => null,
            'statusMessage'                => null,
        ], $transaction->toArray());
    }

    public function testWithRequest()
    {
        $transaction = new Transaction(Transaction::TYPE_INITIAL_REQUEST);
        $this->assertEquals(Transaction::TYPE_INITIAL_REQUEST, $transaction->getType());
        $this->assertEquals(Transaction::STATE_OPEN, $transaction->getState());
        $this->assertTrue($transaction->isInitial());

        /** @var SuccessResponse|\PHPUnit_Framework_MockObject_MockObject $response */
        $request = [
            TransactionService::REQUEST_ID => 'req-id',
            'transaction_type'             => Payment::TRANSACTION_TYPE_AUTHORIZATION,
            'requested_amount'             => 10.12,
            'requested_amount_currency'    => 'USD',
            'payment_method'               => 'paymethod',
        ];
        $transaction->setRequest($request);
        $this->assertEquals($request, $transaction->getRequest());

        $this->assertEquals([
            'id'                           => null,
            'orderNumber'                  => null,
            'paymentUniqueId'              => null,
            'paymentMethod'                => 'paymethod',
            'transactionType'              => Payment::TRANSACTION_TYPE_AUTHORIZATION,
            'transactionId'                => null,
            'parentTransactionId'          => null,
            'providerTransactionId'        => null,
            'providerTransactionReference' => null,
            'requestId'                    => 'req-id',
            'type'                         => Transaction::TYPE_INITIAL_REQUEST,
            'amount'                       => 10.12,
            'currency'                     => 'USD',
            'createdAt'                    => $transaction->getCreatedAt()->format(\DateTime::W3C),
            'state'                        => Transaction::STATE_OPEN,
            'response'                     => null,
            'request'                      => $request,
            'statusMessage'                => null,
        ], $transaction->toArray());
    }
}
