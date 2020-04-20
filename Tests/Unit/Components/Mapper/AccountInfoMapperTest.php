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
use PHPUnit_Framework_MockObject_MockObject;
use Shopware\Models\Customer\Customer;
use WirecardElasticEngine\Components\Mapper\AccountInfoMapper;
use WirecardElasticEngine\Components\Services\SessionManager;

class AccountInfoMapperTest extends TestCase
{
    protected $user = [
        'additional' => [
            'user' => [
                'id'                   => '12',
                'customernumber'       => '10001',
                'firstname'            => 'First Name',
                'lastname'             => 'Last Name',
                'email'                => 'test@example.com',
                'accountmode'          => Customer::ACCOUNT_MODE_CUSTOMER,
                'lastlogin'            => '2019-09-23 23:12:12',
                'password_change_date' => '2019-09-24 23:12:13',
                'changed'              => '2019-09-25 23:12:14',
                'firstlogin'           => '2019-09-26 23:12:14',
            ],
        ],
    ];

    /**
     * @var SessionManager|PHPUnit_Framework_MockObject_MockObject
     */
    protected $sessionManager;

    public function setUp()
    {
        $this->sessionManager = $this->createMock(SessionManager::class);
    }

    public function testGetAccountInfo()
    {
        $refDates = new \DateTime('2019-08-09 11:00:00');
        $this->sessionManager->method('getUserId')->willReturn('1');
        $mapper = new AccountInfoMapper($this->sessionManager, $this->user, '01', false, $refDates, $refDates, 0);
        $mapped = $mapper->getAccountInfo('123');
        $this->assertEquals(
            [
                'authentication-method'      => '02',
                'authentication-timestamp'   => '2019-09-23',
                'challenge-indicator'        => '01',
                'creation-date'              => '2019-09-26',
                'update-date'                => '2019-09-25',
                'password-change-date'       => '2019-09-24',
                'shipping-address-first-use' => '2019-08-09',
                'card-creation-date'         => '2019-08-09',
                'purchases-last-six-months'  => 0
            ],
            $mapped->mappedProperties()
        );
    }

    public function testGetAccountInfoGuest()
    {
        $user                                      = $this->user;
        $user['additional']['user']['accountmode'] = Customer::ACCOUNT_MODE_FAST_LOGIN;
        $refDates                                  = new \DateTime('2019-08-09 11:00:00');
        $this->sessionManager->method('getUserId')->willReturn('1');
        $mapper = new AccountInfoMapper($this->sessionManager, $user, '01', false, $refDates, $refDates, 0);
        $mapped = $mapper->getAccountInfo('123');
        $this->assertEquals(
            [
                'authentication-method'    => '01',
                'authentication-timestamp' => '2019-09-23',
                'challenge-indicator'      => '01',
            ],
            $mapped->mappedProperties()
        );
    }

    public function testGetAccountInfoNewToken()
    {
        $refDates = new \DateTime('2019-08-09 11:00:00');
        $this->sessionManager->method('getUserId')->willReturn('1');
        $mapper = new AccountInfoMapper($this->sessionManager, $this->user, '01', true, $refDates, $refDates, 0);
        $mapped = $mapper->getAccountInfo('123');
        $this->assertEquals(
            [
                'authentication-method'      => '02',
                'authentication-timestamp'   => '2019-09-23',
                'challenge-indicator'        => '04',
                'creation-date'              => '2019-09-26',
                'update-date'                => '2019-09-25',
                'password-change-date'       => '2019-09-24',
                'shipping-address-first-use' => '2019-08-09',
                'card-creation-date'         => '2019-08-09',
                'purchases-last-six-months'  => 0
            ],
            $mapped->mappedProperties()
        );
    }

    public function testGetAccountInfoNoOneClickCheckout()
    {
        $refDates = new \DateTime('2019-08-09 11:00:00');
        $this->sessionManager->method('getUserId')->willReturn('1');
        $mapper = new AccountInfoMapper($this->sessionManager, $this->user, '02', true, $refDates, $refDates, 0);
        $mapped = $mapper->getAccountInfo(null);
        $this->assertEquals(
            [
                'authentication-method'      => '02',
                'authentication-timestamp'   => '2019-09-23',
                'challenge-indicator'        => '02',
                'creation-date'              => '2019-09-26',
                'update-date'                => '2019-09-25',
                'password-change-date'       => '2019-09-24',
                'shipping-address-first-use' => '2019-08-09',
                'card-creation-date'         => '2019-08-09',
                'purchases-last-six-months'  => 0
            ],
            $mapped->mappedProperties()
        );
    }
}
