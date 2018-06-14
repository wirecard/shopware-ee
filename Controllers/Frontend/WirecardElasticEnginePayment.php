<?php
/**
 * Shop System Plugins - Terms of Use
 * The plugins offered are provided free of charge by Wirecard AG and are explicitly not part
 * of the Wirecard AG range of products and services.
 * They have been tested and approved for full functionality in the standard configuration
 * (status on delivery) of the corresponding shop system. They are under General Public
 * License version 3 (GPLv3) and can be used, developed and passed on to third parties under
 * the same terms.
 * However, Wirecard AG does not provide any guarantee or accept any liability for any errors
 * occurring when used in an enhanced, customized shop system configuration.
 * Operation in an enhanced, customized configuration is at your own risk and requires a
 * comprehensive test phase by the user of the plugin.
 * Customers use the plugins at their own risk. Wirecard AG does not guarantee their full
 * functionality neither does Wirecard AG assume liability for any disadvantages related to
 * the use of the plugins. Additionally, Wirecard AG does not guarantee the full functionality
 * for customized shop systems or installed plugins of other vendors of plugins within the same
 * shop system.
 * Customers are responsible for testing the plugin's functionality before starting productive
 * operation.
 * By installing the plugin into the shop system the customer agrees to these terms of use.
 * Please do not use the plugin if you do not agree to these terms of use!
 */

use Shopware\Components\CSRFWhitelistAware;

use Wirecard\PaymentSdk\Config\Config;
use Wirecard\PaymentSdk\Config\PaymentMethodConfig;
use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Transaction\PayPalTransaction;
use Wirecard\PaymentSdk\TransactionService;

use WirecardShopwareElasticEngine\Components\Payments\PaypalPayment;

use Wirecard\PaymentSdk\Response\FailureResponse;
use Wirecard\PaymentSdk\Response\SuccessResponse;

use Shopware\Models\Order\Status;

class Shopware_Controllers_Frontend_WirecardElasticEnginePayment extends Shopware_Controllers_Frontend_Payment implements CSRFWhitelistAware
{
    public function indexAction()
    {
        if ($this->getPaymentShortName() == 'wirecard_elastic_engine_paypal') {
            return $this->redirect(['action' => 'paypal', 'forceSecure' => true]);
        }
    }

    public function paypalAction()
    {
        if (!$this->validateBasket()) {
            return $this->redirect([
                'controller'                        => 'checkout',
                'action'                            => 'cart',
                'wirecard_elast_engine_update_cart' => 'true'
            ]);
        }
        
        $paymentData = $this->getPaymentData('paypal');

        $paypal = new PaypalPayment();

        $paymentProcess = $paypal->processPayment($paymentData);

        if ($paymentProcess['status'] === 'success') {
            return $this->redirect($paymentProcess['redirect']);
        } else {
            $this->errorHandling(-1);
        }
    }

    public function returnAction()
    {
        $request = $this->Request()->getParams();

        if (!isset($request['method'])) {
            return $this->errorHandling(2); // FIXXXME
        }

        $response = null;
        if ($request['method'] === 'paypal') {
            $paypal = new PaypalPayment();
            $response = $paypal->getPaymentResponse($request);
        }
        
        if (!$response) {
            return $this->errorHandling(2); // FIXXXME
        }

        if ($response instanceof SuccessResponse) {
            $xmlResponse = new SimpleXMLElement($response->getRawData());

            $transactionType = $response->getTransactionType();
            $customFields = $response->getCustomFields();
            

            $transactionId = $response->getTransactionId();
            $paymentUniqueId = $response->getProviderTransactionId();
            $signature = $customFields->get('signature');

            $sql = '
                SELECT id FROM s_order
                WHERE transactionID=? AND temporaryID=?
                AND status!=-1
            ';

            $orderId = Shopware()->Db()->fetchOne($sql, [
                $transactionId,
                $paymentUniqueId,
            ]);

            if ($orderId) {
                return $this->redirect([
                    'module' => 'frontend',
                    'controller' => 'checkout',
                    'action' => 'finish',
                    'sUniqueID' => $paymentUniqueId
                ]);
            }
            try {
                $basket = $this->loadBasketFromSignature($signature);
                $this->saveOrder($transactionId, $paymentUniqueId);

                return $this->redirect([
                    'module' => 'frontend',
                    'controller' => 'checkout',
                    'action' => 'finish',
                ]);
            } catch (RuntimeException $e) {
                var_dump($e->getMessage());
                exit();
                $this->errorHandling(4); // FIXXME
            }
        } elseif ($response instanceof FailureResponse) {
            Shopware()->PluginLogger()->error('Response validation status: %s', $response->isValidSignature() ? 'true' : 'false');

            foreach ($response->getStatusCollection() as $status) {
                $severity = ucfirst($status->getSeverity());
                $code = $status->getCode();
                $description = $status->getDescription();
                $errorMessage = sprintf('%s with code %s and message "%s" occurred.', $severity, $code, $description);
                Shopware()->PluginLogger()->error($errorMessage);
            }
        }

        var_dump("ERROR");
        exit();
    }
    
