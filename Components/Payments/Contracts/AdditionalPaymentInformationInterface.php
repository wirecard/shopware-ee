<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

namespace WirecardElasticEngine\Components\Payments\Contracts;

use WirecardElasticEngine\Models\Transaction;

/**
 * @package WirecardElasticEngine\Components\Payments\Contracts
 *
 * @since   1.1.0
 */
interface AdditionalPaymentInformationInterface
{
    /**
     * Some payments (e.g. PIA) require additional payment information on the checkout finish page (e.g. bank data).
     *
     * @param \Enlight_View_Default $view
     *
     * @since 1.1.0
     */
    public function assignAdditionalPaymentInformation(\Enlight_View_Default $view);
}
