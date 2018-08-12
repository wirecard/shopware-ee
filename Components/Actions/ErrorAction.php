<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
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
     * Payment processing failed due to wrong age
     */
    const PROCESSING_FAILED_WRONG_AGE = 5;

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
