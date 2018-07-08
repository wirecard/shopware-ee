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

use Doctrine\DBAL\DBALException;

use Shopware\Components\CSRFWhitelistAware;
use Shopware\Components\Routing\Router;
use Shopware\Models\Order\Order;
use Shopware\Models\Order\Status;

use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Response\FormInteractionResponse;
use Wirecard\PaymentSdk\Response\FailureResponse;
use Wirecard\PaymentSdk\Response\SuccessResponse;
use Wirecard\PaymentSdk\TransactionService;

use WirecardShopwareElasticEngine\Components\Data\OrderSummary;
use WirecardShopwareElasticEngine\Components\Mapper\BasketMapper;
use WirecardShopwareElasticEngine\Components\Mapper\UserMapper;
use WirecardShopwareElasticEngine\Components\Payments\Payment;
use WirecardShopwareElasticEngine\Components\Services\PaymentFactory;
use WirecardShopwareElasticEngine\Components\Services\PaymentHandler;
use WirecardShopwareElasticEngine\Components\StatusCodes;
use WirecardShopwareElasticEngine\Components\Payments\CreditCardPayment;
use WirecardShopwareElasticEngine\Components\Payments\PaypalPayment;
use WirecardShopwareElasticEngine\Exception\ArrayKeyNotFoundException;
use WirecardShopwareElasticEngine\Exception\InvalidBasketException;
use WirecardShopwareElasticEngine\Exception\InvalidBasketItemException;
use WirecardShopwareElasticEngine\Exception\UnknownPaymentException;
use WirecardShopwareElasticEngine\Models\Transaction;
use WirecardShopwareElasticEngine\Models\OrderTransaction;

use Wirecard\PaymentSdk\Mapper\ResponseMapper;

// @codingStandardsIgnoreStart
class Shopware_Controllers_Frontend_WirecardElasticEnginePayment extends Shopware_Controllers_Frontend_Payment implements CSRFWhitelistAware
{
    // @codingStandardsIgnoreEnd

    const ROUTER_ACTION = 'action';
    const ROUTER_METHOD = 'method';
    const ROUTER_FORCE_SECURE = 'forceSecure';

    /**
     * Forward to Wirecard Gateway.
     */
    public function indexAction()
    {
        return $this->forward('gateway');
    }

    /**
     * Gets payment from `PaymentFactory`, assembles the `OrderSummary` and executes the payment through the
     * `PaymentHandler` service. Further action depends on the response from the handler.
     *
     * @throws ArrayKeyNotFoundException
     * @throws InvalidBasketException
     * @throws InvalidBasketItemException
     * @throws UnknownPaymentException
     */
    public function gatewayAction()
    {
        /** @var PaymentFactory $paymentFactory */
        $paymentFactory = $this->get('wirecard_elastic_engine.payment_factory');
        $payment        = $paymentFactory->create($this->getPaymentShortName());

        $orderSummary = new OrderSummary(
            $payment,
            new UserMapper(
                $this->getUser(),
                $this->get('request')->getClientIp(),
                $this->get('shopware_storefront.context_service')->getLocale()->getLocale()
            ),
            new BasketMapper(
                $this->getBasket(),
                $this->getCurrencyShortName(),
                $this->get('shopware.api.article'),
                $payment->getTransaction()
            ),
            new Amount($this->getAmount(), $this->getCurrencyShortName()),
            new Redirect(
                $this->getRedirectRoute('return', $payment->getName()),
                $this->getRedirectRoute('cancel', $payment->getName()),
                $this->getRedirectRoute('failure', $payment->getName())
            )
        );

        /** @var PaymentHandler $handler */
        $handler = $this->get('wirecard_elastic_engine.payment_handler');
        $handler->setOrderSummary($orderSummary);
        $handler->setTransactionService(
            new TransactionService($payment->getTransactionConfig(
                $this->container->getParameterBag(),
                $this->container->get('shopware.plugin_payment_installer')
            ), $this->get('pluginlogger'))
        );

        $response = $handler->execute();
    }

