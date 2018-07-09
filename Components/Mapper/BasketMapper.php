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

use Wirecard\PaymentSdk\Entity\Basket;
use Wirecard\PaymentSdk\Transaction\Transaction;
use WirecardShopwareElasticEngine\Exception\ArrayKeyNotFoundException;
use WirecardShopwareElasticEngine\Exception\InvalidBasketException;
use WirecardShopwareElasticEngine\Exception\InvalidBasketItemException;

class BasketMapper extends ArrayMapper
{
    const BASKET_CONTENT = 'content';
    const ARTICLE_QUANTITY = 'quantity';
    const BASKET_SHIPPING_COSTS_WITH_TAX = 'sShippingcostsWithTax';
    const BASKET_SHIPPING_COSTS_TAX = 'sShippingcostsTax';
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
     * @param array $shopwareBasket
     * @param string $currency
     * @param \sArticles $articles
     * @param Transaction $transaction
     *
     * @throws InvalidBasketException
     * @throws InvalidBasketItemException
     * @throws ArrayKeyNotFoundException
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
        return $this->get(self::BASKET_CONTENT);
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
        $basket   = $this->getShopwareBasket();
        $currency = $this->currency;
        $lines    = [];

        foreach ($this->getShopwareBasketContent() as $item) {
            $basketItem = new BasketItemMapper($item, $currency);

            $name        = $basketItem->getArticleName();
            $orderNumber = $basketItem->getOrderNumber();
            $price       = $basketItem->getPrice();
            $quantity    = $basketItem->getQuantity();
            $taxRate     = $basketItem->getTaxRate();

            $lines[] = "${name} - ${orderNumber} - ${price} - ${currency} - ${quantity} - ${taxRate}%";
        }

        if (! empty($basket[self::BASKET_SHIPPING_COSTS_WITH_TAX]) && isset($basket[self::BASKET_SHIPPING_COSTS_TAX])) {
            $lines[] = "Shipping - shipping - ${basket[self::BASKET_SHIPPING_COSTS_WITH_TAX]} " .
                       "${currency} - ${basket[self::BASKET_SHIPPING_COSTS_TAX]}";
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
     */
    protected function createWirecardBasket()
    {
        if (! $this->validateBasket()) {
            throw new InvalidBasketException($this->getShopwareBasket());
        }

        $basket = new Basket();

        $basket->setVersion($this->transaction);

        foreach ($this->getShopwareBasketContent() as $item) {
            $basketItem = new BasketItemMapper($item, $this->currency);
            $basket->add($basketItem->getWirecardItem());
        }

        return $basket;
    }

    /**
     * Validates the given shopware basket.
     *
     * @return bool
     * @throws InvalidBasketItemException
     * @throws ArrayKeyNotFoundException
     */
    private function validateBasket()
    {
        $basket = $this->getShopwareBasket();

        if (! isset($basket[self::BASKET_CONTENT])) {
            return false;
        }

        foreach ($basket[self::BASKET_CONTENT] as $item) {
            $basketItem = new BasketItemMapper($item, $this->currency);

            $article = $this->articles->sGetProductByOrdernumber($basketItem->getOrderNumber());

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
     * @return array
     */
    public function toArray()
    {
        return [
            'content' => $this->getShopwareBasketContent()
        ];
    }
}
