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

namespace WirecardShopwareElasticEngine\Subscriber;

use Enlight\Event\SubscriberInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Shopware\Components\Theme\LessDefinition;
use Shopware\Models\Order\Order;
use Shopware\Models\Order\Status;

class FrontendSubscriber implements SubscriberInterface
{
    /**
     * @var string
     */
    private $pluginDirectory;

    /**
     * @var \Enlight_Template_Manager
     */
    private $templateManager;

    /**
     * @param string                    $pluginDirectory
     * @param \Enlight_Template_Manager $templateManager
     */
    public function __construct($pluginDirectory, \Enlight_Template_Manager $templateManager)
    {
        $this->pluginDirectory = $pluginDirectory;
        $this->templateManager = $templateManager;
    }

    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PreDispatch'                          => 'onPreDispatch',
            'Theme_Compiler_Collect_Plugin_Less'                             => 'onCollectLessFiles',
            'Enlight_Controller_Action_PostDispatchSecure_Frontend_Checkout' => 'onPostDispatchCheckout',
        ];
    }

    public function onPreDispatch(\Enlight_Controller_ActionEventArgs $args)
    {
        $this->templateManager->addTemplateDir($this->pluginDirectory . '/Resources/views');
    }

    public function onCollectLessFiles()
    {
        $less = new LessDefinition(
            [],
            [$this->pluginDirectory . '/Resources/views/frontend/_public/src/less/all.less'],
            $this->pluginDirectory
        );

        return new ArrayCollection([$less]);
    }

    public function onPostDispatchCheckout(\Enlight_Controller_ActionEventArgs $args)
    {
        $controller = $args->getSubject();
        $request    = $controller->Request();
        $view       = $controller->View();

        if ($request->getActionName() === 'finish') {
            $this->assignPaymentStatus($view);
        }

        $errorCode = $request->getParam('wirecard_elastic_engine_error_code');
        if ($errorCode) {
            $view->assign('wirecardElasticEngineErrorCode', $errorCode);
            $view->assign('wirecardElasticEngineErrorMessage', $request->getParam('wirecard_elastic_engine_error_msg'));
        }

        if ($request->getParam('wirecard_elastic_engine_update_cart')) {
            $view->assign('wirecardElasticEngineUpdateCart', true);
        }
    }

    private function assignPaymentStatus(\Enlight_View_Default $view)
    {
        if (strpos($view->getAssign('sPayment'), 'wirecard_elastic_engine') === false) {
            return;
        }
        $sOrderNumber = $view->getAssign('sOrderNumber');
        if (! $sOrderNumber) {
            return;
        }
        /** @var Order $order */
        $order = Shopware()->Models()->getRepository(Order::class)->findOneBy(['number' => $sOrderNumber]);
        if (! $order) {
            return;
        }

        switch ($order->getPaymentStatus()->getId()) {
            case Status::PAYMENT_STATE_OPEN:
                $paymentStatus = 'pending';
                break;
            case Status::PAYMENT_STATE_THE_PROCESS_HAS_BEEN_CANCELLED:
                $paymentStatus = 'canceled';
                break;
            default:
                $paymentStatus = 'success';
        }

        $view->assign('wirecardElasticEnginePayment', true);
        $view->assign('wirecardElasticEnginePaymentStatus', $paymentStatus);
    }
}