    private function getRedirectRoute($action, $method)
    {
        return $this->get('router')->assemble([
            self::ROUTER_ACTION       => $action,
            self::ROUTER_METHOD       => $method,
            self::ROUTER_FORCE_SECURE => true,
        ]);
    }

    /**
     * Starts transaction with PayPal.
     * User gets redirected to Paypal payment page.
     */
    public function paypalAction()
    {
        //        if (!$this->validateBasket()) {
        //            return $this->redirect([
        //                'controller'                          => 'checkout',
        //                'action'                              => 'cart',
        //                'wirecard_elastic_engine_update_cart' => 'true',
        //            ]);
        //        }
        //
        //        $paymentData = $this->getPaymentData(PaypalPayment::PAYMETHOD_IDENTIFIER);
        //
        //        $paypal = new PaypalPayment();
        //
        //        $paymentProcess = $paypal->processPayment($paymentData);
        //
        //        if ($paymentProcess['status'] === 'success') {
        //            return $this->redirect($paymentProcess['redirect']);
        //        }
        //
        //        return $this->errorHandling(StatusCodes::ERROR_STARTING_PROCESS_FAILED);
    }

    /**
     * Starts transaction with CreditCard.
     * Loads iframe from Wirecard
     */
    public function creditCardAction()
    {
        $params = $this->Request()->getParams();

        if (! empty($params['parent_transaction_id'])
            && ! empty($params['token_id'])) {
            if (! empty($params['jsresponse']) && $params['jsresponse']) {
                $router     = $this->Front()->Router();
                $creditCard = new CreditCardPayment();

                $response = $creditCard->processJsResponse(
                    $params,
                    $router->assemble([
                        'action'      => 'return',
                        'method'      => CreditCardPayment::PAYMETHOD_IDENTIFIER,
                        'forceSecure' => true,
                    ])
                );

                $status = $this->handleReturnResponse($response);

                if ($status['type'] === 'form') {
                    $this->View()->assign('threeDSecure', true);
                    $this->View()->assign('method', $status['method']);
                    $this->View()->assign('url', $status['url']);
                    $this->View()->assign('formFields', $status['formFields']);
                    return;
                }

                if ($status['type'] === 'success') {
                    if (! empty($status['uniqueId'])) {
                        $this->redirect([
                            'module'     => 'frontend',
                            'controller' => 'checkout',
                            'action'     => 'finish',
                            'sUniqueID'  => $status['uniqueId'],
                        ]);
                    } else {
                        $this->redirect([
                            'module'     => 'frontend',
                            'controller' => 'checkout',
                            'action'     => 'finish',
                        ]);
                    }
                    return;
                }

                if ($status['type'] === 'error') {
                    if (! empty($status['msg'])) {
                        $this->errorHandling($status['code'], $status['msg']);
                    } else {
                        $this->errorHandling($status['code']);
                    }
                }
                return;
            }
        }

        if (! $this->validateBasket()) {
            return $this->redirect([
                'controller'                          => 'checkout',
                'action'                              => 'cart',
                'wirecard_elastic_engine_update_cart' => 'true',
            ]);
        }

        $baseUrl = Shopware()->Config()->getByNamespace(
            'WirecardShopwareElasticEngine',
            'wirecardElasticEngineCreditCardServer'
        );

        $this->View()->assign('wirecardUrl', $baseUrl);

        $paymentData = $this->getPaymentData(CreditCardPayment::PAYMETHOD_IDENTIFIER);
        $creditCard  = new CreditCardPayment();
        $requestData = $creditCard->getRequestDataForIframe($paymentData);
        $this->View()->assign('wirecardRequestData', $requestData);
        $rawRequestData = json_decode($requestData, true);
        $requestId      = $rawRequestData[TransactionService::REQUEST_ID];
        if (! $creditCard->addTransactionRequestId($requestId)) {
            return $this->errorHandling(StatusCodes::ERROR_STARTING_PROCESS_FAILED);
        }
    }

