<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

namespace WirecardElasticEngine\Components\Payments\Contracts;

use WirecardElasticEngine\Components\Mapper\UserMapper;
use WirecardElasticEngine\Components\Services\SessionManager;

/**
 * @package WirecardElasticEngine\Components\Payments\Contracts
 *
 * @since   1.1.0
 */
interface DisplayRestrictionInterface
{
    /**
     * @param UserMapper     $userMapper
     * @param SessionManager $sessionManager
     *
     * @return bool
     *
     * @since 1.1.0
     */
    public function checkDisplayRestrictions(UserMapper $userMapper, SessionManager $sessionManager);
}
