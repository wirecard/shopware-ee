// {block name="backend/wirecard_extend_order/view/detail/wirecard_info_tab"}

Ext.define('Shopware.apps.WirecardExtendOrder.view.detail.WirecardInfoTab', {
    extend: 'Ext.container.Container',
    padding: 10,
    title: '{s name="TabTitle" namespace="backend/wirecard_elastic_engine/order_info_tab"}{/s}',
    autoScroll: true,

    historyStore: null,

    snippets: {
        infoTitle: '{s name="InfoTitle" namespace="backend/wirecard_elastic_engine/order_info_tab"}{/s}',
        wirecardOrderNumber: '{s name="WirecardOrderNumber" namespace="backend/wirecard_elastic_engine/order_info_tab"}{/s}',
        transactionId: '{s name="TransactionId" namespace="backend/wirecard_elastic_engine/order_info_tab"}{/s}',
        providerTransactionId: '{s name="ProviderTransactionId" namespace="backend/wirecard_elastic_engine/order_info_tab"}{/s}',
        providerTransactionReference: '{s name="providerTransactionReference" namespace="backend/wirecard_elastic_engine/order_info_tab"}{/s}',

        noTransactionInfoFound: '{s name="NoTransactionFound" namespace="backend/wirecard_elastic_engine/order_info_tab"}{/s}',
        backendOperationTitle: '{s name="BackendOperation" namespace="backend/wirecard_elastic_engine/order_info_tab"}{/s}',

        transactions: 'Transactions',
        transactionsTable: {
            createdAt: 'Created At',
            transactionId: 'Transaction Id',
            transactionType: 'Transaction type',
            amount: 'Amount',
            currency: 'Currency'
        },

        buttons: {
            Cancel: '{s name="CancelButtonText" namespace="backend/wirecard_elastic_engine/order_info_tab"}{/s}',
            Capture: '{s name="CaptureButtonText" namespace="backend/wirecard_elastic_engine/order_info_tab"}{/s}',
            Credit: '{s name="CreditButtonText" namespace="backend/wirecard_elastic_engine/order_info_tab"}{/s}',
            Pay: '{s name="PayButtonText" namespace="backend/wirecard_elastic_engine/order_info_tab"}{/s}',
            Refund: '{s name="RefundButtonText" namespace="backend/wirecard_elastic_engine/order_info_tab"}{/s}'
        },

        error: {
            backendOperationTitle: '{s name="BackendOperationErrorTitle" namespace="backend/wirecard_elastic_engine/order_info_tab"}{/s}',
            unsufficiantData: '{s name="BackendOperationUnsufficiantDataError" namespace="backend/wirecard_elastic_engine/order_info_tab"}{/s}',
            unknownBackendOperation: '{s name="UnknownBackendOperationError" namespace="backend/wirecard_elastic_engine/order_info_tab"}{/s}',
            unknownPaymethod: '{s name="UnknownPaymethodError" namespace="backend/wirecard_elastic_engine/order_info_tab"}{/s}'
        }
    },

    initComponent: function() {
        var me = this;

        me.items = [
            me.createInfoContainer(),
            me.createTransactionsContainer(),
            me.createBackendOperationContainer()
        ];
        me.callParent(arguments);
        me.loadData(me.record);
    },

    createInfoContainer: function() {
        var me = this;

        return Ext.create('Ext.panel.Panel', {
            title: me.snippets.infoTitle,
            alias: 'wirecard-info-panel',
            bodyPadding: 10,
            margin: '10 0',
            flex: 1,
            paddingRight: 5,
            items: [
            ]
        });
    },

    createTransactionsContainer: function () {
        var me = this;

        me.historyStore = Ext.create('Ext.data.Store', {
            storeId: 'historyStore',
            fields: [
                'orderNumber',
                'transactionType',
                'parentTransactionId',
                'transactionId',
                'providerTransactionId',
                'providerTransactionReference',
                'requestId',
                'createdAt',
                'amount',
                'currency',
                'response'
            ],
            data: []
        });

        return Ext.create('Ext.grid.Panel', {
            title: me.snippets.transactions,
            alias: 'wirecard-transaction-history',
            store: me.historyStore,
            border: 0,
            columns: [
                { text: me.snippets.transactionsTable.createdAt, dataIndex: 'createdAt', flex: 1 },
                { text: me.snippets.transactionsTable.transactionId, dataIndex: 'transactionId', flex: 1 },
                { text: me.snippets.transactionsTable.transactionType, dataIndex: 'transactionType', flex: 1 },
                { text: me.snippets.transactionsTable.amount, dataIndex: 'amount', flex: 1, renderer: Ext.util.Format.numberRenderer('0.00') },
                { text: me.snippets.transactionsTable.currency, dataIndex: 'currency', flex: 1 },
                {
                    xtype: 'actioncolumn',
                    width: 50,
                    items: [{
                        iconCls: 'sprite-magnifier-medium',
                        tooltip: 'Open history details',

                        handler: function (view, rowIndex, colIndex, item, opts, record) {
                            var historyWindow = Ext.create('Shopware.apps.WirecardExtendOrder.view.HistoryWindow', { record: record });
                            historyWindow.show();
                        }
                    }]
                }
            ],
            bodyPadding: 10,
            margin: '10 0',
            width: '100%'
        });
    },

    createBackendOperationContainer: function() {
        var me = this;

        return Ext.create('Ext.panel.Panel', {
            title: me.snippets.backendOperationTitle,
            alias: 'wirecard-backend-operation',
            bodyPadding: 10,
            flex: 1,
            paddingRight: 5,
            items: [
            ]
        });
    },

    loadData: function(record) {
        var me = this,
            data = record.data,
            detailsStore = Ext.create('Shopware.apps.WirecardExtendOrder.store.WirecardOrderDetails'),
            payMethod = record.getPayment().first().get('name'),
            infoPanel = me.child('[alias=wirecard-info-panel]'),
            backendOperationPanel = me.child(['[alias=wirecard-backend-operation]']);

        detailsStore.getProxy().extraParams = {
            orderNumber: data.number,
            payMethod: payMethod
        };

        detailsStore.load({
            callback: function(records, operation) {
                var data = Array.isArray(records) && records.length === 1 ? records[0].getData() : false,
                    historyData = [];
                window.DATA = data;

                if (!data) {
                    infoPanel.add({
                        xtype: 'container',
                        html: '<p>' + me.snippets.noTransactionInfoFound + '</p>'
                    });
                    return;
                }

                data.transactions.forEach(function(transaction) {
                    historyData.push({
                        orderNumber: transaction.orderNumber,
                        parentTransactionId: transaction.parentTransactionId,
                        requestId: transaction.requestId,
                        transactionId: transaction.transactionId,
                        providerTransactionId: transaction.providerTransactionId,
                        providerTransactionReference: transaction.providerTransactionReference,
                        createdAt: new Date(transaction.createdAt).toLocaleString(),
                        transactionType: transaction.transactionType,
                        amount: transaction.amount,
                        currency: transaction.currency,
                        response: transaction.response
                    });
                });

                if (historyData.length) {
                    me.historyStore.loadData(historyData, false);
                }

                Object.keys(data.backendOperations).forEach(function(key) {
                    var operation = data.backendOperations[key];

                    backendOperationPanel.add(Ext.create('Ext.button.Button', {
                        text: me.snippets.buttons[operation],
                        cls: 'secondary',
                        action: 'wirecard-operation-' + key,
                        handler: function() {
                            var backendOperationWindow = Ext.create('Shopware.apps.WirecardExtendOrder.view.BackendOperationWindow', {
                                record: me.record,
                                data: data,
                                operation: operation,
                                title: 'BackendOperationWindow: ' + operation
                            });
                            backendOperationWindow.show();
                            // me.processBackendOperation(operationType);
                        }
                    }));
                });
            }
        });
    }
});
// {/block}
