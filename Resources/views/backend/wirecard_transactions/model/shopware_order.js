Ext.define('Shopware.apps.WirecardTransactions.model.ShopwareOrder', {
    extend: 'Shopware.data.Model',

    configure: function() {
        return {
            controller: 'WirecardTransactions'
        };
    },

    fields: [
        { name: 'number', type: 'string' },
        { name: 'invoiceAmount', type: 'float' },
        { name: 'languageIso', type: 'string' },
        { name: 'customerId', type: 'string' },
        { name: 'orderTime', type: 'datetime' },
        { name: 'status', type: 'string' },
        { name: 'cleared', type: 'string' },
        { name: 'paymentId', type: 'string' },
        { name: 'currency', type: 'string' },
        { name: 'transactionId', type: 'string' },
        { name: 'temporaryId', type: 'string' },
        { name: 'wirecardTransactions' }
    ],

    /**
     * @type { Array }
     */
    associations: [
        { type: 'hasMany', model: 'Shopware.apps.Base.model.Customer', name: 'getCustomer', associationKey: 'customer' },
        { type: 'hasMany', model: 'Shopware.apps.Base.model.Shop', name: 'getLanguageSubShop', associationKey: 'languageSubShop' },
        { type: 'hasMany', model: 'Shopware.apps.Base.model.OrderStatus', name: 'getOrderStatus', associationKey: 'orderStatus' },
        { type: 'hasMany', model: 'Shopware.apps.Base.model.Payment', name: 'getPayment', associationKey: 'payment' },
        { type: 'hasMany', model: 'Shopware.apps.Base.model.PaymentStatus', name: 'getPaymentStatus', associationKey: 'paymentStatus' },
        { type: 'hasMany', model: 'Shopware.apps.WirecardTransactions.model.WirecardTransactions', name: 'getWirecardTransactions', associationKey: 'wirecardTransactions' }
    ]

});
