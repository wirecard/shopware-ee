<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

namespace WirecardElasticEngine\Tests\Unit\Models;

use WirecardElasticEngine\Models\CreditCardVault;
use WirecardElasticEngine\Tests\Unit\ModelTestCase;

class CreditCardVaultTest extends ModelTestCase
{
    /**
     * @var CreditCardVault
     */
    protected $model;

    public function getModel()
    {
        return new CreditCardVault();
    }

    public function testGetId()
    {
        $this->assertNull($this->model->getId());
    }

    public function testSettersAndGetters()
    {
        $this->assertNotNull($this->model->getLastUsed());
        $this->assertGetterAndSetter('userId', 10);
        $this->assertGetterAndSetter('token', 'FOOTOKEN123');
        $this->assertGetterAndSetter('maskedAccountNumber', '1111****9999');
        $this->assertGetterAndSetter('lastUsed', new \DateTime(), $this->model->getLastUsed());
        $this->assertGetterAndSetter('created', new \DateTime(), $this->model->getCreated());
        $this->assertGetterAndSetter('bindBillingAddress', ['billingAddr']);
        $this->assertGetterAndSetter('bindBillingAddressHash', md5(serialize(['billingAddr'])));
        $this->assertGetterAndSetter('bindShippingAddress', ['shippingAddr']);
        $this->assertGetterAndSetter('bindShippingAddressHash', md5(serialize(['shippingAddr'])));
        $this->assertGetterAndSetter('additionalData', ['additional']);
        $this->assertEquals([
            'id'                      => null,
            'userId'                  => 10,
            'token'                   => 'FOOTOKEN123',
            'maskedAccountNumber'     => '1111****9999',
            'lastUsed'                => $this->model->getLastUsed()->format(\DateTime::W3C),
            'created'                 => $this->model->getCreated()->format(\DateTime::W3C),
            'bindBillingAddress'      => ['billingAddr'],
            'bindBillingAddressHash'  => md5(serialize(['billingAddr'])),
            'bindShippingAddress'     => ['shippingAddr'],
            'bindShippingAddressHash' => md5(serialize(['shippingAddr'])),
            'additionalData'          => ['additional'],
        ], $this->model->toArray());
    }
}
