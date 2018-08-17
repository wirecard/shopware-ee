<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

use Shopware\Components\CSRFWhitelistAware;
use Shopware\Models\Order\Order;
use Shopware\Models\Order\Status;
use Shopware\Models\Shop\Shop;
use Wirecard\PaymentSdk\BackendService;
use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Response\SuccessResponse;
use Wirecard\PaymentSdk\TransactionService;
use WirecardElasticEngine\Components\Actions\Action;
use WirecardElasticEngine\Components\Actions\ErrorAction;
use WirecardElasticEngine\Components\Actions\RedirectAction;
use WirecardElasticEngine\Components\Actions\ViewAction;
use WirecardElasticEngine\Components\Data\OrderSummary;
use WirecardElasticEngine\Components\Mapper\BasketMapper;
use WirecardElasticEngine\Components\Mapper\UserMapper;
use WirecardElasticEngine\Components\Services\NotificationHandler;
use WirecardElasticEngine\Components\Services\PaymentFactory;
use WirecardElasticEngine\Components\Services\PaymentHandler;
use WirecardElasticEngine\Components\Services\ReturnHandler;
use WirecardElasticEngine\Components\Services\SessionManager;
use WirecardElasticEngine\Components\Services\TransactionManager;
use WirecardElasticEngine\Exception\ArrayKeyNotFoundException;
use WirecardElasticEngine\Exception\BasketException;
use WirecardElasticEngine\Exception\CouldNotSaveOrderException;
use WirecardElasticEngine\Exception\UnknownActionException;
use WirecardElasticEngine\Exception\UnknownPaymentException;
use WirecardElasticEngine\Models\CreditCardVault;
use WirecardElasticEngine\Models\Transaction;
use WirecardElasticEngine\WirecardElasticEngine;

/**
 * @since 1.0.0
 */
// @codingStandardsIgnoreStart
class Shopware_Controllers_Frontend_WirecardElasticEnginePayment extends Shopware_Controllers_Frontend_Payment implements CSRFWhitelistAware
{
    // @codingStandardsIgnoreEnd

    const ROUTER_CONTROLLER = 'controller';
    const ROUTER_ACTION = 'action';
    const ROUTER_METHOD = 'method';
    const ROUTER_FORCE_SECURE = 'forceSecure';

    /**
     * Gets payment from `PaymentFactory`, assembles the `OrderSummary` and executes the payment through the
     * `PaymentHandler` service. Further action depends on the response from the handler.
     *
     * @throws ArrayKeyNotFoundException
     * @throws UnknownActionException
     * @throws UnknownPaymentException
     *
     * @since 1.0.0
     */
    public function indexAction()
    {
        /** @var PaymentHandler $handler */
        $handler = $this->get('wirecard_elastic_engine.payment_handler');
        $payment = $this->getPaymentFactory()->create($this->getPaymentShortName());

        try {
            $currency     = $this->getCurrencyShortName();
            $userMapper   = new UserMapper(
                $this->getUser(),
                $this->Request()->getClientIp(),
                $this->getModelManager()->getRepository(Shop::class)
                     ->getActiveDefault()
                     ->getLocale()
                     ->getLocale()
            );
            $basketMapper = new BasketMapper(
                $this->getBasket(),
                $this->persistBasket(),
                $currency,
                $this->getModules()->Articles(),
                $payment->getTransaction(),
                $this->get('snippets'),
                $this->getShippingMethod()
            );
            $amount       = new Amount(BasketMapper::numberFormat($this->getAmount()), $currency);
        } catch (BasketException $e) {
            $this->getLogger()->notice($e->getMessage());
            return $this->redirect([
                self::ROUTER_CONTROLLER               => 'checkout',
                self::ROUTER_ACTION                   => 'cart',
                'wirecard_elastic_engine_update_cart' => 'true',
            ]);
        }

        $action = $handler->execute(
            new OrderSummary(
                $this->generatePaymentUniqueId(),
                $payment,
                $userMapper,
                $basketMapper,
                $amount,
                $this->getSessionManager()->getDeviceFingerprintId($payment->getPaymentConfig()->getTransactionMAID()),
                $this->getSessionManager()->getPaymentData()
            ),
            new TransactionService(
                $payment->getTransactionConfig(
                    $this->getModelManager()->getRepository(Shop::class)->getActiveDefault(),
                    $this->container->getParameterBag(),
                    $currency
                ),
                $this->getLogger()
            ),
            new Redirect(
                $this->getRoute('return', $payment->getName()),
                $this->getRoute('cancel', $payment->getName()),
                $this->getRoute('failure', $payment->getName())
            ),
            $this->getRoute('notify', $payment->getName()),
            $this->Request(),
            $this->getModules()->Order()
        );

        return $this->handleAction($action);
    }

