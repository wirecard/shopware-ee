/**
 * Shop System Plugins - Terms of Use
 *
 * The plugins offered are provided free of charge by Wirecard AG and are explicitly not part
 * of the Wirecard AG range of products and services.
 *
 * They have been tested and approved for full functionality in the standard configuration
 * (status on delivery) of the corresponding shop system. They are under General Public
 * License version 3 (GPLv3) and can be used, developed and passed on to third parties under
 * the same terms.
 *
 * However, Wirecard AG does not provide any guarantee or accept any liability for any errors
 * occurring when used in an enhanced, customized shop system configuration.
 *
 * Operation in an enhanced, customized configuration is at your own risk and requires a
 * comprehensive test phase by the user of the plugin.
 *
 * Customers use the plugins at their own risk. Wirecard AG does not guarantee their full
 * functionality neither does Wirecard AG assume liability for any disadvantages related to
 * the use of the plugins. Additionally, Wirecard AG does not guarantee the full functionality
 * for customized shop systems or installed plugins of other vendors of plugins within the same
 * shop system.
 *
 * Customers are responsible for testing the plugin's functionality before starting productive
 * operation.
 *
 * By installing the plugin into the shop system the customer agrees to these terms of use.
 * Please do not use the plugin if you do not agree to these terms of use!
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
