<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

namespace WirecardElasticEngine\Subscriber;

use Enlight\Event\SubscriberInterface;

/**
 * @package WirecardElasticEngine\Subscriber
 *
 * @since   1.0.0
 */
class BackendSubscriber implements SubscriberInterface
{
    /**
     * @var string
     */
    private $pluginDirectory;

    /**
     * @param string $pluginDirectory
     *
     * @since 1.0.0
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
            'Enlight_Controller_Action_PostDispatchSecure_Backend_Index' => 'onLoadBackendIndex',
            'Enlight_Controller_Action_PostDispatchSecure_Backend_Order' => 'onOrderPostDispatch',
            'Enlight_Controller_Action_Backend_Payment_UpdatePayments'   => 'onUpdatePayments',
        ];
    }

    /**
     * @param \Enlight_Controller_ActionEventArgs $args
     *
     * @since 1.0.0
     */
    public function onLoadBackendIndex(\Enlight_Controller_ActionEventArgs $args)
    {
        $view = $args->getSubject()->View();
        $view->addTemplateDir($this->pluginDirectory . '/Resources/views/');
        $view->extendsTemplate('backend/wirecard_elastic_engine_transactions/chat.tpl');
        $view->extendsTemplate('backend/wirecard_elastic_engine_transactions/test_credentials.tpl');
    }

    /**
     * @param \Enlight_Controller_ActionEventArgs $args
     *
     * @since 1.0.0
     */
    public function onOrderPostDispatch(\Enlight_Controller_ActionEventArgs $args)
    {
        $controller = $args->getSubject();

        $view    = $controller->View();
        $request = $controller->Request();

        $view->addTemplateDir($this->pluginDirectory . '/Resources/views');

        if ($request->getActionName() === 'index') {
            $view->extendsTemplate('backend/wirecard_elastic_engine_extend_order/app.js');
        }

        if ($request->getActionName() === 'load') {
            $view->extendsTemplate('backend/wirecard_elastic_engine_extend_order/view/detail/window.js');
        }
    }

    /**
     * @param \Enlight_Event_EventArgs $args
     *
     * @since 1.3.4
     */
    public function onUpdatePayments(\Enlight_Event_EventArgs $args)
    {
        $payment        = $args->getSubject();
        $paymentFactory = $payment->get('wirecard_elastic_engine.payment_factory');
        $request        = $payment->Request();
        $requestBody    = json_decode($request->getRawBody(), true);

        if ($paymentFactory->isSupportedPayment($requestBody['name'])) {
            $paymentInstance = $paymentFactory->create($requestBody['name']);

            try {
                $paymentInstance->validateUpdate($requestBody);
            } catch (\Exception $e) {
                $payment->View()->assign([
                    'success'  => false,
                    'errorMsg' => $e->getMessage(),
                ]);
                return false;
            }
        }
    }
}
