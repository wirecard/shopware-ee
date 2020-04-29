<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

namespace WirecardElasticEngine\Tests\Unit\Components\Mapper;

use PHPUnit\Framework\TestCase;
use WirecardElasticEngine\Components\Mapper\RiskInfoMapper;

class RiskInfoMapperTest extends TestCase
{
    public function testGetRiskInfo()
    {
        $mapper = new RiskInfoMapper('foo@bar.com', true);
        $mapped = $mapper->getRiskInfo();
        $this->assertEquals(
            [
                'delivery-mail' => 'foo@bar.com',
                'reorder-items' => '02'
            ],
            $mapped->mappedProperties()
        );
    }

    public function testGetRiskInfoWithoutReorderedItems()
    {
        $mapper = new RiskInfoMapper('foo@bar.com', false);
        $mapped = $mapper->getRiskInfo();
        $this->assertEquals(
            [
                'delivery-mail' => 'foo@bar.com',
                'reorder-items' => '01'
            ],
            $mapped->mappedProperties()
        );
    }
}
