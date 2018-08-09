<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

namespace WirecardElasticEngine\Tests\Unit\Subscriber;

use Doctrine\ORM\EntityManagerInterface;
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
        $em              = $this->createMock(EntityManagerInterface::class);

        $subscriber = new FrontendSubscriber('', $templateManager, $paymentFactory, $em);

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
        $em              = $this->createMock(EntityManagerInterface::class);

        $subscriber = new FrontendSubscriber('', $templateManager, $paymentFactory, $em);
        $subscriber->onCollectLessFiles();
    }

    public function testOnPostDispatchCheckout()
    {
        /** @var \Enlight_Template_Manager|\PHPUnit_Framework_MockObject_MockObject $templateManager */
        /** @var PaymentFactory|\PHPUnit_Framework_MockObject_MockObject $paymentFactory */
        $templateManager = $this->createMock(\Enlight_Template_Manager::class);
        $paymentFactory  = $this->createMock(PaymentFactory::class);
        $em              = $this->createMock(EntityManagerInterface::class);

        $subscriber = new FrontendSubscriber('', $templateManager, $paymentFactory, $em);

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