    /**
     * Generate a sortable paymentUniqueId (used as internal order number sent to wirecard) that is stored with each
     * transaction and the shopware order as `temporaryId` (paymentUniqueId). The actual order number will be generated
     * in the returnAction.
     * Format: "[timestamp][uniqueId]", length: timestamp=10, uniqueId=5 (important for sepa mandate id)
     *
     * @return string
     * @throws Exception
     *
     * @since 1.0.0
     */
    private function generatePaymentUniqueId()
    {
        $repo = $this->getModelManager()->getRepository(Transaction::class);
        do {
            $id = time() . substr(md5(uniqid('', true)), 0, 5);
        } while ($repo->findOneBy(['paymentUniqueId' => $id]));
        return $id;
    }

    /**
     * Returns the shipping/dispatch data as array.
     *
     * @return array|null
     *
     * @since 1.0.0
     */
    public function getShippingMethod()
    {
        if (empty(Shopware()->Session()->sOrderVariables['sDispatch'])) {
            return null;
        }
        return Shopware()->Session()->sOrderVariables['sDispatch'];
    }

    /**
     * After paying the user gets redirected to this action, where the `ReturnHandler` takes care about what to do
     * next (e.g. redirecting to the "Thank you" page, rendering templates, ...).
     *
     * @see ReturnHandler
     *
     * @throws UnknownActionException
     * @throws UnknownPaymentException
     *
     * @since 1.0.0
     */
    public function returnAction()
    {
        /** @var ReturnHandler $returnHandler */
        $returnHandler = $this->get('wirecard_elastic_engine.return_handler');
        $request       = $this->Request();
        $payment       = $this->getPaymentFactory()->create($request->getParam(self::ROUTER_METHOD));

        try {
            $response = $returnHandler->handleRequest(
                $payment,
                new TransactionService($payment->getTransactionConfig(
                    $this->getModelManager()->getRepository(Shop::class)->getActiveDefault(),
                    $this->container->getParameterBag(),
                    $this->getCurrencyShortName()
                ), $this->getLogger()),
                $request,
                $this->getSessionManager()
            );

            $action = $response instanceof SuccessResponse
                ? $this->createOrder($returnHandler, $response)
                : $returnHandler->handleResponse($response);
        } catch (\Exception $e) {
            $this->logException('Return processing failed', $e);
            $action = new ErrorAction(ErrorAction::PROCESSING_FAILED, 'Return processing failed');
        }

        return $this->handleAction($action);
    }

    /**
     * @param ReturnHandler   $returnHandler
     * @param SuccessResponse $response
     *
     * @return Action
     * @throws CouldNotSaveOrderException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \WirecardElasticEngine\Exception\InitialTransactionNotFoundException
     */
    private function createOrder(ReturnHandler $returnHandler, SuccessResponse $response)
    {
        /** @var TransactionManager $transactionManager */
        $transactionManager = $this->get('wirecard_elastic_engine.transaction_manager');

        $this->getSessionManager()->destroyDeviceFingerprintId();

        $orderStatus        = Status::ORDER_STATE_OPEN;
        $orderStatusComment = null;

        $initialTransaction = $transactionManager->getInitialTransaction($response);
        $orderBasket        = $this->loadBasketFromSignature($initialTransaction->getBasketSignature());
        try {
            $this->verifyBasketSignature($initialTransaction->getBasketSignature(), $orderBasket);
        } catch (\RuntimeException $exception) {
            $orderStatusComment = 'Basket verification failed: ' . $exception->getMessage();
            $this->getLogger()->warning($orderStatusComment);
            $orderStatus = Status::ORDER_STATE_CLARIFICATION_REQUIRED;
        }

        // check if payment status has already been set by notification (see NotificationHandler)
        $paymentStatus = Status::PAYMENT_STATE_OPEN;
        if ($initialTransaction->getPaymentStatus()) {
            $paymentStatus = $initialTransaction->getPaymentStatus();
        }

        $orderNumber = $this->saveOrder(
            $response->getTransactionId(),
            $initialTransaction->getPaymentUniqueId(),
            $paymentStatus,
            NotificationHandler::shouldSendStatusMail($paymentStatus)
        );
        $this->getLogger()->debug("Saved order $orderNumber with payment status $paymentStatus");
        if (! $orderNumber) {
            throw new CouldNotSaveOrderException(
                $response->getTransactionId(),
                $initialTransaction->getPaymentUniqueId(),
                $paymentStatus
            );
        }
        $initialTransaction->setOrderNumber($orderNumber);
        $this->getModelManager()->flush($initialTransaction);

        $this->sendStatusMailOnSaveOrder($orderNumber, $paymentStatus);

        if ($orderStatus !== Status::ORDER_STATE_OPEN) {
            $this->setOrderStatus($orderNumber, $orderStatus, $orderStatusComment);
        }

        // check again if payment status has been set by notification and try to update payment status
        if (! $initialTransaction->getPaymentStatus()) {
            $this->getModelManager()->refresh($initialTransaction);
            if ($initialTransaction->getPaymentStatus()) {
                $this->getLogger()->debug('Payment status has changed to ' . $initialTransaction->getPaymentStatus());
                $this->savePaymentStatus(
                    $response->getTransactionId(),
                    $initialTransaction->getPaymentUniqueId(),
                    $initialTransaction->getPaymentStatus(),
                    NotificationHandler::shouldSendStatusMail($initialTransaction->getPaymentStatus())
                );
            }
        }

        return $returnHandler->handleSuccess($response, $initialTransaction, $orderStatusComment);
    }

