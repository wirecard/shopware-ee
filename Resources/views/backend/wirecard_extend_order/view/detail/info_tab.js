// {block name="backend/wirecard_extend_order/view/detail/info_tab"}
// {namespace name="backend/wirecard_elastic_engine/order_info_tab"}
Ext.define('Shopware.apps.WirecardExtendOrder.view.detail.InfoTab', {
    extend: 'Ext.container.Container',
    padding: 10,
    title: '{s name="TabTitle"}{/s}',
    autoScroll: true,

    snippets: {
        infoTitle: '{s name="InfoTitle"}{/s}',
        paymentUniqueId: '{s name="PaymentUniqueId" namespace="backend/wirecard_elastic_engine/transactions_window"}{/s}',
        noTransactionInfoFound: '{s name="NoTransactionFound"}{/s}',
        transactionsTitle: '{s name="TransactionsTitle"}{/s}',
        transactionsTable: {
            createdAt: '{s name="CreatedAt" namespace="backend/wirecard_elastic_engine/transactions_window"}{/s}',
            type: '{s name="Type" namespace="backend/wirecard_elastic_engine/transactions_window"}{/s}',
            transactionId: '{s name="TransactionId" namespace="backend/wirecard_elastic_engine/transactions_window"}{/s}',
            transactionType: '{s name="TransactionType" namespace="backend/wirecard_elastic_engine/transactions_window"}{/s}',
            amount: '{s name="Amount" namespace="backend/wirecard_elastic_engine/transactions_window"}{/s}',
            currency: '{s name="Currency" namespace="backend/wirecard_elastic_engine/transactions_window"}{/s}'
        },
        operations: {
            title: '{s name="BackendOperation"}{/s}',
            successTitle: '{s name="BackendOperationSuccessTitle"}{/s}',
            successMessage: '{s name="BackendOperationSuccessMessage"}{/s}',
            errorTitle: '{s name="BackendOperationErrorTitle"}{/s}',
            cancelConfirmation: '{s name="BackendOperationCancelConfirmation"}{/s}'
        },
        amountDialog: {
            title: '{s name="AmountDialogTitle"}{/s}',
            fieldLabel: '{s name="AmountDialogFieldLabel"}{/s}',
            submit: '{s name="AmountDialogSubmit"}{/s}',
            close: '{s name="AmountDialogClose"}{/s}'
        },
        buttons: {
            openTransaction: '{s name="OpenTransactionButtonText"}{/s}',
            payCapture: '{s name="PayCaptureButtonText"}{/s}',
            refund: '{s name="RefundButtonText"}{/s}',
            creditRefund: '{s name="CreditRefundButtonText"}{/s}',
            cancelRefund: '{s name="CancelRefundButtonText"}{/s}'
        }
    },

    detailsStore: null,
    transactionsStore: null,

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
            alias: 'wirecardee-info-panel',
            bodyPadding: 10,
            margin: '10 0',
            flex: 1,
            paddingRight: 5,
            items: []
        });
    },

    createTransactionsContainer: function () {
        var me = this;

        me.transactionsStore = Ext.create('Ext.data.Store', {
            storeId: 'transactionsStore',
            fields: [
                'orderNumber',
                'paymentUniqueId',
                'paymentMethod',
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
                'request',
                'backendOperations',
                'isFinal',
                'state',
                'type'
            ],
            data: []
        });

        return Ext.create('Ext.grid.Panel', {
            title: me.snippets.transactionsTitle,
            alias: 'wirecardee-transaction-history',
            store: me.transactionsStore,
            border: 1,
            viewConfig: {
                enableTextSelection: true
            },
            columns: [
                { header: me.snippets.transactionsTable.createdAt, dataIndex: 'createdAt', flex: 1 },
                { header: me.snippets.transactionsTable.type, dataIndex: 'type', flex: 1 },
                { header: me.snippets.transactionsTable.transactionId, dataIndex: 'transactionId', flex: 1 },
                { header: me.snippets.transactionsTable.transactionType, dataIndex: 'transactionType', flex: 1 },
                {
                    header: me.snippets.transactionsTable.amount,
                    dataIndex: 'amount',
                    flex: 1,
                    renderer: Ext.util.Format.numberRenderer('0.00')
                },
                { header: me.snippets.transactionsTable.currency, dataIndex: 'currency', flex: 1 },
                {
                    xtype: 'actioncolumn',
                    width: 150,
                    items: [{
                        iconCls: 'sprite-magnifier-medium',
                        tooltip: me.snippets.buttons.openTransaction,

                        handler: function (view, rowIndex, colIndex, item, opts, record) {
                            var detailsWindow = Ext.create('Shopware.apps.WirecardExtendOrder.view.TransactionDetailsWindow', { record: record });
                            detailsWindow.show();
                        }
                    }, {
                        iconCls: 'sprite-cheque--plus',
                        tooltip: me.snippets.buttons.payCapture,
                        handler: function (view, row, col, item, opts, record) {
                            me.showAmountDialog(record.data, 'pay');
                        },
                        getClass: function (value, meta, record) {
                            var transaction = record.data;
                            if (!me.hasBackendOperation(transaction, 'pay')) {
                                return 'x-hide-display';
                            }
                        }
                    }, {
                        iconCls: 'sprite-arrow-circle-315',
                        tooltip: me.snippets.buttons.refund,
                        handler: function (view, row, col, item, opts, record) {
                            me.showAmountDialog(record.data, 'refund');
                        },
                        getClass: function (value, meta, record) {
                            var transaction = record.data;
                            if (!me.hasBackendOperation(transaction, 'refund')) {
                                return 'x-hide-display';
                            }
                        }
                    }, {
                        iconCls: 'sprite-arrow-circle-315',
                        tooltip: me.snippets.buttons.creditRefund,
                        handler: function (view, row, col, item, opts, record) {
                            me.showAmountDialog(record.data, 'credit');
                        },
                        getClass: function (value, meta, record) {
                            var transaction = record.data;
                            if (!me.hasBackendOperation(transaction, 'credit')) {
                                return 'x-hide-display';
                            }
                        }
                    }, {
                        iconCls: 'sprite-cross-circle',
                        tooltip: me.snippets.buttons.cancelRefund,
                        handler: function (view, row, col, item, opts, record) {
                            Ext.MessageBox.confirm(me.snippets.buttons.cancelRefund, me.snippets.operations.cancelConfirmation, function (choice) {
                                if (choice === 'no') {
                                    return false;
                                }
                                if (me.child('[alias=wirecardee-transaction-history]')) {
                                    me.child('[alias=wirecardee-transaction-history]').disable();
                                }
                                me.processBackendOperation(record.data, 'cancel');
                            });
                        },
                        getClass: function (value, meta, record) {
                            var transaction = record.data;
                            if (!me.hasBackendOperation(transaction, 'cancel')) {
                                return 'x-hide-display';
                            }
                        }
                    }]
                }
            ],
            bodyPadding: 0,
            margin: '10 0',
            width: '100%'
        });
    },

    hasBackendOperation: function (transaction, operation) {
        return !transaction.isFinal &&
            transaction.backendOperations &&
            transaction.backendOperations[operation] &&
            transaction.state !== 'closed' &&
            transaction.type === 'notify';
    },

    showAmountDialog: function (transaction, operation) {
        var me = this;
        var win = Ext.create('Ext.window.Window', {
            title: me.snippets.amountDialog.title,
            id: 'wirecardee-transaction-amount-window',
            layout: 'fit',
            width: 300,
            height: 100,
            items: {
                id: 'wirecardee-transaction-amount',
                xtype: 'numberfield',
                fieldLabel: me.snippets.amountDialog.fieldLabel,
                value: transaction.amount
            },
            buttons: [{
                text: me.snippets.amountDialog.submit,
                handler: function () {
                    if (me.child('[alias=wirecardee-transaction-history]')) {
                        me.child('[alias=wirecardee-transaction-history]').disable();
                    }
                    win.mask();
                    me.processBackendOperation(transaction, operation, Ext.getCmp('wirecardee-transaction-amount').getValue());
                }
            }, {
                text: me.snippets.amountDialog.close,
                handler: function () {
                    win.close();
                }
            }]
        }).show();
    },

    loadData: function (record) {
        var data = record.data,
            payment = record.getPayment().first().get('name');
        this.detailsStore = Ext.create('Shopware.apps.WirecardExtendOrder.store.OrderDetails');

        this.detailsStore.getProxy().extraParams = {
            orderNumber: data.number,
            payment: payment
        };

        this.loadStore();
    },

    loadStore: function () {
        var me = this,
            infoPanel = me.child('[alias=wirecardee-info-panel]');

        if (me.child('[alias=wirecardee-transaction-history]')) {
            me.child('[alias=wirecardee-transaction-history]').disable();
        }

        this.detailsStore.load({
            callback: function (records) {
                var data = Array.isArray(records) && records.length === 1 ? records[0].getData() : false;
                window.DATA = data;

                if (!data || !data.transactions || !data.transactions.length) {
                    infoPanel.add({
                        xtype: 'container',
                        html: '<p>' + me.snippets.noTransactionInfoFound + '</p>'
                    });
                    return;
                }

                data.transactions.forEach(function (transaction) {
                    if (transaction.type === 'initial-response' || transaction.type === 'initial-request') {
                        infoPanel.add({
                            xtype: 'container',
                            renderTpl: me.createInfoPanelTemplate(),
                            renderData: transaction
                        });
                    }
                    if (transaction.type === 'initial-response' && transaction.paymentMethod === 'sepadirectdebit') {
                        infoPanel.add({
                            xtype: 'container',
                            renderTpl: Ext.create('Ext.XTemplate',
                                '{literal}<tpl for=".">',
                                '<div class="wirecardee-info-panel-sepa">',
                                '<h3>SEPA Direct Debit Mandate Data</h3>',
                                '<p><label class="x-form-item-label">Creditor Id:</label> {creditorId}</p>',
                                '<p><label class="x-form-item-label">Due Date:</label> {dueDate}</p>',
                                '<p><label class="x-form-item-label">Mandate Id:</label> {mandateId}</p>',
                                '<p><label class="x-form-item-label">Mandate Signature Date:</label> {mandateSignedDate}</p>',
                                '</div>',
                                '</tpl>{/literal}'
                            ),
                            renderData: {
                                creditorId: transaction.response['creditor-id'],
                                dueDate: transaction.response['due-date'],
                                mandateId: transaction.response['mandate.0.mandate-id'],
                                mandateSignedDate: transaction.response['mandate.0.signed-date']
                            }
                        });
                    }
                });

                me.transactionsStore.loadData(data.transactions, false);

                if (me.child('[alias=wirecardee-transaction-history]')) {
                    me.child('[alias=wirecardee-transaction-history]').enable();
                }
            }
        });
    },

    processBackendOperation(transaction, operation, amount) {
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
            success: function (response) {
                var data = Ext.decode(response.responseText);
                var message = {
                    title: me.snippets.operations.successTitle,
                    text: me.snippets.operations.successMessage,
                    width: 400
                };

                if (!data.success) {
                    message.title = me.snippets.operations.errorTitle;
                    message.text = data.message;
                }
                Shopware.Notification.createStickyGrowlMessage(message);
                me.loadStore();
                if (Ext.getCmp('wirecardee-transaction-amount-window')) {
                    Ext.getCmp('wirecardee-transaction-amount-window').close();
                }
            }
        });
    },

    createInfoPanelTemplate: function () {
        var me = this;
        return new Ext.XTemplate(
            '{literal}<tpl for=".">',
            '<div class="wirecardee-info-panel">',
            '<p><label class="x-form-item-label">' + me.snippets.paymentUniqueId + ':</label> {paymentUniqueId}</p>',
            '</div>',
            '</tpl>{/literal}'
        );
    }
});
// {/block}
