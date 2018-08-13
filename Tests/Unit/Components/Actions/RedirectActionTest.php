<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

namespace WirecardElasticEngine\Tests\Unit\Components\Data;

use PHPUnit\Framework\TestCase;
use WirecardElasticEngine\Components\Actions\Action;
use WirecardElasticEngine\Components\Actions\RedirectAction;

class RedirectActionTest extends TestCase
{
    public function testInstance()
    {
        $redirect = new RedirectAction('https://localhost/redirect');
        $this->assertInstanceOf(Action::class, $redirect);
        $this->assertEquals('https://localhost/redirect', $redirect->getUrl());
    }
}
