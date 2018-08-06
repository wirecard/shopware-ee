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
 * Thrown by the `Mapper` class when a required array key is not existent.
 *
 * @package WirecardElasticEngine\Exception
 *
 * @since   1.0.0
 */
class ArrayKeyNotFoundException extends \Exception
{
    /**
     * @param string $key
     * @param string $className
     * @param array  $arrayEntity
     *
     * @since 1.0.0
     */
    public function __construct($key, $className, array $arrayEntity)
    {
        parent::__construct("Array key ($key) not found in $className: " . var_export($arrayEntity, true));
    }
}
