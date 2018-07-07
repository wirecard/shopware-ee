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
use WirecardShopwareElasticEngine\Exception\InvalidBasketItemException;

class BasketItemMapper
{
    const ITEM_ARTICLE_NAME = 'articlename';
    const ITEM_ORDER_NUMBER = 'ordernumber';
    const ITEM_ADDITIONAL_DETAILS = 'additional_details';
    const ITEM_ADDITIONAL_DETAILS_DESCRIPTION = 'description';
    const ITEM_ADDITIONAL_DETAILS_PRICES = 'prices';
    const ITEM_ADDITIONAL_DETAILS_PRICES_PRICE_NUMERIC = 'price_numeric';
    const ITEM_TAX = 'tax';
    const ITEM_TAX_RATE = 'tax_rate';
    const ITEM_QUANTITY = 'quantity';
    const ITEM_PRICE = 'price';

    /**
     * @var array
     */
    protected $shopwareItem;

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
     */
    public function __construct(array $shopwareItem, $currency)
    {
        $this->shopwareItem = $shopwareItem;
        $this->currency     = $currency;
        $this->wirecardItem = $this->createWirecardItem();
    }

    /**
     * @return array
     */
    public function getShopwareItem()
    {
        return $this->shopwareItem;
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
     */
    public function getArticleName()
    {
        return $this->getShopwareItem()[self::ITEM_ARTICLE_NAME];
    }

    /**
     * @return string
     */
    public function getOrderNumber()
    {
        return $this->getShopwareItem()[self::ITEM_ORDER_NUMBER];
    }

    /**
     * @return bool
     */
    public function hasAdditionalDetails()
    {
        return isset($this->getShopwareItem()[self::ITEM_ADDITIONAL_DETAILS]);
    }

    /**
     * @return array
     */
    public function getAdditionalDetails()
    {
        return $this->getShopwareItem()[self::ITEM_ADDITIONAL_DETAILS];
    }

    /**
     * @return float
     */
    public function getTaxRate()
    {
        return $this->getShopwareItem()[self::ITEM_TAX_RATE];
    }

    /**
     * @return float
     */
    public function getTax()
    {
        return $this->getShopwareItem()[self::ITEM_TAX];
    }

    /**
     * @return int
     */
    public function getQuantity()
    {
        return $this->getShopwareItem()[self::ITEM_QUANTITY];
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        if (! $this->hasAdditionalDetails()
            || ! isset($this->getAdditionalDetails()[self::ITEM_ADDITIONAL_DETAILS_DESCRIPTION])) {
            return '';
        }

        return $this->getAdditionalDetails()[self::ITEM_ADDITIONAL_DETAILS_DESCRIPTION];
    }

    /**
     * @return float
     */
    public function getPrice()
    {
        $price = floatval(str_replace(',', '.', $this->getShopwareItem()[self::ITEM_PRICE]));

        if ($this->hasAdditionalDetails()) {
            $additionalDetails = $this->getAdditionalDetails();
            $price             = $additionalDetails[self::ITEM_ADDITIONAL_DETAILS_PRICES_PRICE_NUMERIC];

            if (isset($additionalDetails[self::ITEM_ADDITIONAL_DETAILS_PRICES])
                && count($additionalDetails[self::ITEM_ADDITIONAL_DETAILS_PRICES]) === 1) {
                $prices = $additionalDetails[self::ITEM_ADDITIONAL_DETAILS_PRICES][0];
                $price  = $prices[self::ITEM_ADDITIONAL_DETAILS_PRICES_PRICE_NUMERIC];
            }
        }

        return $price;
    }

    /**
     * Creates a Wirecard SDK basket object based on the given shopware item.
     *
     * @return mixed|Item
     * @throws InvalidBasketItemException
     */
    protected function createWirecardItem()
    {
        if (! $this->validateItem()) {
            throw new InvalidBasketItemException($this->getShopwareItem());
        }

        $name        = $this->getArticleName();
        $orderNumber = $this->getOrderNumber();
        $description = $this->getDescription();
        $tax         = str_replace(',', '.', $this->getTax());
        $taxRate     = $this->getTaxRate();
        $quantity    = $this->getQuantity();
        $price       = $this->getPrice();

        $amount    = new Amount($price, $this->currency);
        $taxAmount = new Amount(floatval($tax) / $quantity, $this->currency);

        $wirecardItem = new Item($name, $amount, $quantity);
        $wirecardItem->setArticleNumber($orderNumber);
        $wirecardItem->setDescription($description);
        $wirecardItem->setTaxRate($taxRate);
        $wirecardItem->setTaxAmount($taxAmount);

        return $this->wirecardItem;
    }

    /**
     * Validates the shopware array item.
     *
     * @return bool
     */
    private function validateItem()
    {
        $item = $this->getShopwareItem();
        if (! isset(
            $item[self::ITEM_ARTICLE_NAME],
            $item[self::ITEM_ORDER_NUMBER],
            $item[self::ITEM_TAX],
            $item[self::ITEM_QUANTITY],
            $item[self::ITEM_PRICE]
        )) {
            return false;
        }

        return true;
    }
}
