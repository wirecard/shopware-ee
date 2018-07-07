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

use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Entity\Redirect;
use WirecardShopwareElasticEngine\Components\Mapper\BasketMapper;
use WirecardShopwareElasticEngine\Components\Payments\Payment;

class OrderDetails
{
    /**
     * @var Payment
     */
    protected $payment;

    /**
     * @var array
     */
    protected $user;

    /**
     * @var BasketMapper
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
     * @var Redirect
     */
    protected $redirect;

    /**
     * OrderDetails constructor.
     *
     * @param Payment      $payment
     * @param array        $user
     * @param BasketMapper $basketMapper
     * @param Amount       $amount
     * @param Redirect     $redirect
     */
    public function __construct(
        Payment $payment,
        // TODO: UserMapper
        array $user,
        BasketMapper $basketMapper,
        Amount $amount,
        Redirect $redirect
    ) {
        $this->payment   = $payment;
        $this->user      = $user;
        $this->amount    = $amount;
        $this->basket    = $basketMapper;
        $this->redirect  = $redirect;
    }

    /**
     * @return array
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return BasketMapper
     */
    public function getBasketMapper()
    {
        return $this->basket;
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

    /**
     * @return Redirect
     */
    public function getRedirect()
    {
        return $this->redirect;
    }
}