    /**
     * Mails should be send if either the final state is already returned by the return action or
     * if the state is open and the merchant wants to to send pending mails.
     *
     * @param int $orderNumber
     * @param int $paymentStatus
     *
     * @throws Exception
     *
     * @since 1.0.0
     */
    private function sendStatusMailOnSaveOrder($orderNumber, $paymentStatus)
    {
        $sendPendingMails = $this->container->get('config')->getByNamespace(
            WirecardElasticEngine::NAME,
            'wirecardElasticEnginePendingMail'
        );
        if ($paymentStatus !== Status::PAYMENT_STATE_OPEN || ! $sendPendingMails) {
            return;
        }

        $order = $this->getModelManager()->getRepository(Order::class)->findOneBy(['number' => $orderNumber]);
        if (! $order) {
            return;
        }
        $shopwareOrder = $this->getModules()->Order();
        $mail          = $shopwareOrder->createStatusMail($order->getId(), $paymentStatus);
        if ($mail) {
            $shopwareOrder->sendStatusMail($mail);
        }
    }

    /**
     * This method is called by Wirecard servers to modify the state of an order. Notifications are generally the
     * source of truth regarding orders, meaning the `NotificationHandler` will most likely overwrite things
     * by the `ReturnHandler`.
     *
     * @throws UnknownPaymentException
     *
     * @since 1.0.0
     */
    public function notifyAction()
    {
        // Disable template rendering for incoming notifications
        /** @var Enlight_Controller_Plugins_ViewRenderer_Bootstrap $viewRenderer */
        $viewRenderer = $this->get('front')->Plugins()->get('ViewRenderer');
        $viewRenderer->setNoRender();

        /** @var PaymentFactory $paymentFactory */
        /** @var NotificationHandler $notificationHandler */
        $paymentFactory      = $this->get('wirecard_elastic_engine.payment_factory');
        $notificationHandler = $this->get('wirecard_elastic_engine.notification_handler');
        $request             = $this->Request();
        $payment             = $paymentFactory->create($request->getParam(self::ROUTER_METHOD));

        try {
            $backendService = new BackendService($payment->getTransactionConfig(
                $this->getModelManager()->getRepository(Shop::class)->getActiveDefault(),
                $this->container->getParameterBag(),
                $this->getCurrencyShortName()
            ));
            $notification   = $backendService->handleNotification($request->getRawBody());

            $notifyTransaction = $notificationHandler->handleResponse(
                $this->getModules()->Order(),
                $notification,
                $backendService
            );
            if ($notifyTransaction) {
                $notificationMail = $this->get('wirecard_elastic_engine.mail.merchant_notification');
                $notificationMail->send($notification, $notifyTransaction);
            }
        } catch (\Exception $e) {
            $this->logException('Notification handling failed', $e);
        }
    }

