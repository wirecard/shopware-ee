<?php

namespace WirecardShopwareElasticEngine\Subscriber;

use Enlight\Event\SubscriberInterface;

class ExtendOrder implements SubscriberInterface
{
    /**
     * @var string
     */
    private $pluginDirectory;

    /**
     * @param $pluginDirectory
     */
    public function __construct($pluginDirectory)
    {
        $this->pluginDirectory = $pluginDirectory;
    }
    
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatchSecure_Backend_Order' => 'onOrderPostDispatch'
        ];
    }

    public function onOrderPostDispatch(\Enlight_Event_EventArgs $args)
    {
        $controller = $args->getSubject();

        $view = $controller->View();
        $request = $controller->Request();

        $view->addTemplateDir($this->pluginDirectory . '/Resources/views');

        if ($request->getActionName() == 'index') {
            $view->extendsTemplate('backend/wirecard_extend_order/app.js');
        }
 
        if ($request->getActionName() == 'load') {
            $view->extendsTemplate('backend/wirecard_extend_order/view/detail/window.js');
        }
    }
}
