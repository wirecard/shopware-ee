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

namespace WirecardElasticEngine\Components\Actions;

/**
 * Returned by Handlers if an error occurred, containing an error code and message.
 *
 * @package WirecardElasticEngine\Components\Actions
 *
 * @since   1.0.0
 */
class ErrorAction implements Action
{
    /**
     * Payment processing failed (e.g. due to an exception)
     */
    const PROCESSING_FAILED = 1;

    /**
     * The API returned a `FailureResponse`
     */
    const FAILURE_RESPONSE = 2;

    /**
     * Payment was cancelled by the consumer
     */
    const PAYMENT_CANCELED = 3;

    /**
     * Backend-operation failed (e.g. due to an exception)
     */
    const BACKEND_OPERATION_FAILED = 4;

    /**
     * @var int
     */
    protected $code;

    /**
     * @var string
     */
    protected $message;

    /**
     * @param int    $code
     * @param string $message
     *
     * @since 1.0.0
     */
    public function __construct($code, $message)
    {
        $this->code    = $code;
        $this->message = $message;
    }

    /**
     * @return int
     *
     * @since 1.0.0
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function getMessage()
    {
        return $this->message;
    }
}
