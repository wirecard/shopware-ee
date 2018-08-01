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

namespace WirecardElasticEngine\Tests\Unit\Subscriber;

use PHPUnit\Framework\TestCase;
use WirecardElasticEngine\Components\Services\PaymentFactory;
use WirecardElasticEngine\Subscriber\FrontendSubscriber;

class FrontendSubscriberTest extends TestCase
{
    public function testSubscribedEvents()
    {
        $this->assertEquals([
            'Enlight_Controller_Action_PreDispatch'                          => 'onPreDispatch',
            'Theme_Compiler_Collect_Plugin_Less'                             => 'onCollectLessFiles',
            'Enlight_Controller_Action_PostDispatchSecure_Frontend_Checkout' => 'onPostDispatchCheckout',
        ], FrontendSubscriber::getSubscribedEvents());
    }

    public function testOnPreDispatch()
    {
        /** @var \Enlight_Template_Manager|\PHPUnit_Framework_MockObject_MockObject $templateManager */
        /** @var PaymentFactory|\PHPUnit_Framework_MockObject_MockObject $paymentFactory */
        $templateManager = $this->createMock(\Enlight_Template_Manager::class);
        $paymentFactory  = $this->createMock(PaymentFactory::class);

        $subscriber = new FrontendSubscriber('', $templateManager, $paymentFactory);

        /** @var \Enlight_Controller_ActionEventArgs|\PHPUnit_Framework_MockObject_MockObject $args */
        $args = $this->createMock(\Enlight_Controller_ActionEventArgs::class);
        $subscriber->onPreDispatch($args);
    }

    public function testOnCollectLessFiles()
    {
        /** @var \Enlight_Template_Manager|\PHPUnit_Framework_MockObject_MockObject $templateManager */
        /** @var PaymentFactory|\PHPUnit_Framework_MockObject_MockObject $paymentFactory */
        $templateManager = $this->createMock(\Enlight_Template_Manager::class);
        $paymentFactory  = $this->createMock(PaymentFactory::class);

        $subscriber = new FrontendSubscriber('', $templateManager, $paymentFactory);
        $subscriber->onCollectLessFiles();
    }

    public function testOnPostDispatchCheckout()
    {
        /** @var \Enlight_Template_Manager|\PHPUnit_Framework_MockObject_MockObject $templateManager */
        /** @var PaymentFactory|\PHPUnit_Framework_MockObject_MockObject $paymentFactory */
        $templateManager = $this->createMock(\Enlight_Template_Manager::class);
        $paymentFactory  = $this->createMock(PaymentFactory::class);

        $subscriber = new FrontendSubscriber('', $templateManager, $paymentFactory);

        $view       = $this->createMock(\Enlight_View_Default::class);
        $request    = $this->createMock(\Enlight_Controller_Request_Request::class);
        $controller = $this->createMock(\Enlight_Controller_Action::class);
        $controller->expects($this->atLeastOnce())->method('View')->willReturn($view);
        $controller->expects($this->atLeastOnce())->method('Request')->willReturn($request);

        /** @var \Enlight_Controller_ActionEventArgs|\PHPUnit_Framework_MockObject_MockObject $args */
        $args = $this->createMock(\Enlight_Controller_ActionEventArgs::class);
        $args->expects($this->atLeastOnce())->method('getSubject')->willReturn($controller);

        $subscriber->onPostDispatchCheckout($args);
    }
}
