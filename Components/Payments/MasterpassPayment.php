<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

namespace WirecardElasticEngine\Components\Payments;

use Shopware\Models\Order\Order;
use Shopware\Models\Shop\Shop;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Wirecard\PaymentSdk\Config\PaymentMethodConfig;
use Wirecard\PaymentSdk\Transaction\MasterpassTransaction;
use Wirecard\PaymentSdk\Transaction\Transaction;
use WirecardElasticEngine\Components\Data\PaymentConfig;
use WirecardElasticEngine\Models\Transaction as TransactionModel;

/**
 * @package WirecardElasticEngine\Components\Payments
 *
 * @since   1.1.0
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
     * @since 1.1.0
     */
    public function getTransaction()
    {
        if (! $this->transactionInstance) {
            $this->transactionInstance = new MasterpassTransaction();
        }
        return $this->transactionInstance;
    }

    /**
     * If paymentMethod is 'masterpass' and transaction type is 'debit' or 'authorization',
     * no backend operation is allowed.
     *
     * @param Order            $order
     * @param string|null      $operation
     * @param TransactionModel $parentTransaction
     *
     * @return MasterpassTransaction|null
     *
     * @since 1.1.0
     */
    public function getBackendTransaction(Order $order, $operation, TransactionModel $parentTransaction)
    {
        $transactionType = $parentTransaction->getTransactionType();
        if ($parentTransaction->getPaymentMethod() === MasterpassTransaction::NAME
            && ($transactionType === Transaction::TYPE_DEBIT || $transactionType === Transaction::TYPE_AUTHORIZATION)
        ) {
            return null;
        }
        return new MasterpassTransaction();
    }

    /**
     * {@inheritdoc}
     */
    public function getTransactionConfig(ParameterBagInterface $parameterBag, $selectedCurrency)
    {
        $config = parent::getTransactionConfig($parameterBag, $selectedCurrency);
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
