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

        noTransactionInfoFound: '{s name="NoTransactionFound" namespace="backend/wirecard_elastic_engine/order_info_tab"}{/s}',
        backendOperationTitle: '{s name="BackendOperation" namespace="backend/wirecard_elastic_engine/order_info_tab"}{/s}',

        transactionHistory: 'TransactionHistory',
        transactionHistoryTable: {
            createdAt: 'Created At',
            transactionType: 'Transaction type',
            amount: 'Amount',
            currency: 'Currency'
        },
        transactionType: {
            'authorization': 'Authorize',
            'failed': 'Failed',
            'purchase': 'Purchase',
            'refund': 'Refund',
            'cancel': 'Cancel',
            'credit': 'Credit',
            'capture': 'Capture',
            'pending': 'Pending',
            'void-authorization' : 'Void Authorization'
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
            me.createTransactionHistoryContainer(),
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

    createTransactionHistoryContainer: function () {
        var me = this;

        me.historyStore = Ext.create('Ext.data.Store', {
            storeId: 'historyStore',
            fields: [
                'orderNumber',
                'parentTransactionId',
                'requestId',
                'transactionId',
                'providerTransactionId',
                'createdAt',
                'transactionType',
                'amount',
                'currency',
                'returnResponse',
                'notificationResponse'
            ],
            data: []
        });

        return Ext.create('Ext.grid.Panel', {
            title: me.snippets.transactionHistory,
            alias: 'wirecard-transaction-history',
            store: me.historyStore,
            border: 0,
            columns: [
                { text: me.snippets.transactionHistoryTable.createdAt, dataIndex: 'createdAt', flex: 1 },
                { text: me.snippets.transactionHistoryTable.transactionType, dataIndex: 'transactionType', flex: 1 },
                { text: me.snippets.transactionHistoryTable.amount, dataIndex: 'amount', flex: 1, renderer: Ext.util.Format.numberRenderer('0.00') },
                { text: me.snippets.transactionHistoryTable.currency, dataIndex: 'currency', flex: 1 },
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

                if (data) {
                    infoPanel.add({
                        xtype: 'container',
                        renderTpl: me.createInfoTemplate(),
                        renderData: data.transactionData
                    });

                    data.transactionHistory.forEach(function(transaction) {
                        var entryData = {
                            orderNumber: transaction.orderNumber,
                            parentTransactionId: transaction.parentTransactionId,
                            requestId: transaction.requestId,
                            transactionId: transaction.transactionId,
                            providerTransactionId: transaction.providerTransactionId,
                            createdAt: new Date(transaction.createdAt).toLocaleString(),
                            transactionType: me.snippets.transactionType[transaction.transactionType],
                            amount: transaction.amount,
                            currency: transaction.currency,
                            returnResponse: transaction.returnResponse,
                            notificationResponse: transaction.notificationResponse
                        };
                        historyData.push(entryData);
                    });

                    if (historyData.length) {
                        me.historyStore.loadData(historyData, false);
                    }

                    Object.keys(data.backendOperations).forEach(function(key) {
                        var operationType = key,
                            operation = data.backendOperations[key];

                        backendOperationPanel.add(Ext.create('Ext.button.Button', {
                            text: me.snippets.buttons[operation],
                            cls: 'secondary',
                            action: 'wirecard-operation-' + operationType,
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
                } else {
                    infoPanel.add({
                        xtype: 'container',
                        html: '<p>' + me.snippets.noTransactionInfoFound + '</p>'
                    });
                }
            }
        });
    },

    createInfoTemplate: function() {
        var me = this;

        return new Ext.XTemplate(
            '{literal}<tpl for=".">',
            '<div class="wirecard-info-pnl">',
            '<p>' + me.snippets.wirecardOrderNumber + ': {id}</p>',
            '<p>' + me.snippets.transactionId + ': {transactionId}</p>',
            '<p>' + me.snippets.providerTransactionId + ': {providerTransactionId}</p>',
            '</div>',
            '</tpl>{/literal}'
        );
    }
});
// {/block}
