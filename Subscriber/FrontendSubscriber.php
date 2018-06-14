<?php

namespace WirecardShopwareElasticEngine\Subscriber;

use Enlight\Event\SubscriberInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Shopware\Components\Theme\LessDefinition;

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
     * @param $pluginDirectory
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
            'Enlight_Controller_Action_PreDispatch' => 'onPreDispatch',
            'Theme_Compiler_Collect_Plugin_Less' => 'onCollectLessFiles',
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
        $request = $controller->Request();

        $errorCode = $request->getParam('wirecard_elast_engine_error_code');
        $updateCart = $request->getParam('wirecard_elast_engine_update_cart');
        
        $view = $controller->View();

        if ($errorCode) {
            $view->assign('wirecardElasticEngineErrorCode', $errorCode);
        }

        if ($updateCart) {
            $view->assign('wirecardElasticEngineUpdateCart', true);
        }
    }
}
