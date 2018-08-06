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

/**
 * Thrown by the `BasketItemMapper` if item validation fails.
 *
 * @see     BasketItemMapper::validateItem()
 *
 * @package WirecardElasticEngine\Exception
 *
 * @since   1.0.0
 */
class InvalidBasketItemException extends BasketException
{
    /**
     * @since 1.0.0
     */
    public function __construct()
    {
        parent::__construct("Invalid basket item");
    }
}
