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
use Wirecard\PaymentSdk\Entity\Item;
use WirecardElasticEngine\Exception\ArrayKeyNotFoundException;
use WirecardElasticEngine\Exception\InvalidBasketItemException;

/**
 * Represents a single item from the Shopware basket as object.
 *
 * @package WirecardElasticEngine\Components\Mapper
 *
 * @since   1.0.0
 */
class BasketItemMapper extends ArrayMapper
{
    const ARTICLE_NAME = 'articlename';
    const ORDER_NUMBER = 'ordernumber';
    const DETAILS = 'additional_details';
    const DETAILS_DESCRIPTION = 'description';
    const DETAILS_PRICES = 'prices';
    const DETAILS_PRICES_PRICE_NUMERIC = 'price_numeric';
    const TAX = 'tax';
    const TAX_RATE = 'tax_rate';
    const QUANTITY = 'quantity';
    const PRICE = 'price';
    const PRICE_NUMERIC = 'priceNumeric';
    const AMOUNTNET_NUMERIC = 'amountnetNumeric';

    /**
     * @var Item
     */
    protected $wirecardItem;

    /**
     * @var string
     */
    protected $currency;

    /**
     * Additionally creates a Wirecard `Item` object which can be retrieved via `getWirecardItem()`.
     *
     * @param array  $shopwareItem
     * @param string $currency
     *
     * @throws InvalidBasketItemException
     * @throws ArrayKeyNotFoundException
     *
     * @since 1.0.0
     */
    public function __construct(array $shopwareItem, $currency)
    {
        $this->arrayEntity  = $shopwareItem;
        $this->currency     = $currency;
        $this->wirecardItem = $this->createWirecardItem();
    }

    /**
     * @return array
     *
     * @since 1.0.0
     */
    public function getShopwareItem()
    {
        return $this->arrayEntity;
    }

    /**
     * @return Item
     *
     * @since 1.0.0
     */
    public function getWirecardItem()
    {
        return $this->wirecardItem;
    }

    /**
     * @return string
     * @throws ArrayKeyNotFoundException
     *
     * @since 1.0.0
     */
    public function getArticleName()
    {
        return $this->get(self::ARTICLE_NAME);
    }

    /**
     * The shopware basket item "ordernumber" is actually the article-number/sku
     *
     * @return string
     * @throws ArrayKeyNotFoundException
     *
     * @since 1.0.0
     */
    public function getArticleNumber()
    {
        return $this->get(self::ORDER_NUMBER);
    }

    /**
     * @return float
     * @throws ArrayKeyNotFoundException
     *
     * @since 1.0.0
     */
    public function getTaxRate()
    {
        return $this->isTaxFree() ? 0 : $this->get(self::TAX_RATE);
    }

    /**
     * @return float
     * @throws ArrayKeyNotFoundException
     *
     * @since 1.0.0
     */
    public function getTax()
    {
        return floatval(str_replace(',', '.', $this->get(self::TAX)));
    }

    /**
     * @return int
     * @throws ArrayKeyNotFoundException
     *
     * @since 1.0.0
     */
    public function getQuantity()
    {
        return intval($this->get(self::QUANTITY));
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function getDescription()
    {
        return $this->getOptional([self::DETAILS, self::DETAILS_DESCRIPTION], '');
    }

    /**
     * @return float
     * @throws ArrayKeyNotFoundException
     *
     * @since 1.0.0
     */
    public function getPrice()
    {
        $price = floatval(str_replace(',', '.', $this->get(self::PRICE)));

        if (!$this->isTaxFree()) {
            $details = $this->getOptional(self::DETAILS);
            if ($details) {
                if (isset($details[self::DETAILS_PRICES]) && count($details[self::DETAILS_PRICES]) === 1) {
                    $prices = $details[self::DETAILS_PRICES];
                    if (isset($prices[0][self::DETAILS_PRICES_PRICE_NUMERIC])) {
                        return $prices[0][self::DETAILS_PRICES_PRICE_NUMERIC];
                    }
                }
            }
        }

        $priceNumeric = $this->getOptional(self::PRICE_NUMERIC);
        if (!is_null($priceNumeric)) {
            return $priceNumeric;
        }

        return $price;
    }

    /**
     * Creates a Wirecard SDK basket object based on the given shopware item.
     *
     * @return Item
     * @throws InvalidBasketItemException
     * @throws ArrayKeyNotFoundException
     *
     * @since 1.0.0
     */
    protected function createWirecardItem()
    {
        $this->validateItem();

        $amount   = new Amount(BasketMapper::numberFormat($this->getPrice()), $this->currency);
        $quantity = $this->getQuantity();

        $item = new Item($this->getArticleName(), $amount, $quantity);
        $item->setArticleNumber($this->getArticleNumber());
        $item->setDescription($this->getDescription());

        // Negative tax amount results in api-error "400.1221 order item tax amount is invalid"
        if ($amount->getValue() >= 0.0) {
            $taxAmount = new Amount(BasketMapper::numberFormat($this->getTax() / $quantity), $this->currency);
            $item->setTaxRate($this->getTaxRate());
            $item->setTaxAmount($taxAmount);
        }

        return $item;
    }

    /**
     * Validates the shopware array item.
     *
     * @throws InvalidBasketItemException
     *
     * @since 1.0.0
     */
    private function validateItem()
    {
        $item = $this->getShopwareItem();
        if (! isset(
            $item[self::ARTICLE_NAME],
            $item[self::ORDER_NUMBER],
            $item[self::TAX],
            $item[self::QUANTITY],
            $item[self::PRICE]
        )) {
            throw new InvalidBasketItemException();
        }
    }

    /**
     * Check if the order is sent taxfree or not
     *
     * @return bool
     * @since 1.3.8
     */
    private function isTaxFree()
    {
        $priceNumeric     = $this->getOptional(self::PRICE_NUMERIC);
        $amountNetNumeric = $this->getOptional(self::AMOUNTNET_NUMERIC);
        $isTaxFree = (! is_null($priceNumeric) && ! is_null($amountNetNumeric) && $priceNumeric == $amountNetNumeric);
        return $isTaxFree;
    }
}
