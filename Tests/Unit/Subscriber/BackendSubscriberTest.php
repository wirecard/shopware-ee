<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

namespace WirecardElasticEngine\Tests\Unit\Subscriber;

use PHPUnit\Framework\TestCase;
use WirecardElasticEngine\Subscriber\BackendSubscriber;
use WirecardElasticEngine\Components\Services\PaymentFactory;
use WirecardElasticEngine\Components\Payments\RatepayInvoicePayment;

class BackendSubscriberTest extends TestCase
{
    public function testSubscribedEvents()
    {
        $this->assertEquals([
            'Enlight_Controller_Action_PostDispatchSecure_Backend_Order' => 'onOrderPostDispatch',
            'Enlight_Controller_Action_PostDispatchSecure_Backend_Index' => 'onLoadBackendIndex',
            'Enlight_Controller_Action_Backend_Payment_UpdatePayments'   => 'onUpdatePayments',
        ], BackendSubscriber::getSubscribedEvents());
    }

    public function testIndexAction()
    {
        $view = $this->createMock(\Enlight_View_Default::class);
        $view->expects($this->once())->method('addTemplateDir')->with('fooDir/Resources/views');
        $view->expects($this->once())->method('extendsTemplate')->with('backend/wirecard_elastic_engine_extend_order/app.js');

        $request = $this->createMock(\Enlight_Controller_Request_Request::class);
        $request->method('getActionName')->willReturn('index');

        $controller = $this->createMock(\Enlight_Controller_Action::class);
        $controller->expects($this->atLeastOnce())->method('View')->willReturn($view);
        $controller->expects($this->atLeastOnce())->method('Request')->willReturn($request);

        /** @var \Enlight_Controller_ActionEventArgs|\PHPUnit_Framework_MockObject_MockObject $args */
        $args = $this->createMock(\Enlight_Controller_ActionEventArgs::class);
        $args->expects($this->atLeastOnce())->method('getSubject')->willReturn($controller);

        $subscriber = new BackendSubscriber('fooDir');
        $subscriber->onOrderPostDispatch($args);
    }

    public function testLoadAction()
    {
        $view = $this->createMock(\Enlight_View_Default::class);
        $view->expects($this->once())->method('extendsTemplate')
             ->with('backend/wirecard_elastic_engine_extend_order/view/detail/window.js');

        $request = $this->createMock(\Enlight_Controller_Request_Request::class);
        $request->method('getActionName')->willReturn('load');

        $controller = $this->createMock(\Enlight_Controller_Action::class);
        $controller->expects($this->atLeastOnce())->method('View')->willReturn($view);
        $controller->expects($this->atLeastOnce())->method('Request')->willReturn($request);

        /** @var \Enlight_Controller_ActionEventArgs|\PHPUnit_Framework_MockObject_MockObject $args */
        $args = $this->createMock(\Enlight_Controller_ActionEventArgs::class);
        $args->expects($this->atLeastOnce())->method('getSubject')->willReturn($controller);

        $subscriber = new BackendSubscriber('');
        $subscriber->onOrderPostDispatch($args);
    }

    public function testOtherAction()
    {
        $view = $this->createMock(\Enlight_View_Default::class);
        $view->expects($this->never())->method('extendsTemplate');

        $request = $this->createMock(\Enlight_Controller_Request_Request::class);
        $request->method('getActionName')->willReturn('foobar');

        $controller = $this->createMock(\Enlight_Controller_Action::class);
        $controller->expects($this->atLeastOnce())->method('View')->willReturn($view);
        $controller->expects($this->atLeastOnce())->method('Request')->willReturn($request);

        /** @var \Enlight_Controller_ActionEventArgs|\PHPUnit_Framework_MockObject_MockObject $args */
        $args = $this->createMock(\Enlight_Controller_ActionEventArgs::class);
        $args->expects($this->atLeastOnce())->method('getSubject')->willReturn($controller);

        $subscriber = new BackendSubscriber('');
        $subscriber->onOrderPostDispatch($args);
    }

    public function testOnUpdatePayments()
    {
        $view = $this->createMock(\Enlight_View_Default::class);

        $request = $this->createMock(\Enlight_Controller_Request_Request::class);
        $request->expects($this->once())->method('getRawBody')->willReturn(json_encode([
            'name'   => 'wirecard_elastic_engine_ratepay_invoice',
            'active' => true,
        ]));

        $paymentInstance = $this->createMock(RatepayInvoicePayment::class);
        $paymentInstance->expects($this->once())->method('validateUpdate')->willThrowException(new \Exception());

        $paymentFactory = $this->createMock(PaymentFactory::class);
        $paymentFactory->expects($this->once())->method('isSupportedPayment')->willReturn(true);
        $paymentFactory->expects($this->once())->method('create')->willReturn($paymentInstance);

        $payment = $this->createMock(\Shopware_Controllers_Backend_Payment::class);
        $payment->expects($this->once())->method('get')->with('wirecard_elastic_engine.payment_factory')
                ->willReturn($paymentFactory);
        $payment->expects($this->once())->method('Request')->willReturn($request);
        $payment->expects($this->once())->method('View')->willReturn($view);

        /** @var \Enlight_Controller_ActionEventArgs|\PHPUnit_Framework_MockObject_MockObject $args */
        $args = $this->createMock(\Enlight_Controller_ActionEventArgs::class);
        $args->expects($this->once())->method('getSubject')->willReturn($payment);

        $subscriber = new BackendSubscriber('');
        $this->assertEquals(false, $subscriber->onUpdatePayments($args));
    }
}
