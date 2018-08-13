<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

namespace WirecardElasticEngine\Components\Mapper;

use Shopware\Models\Order\Detail;
use Shopware\Models\Order\Order;
use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Entity\Basket;
use Wirecard\PaymentSdk\Entity\Item;

/**
 * Create Wirecard Basket object from Shopware Order entity.
 *
 * @package WirecardElasticEngine\Components\Mapper
 *
 * @since   1.0.0
 */
class OrderBasketMapper
{
    /**
     * Create Wirecard Basket object from Shopware Order entity.
     *
     * @param Order $order
     *
     * @return Basket
     *
     * @since 1.0.0
     */
    public function createBasket(Order $order)
    {
        $basket   = new Basket();
        $currency = $order->getCurrency();

        /** @var Detail $detail */
        foreach ($order->getDetails() as $detail) {
            $basket->add($this->getOrderDetailItem($detail, $currency));
        }
        if ($order->getInvoiceShipping()) {
            $basket->add($this->getShippingItem($order));
        }

        return $basket;
    }

    /**
     * @param Detail $detail
     * @param string $currency
     *
     * @return Item
     *
     * @since 1.0.0
     */
    private function getOrderDetailItem(Detail $detail, $currency)
    {
        $amount = new Amount($detail->getPrice(), $currency);
        $item   = new Item($detail->getArticleName(), $amount, $detail->getQuantity());
        $item->setArticleNumber($detail->getArticleNumber());

        // Negative tax amount results in api-error "400.1221 order item tax amount is invalid"
        if ($amount->getValue() >= 0.0) {
            $item->setTaxRate($detail->getTaxRate());
            $item->setTaxAmount(new Amount(
                BasketMapper::numberFormat($detail->getPrice() * ($detail->getTaxRate() / 100.0)),
                $currency
            ));
        }

        return $item;
    }

    /**
     * @param Order $order
     *
     * @return Item
     *
     * @since 1.0.0
     */
    private function getShippingItem(Order $order)
    {
        $amount = new Amount($order->getInvoiceShipping(), $order->getCurrency());
        $item   = new Item('Shipping', $amount, 1);
        $item->setArticleNumber('shipping');
        $item->setDescription($order->getDispatch()->getName());
        $item->setTaxAmount(new Amount(
            BasketMapper::numberFormat($order->getInvoiceShipping() - $order->getInvoiceShippingNet()),
            $order->getCurrency()
        ));
        $item->setTaxRate($order->getInvoiceShippingNet() / $order->getInvoiceShipping());
        return $item;
    }
}
