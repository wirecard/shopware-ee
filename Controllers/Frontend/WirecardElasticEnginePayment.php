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

use Shopware\Components\CSRFWhitelistAware;
use Shopware\Models\Order\Order;
use Shopware\Models\Order\Status;
use Shopware\Models\Shop\Shop;
use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\TransactionService;
use WirecardShopwareElasticEngine\Components\Actions\Action;
use WirecardShopwareElasticEngine\Components\Actions\ErrorAction;
use WirecardShopwareElasticEngine\Components\Actions\RedirectAction;
use WirecardShopwareElasticEngine\Components\Actions\ViewAction;
use WirecardShopwareElasticEngine\Components\Data\OrderSummary;
use WirecardShopwareElasticEngine\Components\Mapper\BasketMapper;
use WirecardShopwareElasticEngine\Components\Mapper\UserMapper;
use WirecardShopwareElasticEngine\Components\Services\NotificationHandler;
use WirecardShopwareElasticEngine\Components\Services\PaymentFactory;
use WirecardShopwareElasticEngine\Components\Services\PaymentHandler;
use WirecardShopwareElasticEngine\Components\Services\ReturnHandler;
use WirecardShopwareElasticEngine\Components\Services\SessionHandler;
use WirecardShopwareElasticEngine\Exception\ArrayKeyNotFoundException;
use WirecardShopwareElasticEngine\Exception\BasketException;
use WirecardShopwareElasticEngine\Exception\MissingOrderNumberException;
use WirecardShopwareElasticEngine\Exception\UnknownActionException;
use WirecardShopwareElasticEngine\Exception\UnknownPaymentException;

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
     * @throws MissingOrderNumberException
     * @throws UnknownActionException
     * @throws UnknownPaymentException
     * @throws \WirecardShopwareElasticEngine\Exception\OrderNotFoundException
     */
    public function indexAction()
    {
        $payment = $this->getPaymentFactory()->create($this->getPaymentShortName());

        try {
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
                $this->getCurrencyShortName(),
                $this->getModules()->Articles(),
                $payment->getTransaction()
            );
            $amount       = new Amount(BasketMapper::numberFormat($this->getAmount()), $this->getCurrencyShortName());
        } catch (BasketException $e) {
            $this->getLogger()->notice($e->getMessage());
            return $this->redirect([
                self::ROUTER_CONTROLLER               => 'checkout',
                self::ROUTER_ACTION                   => 'cart',
                'wirecard_elastic_engine_update_cart' => 'true',
            ]);
        }

        // Since we're going to need an order number for our `Transaction` we're saving it right away. Confirmation
        // mail here is disabled through the `OrderSubscriber`.
        // The transactionId will later be overwritten.
        $basketSignature  = $this->persistBasket();
        $tmpTransactionId = $basketSignature . '-' . uniqid();
        $orderNumber      = $this->saveOrder($tmpTransactionId, $basketSignature, Status::PAYMENT_STATE_OPEN, false);

        $this->getSessionHandler()->storeOrder($orderNumber, $basketSignature);

        if (! $orderNumber || $orderNumber === '') {
            throw new MissingOrderNumberException();
        }

        /** @var PaymentHandler $handler */
        $handler = $this->get('wirecard_elastic_engine.payment_handler');
        $action  = $handler->execute(
            new OrderSummary($orderNumber, $payment, $userMapper, $basketMapper, $amount),
            new TransactionService(
                $payment->getTransactionConfig(
                    $this->getModelManager()->getRepository(Shop::class)->getActiveDefault(),
                    $this->container->getParameterBag()
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
     * After paying the user gets redirected to this action, where the `ReturnHandler` takes care about what to do
     * next (e.g. redirecting to the "Thank you" page, rendering templates, ...).
     *
     * @see ReturnHandler
     *
     * @throws UnknownActionException
     * @throws UnknownPaymentException
     * @throws \WirecardShopwareElasticEngine\Exception\OrderNotFoundException
     */
    public function returnAction()
    {
        $request = $this->Request();

        $payment = $this->getPaymentFactory()->create($request->getParam(self::ROUTER_METHOD));

        $transactionService = new TransactionService($payment->getTransactionConfig(
            $this->getModelManager()->getRepository(Shop::class)->getActiveDefault(),
            $this->container->getParameterBag()
        ));

        /** @var ReturnHandler $returnHandler */
        $returnHandler = $this->get('wirecard_elastic_engine.return_handler');
        $action        = $returnHandler->execute($payment, $transactionService, $request);

        if (! $action instanceof ErrorAction) {
            $this->getSessionHandler()->clearOrder();
        }

        return $this->handleAction($action);
    }

    /**
     * This method is called by Wirecard servers to modify the state of an order. Notifications are generally the
     * source of truth regarding orders, meaning the `NotificationHandler` will most likely overwrite things
     * by the `ReturnHandler`.
     *
     * @throws UnknownPaymentException
     * @throws \WirecardShopwareElasticEngine\Exception\OrderNotFoundException
     */
    public function notifyAction()
    {
        $request = $this->Request();

        /** @var PaymentFactory $paymentFactory */
        $paymentFactory = $this->get('wirecard_elastic_engine.payment_factory');
        $payment        = $paymentFactory->create($request->getParam(self::ROUTER_METHOD));

        $transactionService = new TransactionService($payment->getTransactionConfig(
            $this->getModelManager()->getRepository(Shop::class)->getActiveDefault(),
            $this->container->getParameterBag()
        ));
        $notification       = $transactionService->handleNotification(file_get_contents('php://input'));

        /** @var NotificationHandler $notificationHandler */
        $notificationHandler = $this->get('wirecard_elastic_engine.notification_handler');

        $notificationHandler->execute($this->getModules()->Order(), $notification);
        exit();
    }

    /**
     * @param Action $action
     *
     * @throws UnknownActionException
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
     */
    private function getRoute($action, $method)
    {
        return $this->get('router')->assemble([
            self::ROUTER_ACTION       => $action,
            self::ROUTER_METHOD       => $method,
            self::ROUTER_FORCE_SECURE => true,
        ]);
    }

    private function cancelOrderAndRestoreBasket()
    {
        /** @var SessionHandler $sessionHandler */
        $sessionHandler  = $this->getSessionHandler();
        $orderNumber     = $sessionHandler->getOrderNumber();
        $basketSignature = $sessionHandler->getBasketSignature();
        $sessionHandler->clearOrder();

        // Try to cancel the cancelled order and payment
        if ($orderNumber) {
            /** @var Order $order */
            $order = $this->get('models')->getRepository(Order::class)
                          ->findOneBy(['number' => $orderNumber]);
            if ($order) {
                /** @var \sOrder $shopwareOrder */
                $shopwareOrder = $this->getModules()->Order();
                $shopwareOrder->setPaymentStatus($order->getId(), Status::PAYMENT_STATE_THE_PROCESS_HAS_BEEN_CANCELLED);
                $shopwareOrder->setOrderStatus($order->getId(), Status::ORDER_STATE_CANCELLED);
            }
        }

        // Try to restore the customers basket
        try {
            if ($basketSignature) {
                $basket = $this->loadBasketFromSignature($basketSignature);
                /** @var sBasket $shopwareBasket */
                $shopwareBasket = $this->getModules()->Basket();

                $items = $basket->offsetGet('sBasket');
                foreach ($items['content'] as $item) {
                    $itemId      = $item['ordernumber'];
                    $itemQuanity = $item['quantity'];
                    $shopwareBasket->sAddArticle($itemId, $itemQuanity);
                }
            }
        } catch (\Exception $e) {
            $this->getLogger()->notice('Could not restore basket after cancel: ' . $e->getMessage());
        }
    }

    /**
     * User gets redirected to this action after canceling payment.
     */
    public function cancelAction()
    {
        return $this->handleError(ErrorAction::PAYMENT_CANCELED, 'Payment canceled by user');
    }

    /**
     * User gets redirected to this action after failed payment attempt.
     */
    public function failureAction()
    {
        return $this->handleError(ErrorAction::FAILURE_RESPONSE, 'Failure response');
    }

    /**
     * @param int    $code
     * @param string $message
     *
     * @throws Exception
     */
    protected function handleError($code, $message = "")
    {
        $this->cancelOrderAndRestoreBasket();

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
     */
    public function getWhitelistedCSRFActions()
    {
        return ['return', 'notify', 'failure', 'notifyBackend'];
    }

    /**
     * @return PaymentFactory
     * @throws Exception
     */
    private function getPaymentFactory()
    {
        return $this->get('wirecard_elastic_engine.payment_factory');
    }

    /**
     * @return SessionHandler
     * @throws Exception
     */
    private function getSessionHandler()
    {
        return $this->get('wirecard_elastic_engine.session_handler');
    }

    /**
     * @return Shopware_Components_Modules
     * @throws Exception
     */
    private function getModules()
    {
        return $this->get('modules');
    }

    /**
     * @return \Shopware\Components\Logger
     * @throws Exception
     */
    private function getLogger()
    {
        return $this->get('pluginlogger');
    }
}
