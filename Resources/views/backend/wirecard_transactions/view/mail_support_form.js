// {namespace name="backend/wirecard_elastic_engine/support_mail"}
// {block name="backend/wireacard_transactions/view/mail_support_form"}
Ext.define('Shopware.apps.WirecardTransactions.view.MailSupportForm', {
    extend: 'Ext.form.Panel',
    alias: 'widget.wirecardee-transactions-mail-support-form',
    title: '{s name="FormTitle"}{/s}',

    anchor: '100%',
    border: false,
    bodyPadding: 10,

    style: {
        background: '#EBEDEF'
    },

    fieldDefaults: {
        anchor: '100%',
        labelWidth: 180
    },

    initComponent: function() {
        var me = this;
        me.items = me.createItems();
        me.callParent(arguments);
    },

    createItems: function() {
        return [{
            xtype: 'textfield',
            name: 'address',
            fieldLabel: '{s name="SenderAddress"}{/s}',
            helpText: '{s name="SenderAddressDescription"}{/s}',
            vtype: 'email',
            allowBlank: false
        }, {
            xtype: 'textfield',
            name: 'replyTo',
            fieldLabel: '{s name="ReplyTo"}{/s}',
            helpText: '{s name="ReplyToDescription"}{/s}',
            vtype: 'email'
        }, {
            xtype: 'textarea',
            name: 'message',
            fieldLabel: '{s name="Message"}{/s}',
            helpText: '{s name="MessageDescription"}{/s}',
            height: 400,
            rows: 20,
            allowBlank: false
        }];
    }
});
// {/block}
