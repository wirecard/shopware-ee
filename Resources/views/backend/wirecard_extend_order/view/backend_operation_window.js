// {block name="backend/wirecard_extend_order/view/backend_operation_window"}
Ext.define('Shopware.apps.WirecardExtendOrder.view.BackendOperationWindow', {
    extend: 'Enlight.app.Window',
    alias: 'widget.wirecard-extend-order-backend-operation-window',
    height: 600,
    title: 'BackendOperationWindow',
    layout: 'anchor',
    bodyPadding: 10,

    style: {
        background: '#EBEDEF'
    },

    snippets: {
        amountLabel: 'Amount',
        refundTitle: 'Refund',
        captureTitle: 'Catpure',
        operationHistory: 'Operation History',
        processOperation: 'process Operation',
        error: {
            backendOperationTitle: '{s name="BackendOperationErrorTitle" namespace="backend/wirecard_elastic_engine/order_info_tab"}{/s}',
            unknownBackendOperation: '{s name="UnknownBackendOperationError" namespace="backend/wirecard_elastic_engine/order_info_tab"}{/s}',
            unknownPaymethod: '{s name="UnknownPaymethodError" namespace="backend/wirecard_elastic_engine/order_info_tab"}{/s}'
        },
        success: {
            backendOperationTitle: 'Backendoperation successful',
            msg: ': the operation was successul'
        }
    },

    hasForm: false,
    purchased: 0,
    maxValue: 0,
    openValue: 0,
    currency: '',
    operationHistoryContainer: null,
    amountFormContainer: null,

    initComponent: function() {
        var me = this;

        me.items = me.createItems();

        me.callParent(arguments);
    },

    createItems: function() {
        var me = this,
            items = [];

        items.push(me.createOperationHistory());

        me.amountFormContainer = me.createAmountFormContainer();
        if (me.amountFormContainer) {
            items.push(me.amountFormContainer);
        }
        items.push(me.createProcessButton());

        return items;
    },

    createOperationHistory: function() {
        var me = this,
            items = [],
            purchaseStr = '';

        me.data.transactionHistory.forEach(function(transaction) {
            var transactionType = transaction.transactionType,
                amount = transaction.amount;

            me.currency = transaction.currency;

            if (transactionType === 'authorization') {
                me.openValue = amount;
            } else if (transactionType === 'purchase') {
                me.purchased = amount;
            } else if (transactionType === 'capture') {
                me.openValue -= amount;
            } else if (transactionType === 'refund') {
                me.purchased -= amount;
            }

            items.push({
                xtype: 'container',
                html: '<p><label>' + transactionType + ': </label>' + Ext.util.Format.number(amount) + ' ' + me.currency + '</p>'
            });
        });

        purchaseStr = Ext.util.Format.number(me.purchased);
        me.purchased = parseFloat(
            purchaseStr.replace(Ext.util.Format.thousandSeparator, '')
                .replace(Ext.util.Format.decimalSeparator, '.')
        );

        items.push({
            xtype: 'container',
            html: '<hr>'
        });
        if (me.purchased) {
            items.push({
                xtype: 'container',
                html: '<p><label>purchased: </label>' + Ext.util.Format.number(me.purchased) + ' ' + me.currency + '</p>'
            });
        }
        if (me.openValue) {
            items.push({
                xtype: 'container',
                html: '<p><label>open: </label>' + Ext.util.Format.number(me.openValue) + ' ' + me.currency + '</p>'
            });
        }

        me.operationHistory = Ext.create('Ext.panel.Panel', {
            title: me.snippets.operationHistory,
            alias: 'wirecard-backend-operation-history',
            bodyPadding: 10,
            flex: 1,
            paddingRight: 5,
            items: items
        });

        return me.operationHistory;
    },

    createAmountFormContainer: function() {
        var me = this;

        if (me.operation === 'Refund') {
            return me.createRefundForm();
        }

        if (me.operation === 'Capture') {
            return me.createCaptureForm();
        }

        return false;
    },

    createRefundForm: function() {
        var me = this;

        me.hasForm = true;
        me.maxValue = me.purchased;

        return Ext.create('Ext.form.Panel', {
            title: me.snippets.refundTitle,
            bodyPadding: 10,
            padding: '10 0',
            items: [{
                xtype: 'textfield',
                name: 'amount',
                fieldLabel: me.snippets.amountLabel,
                value: Ext.util.Format.number(me.maxValue),
                validator: Ext.bind(me.onAmountChange, me)
            }]
        });
    },

    createCaptureForm: function() {
        var me = this;

        me.hasForm = true;
        me.maxValue = me.openValue;

        return Ext.create('Ext.form.Panel', {
            title: me.snippets.captureTitle,
            bodyPadding: 10,
            padding: '10 0',
            items: [{
                xtype: 'textfield',
                name: 'amount',
                fieldLabel: me.snippets.amountLabel,
                value: Ext.util.Format.number(me.maxValue),
                validator: Ext.bind(me.onAmountChange, me)
            }]
        });
    },

    onAmountChange: function(value) {
        var me = this,
            amount = parseFloat(
                value.replace(Ext.util.Format.thousandSeparator, '')
                    .replace(Ext.util.Format.decimalSeparator, '.')
            );

        if (value === '') {
            return true;
        }

        if (isNaN(amount)) {
            return false;
        }

        if (amount > me.maxValue) {
            return false;
        }

        return true;
    },

    createProcessButton: function() {
        var me = this,
            button = Ext.create('Ext.button.Button', {
                text: 'process',
                cls: 'primary',
                handler: function() {
                    me.processBackendOperation(me.record, me.operation);
                }
            });

        return Ext.create('Ext.panel.Panel', {
            title: me.snippets.processOperation,
            alias: 'wirecard-backend-process-operation',
            bodyPadding: 10,
            flex: 1,
            paddingRight: 5,
            items: [button]
        });
    },

    processBackendOperation: function(record, operation) {
        var me = this,
            url = '{url controller="wirecardTransactions" action="processBackendOperations"}',
            payMethod = me.record.getPayment().first().get('name'),
            amount = me.hasForm ? me.amountFormContainer.getForm().findField('amount').getValue() : null,
            params = {};

        me.mask('ProcessOperation');

        if (!operation) {
            Shopware.Notification.createStickyGrowlMessage({
                title: me.snippets.error.backendOperationTitle,
                text: me.snippets.error.unknownBackendOperation,
                width: 440,
                log: false
            });
            return;
        }

        params = {
            operation: operation,
            orderNumber: me.record.data.number,
            payMethod: payMethod
        };

        if (amount) {
            if (!me.onAmountChange(amount)) {
                Shopware.Notification.createStickyGrowlMessage({
                    title: me.snippets.error.backendOperationTitle,
                    text: 'AmountExceeded',
                    width: 440,
                    log: false
                });
                me.amountFormContainer.getForm().findField('amount').setValue(Ext.util.Format.number(me.maxValue));
                return;
            }

            amount = parseFloat(
                amount.replace(Ext.util.Format.thousandSeparator, '')
                    .replace(Ext.util.Format.decimalSeparator, '.')
            );

            params.amount = amount;
            params.currency = 'EUR';
        }

        Ext.Ajax.request({
            url: url,
            params: params,
            success: function (response) {
                var data = Ext.decode(response.responseText),
                    msg = '';
                me.unmask();
                if (!data.success) {
                    if (data.msg) {
                        msg = me.snippets.error[data.msg];
                    } else {
                        msg = me.snippets.error.unkownError;
                    }

                    Shopware.Notification.createStickyGrowlMessage({
                        title: me.snippets.error.backendOperationTitle,
                        text: msg,
                        width: 440,
                        log: false
                    });
                } else {
                    Shopware.Notification.createStickyGrowlMessage({
                        title: me.snippets.success.backendOperationTitle,
                        text: me.operation + me.snippets.success.msg,
                        width: 440,
                        log: false
                    });
                    me.close();
                }
            }
        });
    }

});
// {/block}