    /**
     * After paying user gets redirected to this action.
     * The order gets saved (if not already existing through notification).
     * Required parameter:
     *  (string) method
     *  Wirecard\PaymentSdk\Response
     */
    public function returnAction()
    {
        $request = $this->Request()->getParams();

        if (! isset($request['method'])) {
            return $this->errorHandling(StatusCodes::ERROR_NOT_A_VALID_METHOD);
        }

        $response = null;
        if ($request['method'] === PaypalPayment::PAYMETHOD_IDENTIFIER) {
            $paypal   = new PaypalPayment();
            $response = $paypal->getPaymentResponse($request);
        } elseif ($request['method'] === CreditCardPayment::PAYMETHOD_IDENTIFIER) {
            $creditCard = new CreditCardPayment();
            $response   = $creditCard->getPaymentResponse($request);
        }

        if (! $response) {
            return $this->errorHandling(StatusCodes::ERROR_NOT_A_VALID_METHOD);
        }

        if ($response instanceof SuccessResponse) {
            $customFields          = $response->getCustomFields();
            $transactionId         = $response->getTransactionId();
            $providerTransactionId = $response->getProviderTransactionId();
            $signature             = $customFields->get('signature');

            $wirecardOrderNumber = $response->findElement('order-number');
            if ((getenv('SHOPWARE_ENV') === 'dev' || getenv('SHOPWARE_ENV') === 'development')
                && strpos($wirecardOrderNumber, '-') >= 0) {
                $wirecardOrderNumber = explode('-', $wirecardOrderNumber)[1];
            }

            $elasticEngineTransaction = Shopware()->Models()
                                                  ->getRepository(Transaction::class)
                                                  ->findOneBy(['id' => $wirecardOrderNumber]);

            if (! $elasticEngineTransaction) {
                return $this->errorHandling(StatusCodes::ERROR_CRITICAL_NO_ORDER);
            }

            $elasticEngineTransaction->setTransactionId($transactionId);
            $elasticEngineTransaction->setProviderTransactionId($providerTransactionId);
            $elasticEngineTransaction->setReturnResponse(serialize($response->getData()));
            $paymentStatus = intval($elasticEngineTransaction->getPaymentStatus());

            $orderTransaction = Shopware()->Models()->getRepository(OrderTransaction::class)
                                          ->findOneBy([
                                              'transactionId'       => $transactionId,
                                              'parentTransactionId' => $transactionId,
                                          ]);

            if (! $orderTransaction) {
                $orderTransaction = new OrderTransaction();
                $orderTransaction->setParentTransactionId($transactionId);
                $orderTransaction->setTransactionId($transactionId);
                $orderTransaction->setProviderTransactionId($providerTransactionId);
                $orderTransaction->setCreatedAt(new \DateTime('now'));
            }

            $orderTransaction->setReturnResponse(serialize($response->getData()));

            if (! $signature) {
                $signature = $elasticEngineTransaction->getBasketSignature();
            }

            $order = Shopware()->Models()
                               ->getRepository(Order::class)
                               ->findOneBy([
                                   'transactionId' => $transactionId,
                                   'temporaryId'   => $transactionId,
                                   'status'        => -1,
                               ]);

            if ($order) {
                Shopware()->Models()->flush();
                try {
                    Shopware()->Models()->persist($orderTransaction);
                    Shopware()->Models()->flush();
                } catch (DBALException $e) {
                    $this->container->get('pluginlogger')->info('duplicate entry on OrderTransaction');
                    $em = $this->container->get('models');
                    if (! $em->isOpen()) {
                        $em = $em->create(
                            $em->getConnection(),
                            $em->getConfiguration()
                        );
                    }
                    $orderTransaction = $em->getRepository(OrderTransaction::class)
                                           ->findOneBy([
                                               'transactionId'       => $transactionId,
                                               'parentTransactionId' => $transactionId,
                                           ]);
                    if ($orderTransaction) {
                        $em->setReturnResponse(serialize($response->getData()));
                        $em->flush();
                    } else {
                        $this->container->get('pluginlogger')->error($e->getMessage());
                        return $this->errorHandling(StatusCodes::ERROR_CRITICAL_NO_ORDER, $e->getMessage());
                    }
                }
                return $this->redirect([
                    'module'     => 'frontend',
                    'controller' => 'checkout',
                    'action'     => 'finish',
                    'sUniqueID'  => $transactionId,
                ]);
            }

            try {
                $this->loadBasketFromSignature($signature);

                if ($paymentStatus) {
                    $orderNumber = $this->saveOrder($transactionId, $transactionId, $paymentStatus);
                } else {
                    $orderNumber = $this->saveOrder($transactionId, $transactionId);
                }

                $elasticEngineTransaction->setOrderNumber($orderNumber);
                $orderTransaction->setOrderNumber($orderNumber);
                Shopware()->Models()->flush();
                try {
                    if (! $orderTransaction->getId()) {
                        Shopware()->Models()->persist($orderTransaction);
                        Shopware()->Models()->flush();
                    }
                } catch (DBALException $e) {
                    $em = $this->container->get('models');
                    if (! $em->isOpen()) {
                        $em = $em->create(
                            $em->getConnection(),
                            $em->getConfiguration()
                        );
                    }
                    $this->container->get('pluginlogger')->info('duplicate entry on OrderTransaction');
                    $orderTransaction = $em->getRepository(OrderTransaction::class)
                                           ->findOneBy([
                                               'transactionId'       => $transactionId,
                                               'parentTransactionId' => $transactionId,
                                           ]);
                    if ($orderTransaction) {
                        $orderTransaction->setReturnResponse(serialize($response->getData()));
                        $em->flush();
                    } else {
                        $this->container->get('pluginlogger')->error($e->getMessage());
                        return $this->errorHandling(StatusCodes::ERROR_CRITICAL_NO_ORDER, $e->getMessage());
                    }
                }

                return $this->redirect([
                    'module'     => 'frontend',
                    'controller' => 'checkout',
                    'action'     => 'finish',
                ]);
            } catch (RuntimeException $e) {
                $this->container->get('pluginlogger')->error($e->getMessage());
                return $this->errorHandling(StatusCodes::ERROR_CRITICAL_NO_ORDER, $e->getMessage());
            }
        } elseif ($response instanceof FailureResponse) {
            $this->container->get('pluginlogger')->error(
                sprintf(
                    'Response validation status: %s',
                    $response->isValidSignature() ? 'true' : 'false'
                )
            );

            $errorMessages = "";

            foreach ($response->getStatusCollection() as $status) {
                /** @var \Wirecard\PaymentSdk\Entity\Status $status */
                $severity      = ucfirst($status->getSeverity());
                $code          = $status->getCode();
                $description   = $status->getDescription();
                $errorMessage  = sprintf('%s with code %s and message "%s" occurred.', $severity, $code, $description);
                $errorMessages .= $errorMessage . '<br>';

                $this->container->get('pluginlogger')->error($errorMessage);
            }

            return $this->errorHandling(StatusCodes::ERROR_FAILURE_RESPONSE, $errorMessages);
        }

        return $this->errorHandling(StatusCodes::ERROR_FAILURE_RESPONSE);
    }

