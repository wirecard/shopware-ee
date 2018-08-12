<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

namespace WirecardElasticEngine\Components\Payments\Contracts;

use WirecardElasticEngine\Components\Services\SessionManager;

/**
 * @package WirecardElasticEngine\Components\Payments\Contracts
 *
 * @since   1.0.0
 */
interface AdditionalViewAssignmentsInterface
{
    /**
     * Some payments (e.g. SEPA) require additional view assignments (e.g. for displaying additional input fields).
     *
     * @param SessionManager $sessionManager
     *
     * @return array
     *
     * @since 1.0.0
     */
    public function getAdditionalViewAssignments(SessionManager $sessionManager);
}
