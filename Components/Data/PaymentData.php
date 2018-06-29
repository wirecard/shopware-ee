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

namespace WirecardShopwareElasticEngine\Components\Data;

use Doctrine\ORM\EntityManagerInterface;
use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Entity\Basket;

class PaymentData
{
    /**
     * @var array
     */
    protected $user;

    /**
     * @var array
     */
    protected $basket;

    /**
     * @var float
     */
    protected $rawAmount;

    /**
     * @var Amount
     */
    protected $amount;

    /**
     * @var string
     */
    protected $currency;

    /**
     * @var \Enlight_Controller_Router
     */
    protected $router;

    /**
     * @var \Enlight_Controller_Request_Request
     */
    protected $request;

    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * PaymentData constructor.
     *
     * @param array                               $user
     * @param array                               $basket
     * @param                                     $amount
     * @param                                     $currency
     * @param \Enlight_Controller_Router          $router
     * @param \Enlight_Controller_Request_Request $request
     * @param EntityManagerInterface              $em
     */
    public function __construct(
        array $user,
        array $basket,
        $amount,
        $currency,
        \Enlight_Controller_Router $router,
        \Enlight_Controller_Request_Request $request,
        EntityManagerInterface $em
    ) {
        $this->user      = $user;
        $this->basket    = $basket;
        $this->currency  = $currency;
        $this->router    = $router;
        $this->request   = $request;
        $this->rawAmount = $amount;
        $this->em        = $em;
        $this->amount    = new Amount($amount, $currency);
    }

    /**
     * @return array
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return array
     */
    public function getBasket()
    {
        return $this->basket;
    }

    /**
     * @return Basket
     */
    public function getElasticEngineBasket()
    {
        $basket = new Basket();
        return $basket;
    }

    /**
     * @return string
     */
    public function getBasketText()
    {
        return '';
    }

    /**
     * @return bool
     */
    public function validateBasket()
    {
        return false;
    }

    /**
     * @return float
     */
    public function getRawAmount()
    {
        return $this->rawAmount;
    }

    /**
     * @return Amount
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    public function getRedirectUrl()
    {
        //        return new Redirect();
    }
}
