<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

namespace WirecardElasticEngine\Exception;

use WirecardElasticEngine\Components\Mapper\BasketMapper;

/**
 * Thrown by the `BasketMapper` if basket validation fails.
 *
 * @see     BasketMapper::validateBasket()
 *
 * @package WirecardElasticEngine\Exception
 *
 * @since   1.0.0
 */
class InvalidBasketException extends BasketException
{
    /**
     * @since 1.0.0
     */
    public function __construct()
    {
        parent::__construct("Invalid basket");
    }
}
