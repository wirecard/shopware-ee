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
use WirecardElasticEngine\Components\Actions\ViewAction;

class ViewActionTest extends TestCase
{
    public function testInstance()
    {
        $view = new ViewAction(null);
        $this->assertInstanceOf(Action::class, $view);
        $this->assertNull($view->getTemplate());
        $this->assertEquals([], $view->getAssignments());

        $view = new ViewAction(null, ['foo' => 'bar', 'bar' => 'foo']);
        $this->assertNull($view->getTemplate());
        $this->assertEquals(['foo' => 'bar', 'bar' => 'foo'], $view->getAssignments());

        $view = new ViewAction('test.tpl');
        $this->assertEquals('test.tpl', $view->getTemplate());
        $this->assertEquals([], $view->getAssignments());

        $view = new ViewAction('test/test.tpl', ['bar' => 'bar', 'foo' => 'foo']);
        $this->assertEquals('test/test.tpl', $view->getTemplate());
        $this->assertEquals(['bar' => 'bar', 'foo' => 'foo'], $view->getAssignments());
    }
}
