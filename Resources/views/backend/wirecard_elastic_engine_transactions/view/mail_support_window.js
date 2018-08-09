/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

// {namespace name="backend/wirecard_elastic_engine/support_mail"}
// {block name="backend/wireacard_transactions/view/mail_support_window"}
Ext.define('Shopware.apps.WirecardElasticEngineTransactions.view.MailSupportWindow', {
    extend: 'Enlight.app.Window',
    alias: 'widget.wirecardee-mail-support-window',
    layout: 'anchor',
    title: '{s name="Title"}{/s}',

    toolbar: null,

    initComponent: function() {
        var me = this;
        me.dockedItems = [me.createToolbar()];
        me.items = [me.createForm()];
        me.callParent(arguments);
    },

    /**
     * Creates the toolbar for the mail support window.
     * @returns { null|* }
     */
    createToolbar: function() {
        var me = this;
        me.toolbar = Ext.create('Shopware.apps.WirecardElasticEngineTransactions.view.MailToolbar');
        return me.toolbar;
    },

    /**
     * Creates the form for the mail support window.
     * @returns { Shopware.apps.WirecardElasticEngineTransactions.view.MailSupportForm }
     */
    createForm: function() {
        return Ext.create('Shopware.apps.WirecardElasticEngineTransactions.view.MailSupportForm');
    }
});
// {/block}
