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
use Shopware\Models\Order\Status;
use Shopware\Models\Shop\Shop;
use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\TransactionService;
use WirecardShopwareElasticEngine\Components\Actions\Action;
use WirecardShopwareElasticEngine\Components\Actions\ErrorAction;
use WirecardShopwareElasticEngine\Components\Actions\RedirectAction;
use WirecardShopwareElasticEngine\Components\Data\OrderSummary;
use WirecardShopwareElasticEngine\Components\Mapper\BasketMapper;
use WirecardShopwareElasticEngine\Components\Mapper\UserMapper;
use WirecardShopwareElasticEngine\Components\Services\NotificationHandler;
use WirecardShopwareElasticEngine\Components\Services\PaymentFactory;
use WirecardShopwareElasticEngine\Components\Services\PaymentHandler;
use WirecardShopwareElasticEngine\Components\Services\ReturnHandler;
use WirecardShopwareElasticEngine\Exception\ArrayKeyNotFoundException;
use WirecardShopwareElasticEngine\Exception\BasketException;
use WirecardShopwareElasticEngine\Exception\MissingOrderNumberException;
use WirecardShopwareElasticEngine\Exception\UnknownActionException;
use WirecardShopwareElasticEngine\Exception\UnknownPaymentException;

// @codingStandardsIgnoreStart
class Shopware_Controllers_Frontend_WirecardElasticEnginePayment extends Shopware_Controllers_Frontend_Payment implements CSRFWhitelistAware
{
    // @codingStandardsIgnoreEnd

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
     */
    public function indexAction()
    {
        // Since we're going to need an order number for our `Transaction` we're saving it right away. Confirmation
        // mail here is disabled through the `OrderSubscriber`.
        // The transactionId will later be overwritten.
        $basketSignature = $this->persistBasket();
        $orderNumber     = $this->saveOrder($basketSignature, $basketSignature, Status::PAYMENT_STATE_OPEN, false);

        if (! $orderNumber || $orderNumber === '') {
            throw new MissingOrderNumberException();
        }

        /** @var PaymentFactory $paymentFactory */
        $paymentFactory = $this->get('wirecard_elastic_engine.payment_factory');
        $payment        = $paymentFactory->create($this->getPaymentShortName());

        try {
            $orderSummary = new OrderSummary(
                $orderNumber,
                $payment,
                new UserMapper(
                    $this->getUser(),
                    $this->Request()->getClientIp(),
                    $this->getModelManager()->getRepository(Shop::class)
                                            ->getActiveDefault()
                                            ->getLocale()
                                            ->getLocale()
                ),
                new BasketMapper(
                    $this->getBasket(),
                    $this->getCurrencyShortName(),
                    $this->get('modules')->getModule('Articles'),
                    $payment->getTransaction()
                ),
                new Amount($this->getAmount(), $this->getCurrencyShortName())
            );
        } catch (BasketException $e) {
            $this->get('pluginlogger')->notice($e->getMessage());
            return $this->redirect([
                'controller'                          => 'checkout',
                'action'                              => 'cart',
                'wirecard_elastic_engine_update_cart' => 'true',
            ]);
        }

        /** @var PaymentHandler $handler */
        $handler = $this->get('wirecard_elastic_engine.payment_handler');

        $action = $handler->execute(
            $orderSummary,
            new TransactionService($payment->getTransactionConfig(
                $this->getModelManager()->getRepository(Shop::class)->getActiveDefault(),
                $this->container->getParameterBag(),
                $this->container->get('shopware_plugininstaller.plugin_manager')
            ), $this->get('pluginlogger')),
            new Redirect(
                $this->getRoute('return', $payment->getName()),
                $this->getRoute('cancel', $payment->getName()),
                $this->getRoute('failure', $payment->getName())
            ),
            $this->getRoute('notify', $payment->getName())
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
     */
    public function returnAction()
    {
        $request = $this->Request();

        /** @var PaymentFactory $paymentFactory */
        $paymentFactory = new PaymentFactory($this->get('config'));
        $payment        = $paymentFactory->create($request->getParam('method'));

        $transactionService = new TransactionService($payment->getTransactionConfig(
            $this->getModelManager()->getRepository(Shop::class)->getActiveDefault(),
            $this->container->getParameterBag(),
            $this->container->get('shopware_plugininstaller.plugin_manager')
        ));
        $response           = $transactionService->handleResponse($request->getParams());

        $returnHandler = new ReturnHandler($this->get('router'), $this->getModelManager(), $this->get('pluginlogger'));
        $action        = $returnHandler->execute($response);

        return $this->handleAction($action);
    }

    /**
     * This method is called by Wirecard servers to modify the state of an order. Notifications are generally the
     * source of truth regarding orders, meaning the `NotificationHandler` will most likely overwrite things
     * by the `ReturnHandler`.
     *
     * @throws UnknownPaymentException
     */
    public function notifyAction()
    {
        $request = $this->Request();

        /** @var PaymentFactory $paymentFactory */
        $paymentFactory = new PaymentFactory($this->get('config'));
        $payment        = $paymentFactory->create($request->getParam('method'));

        $transactionService = new TransactionService($payment->getTransactionConfig(
            $this->getModelManager()->getRepository(Shop::class)->getActiveDefault(),
            $this->container->getParameterBag(),
            $this->container->get('shopware_plugininstaller.plugin_manager')
        ));
        $notification       = $transactionService->handleNotification(file_get_contents('php://input'));

        $notificationHandler = new NotificationHandler(
            $this->get('modules')->Order(),
            $this->get('router'),
            $this->getModelManager(),
            $this->get('pluginlogger')
        );
        $notificationHandler->execute($notification);

        die();
    }

    /**
     * @param Action $action
     */
    protected function handleAction(Action $action)
    {
        if ($action instanceof RedirectAction) {
            return $this->redirect($action->getUrl());
        }

        if ($action instanceof ErrorAction) {
            return $this->handleError($action->getCode(), $action->getMessage());
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

    /**
     * Starts transaction with CreditCard.
     * Loads iframe from Wirecard
     */
//    public function creditCardAction()
//    {
//        $params = $this->Request()->getParams();
//
//        if (! empty($params['parent_transaction_id'])
//            && ! empty($params['token_id'])) {
//            if (! empty($params['jsresponse']) && $params['jsresponse']) {
//                $router     = $this->Front()->Router();
//                $creditCard = new CreditCardPayment();
//
//                $response = $creditCard->processJsResponse(
//                    $params,
//                    $router->assemble([
//                        'action'      => 'return',
//                        'method'      => CreditCardPayment::PAYMETHOD_IDENTIFIER,
//                        'forceSecure' => true,
//                    ])
//                );
//
//                $status = $this->handleReturnResponse($response);
//
//                if ($status['type'] === 'form') {
//                    $this->View()->assign('threeDSecure', true);
//                    $this->View()->assign('method', $status['method']);
//                    $this->View()->assign('url', $status['url']);
//                    $this->View()->assign('formFields', $status['formFields']);
//                    return;
//                }
//
//                if ($status['type'] === 'success') {
//                    if (! empty($status['uniqueId'])) {
//                        $this->redirect([
//                            'module'     => 'frontend',
//                            'controller' => 'checkout',
//                            'action'     => 'finish',
//                            'sUniqueID'  => $status['uniqueId'],
//                        ]);
//                    } else {
//                        $this->redirect([
//                            'module'     => 'frontend',
//                            'controller' => 'checkout',
//                            'action'     => 'finish',
//                        ]);
//                    }
//                    return;
//                }
//
//                if ($status['type'] === 'error') {
//                    if (! empty($status['msg'])) {
//                        $this->errorHandling($status['code'], $status['msg']);
//                    } else {
//                        $this->errorHandling($status['code']);
//                    }
//                }
//                return;
//            }
//        }
//
//        if (! $this->validateBasket()) {
//            return $this->redirect([
//                'controller'                          => 'checkout',
//                'action'                              => 'cart',
//                'wirecard_elastic_engine_update_cart' => 'true',
//            ]);
//        }
//
//        $baseUrl = Shopware()->Config()->getByNamespace(
//            'WirecardShopwareElasticEngine',
//            'wirecardElasticEngineCreditCardServer'
//        );
//
//        $this->View()->assign('wirecardUrl', $baseUrl);
//
//        $paymentData = $this->getPaymentData(CreditCardPayment::PAYMETHOD_IDENTIFIER);
//        $creditCard  = new CreditCardPayment();
//        $requestData = $creditCard->getRequestDataForIframe($paymentData);
//        $this->View()->assign('wirecardRequestData', $requestData);
//        $rawRequestData = json_decode($requestData, true);
//        $requestId      = $rawRequestData[TransactionService::REQUEST_ID];
//        if (! $creditCard->addTransactionRequestId($requestId)) {
//            return $this->errorHandling(StatusCodes::ERROR_STARTING_PROCESS_FAILED);
//        }
//    }

    /**
     * Handles responses for iframe payments
     */
//    public function handleReturnResponse($response)
//    {
//        if ($response instanceof FormInteractionResponse) {
//            return [
//                'type'       => 'form',
//                'method'     => $response->getMethod(),
//                'formFields' => $response->getFormFields(),
//                'url'        => $response->getUrl(),
//            ];
//        } elseif ($response instanceof SuccessResponse) {
//            $customFields          = $response->getCustomFields();
//            $transactionId         = $response->getTransactionId();
//            $providerTransactionId = $response->getProviderTransactionId();
//            $signature             = $customFields->get('signature');
//
//            $elasticEngineTransaction = null;
//            try {
//                $wirecardOrderNumber = $response->findElement('order-number');
//                if ((getenv('SHOPWARE_ENV') === 'dev' || getenv('SHOPWARE_ENV') === 'development')
//                    && strpos($wirecardOrderNumber, '-') >= 0) {
//                    $wirecardOrderNumber = explode('-', $wirecardOrderNumber)[1];
//                }
//
//                $elasticEngineTransaction = Shopware()->Models()
//                                                      ->getRepository(OrderNumberAssignment::class)
//                                                      ->findOneBy(['id' => $wirecardOrderNumber]);
//            } catch (\Exception $e) {
//                $requestId                = $response->getRequestId();
//                $elasticEngineTransaction = Shopware()->Models()
//                                                      ->getRepository(OrderNumberAssignment::class)
//                                                      ->findOneBy(['requestId' => $requestId]);
//            }
//
//            if (! $elasticEngineTransaction) {
//                return [
//                    'type' => 'error',
//                    'code' => StatusCodes::ERROR_CRITICAL_NO_ORDER,
//                ];
//            }
//
//            $elasticEngineTransaction->setTransactionId($transactionId);
//            $elasticEngineTransaction->setProviderTransactionId($providerTransactionId);
//            $elasticEngineTransaction->setReturnResponse(serialize($response->getData()));
//            $paymentStatus = intval($elasticEngineTransaction->getPaymentStatus());
//
//            $orderTransaction = Shopware()->Models()->getRepository(Transaction::class)
//                                          ->findOneBy([
//                                              'transactionId'       => $transactionId,
//                                              'parentTransactionId' => $transactionId,
//                                          ]);
//
//            if (! $orderTransaction) {
//                $orderTransaction = new Transaction();
//                $orderTransaction->setParentTransactionId($transactionId);
//                $orderTransaction->setTransactionId($transactionId);
//                $orderTransaction->setProviderTransactionId($providerTransactionId);
//                $orderTransaction->setCreatedAt(new \DateTime('now'));
//            }
//
//            $orderTransaction->setReturnResponse(serialize($response->getData()));
//
//            if (! $signature) {
//                $signature = $elasticEngineTransaction->getBasketSignature();
//            }
//
//            $order = Shopware()->Models()
//                               ->getRepository(Order::class)
//                               ->findOneBy([
//                                   'transactionId' => $transactionId,
//                                   'temporaryId'   => $transactionId,
//                                   'status'        => -1,
//                               ]);
//
//            if ($order) {
//                Shopware()->Models()->flush();
//                try {
//                    if ($orderTransaction->getId()) {
//                        Shopware()->Models()->persist($orderTransaction);
//                        Shopware()->Models()->flush();
//                    }
//                } catch (DBALException $e) {
//                    $this->container->get('pluginlogger')->info('duplicate entry on OrderTransaction');
//                    $em = $this->container->get('models');
//                    if (! $em->isOpen()) {
//                        $em = $em->create(
//                            $em->getConnection(),
//                            $em->getConfiguration()
//                        );
//                    }
//                    $orderTransaction = $em->getRepository(Transaction::class)
//                                           ->findOneBy([
//                                               'transactionId'       => $transactionId,
//                                               'parentTransactionId' => $transactionId,
//                                           ]);
//                    if ($orderTransaction) {
//                        $orderTransaction->setReturnResponse(serialize($response->getData()));
//                        $em->flush();
//                    } else {
//                        $this->container->get('pluginlogger')->error($e->getMessage());
//                        return $this->errorHandling(StatusCodes::ERROR_CRITICAL_NO_ORDER, $e->getMessage());
//                    }
//                }
//                return [
//                    'type'     => 'success',
//                    'uniqueId' => $transactionId,
//                ];
//            }
//
//            try {
//                $this->loadBasketFromSignature($signature);
//
//                if ($paymentStatus) {
//                    $orderNumber = $this->saveOrder($transactionId, $transactionId, $paymentStatus);
//                } else {
//                    $orderNumber = $this->saveOrder($transactionId, $transactionId);
//                }
//
//                $elasticEngineTransaction->setOrderNumber($orderNumber);
//                $orderTransaction->setOrderNumber($orderNumber);
//                Shopware()->Models()->flush();
//
//                try {
//                    if (! $orderTransaction->getId()) {
//                        Shopware()->Models()->persist($orderTransaction);
//                        Shopware()->Models()->flush();
//                    }
//                } catch (DBALException $e) {
//                    $em = $this->container->get('models');
//                    if (! $em->isOpen()) {
//                        $em = $em->create(
//                            $em->getConnection(),
//                            $em->getConfiguration()
//                        );
//                    }
//                    $this->container->get('pluginlogger')->info('duplicate entry on OrderTransaction');
//
//                    $orderTransaction = $em->getRepository(Transaction::class)
//                                           ->findOneBy([
//                                               'transactionId'       => $transactionId,
//                                               'parentTransactionId' => $transactionId,
//                                           ]);
//                    if ($orderTransaction) {
//                        $orderTransaction->setOrderNumber($orderNumber);
//                        $orderTransaction->setReturnResponse(serialize($response->getData()));
//                        $em->flush();
//                    } else {
//                        $this->container->get('pluginlogger')->error($e->getMessage());
//                        return $this->errorHandling(StatusCodes::ERROR_CRITICAL_NO_ORDER, $e->getMessage());
//                    }
//                }
//
//                return ['type' => 'success'];
//            } catch (RuntimeException $e) {
//                $this->container->get('pluginlogger')->error($e->getMessage());
//                return [
//                    'type' => 'error',
//                    'code' => StatusCodes::ERROR_CRITICAL_NO_ORDER,
//                    'msg'  => $e->getMessage(),
//                ];
//            }
//        } elseif ($response instanceof FailureResponse) {
//            $this->container->get('pluginlogger')->error(
//                sprintf(
//                    'Response validation status: %s',
//                    $response->isValidSignature() ? 'true' : 'false'
//                )
//            );
//
//            $errorMessages = "";
//
//            foreach ($response->getStatusCollection() as $status) {
//                /** @var \Wirecard\PaymentSdk\Entity\Status $status */
//                $severity      = ucfirst($status->getSeverity());
//                $code          = $status->getCode();
//                $description   = $status->getDescription();
//                $errorMessage  = sprintf('%s with code %s and message "%s" occurred.', $severity, $code, $description);
//                $errorMessages .= $errorMessage . '<br>';
//
//                $this->container->get('pluginlogger')->error($errorMessage);
//            }
//
//            return [
//                'type' => 'error',
//                'code' => StatusCodes::ERROR_CRITICAL_NO_ORDER,
//                'msg'  => $errorMessages,
//            ];
//        }
//
//        return [
//            'type' => 'error',
//            'code' => StatusCodes::ERROR_FAILURE_RESPONSE,
//            'msg'  => '',
//        ];
//    }

    /**
     * User gets redirected to this action after canceling payment.
     */
    public function cancelAction()
    {
        return $this->handleError(ErrorAction::PAYMENT_CANCELED, 'Payment canceled by user');
    }

    /**
     * @param int    $code
     * @param string $message
     */
    protected function handleError($code, $message = "")
    {
        return $this->redirect([
            'controller'                         => 'checkout',
            'action'                             => 'shippingPayment',
            'wirecard_elastic_engine_error_code' => $code,
            'wirecard_elastic_engine_error_msg'  => $message,
        ]);
    }

    /**
     * The action gets called by Server after payment.
     * If not already existing the order gets saved here.
     * order gets its finale state.
     */
//    public function notifyBackendAction()
//    {
//        $request      = $this->Request()->getParams();
//        $notification = file_get_contents("php://input");
//        $this->container->get('pluginlogger')->info('Notifiation: ' . $notification);
//
//        $response = null;
//        if ($request['method'] === PaypalPayment::PAYMETHOD_IDENTIFIER) {
//            $paypal   = new PaypalPayment();
//            $response = $paypal->getPaymentNotification($notification);
//        } elseif ($request['method'] === CreditCardPayment::PAYMETHOD_IDENTIFIER) { // FIX notify not an xml
//            $creditCard = new CreditCardPayment();
//            $configData = $creditCard->getConfigData();
//            $config     = $creditCard->getConfig($configData);
//            parse_str($notification, $array);
//            $mapper   = new ResponseMapper($config);
//            $response = $mapper->mapSeamlessResponse($array, "");
//        }
//
//        if (! $response) {
//            Shopware()->PluginLogger()->error("notification called without response");
//        }
//
//        if ($response instanceof SuccessResponse) {
//            $transactionId         = $response->getTransactionId();
//            $providerTransactionId = $response->getProviderTransactionId();
//            $transactionType       = $response->getTransactionType();
//            $parentTransactionId   = $response->getParentTransactionId() ?
//                $response->getParentTransactionId() :
//                $request['transaction'];
//
//            $elasticEngineTransaction = Shopware()->Models()->getRepository(OrderNumberAssignment::class)
//                                                  ->findOneBy(['transactionId' => $parentTransactionId]);
//
//            $orderNumber          = $elasticEngineTransaction->getOrderNumber();
//            $notificationResponse = $response->getData();
//
//            $defaultTimeZone = date_default_timezone_get();
//            date_default_timezone_set('UTC');
//            $date = new \DateTime($notificationResponse['completion-time-stamp']);
//            $date->setTimeZone(new \DateTimeZone($defaultTimeZone));
//
//            $amount   = $notificationResponse['requested-amount'];
//            $currency = $notificationResponse['currency'];
//
//            $orderTransaction = Shopware()->Models()->getRepository(Transaction::class)
//                                          ->findOneBy([
//                                              'transactionId'       => $transactionId,
//                                              'parentTransactionId' => $parentTransactionId,
//                                          ]);
//
//            $paymentStatusId = 0;
//
//            if ($transactionType === 'refund-debit') {
//                $transactionType = 'refund';
//                $paymentStatusId = Status::PAYMENT_STATE_PARTIALLY_PAID;
//            } elseif ($transactionType === 'capture-authorization') {
//                $transactionType = 'capture';
//                $paymentStatusId = Status::PAYMENT_STATE_PARTIALLY_PAID;
//            } elseif ($transactionType === 'void-authorization') {
//                $transactionType = 'void-authorization';
//                $paymentStatusId = Status::PAYMENT_STATE_THE_PROCESS_HAS_BEEN_CANCELLED;
//            } elseif ($transactionType === 'void-purchase') {
//                $transactionType = 'void-purchase';
//                $paymentStatusId = Status::PAYMENT_STATE_THE_PROCESS_HAS_BEEN_CANCELLED;
//            }
//
//
//            if (! $orderTransaction) {
//                $orderTransaction = new Transaction();
//                $orderTransaction->setOrderNumber($orderNumber);
//                $orderTransaction->setParentTransactionId($parentTransactionId);
//                $orderTransaction->setTransactionId($transactionId);
//                $orderTransaction->setProviderTransactionId($providerTransactionId);
//                $orderTransaction->setCreatedAt($date);
//            }
//            $orderTransaction->setNotificationResponse(serialize($notificationResponse));
//            $orderTransaction->setAmount($amount);
//            $orderTransaction->setCurrency($currency);
//            $orderTransaction->setTransactionType($transactionType);
//
//            try {
//                Shopware()->Models()->persist($orderTransaction);
//                Shopware()->Models()->flush();
//            } catch (DBALException $e) {
//                $em = $this->container->get('models');
//                if (! $em->isOpen()) {
//                    $em = $em->create(
//                        $em->getConnection(),
//                        $em->getConfiguration()
//                    );
//                }
//                $this->container->get('pluginlogger')->info('duplicate entry on OrderTransaction');
//                $orderTransaction = $em->getRepository(Transaction::class)
//                                       ->findOneBy([
//                                           'transactionId'       => $transactionId,
//                                           'parentTransactionId' => $parentTransactionId,
//                                       ]);
//                if ($orderTransaction) {
//                    $orderTransaction->setNotificationResponse(serialize($notificationResponse));
//                    $orderTransaction->setAmount($amount);
//                    $orderTransaction->setCurrency($currency);
//                    $orderTransaction->setTransactionType($transactionType);
//                    $em->flush();
//                } else {
//                    $this->container->get('pluginlogger')->error($e->getMessage());
//                    exit();
//                }
//            }
//
//            if ($paymentStatusId) {
//                $this->savePaymentStatus(
//                    $parentTransactionId,
//                    $parentTransactionId,
//                    $paymentStatusId,
//                    false
//                );
//            }
//        }
//        exit();
//    }

    /**
     * Whitelist notifyAction and returnAction
     */
    public function getWhitelistedCSRFActions()
    {
        return ['return', 'notify', 'notifyBackend'];
    }
}
