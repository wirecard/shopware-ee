<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

namespace WirecardElasticEngine\Components\Mapper;

use WirecardElasticEngine\Exception\ArrayKeyNotFoundException;

/**
 * Base class for an Array mapper. Since Shopware handles a lot of things (basket, current user, ...) as arrays we're
 * mapping them to objects for simplicity and architectural reasons.
 *
 * @package WirecardElasticEngine\Components\Mapper
 *
 * @since   1.0.0
 */
abstract class ArrayMapper
{
    /**
     * @var array
     */
    protected $arrayEntity = [];

    /**
     * Returns a key from an (multidimensional) array. If the key doesn't exist an exception is thrown.
     *
     * @param string|array $keys Single key or a key chain for multidimensional arrays
     *
     * @return mixed
     * @throws ArrayKeyNotFoundException
     *
     * @since 1.0.0
     */
    protected function get($keys)
    {
        $keys  = (array)$keys;
        $value = $this->arrayEntity;
        foreach ($keys as $key) {
            if (! is_array($value) || ! isset($value[$key])) {
                throw new ArrayKeyNotFoundException(implode('.', $keys), get_class($this), $this->arrayEntity);
            }
            $value = $value[$key];
        }
        return $value;
    }

    /**
     * Returns a key from an (multidimensional) array. If the key doesn't exist the fallback value is returned.
     *
     * @param string|array $keys Single key or a key chain for multidimensional arrays
     * @param null $fallback
     *
     * @return mixed|null
     *
     * @since 1.0.0
     */
    protected function getOptional($keys, $fallback = null)
    {
        $keys  = (array)$keys;
        $value = $this->arrayEntity;
        foreach ($keys as $key) {
            if (! is_array($value) || ! isset($value[$key])) {
                return $fallback;
            }
            $value = $value[$key];
        }
        return $value;
    }
}
