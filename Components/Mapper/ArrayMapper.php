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

use WirecardShopwareElasticEngine\Exception\ArrayKeyNotFoundException;

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
     * Returns if an array key exists in an (multidimensional) array.
     *
     * @param string|array $keys Single key or a key chain for multidimensional arrays
     *
     * @return bool
     */
    protected function has($keys)
    {
        $keys  = (array)$keys;
        $value = $this->arrayEntity;
        foreach ($keys as $key) {
            if (! is_array($value) || ! array_key_exists($key, $value)) {
                return false;
            }
            $value = $value[$key];
        }
        return true;
    }

    /**
     * Returns a key from an (multidimensional) array. If the key doesn't exist the fallback value is returned.
     *
     * @param string|array $keys Single key or a key chain for multidimensional arrays
     * @param null $fallback
     *
     * @return mixed|null
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