    /**
     * @param Action $action
     *
     * @throws UnknownActionException
     *
     * @since 1.0.0
     */
    protected function handleAction(Action $action)
    {
        if ($action instanceof RedirectAction) {
            $this->redirect($action->getUrl());
            return;
        }

        if ($action instanceof ViewAction) {
            if ($action->getTemplate() !== null) {
                $this->View()->loadTemplate(
                    'frontend/wirecard_elastic_engine_payment/' . $action->getTemplate()
                );
            }
            foreach ($action->getAssignments() as $key => $value) {
                $this->View()->assign($key, $value);
            }
            return;
        }

        if ($action instanceof ErrorAction) {
            $this->handleError($action->getCode(), $action->getMessage());
            return;
        }

        throw new UnknownActionException(get_class($action));
    }

    /**
     * @param $action
     * @param $method
     *
     * @return string
     * @throws Exception
     *
     * @since 1.0.0
     */
    private function getRoute($action, $method)
    {
        return $this->get('router')->assemble([
            self::ROUTER_ACTION       => $action,
            self::ROUTER_METHOD       => $method,
            self::ROUTER_FORCE_SECURE => true,
        ]);
    }

    /**
     * User gets redirected to this action after canceling payment.
     *
     * @since 1.0.0
     */
    public function cancelAction()
    {
        return $this->handleError(ErrorAction::PAYMENT_CANCELED, 'Payment canceled by user');
    }

    /**
     * User gets redirected to this action after failed payment attempt.
     *
     * @since 1.0.0
     */
    public function failureAction()
    {
        return $this->handleError(ErrorAction::FAILURE_RESPONSE, 'Failure response');
    }

    /**
     * Delete credit card token from vault
     *
     * @since 1.1.0
     */
    public function deleteCreditCardTokenAction()
    {
        $em = $this->getModelManager();

        $creditCardVault = $em->getRepository(CreditCardVault::class)->findOneBy([
            'id'     => $this->Request()->getParam('token'),
            'userId' => $this->getSessionManager()->getUserId(),
        ]);
        if ($creditCardVault) {
            $em->remove($creditCardVault);
            $em->flush();
        }

        return $this->redirect([
            self::ROUTER_CONTROLLER => 'checkout',
            self::ROUTER_ACTION     => 'confirm',
        ]);
    }

    /**
     * @param int    $code
     * @param string $message
     *
     * @throws Exception
     *
     * @since 1.0.0
     */
    protected function handleError($code, $message = "")
    {
        $this->getLogger()->error("Payment failed: ${message} (${code})", [
            'params'     => $this->Request()->getParams(),
            'baseUrl'    => $this->Request()->getBaseUrl(),
            'requestUri' => $this->Request()->getRequestUri(),
        ]);

        return $this->redirect([
            self::ROUTER_CONTROLLER              => 'checkout',
            self::ROUTER_ACTION                  => 'shippingPayment',
            'wirecard_elastic_engine_error_code' => $code,
            'wirecard_elastic_engine_error_msg'  => $message,
        ]);
    }

    /**
     * @return array
     *
     * @since 1.0.0
     */
    public function getWhitelistedCSRFActions()
    {
        return ['return', 'notify', 'failure', 'notifyBackend'];
    }

    /**
     * @param int    $orderNumber
     * @param int    $orderStatusId
     * @param string $orderStatusComment
     *
     * @throws Exception
     *
     * @since 1.0.0
     */
    private function setOrderStatus($orderNumber, $orderStatusId, $orderStatusComment)
    {
        $order = $this->getModelManager()->getRepository(Order::class)->findOneBy(['number' => $orderNumber]);
        if ($order) {
            $this->getModules()->Order()->setOrderStatus($order->getId(), $orderStatusId, false, $orderStatusComment);
        }
    }

    /**
     * @param string    $message
     * @param Exception $exception
     *
     * @throws Exception
     *
     * @since 1.0.0
     */
    private function logException($message, \Exception $exception)
    {
        $this->getLogger()->error(
            $message . ' - ' . get_class($exception) . ': ' . $exception->getMessage()
        );
    }

    /**
     * @return PaymentFactory
     * @throws Exception
     *
     * @since 1.0.0
     */
    private function getPaymentFactory()
    {
        return $this->get('wirecard_elastic_engine.payment_factory');
    }

    /**
     * @return SessionManager
     * @throws Exception
     *
     * @since 1.0.0
     */
    private function getSessionManager()
    {
        return $this->get('wirecard_elastic_engine.session_manager');
    }

    /**
     * @return Shopware_Components_Modules
     * @throws Exception
     *
     * @since 1.0.0
     */
    private function getModules()
    {
        return $this->get('modules');
    }

    /**
     * @return \Shopware\Components\Logger
     * @throws Exception
     *
     * @since 1.0.0
     */
    private function getLogger()
    {
        return $this->get('pluginlogger');
    }
}