    /**
     * Handles responses for iframe payments
     */
    public function handleReturnResponse($response)
    {
        if ($response instanceof FormInteractionResponse) {
            return [
                'type'       => 'form',
                'method'     => $response->getMethod(),
                'formFields' => $response->getFormFields(),
                'url'        => $response->getUrl(),
            ];
        } elseif ($response instanceof SuccessResponse) {
            $customFields          = $response->getCustomFields();
            $transactionId         = $response->getTransactionId();
            $providerTransactionId = $response->getProviderTransactionId();
            $signature             = $customFields->get('signature');

            $elasticEngineTransaction = null;
            try {
                $wirecardOrderNumber = $response->findElement('order-number');
                if ((getenv('SHOPWARE_ENV') === 'dev' || getenv('SHOPWARE_ENV') === 'development')
                    && strpos($wirecardOrderNumber, '-') >= 0) {
                    $wirecardOrderNumber = explode('-', $wirecardOrderNumber)[1];
                }

                $elasticEngineTransaction = Shopware()->Models()
                                                      ->getRepository(Transaction::class)
                                                      ->findOneBy(['id' => $wirecardOrderNumber]);
            } catch (\Exception $e) {
                $requestId                = $response->getRequestId();
                $elasticEngineTransaction = Shopware()->Models()
                                                      ->getRepository(Transaction::class)
                                                      ->findOneBy(['requestId' => $requestId]);
            }

            if (! $elasticEngineTransaction) {
                return [
                    'type' => 'error',
                    'code' => StatusCodes::ERROR_CRITICAL_NO_ORDER,
                ];
            }

            $elasticEngineTransaction->setTransactionId($transactionId);
            $elasticEngineTransaction->setProviderTransactionId($providerTransactionId);
            $elasticEngineTransaction->setReturnResponse(serialize($response->getData()));
            $paymentStatus = intval($elasticEngineTransaction->getPaymentStatus());

            $orderTransaction = Shopware()->Models()->getRepository(OrderTransaction::class)
                                          ->findOneBy([
                                              'transactionId'       => $transactionId,
                                              'parentTransactionId' => $transactionId,
                                          ]);

            if (! $orderTransaction) {
                $orderTransaction = new OrderTransaction();
                $orderTransaction->setParentTransactionId($transactionId);
                $orderTransaction->setTransactionId($transactionId);
                $orderTransaction->setProviderTransactionId($providerTransactionId);
                $orderTransaction->setCreatedAt(new \DateTime('now'));
            }

            $orderTransaction->setReturnResponse(serialize($response->getData()));

            if (! $signature) {
                $signature = $elasticEngineTransaction->getBasketSignature();
            }

            $order = Shopware()->Models()
                               ->getRepository(Order::class)
                               ->findOneBy([
                                   'transactionId' => $transactionId,
                                   'temporaryId'   => $transactionId,
                                   'status'        => -1,
                               ]);

            if ($order) {
                Shopware()->Models()->flush();
                try {
                    if ($orderTransaction->getId()) {
                        Shopware()->Models()->persist($orderTransaction);
                        Shopware()->Models()->flush();
                    }
                } catch (DBALException $e) {
                    $this->container->get('pluginlogger')->info('duplicate entry on OrderTransaction');
                    $em = $this->container->get('models');
                    if (! $em->isOpen()) {
                        $em = $em->create(
                            $em->getConnection(),
                            $em->getConfiguration()
                        );
                    }
                    $orderTransaction = $em->getRepository(OrderTransaction::class)
                                           ->findOneBy([
                                               'transactionId'       => $transactionId,
                                               'parentTransactionId' => $transactionId,
                                           ]);
                    if ($orderTransaction) {
                        $orderTransaction->setReturnResponse(serialize($response->getData()));
                        $em->flush();
                    } else {
                        $this->container->get('pluginlogger')->error($e->getMessage());
                        return $this->errorHandling(StatusCodes::ERROR_CRITICAL_NO_ORDER, $e->getMessage());
                    }
                }
                return [
                    'type'     => 'success',
                    'uniqueId' => $transactionId,
                ];
            }

            try {
                $this->loadBasketFromSignature($signature);

                if ($paymentStatus) {
                    $orderNumber = $this->saveOrder($transactionId, $transactionId, $paymentStatus);
                } else {
                    $orderNumber = $this->saveOrder($transactionId, $transactionId);
                }

                $elasticEngineTransaction->setOrderNumber($orderNumber);
                $orderTransaction->setOrderNumber($orderNumber);
                Shopware()->Models()->flush();

                try {
                    if (! $orderTransaction->getId()) {
                        Shopware()->Models()->persist($orderTransaction);
                        Shopware()->Models()->flush();
                    }
                } catch (DBALException $e) {
                    $em = $this->container->get('models');
                    if (! $em->isOpen()) {
                        $em = $em->create(
                            $em->getConnection(),
                            $em->getConfiguration()
                        );
                    }
                    $this->container->get('pluginlogger')->info('duplicate entry on OrderTransaction');

                    $orderTransaction = $em->getRepository(OrderTransaction::class)
                                           ->findOneBy([
                                               'transactionId'       => $transactionId,
                                               'parentTransactionId' => $transactionId,
                                           ]);
                    if ($orderTransaction) {
                        $orderTransaction->setOrderNumber($orderNumber);
                        $orderTransaction->setReturnResponse(serialize($response->getData()));
                        $em->flush();
                    } else {
                        $this->container->get('pluginlogger')->error($e->getMessage());
                        return $this->errorHandling(StatusCodes::ERROR_CRITICAL_NO_ORDER, $e->getMessage());
                    }
                }

                return ['type' => 'success'];
            } catch (RuntimeException $e) {
                $this->container->get('pluginlogger')->error($e->getMessage());
                return [
                    'type' => 'error',
                    'code' => StatusCodes::ERROR_CRITICAL_NO_ORDER,
                    'msg'  => $e->getMessage(),
                ];
            }
        } elseif ($response instanceof FailureResponse) {
            $this->container->get('pluginlogger')->error(
                sprintf(
                    'Response validation status: %s',
                    $response->isValidSignature() ? 'true' : 'false'
                )
            );

            $errorMessages = "";

            foreach ($response->getStatusCollection() as $status) {
                /** @var \Wirecard\PaymentSdk\Entity\Status $status */
                $severity      = ucfirst($status->getSeverity());
                $code          = $status->getCode();
                $description   = $status->getDescription();
                $errorMessage  = sprintf('%s with code %s and message "%s" occurred.', $severity, $code, $description);
                $errorMessages .= $errorMessage . '<br>';

                $this->container->get('pluginlogger')->error($errorMessage);
            }

            return [
                'type' => 'error',
                'code' => StatusCodes::ERROR_CRITICAL_NO_ORDER,
                'msg'  => $errorMessages,
            ];
        }

        return [
            'type' => 'error',
            'code' => StatusCodes::ERROR_FAILURE_RESPONSE,
            'msg'  => '',
        ];
    }

