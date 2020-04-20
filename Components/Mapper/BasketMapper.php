<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

namespace WirecardElasticEngine\Components\Mapper;

use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Entity\Basket;
use Wirecard\PaymentSdk\Entity\Item;
use Wirecard\PaymentSdk\Transaction\Transaction;
use WirecardElasticEngine\Exception\ArrayKeyNotFoundException;
use WirecardElasticEngine\Exception\InvalidBasketException;
use WirecardElasticEngine\Exception\InvalidBasketItemException;
use WirecardElasticEngine\Exception\NotAvailableBasketException;
use WirecardElasticEngine\Exception\OutOfStockBasketException;

/**
 * Represents the Shopware basket as object.
 *
 * @package WirecardElasticEngine\Components\Mapper
 *
 * @since   1.0.0
 */
class BasketMapper extends ArrayMapper
{
    const CONTENT = 'content';
    const ARTICLE_ID = 'articleID';
    const SHIPPING_COSTS_WITH_TAX = 'sShippingcostsWithTax';
    const SHIPPING_COSTS_TAX = 'sShippingcostsTax';
    const SHIPPING_COSTS_NET = 'sShippingcostsNet';
    const SHIPPING_COSTS_TOTAL = 'sShippingcosts';
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
     * @var string
     */
    protected $signature;

    /**
     * @var \Shopware_Components_Snippet_Manager
     */
    protected $snippetManager;

    /**
     * @var array|null
     */
    protected $shippingMethod;

    /**
     * Additionally creates a Wirecard `Basket` object which can be retrieved via `getWirecardBasket()`.
     *
     * @param array                                $shopwareBasket
     * @param string                               $signature
     * @param string                               $currency
     * @param \sArticles                           $articles
     * @param Transaction                          $transaction
     * @param \Shopware_Components_Snippet_Manager $snippetManager
     * @param array|null                           $shippingMethod
     *
     * @throws ArrayKeyNotFoundException
     * @throws InvalidBasketException
     * @throws InvalidBasketItemException
     * @throws NotAvailableBasketException
     * @throws OutOfStockBasketException
     *
     * @since 1.0.0
     */
    public function __construct(
        array $shopwareBasket,
        $signature,
        $currency,
        \sArticles $articles,
        Transaction $transaction,
        \Shopware_Components_Snippet_Manager $snippetManager,
        $shippingMethod
    ) {
        $this->arrayEntity    = $shopwareBasket;
        $this->signature      = $signature;
        $this->currency       = $currency;
        $this->articles       = $articles;
        $this->transaction    = $transaction;
        $this->snippetManager = $snippetManager;
        $this->shippingMethod = $shippingMethod;
        $this->wirecardBasket = $this->createWirecardBasket();
    }

    /**
     * @return array
     *
     * @since 1.0.0
     */
    public function getShopwareBasket()
    {
        return $this->arrayEntity;
    }

    /**
     * @return Basket
     *
     * @since 1.0.0
     */
    public function getWirecardBasket()
    {
        return $this->wirecardBasket;
    }

    /**
     * @return array
     * @throws ArrayKeyNotFoundException
     *
     * @since 1.0.0
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
     *
     * @since 1.0.0
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
     *
     * @since 1.0.0
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
            $shippingCostsTotal = $this->getOptional(self::SHIPPING_COSTS_TOTAL);
            $shippingCostsNet   = $this->getOptional(self::SHIPPING_COSTS_NET, 0.0);

            $shippingAmount   = new Amount(self::numberFormat($shippingCosts), $this->currency);
            $shippingTaxValue = $shippingCosts - $shippingCostsNet;
            $shippingTaxRate  = $this->getOptional(self::SHIPPING_COSTS_TAX, 0.0);

            $taxFreeShipping = !is_null($shippingCostsTotal) && ($shippingCostsTotal == $shippingCostsNet);
            if ($taxFreeShipping) {
                $shippingAmount   = new Amount(self::numberFormat($shippingCostsTotal), $this->currency);
                $shippingTaxValue = 0.0;
                $shippingTaxRate  = 0.0;
            }

            $shippingName = $this->snippetManager->getNamespace('backend/order/main')
                                                 ->get('overview/shipping/title', 'Shipping');
            $description  = isset($this->shippingMethod['name']) ? $this->shippingMethod['name'] : $shippingName;

            $basketItem = new Item($shippingName, $shippingAmount, 1);
            $basketItem->setDescription($description);
            $basketItem->setArticleNumber('shipping');
            $basketItem->setTaxAmount(new Amount(self::numberFormat($shippingTaxValue), $this->currency));
            $basketItem->setTaxRate($shippingTaxRate);

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
     *
     * @since 1.0.0
     */
    private function validateBasket()
    {
        $basket = $this->getShopwareBasket();

        if (! isset($basket[self::CONTENT])) {
            throw new InvalidBasketException();
        }

        foreach ($basket[self::CONTENT] as $item) {
            $basketItem = new BasketItemMapper($item, $this->currency);
            $article    = $this->articles->sGetProductByOrdernumber($basketItem->getArticleNumber());

            if (! $article) {
                // Some items (extra charges, coupons, etc.) might have an order number but no article.
                continue;
            }

            if (! $article[self::ARTICLE_IS_AVAILABLE]) {
                throw new NotAvailableBasketException($basketItem);
            }
            if ($article[self::ARTICLE_LAST_STOCK] && $basketItem->getQuantity() > $article[self::ARTICLE_IN_STOCK]) {
                throw new OutOfStockBasketException($basketItem);
            }
        }
    }

    /**
     * @return array
     *
     * @since 1.0.0
     */
    public function toArray()
    {
        return $this->getShopwareBasket();
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function getSignature()
    {
        return $this->signature;
    }

    /**
     * Helper function to format numbers throughout the plugin.
     *
     * @param string|float $amount
     *
     * @return string
     *
     * @since 1.0.0
     */
    public static function numberFormat($amount)
    {
        return (float)number_format($amount, 2, '.', '');
    }
}
