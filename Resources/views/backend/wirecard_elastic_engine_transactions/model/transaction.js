/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

Ext.define('Shopware.apps.WirecardElasticEngineTransactions.model.Transaction', {
    extend: 'Shopware.data.Model',
    fields: [
        { name: 'id', type: 'int' },
        { name: 'orderId', type: 'int' },
        { name: 'orderStatus', type: 'int' },
        { name: 'orderPaymentMethod', type: 'string' },
        { name: 'orderNumber', type: 'string' },
        { name: 'paymentUniqueId', type: 'string' },
        { name: 'type', type: 'string' },
        { name: 'transactionId', type: 'string' },
        { name: 'parentTransactionId', type: 'string' },
        { name: 'providerTransactionId', type: 'string' },
        { name: 'transactionType', type: 'string' },
        { name: 'paymentMethod', type: 'string' },
        { name: 'amount', type: 'float' },
        { name: 'currency', type: 'string' },
        { name: 'response', type: 'object' },
        { name: 'request', type: 'object' },
        { name: 'requestId', type: 'string' },
        { name: 'createdAt', type: 'string' },
        { name: 'statusMessage', type: 'string' }
    ],

    /**
     * @returns { { controller: string } }
     */
    configure: function () {
        return {
            controller: 'WirecardElasticEngineTransactions'
        };
    }
});
