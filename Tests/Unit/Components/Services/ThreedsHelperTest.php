<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

namespace WirecardElasticEngine\Tests\Unit\Components\Services;

use Doctrine\ORM\AbstractQuery;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\Model\QueryBuilder;
use Shopware\Models\Order\Order;
use Shopware_Components_Config;
use WirecardElasticEngine\Components\Services\ThreedsHelper;
use WirecardElasticEngine\Models\CreditCardVault;

class ThreedsHelperTest extends TestCase
{
    /** @var ModelManager|PHPUnit_Framework_MockObject_MockObject */
    protected $modelManager;

    /** @var Shopware_Components_Config|PHPUnit_Framework_MockObject_MockObject */
    protected $config;

    /** @var QueryBuilder|PHPUnit_Framework_MockObject_MockObject */
    protected $queryBuilder;

    /** @var AbstractQuery|PHPUnit_Framework_MockObject_MockObject */
    protected $query;

    /** @var CreditCardVault|PHPUnit_Framework_MockObject_MockObject */
    protected $vaultData;

    /** @var ThreedsHelper */
    protected $helper;

    public function setUp()
    {
        $this->queryBuilder = $this->createMock(QueryBuilder::class);
        $this->queryBuilder->method('select')->willReturnSelf();
        $this->queryBuilder->method('from')->willReturnSelf();
        $this->queryBuilder->method('where')->willReturnSelf();
        $this->queryBuilder->method('andWhere')->willReturnSelf();
        $this->queryBuilder->method('setParameter')->willReturnSelf();
        $this->queryBuilder->method('innerJoin')->willReturnSelf();
        $this->queryBuilder->method('orderBy')->willReturnSelf();
        $this->queryBuilder->method('setMaxResults')->willReturnSelf();

        $this->query = $this->createMock(AbstractQuery::class);
        $this->queryBuilder->method('getQuery')->willReturn($this->query);

        $this->vaultData = $this->createMock(CreditCardVault::class);

        $this->modelManager = $this->createMock(ModelManager::class);
        $this->modelManager->method('createQueryBuilder')->willReturn($this->queryBuilder);
        $this->config = $this->createMock(Shopware_Components_Config::class);

        $this->helper = new ThreedsHelper(
            $this->modelManager,
            $this->config
        );
    }

    public function testGetTokenFromPaymentData()
    {
        $this->assertNull($this->helper->getTokenFromPaymentData('this is not an array'));
        $this->assertNull($this->helper->getTokenFromPaymentData([]));
        $this->assertEquals('3', $this->helper->getTokenFromPaymentData(['token' => '3']));
    }

    public function testIsNewToken()
    {
        $this->assertTrue($this->helper->isNewToken('1', ['token' => '3']));
        $this->assertTrue($this->helper->isNewToken('1', ['token' => '', 'saveToken' => 'true']));

        $this->query->method('getOneOrNullResult')->willReturn($this->vaultData);
        $this->assertFalse($this->helper->isNewToken('1', ['token' => '3', 'saveToken' => 'true']));
    }

    public function testGetCardCreationDate()
    {
        $refDate = new \DateTime('2019-09-21 16:58:00');
        $this->vaultData->method('getCreated')->willReturn($refDate);
        $this->query->method('getOneOrNullResult')->willReturn($this->vaultData);
        $dt = $this->helper->getCardCreationDate('uid', 'token');
        $this->assertEquals($refDate, $dt);
    }

    public function testGetCardCreationDateNewToken()
    {
        $refDate = new \DateTime('2019-09-21 16:58:00');
        $creationDate      = $this->helper->getCardCreationDate('uid', 'token');
        $this->assertNotEquals($refDate, $creationDate);
    }

    public function testGetCardCreationDateNoCreationDate()
    {
        $refDate = new \DateTime('2019-09-21 16:58:00');
        $this->query->method('getOneOrNullResult')->willReturn($this->vaultData);
        $creationDate = $this->helper->getCardCreationDate('uid', 'token');
        $this->assertNotEquals($refDate, $creationDate);
    }

    public function testGetSuccessfulOrdersLastSixMonths()
    {
        $this->query->method('getSingleScalarResult')->willReturn(10);
        $this->assertEquals(10, $this->helper->getSuccessfulOrdersLastSixMonths('1'));
    }

    public function testGetSuccessfulOrdersLastSixMonthsNoUserId()
    {
        $this->assertEquals(0, $this->helper->getSuccessfulOrdersLastSixMonths(''));
    }

    public function testHasReorderedItems()
    {
        $basket = [
            'content' => [
                [
                    'articleID' => 42
                ]
            ]
        ];
        $this->query->method('getSingleScalarResult')->willReturn(10);
        $this->assertTrue($this->helper->hasReorderedItems('1', $basket));
    }

    public function testHasReorderedItemsZero()
    {
        $basket = [
            'content' => [
                [
                    'articleID' => 42
                ]
            ]
        ];
        $this->query->method('getSingleScalarResult')->willReturn(0);
        $this->queryBuilder->method('setParameter')->withConsecutive(['userId', '1'], ['articleIds', [42]]);
        $this->assertFalse($this->helper->hasReorderedItems('1', $basket));
    }

    public function testHasReorderedItemsNoBasketContent()
    {
        $this->assertFalse($this->helper->hasReorderedItems('1', []));
        $this->assertFalse($this->helper->hasReorderedItems('1', ['content' => 'not an array']));
    }
}
