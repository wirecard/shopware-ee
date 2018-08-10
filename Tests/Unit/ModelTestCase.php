<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

namespace WirecardElasticEngine\Tests\Unit;

abstract class ModelTestCase extends \PHPUnit_Framework_TestCase
{
    protected $model;

    abstract public function getModel();

    public function setUp()
    {
        $this->model = $this->getModel();
    }

    public function assertGetterAndSetter($property, $value, $initialValue = null, $setter = null, $getter = null)
    {
        if (! $setter) {
            $setter = 'set' . ucfirst($property);
        }

        if (! $getter) {
            $getter = 'get' . ucfirst($property);
        }

        if (! method_exists($this->model, $setter) || ! method_exists($this->model, $getter)) {
            throw new \Exception('Getter or setter not defined for ' . get_class($this->model) . " ($property)");
        }

        $this->assertEquals($initialValue, $this->model->$getter());

        $this->model->$setter($value);
        $this->assertSame($this->model->$getter(), $value);
    }
}
