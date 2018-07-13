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
use Wirecard\PaymentSdk\Entity\Item;
use WirecardShopwareElasticEngine\Exception\ArrayKeyNotFoundException;
use WirecardShopwareElasticEngine\Exception\InvalidBasketItemException;

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

    /**
     * @var Item
     */
    protected $wirecardItem;

    /**
     * @var string
     */
    protected $currency;

    /**
     * BasketItemMapper constructor.
     *
     * @param array  $shopwareItem
     * @param string $currency
     *
     * @throws InvalidBasketItemException
     * @throws ArrayKeyNotFoundException
     */
    public function __construct(array $shopwareItem, $currency)
    {
        $this->arrayEntity  = $shopwareItem;
        $this->currency     = $currency;
        $this->wirecardItem = $this->createWirecardItem();
    }

    /**
     * @return array
     */
    public function getShopwareItem()
    {
        return $this->arrayEntity;
    }

    /**
     * @return Item
     */
    public function getWirecardItem()
    {
        return $this->wirecardItem;
    }

    /**
     * @return string
     * @throws ArrayKeyNotFoundException
     */
    public function getArticleName()
    {
        return $this->get(self::ARTICLE_NAME);
    }

    /**
     * The shopware basket item "ordernumber" is actually the article-number/sku
     * @return string
     * @throws ArrayKeyNotFoundException
     */
    public function getArticleNumber()
    {
        return $this->get(self::ORDER_NUMBER);
    }

    /**
     * @return float
     * @throws ArrayKeyNotFoundException
     */
    public function getTaxRate()
    {
        return $this->get(self::TAX_RATE);
    }

    /**
     * @return float
     * @throws ArrayKeyNotFoundException
     */
    public function getTax()
    {
        return floatval(str_replace(',', '.', $this->get(self::TAX)));
    }

    /**
     * @return int
     * @throws ArrayKeyNotFoundException
     */
    public function getQuantity()
    {
        return intval($this->get(self::QUANTITY));
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->getOptional([self::DETAILS, self::DETAILS_DESCRIPTION], '');
    }

    /**
     * @return float
     * @throws ArrayKeyNotFoundException
     */
    public function getPrice()
    {
        $price = floatval(str_replace(',', '.', $this->get(self::PRICE)));

        $details = $this->getOptional(self::DETAILS);
        if ($details) {
            if (isset($details[self::DETAILS_PRICES]) && count($details[self::DETAILS_PRICES]) === 1) {
                $prices = $details[self::DETAILS_PRICES];
                if (isset($prices[0][self::DETAILS_PRICES_PRICE_NUMERIC])) {
                    return $prices[0][self::DETAILS_PRICES_PRICE_NUMERIC];
                }
            }
            if (isset($details[self::DETAILS_PRICES_PRICE_NUMERIC])) {
                return $details[self::DETAILS_PRICES_PRICE_NUMERIC];
            }
        }
        return $price;
    }

    /**
     * Creates a Wirecard SDK basket object based on the given shopware item.
     *
     * @return Item
     * @throws InvalidBasketItemException
     * @throws ArrayKeyNotFoundException
     */
    protected function createWirecardItem()
    {
        $this->validateItem();

        $amount   = new Amount($this->getPrice(), $this->currency);
        $quantity = $this->getQuantity();

        $item = new Item($this->getArticleName(), $amount, $quantity);
        $item->setArticleNumber($this->getArticleNumber());
        $item->setDescription($this->getDescription());

        // Negative tax amount results in api-error "400.1221 order item tax amount is invalid"
        if ($amount->getValue() >= 0.0) {
            $taxAmount = new Amount($this->getTax() / $quantity, $this->currency);
            $item->setTaxRate($this->getTaxRate());
            $item->setTaxAmount($taxAmount);
        }

        return $item;
    }

    /**
     * Validates the shopware array item.
     *
     * @throws InvalidBasketItemException
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
            throw new InvalidBasketItemException($this);
        }
    }
}
