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

    initComponent: function () {
        var me = this;

        me.items = [
            me.createInfoContainer(),
            me.createTransactionsContainer()
        ];
        me.callParent(arguments);
        me.loadData(me.record);
    },

    createInfoContainer: function () {
        var me = this;

        return Ext.create('Ext.panel.Panel', {
            title: me.snippets.infoTitle,
            alias: 'wirecard-info-panel',
            bodyPadding: 10,
            margin: '10 0',
            flex: 1,
            paddingRight: 5,
            items: []
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
                'response',
                'backendOperations',
                'isFinal'
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
                    width: 150,
                    items: [{
                        iconCls: 'sprite-magnifier-medium',
                        tooltip: 'Open transaction details',

                        handler: function (view, rowIndex, colIndex, item, opts, record) {
                            var historyWindow = Ext.create('Shopware.apps.WirecardExtendOrder.view.HistoryWindow', { record: record });
                            historyWindow.show();
                        }
                    }, {
                        iconCls: 'sprite-cheque--plus',
                        tooltip: 'Pay / Capture',

                        handler: function(view, row, col, item, opts, record) {
                            me.showAmountDialog(record.data, 'pay');
                        },

                        getClass: function(value, meta, record) {
                            var transaction = record.data;

                            if (transaction.isFinal || !transaction.backendOperations || !transaction.backendOperations.pay) {
                                return 'x-hide-display';
                            }
                        }
                    }, {
                        iconCls: 'sprite-arrow-circle-315',
                        tooltip: 'Refund',

                        handler: function(view, row, col, item, opts, record) {
                            me.showAmountDialog(record.data, 'refund');
                        },

                        getClass: function(value, meta, record) {
                            var transaction = record.data;
                            console.log(record.data);

                            if (transaction.isFinal || !transaction.backendOperations || !transaction.backendOperations.refund) {
                                return 'x-hide-display';
                            }
                        }
                    }, {
                        iconCls: 'sprite-cross-circle',
                        tooltip: 'Cancel / Refund',

                        handler: function(view, row, col, item, opts, record) {
                            Ext.MessageBox.confirm('Cancel', 'Cancel transaction?', function(choice) {
                                if (choice === 'no') {
                                    return false;
                                }

                                me.processBackendOperation(record.data, 'cancel');
                            });
                        },

                        getClass: function(value, meta, record) {
                            var transaction = record.data;

                            if (transaction.isFinal || !transaction.backendOperations || !transaction.backendOperations.cancel) {
                                return 'x-hide-display';
                            }
                        }
                    }]
                }
            ],
            bodyPadding: 10,
            margin: '10 0',
            width: '100%'
        });
    },

    showAmountDialog: function (transaction, operation) {
        var me = this;
        var win = Ext.create('Ext.window.Window', {
            title: 'Set amount',
            layout: 'fit',
            width: 300,
            height: 100,
            items: {
                id: 'transaction-amount',
                xtype: 'numberfield',
                fieldLabel: 'Amount',
                value: transaction.amount
            },
            buttons: [{
                text: 'Submit',
                handler: function() {
                    me.processBackendOperation(transaction, operation, Ext.getCmp('transaction-amount').getValue());
                }
            }, {
                text: 'Close',
                handler: function() {
                    win.close();
                }
            }]
        }).show();
    },

    loadData: function (record) {
        var me = this,
            data = record.data,
            detailsStore = Ext.create('Shopware.apps.WirecardExtendOrder.store.WirecardOrderDetails'),
            payment = record.getPayment().first().get('name'),
            infoPanel = me.child('[alias=wirecard-info-panel]'),
            backendOperationPanel = me.child(['[alias=wirecard-backend-operation]']);

        detailsStore.getProxy().extraParams = {
            orderNumber: data.number,
            payment: payment
        };

        detailsStore.load({
            callback: function (records) {
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

                data.transactions.forEach(function (transaction) {
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
                        response: transaction.response,
                        backendOperations: transaction.backendOperations,
                        isFinal: transaction.isFinal
                    });
                });

                if (historyData.length) {
                    me.historyStore.loadData(historyData, false);
                }

                Object.keys(data.backendOperations).forEach(function (key) {
                    var operation = data.backendOperations[key];

                    if (!operation) {
                        return;
                    }

                    backendOperationPanel.add(Ext.create('Ext.button.Button', {
                        text: me.snippets.buttons[operation],
                        cls: 'secondary',
                        action: 'wirecard-operation-' + key,
                        handler: function () {
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
    },

    processBackendOperation(transaction, operation, amount = null) {
        var me = this;

        return Ext.Ajax.request({
            url: '{url controller="wirecardTransactions" action="processBackendOperations"}',
            params: {
                operation: operation,
                payment: me.record.getPayment().first().get('name'),
                transactionId: transaction.transactionId,
                amount: amount,
                currency: transaction.currency
            },
            success: function(response) {
                var data = Ext.decode(response.responseText);

                if (data.success) {
                    Shopware.Notification.createStickyGrowlMessage({
                        title: 'Erfolg',
                        text: 'Operation erfolgreich durchgeführt',
                        width: 400
                    });
                } else {
                    console.error(data);
                    Shopware.Notification.createStickyGrowlMessage({
                        title: 'Fehlgeschlagen',
                        text: data.message,
                        width: 400
                    });
                }
            }
        });
    }
});
// {/block}
