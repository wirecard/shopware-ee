/**
 * Shop System Plugins - Terms of Use
 *
 * The plugins offered are provided free of charge by Wirecard AG and are explicitly not part
 * of the Wirecard AG range of products and services.
 *
 * They have been tested and approved for full functionality in the standard configuration
 * (status on delivery) of the corresponding shop system. They are under General Public
 * License version 3 (GPLv3) and can be used, developed and passed on to third parties under
 * the same terms.
 *
 * However, Wirecard AG does not provide any guarantee or accept any liability for any errors
 * occurring when used in an enhanced, customized shop system configuration.
 *
 * Operation in an enhanced, customized configuration is at your own risk and requires a
 * comprehensive test phase by the user of the plugin.
 *
 * Customers use the plugins at their own risk. Wirecard AG does not guarantee their full
 * functionality neither does Wirecard AG assume liability for any disadvantages related to
 * the use of the plugins. Additionally, Wirecard AG does not guarantee the full functionality
 * for customized shop systems or installed plugins of other vendors of plugins within the same
 * shop system.
 *
 * Customers are responsible for testing the plugin's functionality before starting productive
 * operation.
 *
 * By installing the plugin into the shop system the customer agrees to these terms of use.
 * Please do not use the plugin if you do not agree to these terms of use!
 */

// {namespace name="backend/wirecard_elastic_engine/support_mail"}
// {block name="backend/wireacard_transactions/view/mail_support_form"}
Ext.define('Shopware.apps.WirecardElasticEngineTransactions.view.MailSupportForm', {
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

    /**
     * Returns fields for the support mail form.
     * @returns { *[] }
     */
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
