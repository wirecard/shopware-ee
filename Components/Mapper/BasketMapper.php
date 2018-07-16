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

namespace WirecardShopwareElasticEngine\Components\Mapper;

use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Entity\Basket;
use Wirecard\PaymentSdk\Entity\Item;
use Wirecard\PaymentSdk\Transaction\Transaction;
use WirecardShopwareElasticEngine\Exception\ArrayKeyNotFoundException;
use WirecardShopwareElasticEngine\Exception\InvalidBasketException;
use WirecardShopwareElasticEngine\Exception\InvalidBasketItemException;
use WirecardShopwareElasticEngine\Exception\NotAvailableBasketException;
use WirecardShopwareElasticEngine\Exception\OutOfStockBasketException;

class BasketMapper extends ArrayMapper
{
    const CONTENT = 'content';
    const SHIPPING_COSTS_WITH_TAX = 'sShippingcostsWithTax';
    const SHIPPING_COSTS_TAX = 'sShippingcostsTax';
    const SHIPPING_COSTS_NET = 'sShippingcostsNet';
    const ARTICLE_IS_AVAILABLE = 'isAvailable';
    const ARTICLE_LAST_STOCK = 'laststock';
    const ARTICLE_IN_STOCK = 'instock';

    /**
     * @var Basket
     */
    protected $wirecardBasket;

    /**
     * @var Transaction
     */
    protected $transaction;

    /**
     * @var \sArticles
     */
    protected $articles;

    /**
     * @var string
     */
    protected $currency;

    /**
     * BasketMapper constructor.
     *
     * @param array       $shopwareBasket
     * @param string      $currency
     * @param \sArticles  $articles
     * @param Transaction $transaction
     *
     * @throws ArrayKeyNotFoundException
     * @throws InvalidBasketException
     * @throws InvalidBasketItemException
     * @throws NotAvailableBasketException
     * @throws OutOfStockBasketException
     */
    public function __construct(array $shopwareBasket, $currency, \sArticles $articles, Transaction $transaction)
    {
        $this->arrayEntity    = $shopwareBasket;
        $this->currency       = $currency;
        $this->articles       = $articles;
        $this->transaction    = $transaction;
        $this->wirecardBasket = $this->createWirecardBasket();
    }

    /**
     * @return array
     */
    public function getShopwareBasket()
    {
        return $this->arrayEntity;
    }

    /**
     * @return Basket
     */
    public function getWirecardBasket()
    {
        return $this->wirecardBasket;
    }

    /**
     * @return array
     * @throws ArrayKeyNotFoundException
     */
    protected function getShopwareBasketContent()
    {
        return $this->get(self::CONTENT);
    }

    /**
     * Creates a basket summary text (newline separated list of items) based on the given shopware basket.
     *
     * @return string
     * @throws InvalidBasketItemException
     * @throws ArrayKeyNotFoundException
     */
    public function getBasketText()
    {
        $basket = $this->getShopwareBasket();
        $lines  = [];

        foreach ($this->getShopwareBasketContent() as $item) {
            $basketItem = new BasketItemMapper($item, $this->currency);

            $name          = $basketItem->getArticleName();
            $articleNumber = $basketItem->getArticleNumber();
            $price         = $basketItem->getPrice();
            $quantity      = $basketItem->getQuantity();
            $taxRate       = $basketItem->getTaxRate();

            $lines[] = "$name - $articleNumber - $price - {$this->currency} - $quantity - $taxRate%";
        }

        if (! empty($basket[self::SHIPPING_COSTS_WITH_TAX]) && isset($basket[self::SHIPPING_COSTS_TAX])) {
            $lines[] = "Shipping - shipping - ${basket[self::SHIPPING_COSTS_WITH_TAX]} " .
                       "{$this->currency} - ${basket[self::SHIPPING_COSTS_TAX]}";
        }

        return implode("\n", $lines);
    }

    /**
     * Creates a Wirecard SDK basket object based on the given shopware basket.
     *
     * @return Basket
     * @throws InvalidBasketException
     * @throws InvalidBasketItemException
     * @throws ArrayKeyNotFoundException
     * @throws OutOfStockBasketException
     * @throws NotAvailableBasketException
     */
    protected function createWirecardBasket()
    {
        $this->validateBasket();

        $basket = new Basket();
        // This seems wrong, because setVersion should get a string, not an object.
        // But according to the PaymentSDK developer it's correct.
        $basket->setVersion($this->transaction);

        foreach ($this->getShopwareBasketContent() as $item) {
            $basketItem = new BasketItemMapper($item, $this->currency);
            $basket->add($basketItem->getWirecardItem());
        }

        $shippingCosts = $this->getOptional(self::SHIPPING_COSTS_WITH_TAX);
        if ($shippingCosts) {
            $shippingAmount = new Amount($shippingCosts, $this->currency);

            $shippingTaxValue = $shippingCosts - $this->getOptional(self::SHIPPING_COSTS_NET, 0.0);

            $basketItem = new Item('Shipping', $shippingAmount, 1);
            $basketItem->setDescription('Shipping');
            $basketItem->setArticleNumber('shipping');
            $basketItem->setTaxAmount(new Amount($shippingTaxValue, $this->currency));
            $basketItem->setTaxRate($this->getOptional(self::SHIPPING_COSTS_TAX, 0.0));

            $basket->add($basketItem);
        }

        return $basket;
    }

    /**
     * Validates the given shopware basket.
     *
     * @throws InvalidBasketItemException
     * @throws ArrayKeyNotFoundException
     * @throws OutOfStockBasketException
     * @throws InvalidBasketException
     * @throws NotAvailableBasketException
     */
    private function validateBasket()
    {
        $basket = $this->getShopwareBasket();

        if (! isset($basket[self::CONTENT])) {
            throw new InvalidBasketException($this);
        }

        foreach ($basket[self::CONTENT] as $item) {
            $basketItem = new BasketItemMapper($item, $this->currency);
            $article    = $this->articles->sGetProductByOrdernumber($basketItem->getArticleNumber());

            if (! $article) {
                // Some items (extra charges, coupon, ...) might have an order number but no article.
                continue;
            }

            if (! $article[self::ARTICLE_IS_AVAILABLE]) {
                throw new NotAvailableBasketException($article, $basketItem, $this);
            }
            if ($article[self::ARTICLE_LAST_STOCK] && $basketItem->getQuantity() > $article[self::ARTICLE_IN_STOCK]) {
                throw new OutOfStockBasketException($article, $basketItem, $this);
            }
        }
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->getShopwareBasket();
    }
}