    public function cancelAction()
    {
        $this->errorHandling(1);
    }

    /**
     *
     */
    protected function errorHandling($code)
    {
        $this->redirect([
            'controller'                       => 'checkout',
            'action'                           => 'shippingPayment',
            'wirecard_elast_engine_error_code' => $code
        ]);
    }

    public function notifyAction()
    {
        $request = $this->Request()->getParams();
        $transactionId = "c20d83a6-980b-4544-890e-3dace1505e15";
        $paymentUniqueId = "49922105MJ308353J";

        $paymentStatusId = Status::PAYMENT_STATE_RESERVED; //PAYMENT_STATE_COMPLETELY_PAID
        
        $sql = '
            SELECT id FROM s_order
            WHERE transactionID=? AND temporaryID=?
            AND status!=-1
        ';

        $orderId = Shopware()->Db()->fetchOne($sql, [
                $transactionId,
                $paymentUniqueId,
            ]);
        
        if ($orderId) {
            $order = Shopware()->Modules()->Order()->getOrderById($orderId);

            if (intval($order['cleared']) === Status::PAYMENT_STATE_OPEN) {
                $this->savePaymentStatus($transactionId, $paymentUniqueId, $paymentStatusId, false);
            } else {
                // payment state alreade set
            }
        } else {
        }
        
        var_dump($order);
        exit();
        $signature = "606251ce4a098ded5716ba032a436bc4045de0202630f0f35a57139f28f0d51e";
        try {
            $basket = $this->loadBasketFromSignature($signature);
        } catch (RuntimeException $e) {
            var_dump($e->getMessage());
        }

        if ($basket) {
            var_dump($this->getBasket());
        } else {
        }
        
        exit();
        
        Shopware()->PluginLogger()->info($request);
        exit();
    }
    
    protected function getPaymentData($method)
    {
        $user = $this->getUser();
        $basket = $this->getBasket();
        $amount = $this->getAmount();
        $currency = $this->getCurrencyShortName();
        $router = $this->Front()->Router();

        $paymentData = array(
            'user'      => $user,
            'ipAddr'    => $this->Request()->getClientIp(),
            'basket'    => $basket,
            'amount'    => $amount,
            'currency'  => $currency,
            'returnUrl' => $router->assemble(['action' => 'return', 'method' => $method, 'forceSecure' => true]),
            'cancelUrl' => $router->assemble(['action' => 'cancel', 'forceSecure' => true]),
            'notifyUrl' => $router->assemble(['action' => 'notify', 'method' => $method, 'forceSecure' => true]),
            'signature' => $this->persistBasket()
        );

        return $paymentData;
    }

    /**
     *
     */
    protected function validateBasket()
    {
        $basket = $this->getBasket();

        foreach ($basket['content'] as $item) {
            $article = Shopware()->Modules()->Articles()->sGetProductByOrdernumber($item['ordernumber']);
            if (!$article['isAvailable'] || ($article['laststock'] && intval($item['quantity']) > $article['instock'])) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Whitelist notifyAction
     */
    public function getWhitelistedCSRFActions()
    {
        return ['return', 'notify'];
    }
}
