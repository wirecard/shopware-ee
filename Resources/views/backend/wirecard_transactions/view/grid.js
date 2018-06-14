Ext.define('Shopware.apps.WirecardTransactions.view.Grid', {
    extend: 'Shopware.grid.Panel',
    alias:  'widget.order-listing-grid',
    region: 'center',

    configure: function() {
        var me = this;

        return {
            columns: me.getColumns(),
            rowEditing: false,
            deleteButton: false,
            deleteColumn: false,
            editButton: false,
            editColumn: false,
            addButton: false

        }
    },

    /**
     * @returns { Object }
     */
    getColumns: function () {
        var me = this;

        return {
            status: {
                header: 'Order State',
                draggable: false,
                renderer: me.paymentStatusRenderer
            },
            paymentId: {
                header: 'Payment Method',
                draggable: false,
                renderer: me.paymentMethodRenderer
            },
            number: {
                header: 'Ordernumber',
                draggable: false
            },
            transactionId: {
                header: 'Transaction Id',
                draggable: false
            },
            invoiceAmount: {
                header: 'Amount',
                draggable: false
            },
            orderTime: {
                header: 'Ordertime',
                renderer: me.dateColumnRenderer,
                draggable: false
            }
        }
    },

    /**
     * @returns { Array }
     */
    createActionColumnItems: function () {
        var me = this,
            items = me.callParent(arguments);
        
        items.push({
            iconCls: 'sprite-shopping-basket',
            tooltip: 'Open order details',

            handler: function (view, rowIndex, colIndex, item, opts, record) {
                Shopware.app.Application.addSubApplication({
                    name: 'Shopware.apps.Order',
                    action: 'detail',
                    params: {
                        orderId: record.get('id')
                    }
                });
            }
        });

        return items;
   },
    
    /**
     * @param { String } value
     * @param { Object } metaData
     * @param { Ext.data.Model } record
     * @return { String }
     */
    paymentMethodRenderer: function(value, metaData, record) {
        var status = record.getPayment().first();

        if (status instanceof Ext.data.Model) {
            return status.get('description');
        }

        return value;
    },

    /**
     * @param { String } value
     * @param { Object } metaData
     * @param { Ext.data.Model } record
     * @return { String }
     */
    paymentStatusRenderer: function(value, metaData, record) {
        var status = record.getPaymentStatus().first();

        if (status instanceof Ext.data.Model) {
            return status.get('description');
        }

        return value;
    },

    /**
     * @param { String } value
     * @returns { String }
     */
    dateColumnRenderer: function (value) {
        if (value === Ext.undefined) {
            return value;
        }

        return Ext.util.Format.date(value, 'm.d.Y H:i');
    }

});

