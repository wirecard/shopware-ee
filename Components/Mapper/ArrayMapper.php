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
     * Returns a key from an array. If the key doesn't exist an exception is thrown.
     *
     * @param string $key
     *
     * @return mixed
     * @throws ArrayKeyNotFoundException
     */
    protected function get($key)
    {
        if (! isset($this->arrayEntity[$key])) {
            throw new ArrayKeyNotFoundException($key, get_class($this));
        }
        return $this->arrayEntity[$key];
    }

    /**
     * Returns if an array key exists.
     *
     * @param string $key
     *
     * @return bool
     */
    protected function has($key)
    {
        return array_key_exists($key, $this->arrayEntity);
    }

    /**
     * Returns a key from an array. If the key doesn't exist the fallback value is returned.
     *
     * @param string $key
     * @param null   $fallback
     *
     * @return mixed|null
     */
    protected function getOptional($key, $fallback = null)
    {
        if (! isset($this->arrayEntity[$key])) {
            return $fallback;
        }
        return $this->arrayEntity[$key];
    }
}
