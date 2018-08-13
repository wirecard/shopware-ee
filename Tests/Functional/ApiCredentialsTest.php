<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

namespace WirecardElasticEngine\Tests\Functional;

use Shopware\Components\Test\Plugin\TestCase;
use Wirecard\PaymentSdk\Config\Config;
use Wirecard\PaymentSdk\TransactionService;

class ApiCredentialsTest extends TestCase
{
    const API_TEST_URL = 'https://api-test.wirecard.com';
    const HTTP_USER = '70000-APITEST-AP';
    const HTTP_PASSWORD = 'qD2wzQ_hrc!8';

    protected static $ensureLoadedPlugins = [
        'WirecardElasticEngine',
    ];

    public function testCredentials()
    {
        $testConfig         = new Config(self::API_TEST_URL, self::HTTP_USER, self::HTTP_PASSWORD);
        $transactionService = new TransactionService($testConfig);

        $this->assertTrue($transactionService->checkCredentials());
    }
}