    /**
     * User gets redirected to this action after canceling payment.
     */
    public function cancelAction()
    {
        $this->errorHandling(StatusCodes::CANCELED_BY_USER);
    }

    /**
     * This method handles errors.
     *
     * @see StatusCodes
     *
     * @param int    $code
     * @param string $message
     */
    protected function errorHandling($code, $message = "")
    {
        $this->redirect([
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
    public function notifyAction()
    {
        $request      = $this->Request()->getParams();
        $notification = file_get_contents("php://input");

        $this->container->get('pluginlogger')->info("Notification: " . $notification);

        $response = null;

        if ($request['method'] === PaypalPayment::PAYMETHOD_IDENTIFIER) {
            $paypal   = new PaypalPayment();
            $response = $paypal->getPaymentNotification($notification);
        } elseif ($request['method'] === CreditCardPayment::PAYMETHOD_IDENTIFIER) {
            $creditCard = new CreditCardPayment();
            $response   = $creditCard->getPaymentNotification($notification);
        }

        if (! $response) {
            $this->container->get('pluginlogger')->error("notification called without response");
        }

        if ($response instanceof SuccessResponse) {
            $transactionId         = $response->getTransactionId();
            $providerTransactionId = $response->getProviderTransactionId();
            $transactionType       = $response->getTransactionType();

            $wirecardOrderNumber = $response->findElement('order-number');

            if ($transactionType === Payment::TRANSACTION_TYPE_AUTHORIZATION) {
                $paymentStatusId = Status::PAYMENT_STATE_RESERVED;
            } else {
                $paymentStatusId = Status::PAYMENT_STATE_COMPLETELY_PAID;
            }

            $elasticEngineTransaction = Shopware()->Models()
                                                  ->getRepository(Transaction::class)
                                                  ->findOneBy([
                                                      'id' => $wirecardOrderNumber,
                                                  ]);

            if (! $elasticEngineTransaction) {
                $this->container->get('pluginlogger')->error("no Wirecard Transaction found for order");
                exit();
            }

            $elasticEngineTransaction->setTransactionId($transactionId);
            $elasticEngineTransaction->setProviderTransactionId($providerTransactionId);
            $elasticEngineTransaction->setNotificationResponse(serialize($response->getData()));
            $elasticEngineTransaction->setPaymentStatus($paymentStatusId);
            Shopware()->Models()->flush();

            $orderTransaction = Shopware()->Models()->getRepository(OrderTransaction::class)
                                          ->findOneBy([
                                              'transactionId'       => $transactionId,
                                              'parentTransactionId' => $transactionId,
                                          ]);

            if (! $orderTransaction) {
                $orderTransaction = new OrderTransaction();
                $orderTransaction->setParentTransactionId($transactionId);
                $orderTransaction->setTransactionId($transactionId);
                $orderTransaction->setProviderTransactionId($providerTransactionId);
                $orderTransaction->setCreatedAt(new \DateTime('now'));
            }

            $notificationResponse = $response->getData();

            try {
                $orderTransaction->setNotificationResponse(serialize($notificationResponse));
                $orderTransaction->setTransactionType($transactionType === 'authorization' ?
                    'authorization' :
                    'purchase');
                $orderTransaction->setAmount($notificationResponse['requested-amount']);
                $orderTransaction->setCurrency($notificationResponse['currency']);
                Shopware()->Models()->persist($orderTransaction);
                Shopware()->Models()->flush();
            } catch (DBALException $e) {
                $em = $this->container->get('models');
                $this->container->get('pluginlogger')->info('duplicate entry on OrderTransaction');
                if (! $em->isOpen()) {
                    $em = $em->create(
                        $em->getConnection(),
                        $em->getConfiguration()
                    );
                }
                $orderTransaction = $em->getRepository(OrderTransaction::class)
                                       ->findOneBy([
                                           'transactionId'       => $transactionId,
                                           'parentTransactionId' => $transactionId,
                                       ]);
                if ($orderTransaction) {
                    $orderTransaction->setNotificationResponse(serialize($notificationResponse));
                    $orderTransaction->setTransactionType($transactionType === 'authorization' ?
                        'authorization' :
                        'purchase');
                    $orderTransaction->setAmount($notificationResponse['requested-amount']);
                    $orderTransaction->setCurrency($notificationResponse['currency']);
                    $em->flush();
                } else {
                    $this->container->get('pluginlogger')->error($e->getMessage());
                    exit();
                }
            }

            $order = Shopware()->Models()
                               ->getRepository(Order::class)
                               ->findOneBy([
                                   'transactionId' => $transactionId,
                                   'temporaryId'   => $transactionId,
                               ]);

            if ($order) {
                if ($order->getPaymentStatus()->getId() === Status::PAYMENT_STATE_OPEN) {
                    $this->container->get('pluginlogger')->info("set PaymentStatus for Order " . $order->getId());
                    $this->savePaymentStatus($transactionId, $transactionId, $paymentStatusId, false);
                } else {
                    // payment state already set
                    $this->container->get('pluginlogger')->error("Order with ID " . $order->getId() . " already set");
                }
            }
        }
        exit();
    }

    /**
     * The action gets called by Server after payment.
     * If not already existing the order gets saved here.
     * order gets its finale state.
     */
    public function notifyBackendAction()
    {
        $request      = $this->Request()->getParams();
        $notification = file_get_contents("php://input");
        $this->container->get('pluginlogger')->info('Notifiation: ' . $notification);

        $response = null;
        if ($request['method'] === PaypalPayment::PAYMETHOD_IDENTIFIER) {
            $paypal   = new PaypalPayment();
            $response = $paypal->getPaymentNotification($notification);
        } elseif ($request['method'] === CreditCardPayment::PAYMETHOD_IDENTIFIER) { // FIX notify not an xml
            $creditCard = new CreditCardPayment();
            $configData = $creditCard->getConfigData();
            $config     = $creditCard->getConfig($configData);
            parse_str($notification, $array);
            $mapper   = new ResponseMapper($config);
            $response = $mapper->mapSeamlessResponse($array, "");
        }

        if (! $response) {
            Shopware()->PluginLogger()->error("notification called without response");
        }

        if ($response instanceof SuccessResponse) {
            $transactionId         = $response->getTransactionId();
            $providerTransactionId = $response->getProviderTransactionId();
            $transactionType       = $response->getTransactionType();
            $parentTransactionId   = $response->getParentTransactionId() ?
                $response->getParentTransactionId() :
                $request['transaction'];

            $elasticEngineTransaction = Shopware()->Models()->getRepository(Transaction::class)
                                                  ->findOneBy(['transactionId' => $parentTransactionId]);

            $orderNumber          = $elasticEngineTransaction->getOrderNumber();
            $notificationResponse = $response->getData();

            $defaultTimeZone = date_default_timezone_get();
            date_default_timezone_set('UTC');
            $date = new \DateTime($notificationResponse['completion-time-stamp']);
            $date->setTimeZone(new \DateTimeZone($defaultTimeZone));

            $amount   = $notificationResponse['requested-amount'];
            $currency = $notificationResponse['currency'];

            $orderTransaction = Shopware()->Models()->getRepository(OrderTransaction::class)
                                          ->findOneBy([
                                              'transactionId'       => $transactionId,
                                              'parentTransactionId' => $parentTransactionId,
                                          ]);

            $paymentStatusId = 0;

            if ($transactionType === 'refund-debit') {
                $transactionType = 'refund';
                $paymentStatusId = Status::PAYMENT_STATE_PARTIALLY_PAID;
            } elseif ($transactionType === 'capture-authorization') {
                $transactionType = 'capture';
                $paymentStatusId = Status::PAYMENT_STATE_PARTIALLY_PAID;
            } elseif ($transactionType === 'void-authorization') {
                $transactionType = 'void-authorization';
                $paymentStatusId = Status::PAYMENT_STATE_THE_PROCESS_HAS_BEEN_CANCELLED;
            } elseif ($transactionType === 'void-purchase') {
                $transactionType = 'void-purchase';
                $paymentStatusId = Status::PAYMENT_STATE_THE_PROCESS_HAS_BEEN_CANCELLED;
            }


            if (! $orderTransaction) {
                $orderTransaction = new OrderTransaction();
                $orderTransaction->setOrderNumber($orderNumber);
                $orderTransaction->setParentTransactionId($parentTransactionId);
                $orderTransaction->setTransactionId($transactionId);
                $orderTransaction->setProviderTransactionId($providerTransactionId);
                $orderTransaction->setCreatedAt($date);
            }
            $orderTransaction->setNotificationResponse(serialize($notificationResponse));
            $orderTransaction->setAmount($amount);
            $orderTransaction->setCurrency($currency);
            $orderTransaction->setTransactionType($transactionType);

            try {
                Shopware()->Models()->persist($orderTransaction);
                Shopware()->Models()->flush();
            } catch (DBALException $e) {
                $em = $this->container->get('models');
                if (! $em->isOpen()) {
                    $em = $em->create(
                        $em->getConnection(),
                        $em->getConfiguration()
                    );
                }
                $this->container->get('pluginlogger')->info('duplicate entry on OrderTransaction');
                $orderTransaction = $em->getRepository(OrderTransaction::class)
                                       ->findOneBy([
                                           'transactionId'       => $transactionId,
                                           'parentTransactionId' => $parentTransactionId,
                                       ]);
                if ($orderTransaction) {
                    $orderTransaction->setNotificationResponse(serialize($notificationResponse));
                    $orderTransaction->setAmount($amount);
                    $orderTransaction->setCurrency($currency);
                    $orderTransaction->setTransactionType($transactionType);
                    $em->flush();
                } else {
                    $this->container->get('pluginlogger')->error($e->getMessage());
                    exit();
                }
            }

            if ($paymentStatusId) {
                $this->savePaymentStatus(
                    $parentTransactionId,
                    $parentTransactionId,
                    $paymentStatusId,
                    false
                );
            }
        }
        exit();
    }

    /**
     * Important data of order for further processing in transaction get collected-
     *
     * @return OrderSummary $paymentData
     * @throws Exception
     */
    protected function getPaymentData()
    {
        return new OrderSummary(
            null,
            $this->getUser(),
            $this->getBasket(),
            new Amount($this->getAmount(), $this->getCurrencyShortName()),
            $this->getCurrencyShortName(),
            $this->Front()->Router(),
            $this->Request(),
            $this->container
        );
    }

    /**
     * Whitelist notifyAction and returnAction
     */
    public function getWhitelistedCSRFActions()
    {
        return ['return', 'notify', 'notifyBackend'];
    }
}
