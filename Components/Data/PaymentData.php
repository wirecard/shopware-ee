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

use Symfony\Component\DependencyInjection\ContainerInterface;
use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Entity\Basket;
use Wirecard\PaymentSdk\Entity\Redirect;
use WirecardShopwareElasticEngine\Components\Payments\Payment;

class PaymentData
{
    const BASKET_CONTENT = 'content';
    const BASKET_ITEM_ARTICLE_NAME = 'articlename';
    const BASKET_ITEM_ORDER_NUMBER = 'ordernumber';
    const BASKET_ITEM_TAX_RATE = 'tax_rate';
    const BASKET_ITEM_QUANTITY = 'quantity';
    const BASKET_ITEM_ADDITIONAL_DETAILS = 'additional_details';
    const BASKET_ITEM_ADDITIONAL_DETAILS_PRICES = 'prices';
    const BASKET_ITEM_ADDITIONAL_DETAILS_PRICES_PRICE_NUMERIC = 'price_numeric';
    const BASKET_ITEM_PRICE = 'price';
    const BASKET_SHIPPING_COSTS_WITH_TAX = 'sShippingcostsWithTax';
    const BASKET_SHIPPING_COSTS_TAX = 'sShippingcostsTax';
    const ARTICLE_IS_AVAILABLE = 'isAvailable';
    const ARTICLE_LAST_STOCK = 'laststock';
    const ARTICLE_IN_STOCK = 'instock';
    const ARTICLE_QUANTITY = 'quantity';

    /**
     * @var Payment
     */
    protected $payment;

    /**
     * @var array
     */
    protected $user;

    /**
     * @var array
     */
    protected $rawBasket;

    /**
     * @var Basket
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
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var Redirect
     */
    protected $redirect;

    /**
     * PaymentData constructor.
     *
     * @param Payment                             $payment
     * @param array                               $user
     * @param Basket                              $basket
     * @param Amount                              $amount
     * @param                                     $currency
     * @param Redirect                            $redirect
     * @param \Enlight_Controller_Router          $router
     * @param \Enlight_Controller_Request_Request $request
     * @param ContainerInterface                  $container
     */
    public function __construct(
        Payment $payment,
        array $user,
        Basket $basket,
        Amount $amount,
        $currency,
        Redirect $redirect,
        \Enlight_Controller_Router $router,
        \Enlight_Controller_Request_Request $request,
        ContainerInterface $container
    ) {
        $this->payment   = $payment;
        $this->user      = $user;
        $this->currency  = $currency;
        $this->router    = $router;
        $this->request   = $request;
        $this->container = $container;
        $this->amount    = $amount;
        $this->basket    = $basket;
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
     * @return array
     */
    public function getRawBasket()
    {
        return $this->rawBasket;
    }

    /**
     * @return Basket
     */
    public function getBasket()
    {
        return $this->basket;
    }

    /**
     * @return string
     */
    public function getBasketText()
    {
        $currency     = $this->getCurrency();
        $basket       = $this->getBasket();
        $basketString = '';

        foreach ($basket[self::BASKET_CONTENT] as $item) {
            $name        = $item[self::BASKET_ITEM_ARTICLE_NAME];
            $orderNumber = $item[self::BASKET_ITEM_ORDER_NUMBER];
            $taxRate     = floatval($item[self::BASKET_ITEM_TAX_RATE]);
            $quantity    = $item[self::BASKET_ITEM_QUANTITY];

            if (isset($item[self::BASKET_ITEM_ADDITIONAL_DETAILS])) {
                $additionalDetails = $item[self::BASKET_ITEM_ADDITIONAL_DETAILS];

                if (isset($additionalDetails[self::BASKET_ITEM_ADDITIONAL_DETAILS_PRICES])
                    && count($additionalDetails[self::BASKET_ITEM_ADDITIONAL_DETAILS_PRICES])
                       === 1) {
                    $prices = $additionalDetails[self::BASKET_ITEM_ADDITIONAL_DETAILS_PRICES];
                    $price = $prices[0][self::BASKET_ITEM_ADDITIONAL_DETAILS_PRICES_PRICE_NUMERIC];
                } else {
                    $price = $additionalDetails[self::BASKET_ITEM_ADDITIONAL_DETAILS_PRICES_PRICE_NUMERIC];
                }
            } else {
                $price = floatval(str_replace(',', '.', $item[self::BASKET_ITEM_PRICE]));
            }

            $basketString .= "${name}-${orderNumber}-${price}-${currency}-${quantity}-${taxRate}%\n";
        }

        if (! empty($basket[self::BASKET_SHIPPING_COSTS_WITH_TAX]) && isset($basket[self::BASKET_SHIPPING_COSTS_TAX])) {
            $basketString .= "Shipping - shipping - ${basket[self::BASKET_SHIPPING_COSTS_WITH_TAX]} " .
                             "${currency} - ${basket[self::BASKET_SHIPPING_COSTS_TAX]}";
        }

        return $basketString;
    }

    /**
     * @return bool
     */
    public function validateBasket()
    {
        $basket = $this->getBasket();

        if (! isset($basket[self::BASKET_CONTENT])) {
            return false;
        }

        foreach ($basket[self::BASKET_CONTENT] as $item) {
            if (! isset($item[self::BASKET_ITEM_ORDER_NUMBER])) {
                return false;
            }

            $article = $this->container->get('shopware.api.article')->sGetProductByOrdernumber($item['ordernumber']);

            if (! $article) {
                // Some items (extra charges, ...) might have an order number but no article.
                continue;
            }

            if (! $article[self::ARTICLE_IS_AVAILABLE]
                || ($article[self::ARTICLE_LAST_STOCK]
                    && intval($item[self::ARTICLE_QUANTITY]) > $article[self::ARTICLE_IN_STOCK])) {
                return false;
            }
        }

        return true;
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

    /**
     * @return Redirect
     */
    public function getRedirect()
    {
        return $this->redirect;
    }
}
