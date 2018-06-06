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

class Shopware_Controllers_Frontend_WirecardElasticEnginePayment extends Shopware_Controllers_Frontend_Payment implements CSRFWhitelistAware
{
    public function getUrl($path)
    {
        $protocol = 'http';

        if ($_SERVER['SERVER_PORT'] === 443 || (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) === 'on')) {
            $protocol .= 's';
        }

        $host = $_SERVER['HTTP_HOST'];
        $request = $_SERVER['PHP_SELF'];
        return dirname(sprintf('%s://%s%s', $protocol, $host, $request)) . '/' . $path;
    }

    
    public function indexAction()
    {
        if ($this->getPaymentShortName() == 'wirecard_elastic_engine_paypal') {
            return $this->redirect(['action' => 'paypal', 'forceSecure' => true]);
        }
    }

    public function paypalAction()
    {
        $paymentData = $this->getPaymentData();

        $paypal = new PaypalPayment();

        $redirectUrl = $paypal->processPayment($paymentData);

        return $this->redirect($redirectUrl);
    }

    public function returnAction()
    {
    }
    
    public function cancleAction()
    {
        //        $service = $this->container->get('swag_payment_example.example_payment_service');
        $test = $this->Request()->getParams();

        var_dump($_POST);
        exit();
    }

    public function notifyAction()
    {
        exit();
    }
    
    protected function getPaymentData()
    {
        $user = $this->getUser();
        $basket = $this->getBasket();
        $amount = $this->getAmount();
        $currency = $this->getCurrencyShortName();
        $router = $this->Front()->Router();

        $paymentData = array(
                             'user' => $user,
                             'basket' => $basket,
                             'amount' => $amount,
                             'currency' => $currency,
                             'returnUrl' => $router->assemble(['action' => 'return', 'forceSecure' => true]),
                             'cancleUrl' => $router->assemble(['action' => 'cancle', 'forceSecure' => true]),
                             'notifyUrl' => $router->assemble(['action' => 'notify', 'forceSecure' => true])
                             );
        
        return $paymentData;
    }
   
    /**
     * Whitelist notifyAction
     */
    public function getWhitelistedCSRFActions()
    {
        return ['notify'];
    }
}
