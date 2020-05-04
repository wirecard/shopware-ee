<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

namespace WirecardElasticEngine\Components\Mapper;

use Wirecard\PaymentSdk\Constant\RiskInfoDeliveryTimeFrame;
use Wirecard\PaymentSdk\Constant\RiskInfoReorder;
use Wirecard\PaymentSdk\Entity\RiskInfo;

/**
 * @since 1.4.0
 */
class RiskInfoMapper
{
    /**
     * @var string
     */
    protected $userEmail;

    /**
     * @var bool
     */
    protected $hasReorderedItems;

    public function __construct(
        $userEmail,
        $hasReorderedItems
    ) {
        $this->userEmail         = $userEmail;
        $this->hasReorderedItems = $hasReorderedItems;
    }

    /**
     * @return RiskInfo
     */
    public function getRiskInfo()
    {
        $riskInfo = new RiskInfo();
        $riskInfo->setDeliveryEmailAddress($this->userEmail);
        $riskInfo->setReorderItems($this->hasReorderedItems ?
            RiskInfoReorder::REORDERED : RiskInfoReorder::FIRST_TIME_ORDERED);

        return $riskInfo;
    }
}
