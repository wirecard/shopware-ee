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

use Wirecard\PaymentSdk\Response\FailureResponse;
use Wirecard\PaymentSdk\Response\SuccessResponse;

use Wirecard\PaymentSdk\TransactionService;
use WirecardShopwareElasticEngine\Components\Data\PaymentData;
use WirecardShopwareElasticEngine\Components\Payments\Payment;
use WirecardShopwareElasticEngine\Components\Services\PaymentFactory;
use WirecardShopwareElasticEngine\Components\Services\PaymentProcessor;
use WirecardShopwareElasticEngine\Components\StatusCodes;
use WirecardShopwareElasticEngine\Components\Payments\PaypalPayment;
use WirecardShopwareElasticEngine\Models\Transaction;

// @codingStandardsIgnoreStart
class Shopware_Controllers_Frontend_WirecardElasticEnginePayment extends Shopware_Controllers_Frontend_Payment implements CSRFWhitelistAware
{
    // @codingStandardsIgnoreEnd

    /**
     * Forward to Wirecard Gateway
     */
    public function indexAction()
    {
        return $this->forward('gateway');
    }

    /**
     * Gateway to Wirecard
     */
    public function gatewayAction()
    {
        $paymentData    = $this->getPaymentData();

        if (! $paymentData->validateBasket()) {
            return $this->redirect([
                'controller'                          => 'checkout',
                'action'                              => 'cart',
                'wirecard_elastic_engine_update_cart' => 'true',
            ]);
        }

        /** @var PaymentFactory $paymentFactory */
        $paymentFactory = $this->get('wirecard_elastic_engine.payment_factory');
        $payment        = $paymentFactory->create($this->getPaymentShortName());

        /** @var PaymentProcessor $processor */
        $processor = $this->get('wirecard_elastic_engine.payment_processor');
        $processor->setPayment($payment);
        $processor->setPaymentData($paymentData);
        $processor->setTransactionService(
            new TransactionService($payment->getTransactionConfig(), $this->get('pluginlogger'))
        );

        $processor->execute();
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
     * After paying user gets redirected to this action.
     * The order gets saved (if not already existing through notification).
     * Required parameter:
     *  (string) method
     *  Wirecard\PaymentSdk\Response
     */
    public function returnAction()
    {
        $request = $this->Request()->getParams();

        if (!isset($request['method'])) {
            return $this->errorHandling(StatusCodes::ERROR_NOT_A_VALID_METHOD);
        }

        $response = null;
        if ($request['method'] === PaypalPayment::PAYMETHOD_IDENTIFIER) {
            $paypal = new PaypalPayment();
            $response = $paypal->getPaymentResponse($request);
        }

        if (!$response) {
            return $this->errorHandling(StatusCodes::ERROR_NOT_A_VALID_METHOD);
        }

        if ($response instanceof SuccessResponse) {
            $customFields    = $response->getCustomFields();
            $transactionId   = $response->getTransactionId();
            $paymentUniqueId = $response->getProviderTransactionId();
            $signature       = $customFields->get('signature');

            $wirecardOrderNumber = $response->findElement('order-number');

            $elasticEngineTransaction = Shopware()->Models()
                                                  ->getRepository(Transaction::class)
                                                  ->findOneBy(['id' => $wirecardOrderNumber]);

            if (!$elasticEngineTransaction) {
                return $this->errorHandling(StatusCodes::ERROR_CRITICAL_NO_ORDER);
            }

            $elasticEngineTransaction->setTransactionId($transactionId);
            $elasticEngineTransaction->setProviderTransactionId($paymentUniqueId);
            $elasticEngineTransaction->setReturnResponse(serialize($response->getData()));
            $paymentStatus = intval($elasticEngineTransaction->getPaymentStatus());

            $order = Shopware()->Models()
                               ->getRepository(Order::class)
                               ->findOneBy([
                                   'transactionId' => $transactionId,
                                   'temporaryId'   => $paymentUniqueId,
                                   'status'        => -1,
                               ]);

            if ($order) {
                Shopware()->Models()->persist($elasticEngineTransaction);
                Shopware()->Models()->flush();
                return $this->redirect([
                    'module'     => 'frontend',
                    'controller' => 'checkout',
                    'action'     => 'finish',
                    'sUniqueID'  => $paymentUniqueId,
                ]);
            }

            try {
                $this->loadBasketFromSignature($signature);

                if ($paymentStatus) {
                    $orderNumber = $this->saveOrder($transactionId, $paymentUniqueId, $paymentStatus);
                } else {
                    $orderNumber = $this->saveOrder($transactionId, $paymentUniqueId);
                }

                $elasticEngineTransaction->setOrderNumber($orderNumber);
                Shopware()->Models()->persist($elasticEngineTransaction);
                Shopware()->Models()->flush();

                return $this->redirect([
                    'module'     => 'frontend',
                    'controller' => 'checkout',
                    'action'     => 'finish',
                ]);
            } catch (RuntimeException $e) {
                Shopware()->PluginLogger()->error($e->getMessage());
                return $this->errorHandling(StatusCodes::ERROR_CRITICAL_NO_ORDER, $e->getMessage());
            }
        } elseif ($response instanceof FailureResponse) {
            Shopware()->PluginLogger()->error(
                'Response validation status: %s',
                $response->isValidSignature() ? 'true' : 'false'
            );

            $errorMessages = "";

            foreach ($response->getStatusCollection() as $status) {
                /** @var \Wirecard\PaymentSdk\Entity\Status $status */
                $severity      = ucfirst($status->getSeverity());
                $code          = $status->getCode();
                $description   = $status->getDescription();
                $errorMessage  = sprintf('%s with code %s and message "%s" occurred.', $severity, $code, $description);
                $errorMessages .= $errorMessage . '<br>';

                Shopware()->PluginLogger()->error($errorMessage);
            }

            return $this->errorHandling(StatusCodes::ERROR_FAILURE_RESPONSE, $errorMessages);
        }

        return $this->errorHandling(StatusCodes::ERROR_FAILURE_RESPONSE);
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
     * @see StatusCodes
     *
     * @param int $code
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
        $request = $this->Request()->getParams();
        $notification = file_get_contents("php://input");

        Shopware()->PluginLogger()->info("Notification: " . $notification);

        $response = null;

        if ($request['method'] === PaypalPayment::PAYMETHOD_IDENTIFIER) {
            $paypal = new PaypalPayment();
            $response = $paypal->getPaymentNotification($notification);
        }

        if (!$response) {
            Shopware()->PluginLogger()->error("notification called without response");
        }

        if ($response instanceof SuccessResponse) {
            $transactionId   = $response->getTransactionId();
            $paymentUniqueId = $response->getProviderTransactionId();
            $transactionType = $response-> getTransactionType();

            $wirecardOrderNumber = $response->findElement('order-number');

            if ($transactionType === Payment::TRANSACTION_TYPE_AUTHORIZATION) {
                $paymentStatusId = Status::PAYMENT_STATE_RESERVED;
            } else {
                $paymentStatusId = Status::PAYMENT_STATE_COMPLETELY_PAID;
            }

            $elasticEngineTransaction = Shopware()->Models()
                                                  ->getRepository(Transaction::class)
                                                  ->findOneBy([
                                                      'id' => $wirecardOrderNumber
                                                  ]);
            if (!$elasticEngineTransaction) {
                Shopware()->PluginLogger()->error("no Wirecard Transaction found for order");
                exit();
            }

            $elasticEngineTransaction->setTransactionId($transactionId);
            $elasticEngineTransaction->setProviderTransactionId($paymentUniqueId);
            $elasticEngineTransaction->setNotificationResponse(serialize($response->getData()));
            $elasticEngineTransaction->setPaymentStatus($paymentStatusId);
            Shopware()->Models()->persist($elasticEngineTransaction);
            Shopware()->Models()->flush();

            $order = Shopware()->Models()
                               ->getRepository(Order::class)
                               ->findOneBy([
                                   'transactionId' => $transactionId,
                                   'temporaryId'   => $paymentUniqueId,
                                   'status'        => -1,
                               ]);

            if ($order) {
                if (intval($order['cleared']) === Status::PAYMENT_STATE_OPEN) {
                    Shopware()->PluginLogger()->info("set PaymentStatus for Order " . $order->getId());
                    $this->savePaymentStatus($transactionId, $paymentUniqueId, $paymentStatusId, false);
                } else {
                    // payment state already set
                    Shopware()->PluginLogger()->error("Order with ID " . $order->getId() . " already set");
                }
            }
        }
        exit();
    }

    /**
     * Important data of order for further processing in transaction get collected-
     *
     * @param string $method
     * @return PaymentData $paymentData
     * @throws Exception
     */
    protected function getPaymentData()
    {
        return new PaymentData(
            $this->getUser(),
            $this->getBasket(),
            $this->getAmount(),
            $this->getCurrencyShortName(),
            $this->Front()->Router(),
            $this->Request(),
            $this->getModelManager()
        );
    }

    /**
     * Whitelist notifyAction and returnAction
     */
    public function getWhitelistedCSRFActions()
    {
        return ['return', 'notify'];
    }
}
