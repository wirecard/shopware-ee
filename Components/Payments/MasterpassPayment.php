<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

namespace WirecardElasticEngine\Components\Payments;

use Shopware\Models\Shop\Shop;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Wirecard\PaymentSdk\Config\PaymentMethodConfig;
use Wirecard\PaymentSdk\Transaction\MasterpassTransaction;
use WirecardElasticEngine\Components\Data\PaymentConfig;

/**
 * @package WirecardElasticEngine\Components\Payments
 *
 * @since   1.0.0
 */
class MasterpassPayment extends Payment
{
    const PAYMETHOD_IDENTIFIER = 'wirecard_elastic_engine_masterpass';

    /**
     * @var MasterpassTransaction
     */
    private $transactionInstance;

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'WirecardMasterpass';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::PAYMETHOD_IDENTIFIER;
    }

    /**
     * {@inheritdoc}
     */
    public function getPosition()
    {
        return 4;
    }


    /**
     * @return MasterpassTransaction
     *
     * @since 1.0.0
     */
    public function getTransaction()
    {
        if (! $this->transactionInstance) {
            $this->transactionInstance = new MasterpassTransaction();
        }
        return $this->transactionInstance;
    }

    /**
     * {@inheritdoc}
     */
    public function getTransactionConfig(Shop $shop, ParameterBagInterface $parameterBag, $selectedCurrency)
    {
        $config = parent::getTransactionConfig($shop, $parameterBag, $selectedCurrency);
        $config->add(new PaymentMethodConfig(
            MasterpassTransaction::NAME,
            $this->getPaymentConfig()->getTransactionMAID(),
            $this->getPaymentConfig()->getTransactionSecret()
        ));

        return $config;
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentConfig()
    {
        $paymentConfig = new PaymentConfig(
            $this->getPluginConfig('MasterpassServer'),
            $this->getPluginConfig('MasterpassHttpUser'),
            $this->getPluginConfig('MasterpassHttpPassword')
        );

        $paymentConfig->setTransactionMAID($this->getPluginConfig('MasterpassMerchantId'));
        $paymentConfig->setTransactionSecret($this->getPluginConfig('MasterpassSecret'));
        $paymentConfig->setTransactionOperation($this->getPluginConfig('MasterpassTransactionType'));
        $paymentConfig->setFraudPrevention($this->getPluginConfig('MasterpassFraudPrevention'));

        return $paymentConfig;
    }
}
