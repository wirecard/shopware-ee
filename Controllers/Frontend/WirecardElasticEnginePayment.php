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

    const SOURCE_PAYMENT_HANDLER = 'wirecard_elastic_engine.payment_handler';
    const SOURCE_RETURN_HANDLER = 'wirecard_elastic_engine.return_handler';
    const SOURCE_TRANSACTION_MANAGER = 'wirecard_elastic_engine.transaction_manager';
    const SOURCE_PAYMENT_FACTORY = 'wirecard_elastic_engine.payment_factory';
    const SOURCE_NOTIFICATION_HANDLER = 'wirecard_elastic_engine.notification_handler';

    const ROUTE_ACTION_RETURN = 'return';
    const ROUTE_ACTION_CANCEL = 'cancel';
    const ROUTE_ACTION_FAILURE = 'failure';
    const ROUTE_ACTION_NOTIFY = 'notify';

    const RETURN_ERROR_MESSAGE = 'Return processing failed';
    const NOTIFY_ERROR_MESSAGE = 'Notification handling failed';

    /**
     * @var string
     */
    private $currency;

    /**
     * @var Shopware\Models\Payment\Payment
     */
    private $payment;

    /**
     * @var UserMapper
     */
    private $userMapper;

    /**
     * @var BasketMapper
     */
    private $basketMapper;

    /**
     * @var Amount
     */
    private $amount;

    /**
     * @var TransactionManager
     */
    private $transactionManager;

    /**
     * Gets payment from `PaymentFactory`, assembles the `OrderSummary` and executes the payment through the
     * `PaymentHandler` service. Further action depends on the response from the handler.
     *
     * @throws ArrayKeyNotFoundException
     * @throws UnknownActionException
     * @throws UnknownPaymentException
     * @throws Exception
     *
     * @since 1.0.0
     */
    public function indexAction()
    {
        $this->payment = $this->getPaymentFactory()->create($this->getPaymentShortName());
        try {
            $this->initMandatoryParameters();
        } catch (BasketException $e) {
            $this->getLogger()->notice($e->getMessage());
            return $this->redirect([
                self::ROUTER_CONTROLLER => 'checkout',
                self::ROUTER_ACTION => 'cart',
                'wirecard_elastic_engine_update_cart' => 'true',
            ]);
        }
        $action = $this->getPaymentHandler()->execute(
            $this->createOrderSummary(),
            $this->createTransactionService(),
            $this->getRedirect(),
            $this->getRoute(
                self::ROUTE_ACTION_NOTIFY,
                $this->payment->getName()
            ),
            $this->Request(),
            $this->getModules()->Order()
        );

        return $this->handleAction($action);
    }

    /**
     * After paying the user gets redirected to this action, where the `ReturnHandler` takes care about what to do
     * next (e.g. redirecting to the "Thank you" page, rendering templates, ...).
     *
     * @throws UnknownActionException
     * @throws UnknownPaymentException
     *
     * @see   ReturnHandler
     *
     * @since 1.0.0
     */
    public function returnAction()
    {
        $this->payment = $this->getPaymentFactory()->create($this->request->getParam(self::ROUTER_METHOD));

        try {
            $action = $this->getReturnAction();
        } catch (\Exception $e) {
            $action = $this->getErrorAction($e, self::RETURN_ERROR_MESSAGE);
        }
        return $this->handleAction($action);
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
        // @TODO: Analyse why template rendering has to be disabled especially for notifications
        // Disable template rendering for incoming notifications
        $this->disableTemplateRendering();

        try {
            $payment = $this->getPaymentFactory()->create($this->request->getParam(self::ROUTER_METHOD));
            $backendService = $this->createBackendService($payment);
            $notification = $backendService->handleNotification($this->request->getRawBody());
            $notifyTransaction = $this->getNotifyTransaction($notification, $backendService);
            if ($notifyTransaction) {
                $notificationMail = $this->get('wirecard_elastic_engine.mail.merchant_notification');
                $notificationMail->send($notification, $notifyTransaction);
            }
        } catch (\Exception $e) {
            $this->logException(self::NOTIFY_ERROR_MESSAGE, $e);
        }
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
     * @return array
     *
     * @since 1.0.0
     */
    public function getWhitelistedCSRFActions()
    {
        return ['return', 'notify', 'failure', 'notifyBackend'];
    }

    /**
     * @param ReturnHandler $returnHandler
     * @param SuccessResponse $response
     *
     * @return Action
     * @throws CouldNotSaveOrderException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\ORMException
     * @throws Exception
     */
    private function createOrder(ReturnHandler $returnHandler, SuccessResponse $response)
    {
        $this->getSessionManager()->destroyDeviceFingerprintId();
        $this->transactionManager = $this->getTransactionManager();
        $initialTransaction = $this->transactionManager->getInitialTransaction($response);
        $orderStatus = Status::ORDER_STATE_OPEN;

        $orderStatusComment = $this->verifyBasket($initialTransaction);
        if ($orderStatusComment !== null) {
            $orderStatus = Status::ORDER_STATE_CLARIFICATION_REQUIRED;
        }

        $paymentStatus = $this->getPaymentStatus($initialTransaction);
        $orderNumber = $this->fetchOrderNumber($response, $initialTransaction, $paymentStatus);
        $this->sendStatusMailOnSaveOrder($orderNumber, $paymentStatus);

        if ($orderStatus !== Status::ORDER_STATE_OPEN) {
            $this->setOrderStatus($orderNumber, $orderStatus, $orderStatusComment);
        }

        // check again if payment status has been set by notification and try to update payment status
        if (!$initialTransaction->getPaymentStatus()) {
            $this->updatePaymentStatus($initialTransaction, $response);
        }

        return $returnHandler->handleSuccess($response, $initialTransaction, $orderStatusComment);
    }

    /**
     * @param $initialTransaction
     * @return bool|string
     * @throws Exception
     */
    private function verifyBasket($initialTransaction)
    {
        try {
            $orderBasket = $this->loadBasketFromSignature($initialTransaction->getBasketSignature());
            $this->verifyBasketSignature($initialTransaction->getBasketSignature(), $orderBasket);
            return null;
        } catch (\RuntimeException $exception) {
            $orderStatusComment = 'Basket verification failed: ' . $exception->getMessage();
            $this->getLogger()->warning($orderStatusComment);
            return $orderStatusComment;
        }
    }

    /**
     * @param $initialTransaction
     * @param $response
     * @throws \Doctrine\ORM\ORMException
     */
    private function updatePaymentStatus($initialTransaction, $response)
    {
        $modelManager = $this->getModelManager();
        $modelManager->refresh($initialTransaction);
        $paymentStatus = $initialTransaction->getPaymentStatus();

        if (!$paymentStatus) {
            $notifyTransaction = $this->transactionManager->findNotificationTransaction($initialTransaction);
            if ($notifyTransaction && $notifyTransaction->getPaymentStatus()) {
                $paymentStatus = $notifyTransaction->getPaymentStatus();
            }
        }
        if ($paymentStatus) {
            $this->getLogger()->debug('Payment status has changed to ' . $paymentStatus);
            $this->savePaymentStatus(
                $response->getTransactionId(),
                $initialTransaction->getPaymentUniqueId(),
                $paymentStatus,
                NotificationHandler::shouldSendStatusMail($paymentStatus)
            );
        }
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
     * @return mixed
     * @throws Exception
     */
    private function getPaymentHandler()
    {
        return $this->get(self::SOURCE_PAYMENT_HANDLER);
    }

    /**
     * @return mixed
     * @throws Exception
     */
    private function getReturnHandler()
    {
        return $this->get(self::SOURCE_RETURN_HANDLER);
    }

    /**
     * @return mixed
     * @throws Exception
     */
    private function getTransactionManager()
    {
        return $this->get(self::SOURCE_TRANSACTION_MANAGER);
    }

    /**
     * @return UserMapper
     */
    private function createUserMapper()
    {
        $shop = Shopware()->Shop();
        return new UserMapper(
            $this->getUser(),
            $this->Request()->getClientIp(),
            $shop->getLocale()
                ->getLocale()
        );
    }

    /**
     * @return BasketMapper
     * @throws ArrayKeyNotFoundException
     * @throws \WirecardElasticEngine\Exception\InvalidBasketException
     * @throws \WirecardElasticEngine\Exception\InvalidBasketItemException
     * @throws \WirecardElasticEngine\Exception\NotAvailableBasketException
     * @throws \WirecardElasticEngine\Exception\OutOfStockBasketException
     */
    private function createBasketMapper()
    {
        return new BasketMapper(
            $this->getBasket(),
            $this->persistBasket(),
            $this->currency,
            $this->getModules()->Articles(),
            $this->payment->getTransaction(),
            $this->get('snippets'),
            $this->getShippingMethod()
        );
    }

    /**
     * @throws ArrayKeyNotFoundException
     * @throws \WirecardElasticEngine\Exception\InvalidBasketException
     * @throws \WirecardElasticEngine\Exception\InvalidBasketItemException
     * @throws \WirecardElasticEngine\Exception\NotAvailableBasketException
     * @throws \WirecardElasticEngine\Exception\OutOfStockBasketException
     */
    private function initMandatoryParameters()
    {
        $this->currency = $this->getCurrencyShortName();
        $this->userMapper = $this->createUserMapper();
        $this->basketMapper = $this->createBasketMapper();
        $this->amount = new Amount(BasketMapper::numberFormat($this->getAmount()), $this->currency);
    }

    /**
     * @return OrderSummary
     * @throws Exception
     */
    private function createOrderSummary()
    {
        return new OrderSummary(
            $this->generatePaymentUniqueId(),
            $this->payment,
            $this->userMapper,
            $this->basketMapper,
            $this->amount,
            $this->getSessionManager()->getDeviceFingerprintId(
                $this->payment->getPaymentConfig()->getTransactionMAID()
            ),
            $this->getSessionManager()->getPaymentData()
        );
    }

    /**
     * @return TransactionService
     * @throws Exception
     */
    private function createTransactionService()
    {
        return new TransactionService(
            $this->payment->getTransactionConfig(
                $this->container->getParameterBag(),
                $this->getCurrencyShortName()
            ),
            $this->getLogger()
        );
    }

    /**
     * @param $payment
     * @return BackendService
     */
    private function createBackendService($payment)
    {
        return new BackendService($payment->getTransactionConfig(
            $this->container->getParameterBag(),
            $this->getCurrencyShortName()
        ));
    }

    /**
     * @param $notificationHandler
     * @param $notification
     * @param $backendService
     * @return Transaction
     * @throws Exception
     */
    private function getNotifyTransaction($notification, $backendService)
    {
        return $this->getNotificationHandler()->handleResponse(
            $this->getModules()->Order(),
            $notification,
            $backendService
        );
    }

    /**
     * @return NotificationHandler
     * @throws Exception
     */
    private function getNotificationHandler()
    {
        return $this->get(self::SOURCE_NOTIFICATION_HANDLER);
    }

    /**
     * @return Redirect
     * @throws Exception
     */
    private function getRedirect()
    {
        return new Redirect(
            $this->getRoute(self::ROUTE_ACTION_RETURN, $this->payment->getName()),
            $this->getRoute(self::ROUTE_ACTION_CANCEL, $this->payment->getName()),
            $this->getRoute(self::ROUTE_ACTION_FAILURE, $this->payment->getName())
        );
    }

    /**
     * @return Action
     * @throws CouldNotSaveOrderException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \WirecardElasticEngine\Exception\InitialTransactionNotFoundException
     * @throws \Doctrine\ORM\ORMException
     */
    private function getReturnAction()
    {
        $returnHandler = $this->getReturnHandler();
        $response = $this->getReturnHandler()->handleRequest(
            $this->payment,
            $this->createTransactionService(),
            $this->request,
            $this->getSessionManager()
        );
        return $response instanceof SuccessResponse
            ? $this->createOrder($returnHandler, $response)
            : $returnHandler->handleResponse($response);
    }

    /**
     * @param $exception
     * @param $message
     * @return ErrorAction
     * @throws Exception
     */
    private function getErrorAction($exception, $message)
    {
        $this->logException($message, $exception);
        return new ErrorAction(ErrorAction::PROCESSING_FAILED, $message);
    }

    /**
     * @param $transaction
     * @return int
     */
    private function getPaymentStatus($transaction)
    {
        // check if status has been set by notification via initial or notify transaction (see NotificationHandler)
        if ($transaction->getPaymentStatus()) {
            return $transaction->getPaymentStatus();
        } else {
            $notifyTransaction = $this->transactionManager->findNotificationTransaction($transaction);
            if ($notifyTransaction && $notifyTransaction->getPaymentStatus()) {
                return $notifyTransaction->getPaymentStatus();
            }
        }
        return Status::PAYMENT_STATE_OPEN;
    }

    /**
     * @param $response
     * @param $transaction
     * @param $paymentStatus
     * @return false|int
     * @throws CouldNotSaveOrderException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function fetchOrderNumber($response, $transaction, $paymentStatus)
    {
        $orderNumber = $this->saveOrder(
            $response->getTransactionId(),
            $transaction->getPaymentUniqueId(),
            $paymentStatus,
            NotificationHandler::shouldSendStatusMail($paymentStatus)
        );
        $this->getLogger()->debug("Saved order $orderNumber with payment status $paymentStatus");
        if (! $orderNumber) {
            throw new CouldNotSaveOrderException(
                $response->getTransactionId(),
                $transaction->getPaymentUniqueId(),
                $paymentStatus
            );
        }
        $transaction->setOrderNumber($orderNumber);
        $this->getModelManager()->flush($transaction);
        return $orderNumber;
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
        return $this->get(self::SOURCE_PAYMENT_FACTORY);
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

    /**
     * @throws Exception
     * @since 1.4.0
     */
    private function disableTemplateRendering()
    {
        /** @var Enlight_Controller_Plugins_ViewRenderer_Bootstrap $viewRenderer */
        $viewRenderer = $this->get('front')->Plugins()->get('ViewRenderer');
        $viewRenderer->setNoRender();
    }
}
