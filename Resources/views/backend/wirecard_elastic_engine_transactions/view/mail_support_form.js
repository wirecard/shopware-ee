/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

// {namespace name="backend/wirecard_elastic_engine/support_mail"}
// {block name="backend/wireacard_transactions/view/mail_support_form"}
Ext.define('Shopware.apps.WirecardElasticEngineTransactions.view.MailSupportForm', {
    extend: 'Ext.form.Panel',
    alias: 'widget.wirecardee-transactions-mail-support-form',

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

    /**
     * Returns fields for the support mail form.
     * @returns { *[] }
     */
    createItems: function() {
        return [{
            xtype: 'textfield',
            name: 'address',
            fieldLabel: '{s name="SenderAddress"}{/s}',
            vtype: 'email',
            allowBlank: false
        }, {
            xtype: 'textfield',
            name: 'replyTo',
            fieldLabel: '{s name="ReplyTo"}{/s}',
            vtype: 'email'
        }, {
            xtype: 'textarea',
            name: 'message',
            fieldLabel: '{s name="Message"}{/s}',
            height: 400,
            rows: 20,
            allowBlank: false
        }];
    }
});
// {/block}
