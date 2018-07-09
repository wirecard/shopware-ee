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

namespace WirecardShopwareElasticEngine\Tests\Unit\Components\Data;

use PHPUnit\Framework\TestCase;
use WirecardShopwareElasticEngine\Components\Actions\Action;
use WirecardShopwareElasticEngine\Components\Actions\RedirectAction;
use WirecardShopwareElasticEngine\Subscriber\ExtendOrder;

class ExtendOrderTest extends TestCase
{
    public function testSubscribedEvents()
    {
        $this->assertEquals([
            'Enlight_Controller_Action_PostDispatchSecure_Backend_Order' => 'onOrderPostDispatch',
        ], ExtendOrder::getSubscribedEvents());
    }

    public function testIndexAction()
    {
        /** @var \Enlight_View_Default|\PHPUnit_Framework_MockObject_MockObject $request */
        $view = $this->createMock(\Enlight_View_Default::class);
        $view->expects($this->once())->method('addTemplateDir')->with('fooDir/Resources/views');
        $view->expects($this->once())->method('extendsTemplate')->with('backend/wirecard_extend_order/app.js');

        /** @var \Enlight_Controller_Request_Request|\PHPUnit_Framework_MockObject_MockObject $request */
        $request = $this->createMock(\Enlight_Controller_Request_Request::class);
        $request->method('getActionName')->willReturn('index');

        /** @var \Enlight_Controller_Action|\PHPUnit_Framework_MockObject_MockObject $controller */
        $controller = $this->createMock(\Enlight_Controller_Action::class);
        $controller->expects($this->once())->method('View')->willReturn($view);
        $controller->expects($this->once())->method('Request')->willReturn($request);

        /** @var \Enlight_Controller_ActionEventArgs|\PHPUnit_Framework_MockObject_MockObject $args */
        $args = $this->createMock(\Enlight_Controller_ActionEventArgs::class);
        $args->expects($this->once())->method('getSubject')->willReturn($controller);

        $subscriber = new ExtendOrder('fooDir');
        $subscriber->onOrderPostDispatch($args);
    }

    public function testLoadAction()
    {
        $this->assertEquals([
            'Enlight_Controller_Action_PostDispatchSecure_Backend_Order' => 'onOrderPostDispatch',
        ], ExtendOrder::getSubscribedEvents());

        /** @var \Enlight_View_Default|\PHPUnit_Framework_MockObject_MockObject $request */
        $view = $this->createMock(\Enlight_View_Default::class);
        $view->expects($this->once())->method('extendsTemplate')->with('backend/wirecard_extend_order/view/detail/window.js');

        /** @var \Enlight_Controller_Request_Request|\PHPUnit_Framework_MockObject_MockObject $request */
        $request = $this->createMock(\Enlight_Controller_Request_Request::class);
        $request->method('getActionName')->willReturn('load');

        /** @var \Enlight_Controller_Action|\PHPUnit_Framework_MockObject_MockObject $controller */
        $controller = $this->createMock(\Enlight_Controller_Action::class);
        $controller->expects($this->once())->method('View')->willReturn($view);
        $controller->expects($this->once())->method('Request')->willReturn($request);

        /** @var \Enlight_Controller_ActionEventArgs|\PHPUnit_Framework_MockObject_MockObject $args */
        $args = $this->createMock(\Enlight_Controller_ActionEventArgs::class);
        $args->expects($this->once())->method('getSubject')->willReturn($controller);

        $subscriber = new ExtendOrder('');
        $subscriber->onOrderPostDispatch($args);
    }

    public function testOtherAction()
    {
        $this->assertEquals([
            'Enlight_Controller_Action_PostDispatchSecure_Backend_Order' => 'onOrderPostDispatch',
        ], ExtendOrder::getSubscribedEvents());

        /** @var \Enlight_View_Default|\PHPUnit_Framework_MockObject_MockObject $request */
        $view = $this->createMock(\Enlight_View_Default::class);
        $view->expects($this->never())->method('extendsTemplate');

        /** @var \Enlight_Controller_Request_Request|\PHPUnit_Framework_MockObject_MockObject $request */
        $request = $this->createMock(\Enlight_Controller_Request_Request::class);
        $request->method('getActionName')->willReturn('foobar');

        /** @var \Enlight_Controller_Action|\PHPUnit_Framework_MockObject_MockObject $controller */
        $controller = $this->createMock(\Enlight_Controller_Action::class);
        $controller->expects($this->once())->method('View')->willReturn($view);
        $controller->expects($this->once())->method('Request')->willReturn($request);

        /** @var \Enlight_Controller_ActionEventArgs|\PHPUnit_Framework_MockObject_MockObject $args */
        $args = $this->createMock(\Enlight_Controller_ActionEventArgs::class);
        $args->expects($this->once())->method('getSubject')->willReturn($controller);

        $subscriber = new ExtendOrder('');
        $subscriber->onOrderPostDispatch($args);
    }
}
