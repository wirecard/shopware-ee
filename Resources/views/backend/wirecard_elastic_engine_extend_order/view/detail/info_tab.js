/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

// {block name="backend/wirecard_elastic_engine_extend_order/view/detail/info_tab"}
// {namespace name="backend/wirecard_elastic_engine/order_info_tab"}
Ext.define('Shopware.apps.WirecardElasticEngineExtendOrder.view.detail.InfoTab', {
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
            successTitle: '{s name="BackendOperationSuccessTitle"}{/s}',
            successMessage: '{s name="BackendOperationSuccessMessage"}{/s}',
            errorTitle: '{s name="BackendOperationErrorTitle"}{/s}',
            cancelConfirmation: '{s name="BackendOperationCancelConfirmation"}{/s}'
        },
        amountDialog: {
            fieldLabel: '{s name="Amount" namespace="backend/wirecard_elastic_engine/transactions_window"}{/s}',
            submit: '{s name="AmountDialogSubmit"}{/s}',
            close: '{s name="detail/cancel" namespace="backend/order/main"}{/s}'
        },
        buttons: {
            openTransaction: '{s name="OpenTransactionTooltip" namespace="backend/wirecard_elastic_engine/transactions_window"}{/s}',
            payCapture: '{s name="PayCaptureButtonText"}{/s}',
            refund: '{s name="RefundButtonText"}{/s}',
            creditRefund: '{s name="CreditRefundButtonText"}{/s}',
            cancelRefund: '{s name="CancelRefundButtonText"}{/s}'
        },
        bankData: {
            title: '{s name="BankDataTitle"}{/s}',
            iban: '{s name="IBAN"}{/s}',
            bic: '{s name="BIC"}{/s}'
        },
        sepaMandate: {
            title: '{s name="SepaMandateTitle" namespace="frontend/wirecard_elastic_engine/sepa_direct_debit"}{/s}',
            creditorId: '{s name="CreditorID" namespace="frontend/wirecard_elastic_engine/sepa_direct_debit"}{/s}',
            dueDate: '{s name="DueDate" namespace="frontend/wirecard_elastic_engine/sepa_direct_debit"}{/s}',
            id: '{s name="MandateId" namespace="frontend/wirecard_elastic_engine/sepa_direct_debit"}{/s}',
            signatureDate: '{s name="SignatureDate" namespace="frontend/wirecard_elastic_engine/sepa_direct_debit"}{/s}'
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

    /**
     * Creates the info container within the Wirecard Tab.
     * @returns { Ext.panel.Panel }
     */
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

    /**
     * Creates the transaction table within the Wirecard Tab.
     * @returns { Ext.grid.Panel }
     */
    createTransactionsContainer: function () {
        var me = this;

        me.transactionsStore = Ext.create('Ext.data.Store', {
            storeId: 'wirecardElasticEngineTransactionsStore',
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
                'type',
                'statusMessage'
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
                {
                    header: me.snippets.transactionsTable.createdAt,
                    dataIndex: 'createdAt',
                    flex: 1,
                    renderer: function (value) {
                        return (value === Ext.undefined) ? value
                            : (Ext.util.Format.date(value) + ' ' + Ext.util.Format.date(value, 'H:i:s'));
                    }
                },
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
                        tooltip: me.snippets.buttons.openTransaction,
                        handler: function (view, rowIndex, colIndex, item, opts, record) {
                            Ext.create('Shopware.apps.WirecardElasticEngineExtendOrder.view.TransactionDetailsWindow', { record: record }).show();
                        },
                        getClass: function (value, meta, record) {
                            return record.data.statusMessage ? 'sprite-exclamation' : 'sprite-magnifier-medium';
                        }
                    }, {
                        iconCls: 'sprite-cheque--plus',
                        tooltip: me.snippets.buttons.payCapture,
                        handler: function (view, row, col, item, opts, record) {
                            me.showAmountDialog(me.snippets.buttons.payCapture, record.data, 'pay');
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
                            me.showAmountDialog(me.snippets.buttons.refund, record.data, 'refund');
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
                            me.showAmountDialog(me.snippets.buttons.creditRefund, record.data, 'credit');
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

    /**
     * Returns if a transaction has a specific backend operation. Used for hiding icons in the
     * Transactions list (see above).
     * @param transaction
     * @param operation
     * @returns { boolean|* }
     */
    hasBackendOperation: function (transaction, operation) {
        return !transaction.isFinal &&
            transaction.backendOperations &&
            transaction.backendOperations[operation] &&
            transaction.state !== 'closed' &&
            transaction.type === 'notify';
    },

    /**
     * Shows a dialog to enter an amount. Mainly shown for specific backend operations like capture or refund.
     * @param title
     * @param transaction
     * @param operation
     */
    showAmountDialog: function (title, transaction, operation) {
        var me = this;
        var win = Ext.create('Ext.window.Window', {
            title: title,
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

    /**
     * @param record
     */
    loadData: function (record) {
        var data = record.data,
            payment = record.getPayment().first().get('name');
        this.detailsStore = Ext.create('Shopware.apps.WirecardElasticEngineExtendOrder.store.OrderDetails');

        this.detailsStore.getProxy().extraParams = {
            orderNumber: data.number,
            payment: payment
        };

        this.loadStore();
    },

    /**
     * Loads the details store, creates info panels and loads the transactions store.
     */
    loadStore: function () {
        var me = this,
            infoPanel = me.child('[alias=wirecardee-info-panel]');

        if (me.child('[alias=wirecardee-transaction-history]')) {
            me.child('[alias=wirecardee-transaction-history]').disable();
        }

        this.detailsStore.load({
            callback: function (records) {
                var data = Array.isArray(records) && records.length === 1 ? records[0].getData() : false;

                infoPanel.removeAll();

                if (!data || !data.transactions || !data.transactions.length) {
                    infoPanel.add({
                        xtype: 'container',
                        html: '<p>' + me.snippets.noTransactionInfoFound + '</p>'
                    });
                    return;
                }

                data.transactions.forEach(function (transaction) {
                    if (transaction.type === 'initial-response' || transaction.type === 'initial-request') {
                        infoPanel.add(me.getInfoPanelItem(transaction));

                        if (me.record.getPayment().first().get('name') === 'wirecard_elastic_engine_poi') {
                            var poi = {
                                'bankName': transaction.response['merchant-bank-account.0.bank-name'],
                                'bic': transaction.response['merchant-bank-account.0.bic'],
                                'iban': transaction.response['merchant-bank-account.0.iban'],
                                'address': transaction.response['merchant-bank-account.0.branch-address'],
                                'city': transaction.response['merchant-bank-account.0.branch-city'],
                                'state': transaction.response['merchant-bank-account.0.branch-state']
                            };

                            infoPanel.add({
                                xtype: 'container',
                                renderTpl: me.createBankDataTemplate(),
                                renderData: poi,
                                margin: '0 0 10px'
                            });
                        }
                    }
                    if (transaction.type === 'initial-response' && transaction.paymentMethod === 'sepadirectdebit') {
                        infoPanel.add(me.getInfoPanelSepaMandateInfoItem(transaction));
                    }
                });

                me.transactionsStore.loadData(data.transactions, false);

                if (me.child('[alias=wirecardee-transaction-history]')) {
                    me.child('[alias=wirecardee-transaction-history]').enable();
                }
            }
        });
    },

    getInfoPanelItem: function (transaction) {
        var me = this;
        return {
            xtype: 'container',
            renderTpl: new Ext.XTemplate(
                '{literal}<tpl for=".">',
                '<div class="wirecardee-info-panel">',
                '<p><label class="x-form-item-label">' + me.snippets.paymentUniqueId + ':</label> {paymentUniqueId}</p>',
                '</div>',
                '</tpl>{/literal}'
            ),
            renderData: transaction,
            margin: '0 0 10px'
        };
    },

    getInfoPanelSepaMandateInfoItem: function (transaction) {
        var me = this;
        return {
            xtype: 'container',
            renderTpl: Ext.create('Ext.XTemplate',
                '{literal}<tpl for=".">',
                '<div class="wirecardee-info-panel-sepa">',
                '<h3>' + me.snippets.sepaMandate.title + '</h3>',
                '<p><label class="x-form-item-label">' + me.snippets.sepaMandate.creditorId + ':</label> {creditorId}</p>',
                '<p><label class="x-form-item-label">' + me.snippets.sepaMandate.dueDate + ':</label> {dueDate}</p>',
                '<p><label class="x-form-item-label">' + me.snippets.sepaMandate.id + ':</label> {mandateId}</p>',
                '<p><label class="x-form-item-label">' + me.snippets.sepaMandate.signatureDate + ':</label> {mandateSignedDate}</p>',
                '</div>',
                '</tpl>{/literal}'
            ),
            renderData: {
                creditorId: transaction.response['creditor-id'],
                dueDate: transaction.response['due-date'],
                mandateId: transaction.response['mandate.0.mandate-id'],
                mandateSignedDate: transaction.response['mandate.0.signed-date']
            }
        };
    },

    /**
     * Processes a single backend operation.
     * @param transaction
     * @param operation
     * @param amount
     * @returns { * }
     */
    processBackendOperation: function (transaction, operation, amount) {
        var me = this;
        return Ext.Ajax.request({
            url: '{url controller="wirecardElasticEngineTransactions" action="processBackendOperations"}',
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

    /**
     * Bank data for payment on invoice
     * @return { Ext.XTemplate }
     */
    createBankDataTemplate: function() {
        var me = this;
        return new Ext.XTemplate(
            '{literal}<tpl for=".">',
            '<p><label class="x-form-item-label">' + me.snippets.bankData.title + ':</label></p>',
            '<tpl if="bankName">',
            '<p>{bankName}</p>',
            '</tpl>',
            '<p>' + me.snippets.bankData.iban + ': {iban}</p>',
            '<tpl if="bic">',
            '<p>' + me.snippets.bankData.bic + ': {bic}</p>',
            '</tpl>',
            '<tpl if="address">',
            '<p> {address} <br>',
            '{city} {state}',
            '</p>',
            '</tpl>',
            '</tpl>{/literal}'
        );
    }
});
// {/block}
