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
 * Thrown by the backend controller if testing credentials fails.
 *
 * @see     \Shopware_Controllers_Backend_WirecardElasticEngineTransactions::testCredentialsAction()
 *
 * @package WirecardElasticEngine\Exception
 *
 * @since   1.0.0
 */
class MissingCredentialsException extends \Exception
{
}
