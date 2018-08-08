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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Shopware\Components\Theme\LessDefinition;
use Shopware\Models\Order\Order;
use Shopware\Models\Order\Status;
use WirecardElasticEngine\Components\Payments\Contracts\AdditionalViewAssignmentsInterface;
use WirecardElasticEngine\Components\Services\PaymentFactory;
use WirecardElasticEngine\Components\Services\SessionManager;
use WirecardElasticEngine\Models\Transaction;

/**
 * @package WirecardElasticEngine\Subscriber
 *
 * @since 1.0.0
 */
class FrontendSubscriber implements SubscriberInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var string
     */
    private $pluginDirectory;

    /**
     * @var \Enlight_Template_Manager
     */
    private $templateManager;

    /**
     * @var PaymentFactory
     */
    private $paymentFactory;

    /**
     * @param string                    $pluginDirectory
     * @param \Enlight_Template_Manager $templateManager
     * @param PaymentFactory            $paymentFactory
     *
     * @since 1.0.0
     */
    public function __construct(
        $pluginDirectory,
        \Enlight_Template_Manager $templateManager,
        PaymentFactory $paymentFactory,
        EntityManagerInterface $em
    ) {
        $this->pluginDirectory = $pluginDirectory;
        $this->templateManager = $templateManager;
        $this->paymentFactory  = $paymentFactory;
        $this->em              = $em;
    }

    /**
     * @return array
     *
     * @since 1.0.0
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PreDispatch'                          => 'onPreDispatch',
            'Theme_Compiler_Collect_Plugin_Less'                             => 'onCollectLessFiles',
            'Enlight_Controller_Action_PostDispatchSecure_Frontend_Checkout' => 'onPostDispatchCheckout',
        ];
    }

    /**
     * @param \Enlight_Controller_ActionEventArgs $args
     *
     * @since 1.0.0
     */
    public function onPreDispatch(\Enlight_Controller_ActionEventArgs $args)
    {
        $this->templateManager->addTemplateDir($this->pluginDirectory . '/Resources/views');
    }

    /**
     * @return ArrayCollection
     *
     * @since 1.0.0
     */
    public function onCollectLessFiles()
    {
        $less = new LessDefinition(
            [],
            [$this->pluginDirectory . '/Resources/views/frontend/_public/src/less/all.less'],
            $this->pluginDirectory
        );

        return new ArrayCollection([$less]);
    }

    /**
     * @param \Enlight_Controller_ActionEventArgs $args
     *
     * @throws \WirecardElasticEngine\Exception\UnknownPaymentException
     *
     * @since 1.0.0
     */
    public function onPostDispatchCheckout(\Enlight_Controller_ActionEventArgs $args)
    {
        $controller = $args->getSubject();
        $request    = $controller->Request();
        $view       = $controller->View();

        $params = $request->getParams();

        // In case additional payment data is provided it is stored in the session and later passed to
        // the `OrderSummary` by the controller. Keep in mind that this additional payment data should be passed as
        // an array named `wirecardElasticEngine`.
        // Example: <input type="text" name="wirecardElasticEngine[yourAdditionalFieldName]">
        if (! empty($params['wirecardElasticEngine'])) {
            $sessionManager = $controller->get('wirecard_elastic_engine.session_manager');
            $sessionManager->storePaymentData($params['wirecardElasticEngine']);
        }

        // Display payment status on finish page.
        if ($request->getActionName() === 'finish') {
            $this->assignPaymentStatus($view);
            $this->assignAdditionalPaymentInformations($view);
        }

        if ($request->getActionName() === 'confirm') {
            // Assigns the device fingerprint id to the view in case the payment has fraud prevention enabled.
            $this->assignDeviceFingerprint($view, $controller->get('wirecard_elastic_engine.session_manager'));

            // Some payments may require additional view assignments (e.g. SEPA input fields) which will be assigned
            // here.
            $this->assignAdditionalViewAssignments($view);
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

    /**
     * @param \Enlight_View_Default $view
     *
     * @since 1.0.0
     */
    private function assignPaymentStatus(\Enlight_View_Default $view)
    {
        $payment = $view->getAssign('sPayment');
        if (isset($payment['name']) && strpos($payment['name'], 'wirecard_elastic_engine') === false) {
            return;
        }
        $sOrderNumber = $view->getAssign('sOrderNumber');
        if (! $sOrderNumber) {
            return;
        }
        /** @var Order $order */
        $order = $this->em->getRepository(Order::class)->findOneBy(['number' => $sOrderNumber]);
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

    /**
     * @param \Enlight_View_Default $view
     *
     * @since 1.0.0
     */
    private function assignAdditionalPaymentInformations(\Enlight_View_Default $view)
    {
        $payment = $view->getAssign('sPayment');
        if (isset($payment['name']) && $payment['name'] !== 'wirecard_elastic_engine_pia') {
            return;
        }
        $sOrderNumber = $view->getAssign('sOrderNumber');
        if (! $sOrderNumber) {
            return;
        }

        $transaction = $this->em->getRepository(Transaction::class)->findOneBy([
            'orderNumber' => $sOrderNumber,
            'type' => 'initial-response'
        ]);

        $response = $transaction->getResponse();
        $bankData = [
            'bankName'  => $response['merchant-bank-account.0.bank-name'],
            'bic'       => $response['merchant-bank-account.0.bic'],
            'iban'      => $response['merchant-bank-account.0.iban'],
            'address'   => $response['merchant-bank-account.0.branch-address'],
            'city'      => $response['merchant-bank-account.0.branch-city'],
            'state'     => $response['merchant-bank-account.0.branch-state'],
            'reference' => $response['provider-transaction-reference-id']
        ];

        $view->assign('wirecardElasticEngineBankData', $bankData);
    }
    
    /**
     * @param \Enlight_View_Default $view
     *
     * @throws \WirecardElasticEngine\Exception\UnknownPaymentException
     *
     * @since 1.0.0
     */
    private function assignAdditionalViewAssignments(\Enlight_View_Default $view)
    {
        $sPayment = $view->getAssign('sPayment');
        if (strpos($sPayment['name'], 'wirecard_elastic_engine') === false) {
            return;
        }
        $payment = $this->paymentFactory->create($sPayment['name']);
        if ($payment instanceof AdditionalViewAssignmentsInterface) {
            $view->assign('wirecardElasticEngineViewAssignments', $payment->getAdditionalViewAssignments());
        }
    }

    /**
     * @param \Enlight_View_Default $view
     * @param SessionManager        $sessionManager
     *
     * @throws \WirecardElasticEngine\Exception\UnknownPaymentException
     *
     * @since 1.0.0
     */
    private function assignDeviceFingerprint(\Enlight_View_Default $view, SessionManager $sessionManager)
    {
        $sPayment = $view->getAssign('sPayment');
        if (strpos($sPayment['name'], 'wirecard_elastic_engine') === false) {
            return;
        }
        $payment = $this->paymentFactory->create($sPayment['name']);

        if ($payment->getPaymentConfig()->hasFraudPrevention()) {
            $view->assign('wirecardElasticEngineIncludeDeviceFingerprintIFrame', true);
            $view->assign(
                'wirecardElasticEngineDeviceFingerprintId',
                $sessionManager->getDeviceFingerprintId($payment->getPaymentConfig()->getTransactionMAID())
            );
        }
    }
}
