<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

namespace WirecardElasticEngine\Exception;

/**
 * Thrown if actions returned by handlers are unknown / not implemented.
 *
 * @package WirecardElasticEngine\Exception
 *
 * @since   1.0.0
 */
class UnknownActionException extends BasketException
{
    /**
     * @param string $actionName
     *
     * @since 1.0.0
     */
    public function __construct($actionName)
    {
        parent::__construct("Action '$actionName' not found");
    }
}
