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
 * Thrown during the return action whenever an order could not be saved.
 *
 * @see     \Shopware_Controllers_Frontend_WirecardElasticEnginePayment::returnAction()
 *
 * @package WirecardElasticEngine\Exception
 *
 * @since   1.0.0
 */
class CouldNotSaveOrderException extends \Exception
{
    /**
     * @param string $transactionId
     * @param string $paymentUniqueId
     * @param string $paymentStatus
     *
     * @since 1.0.0
     */
    public function __construct($transactionId, $paymentUniqueId, $paymentStatus)
    {
        parent::__construct("Could not save order ($transactionId, $paymentUniqueId, $paymentStatus)");
    }
}
