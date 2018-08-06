<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

namespace WirecardElasticEngine\Exception;

use WirecardElasticEngine\Components\Mapper\BasketItemMapper;
use WirecardElasticEngine\Components\Mapper\BasketMapper;

/**
 * Thrown by the `BasketMapper` if basket validation fails.
 *
 * @see     BasketMapper::validateBasket()
 *
 * @package WirecardElasticEngine\Exception
 *
 * @since 1.0.0
 */
class OutOfStockBasketException extends BasketException
{
    /**
     * @param BasketItemMapper $item
     *
     * @throws ArrayKeyNotFoundException
     *
     * @since 1.0.0
     */
    public function __construct(BasketItemMapper $item)
    {
        parent::__construct("Article '{$item->getArticleName()}' {$item->getArticleNumber()} is out-of-stock");
    }
}
