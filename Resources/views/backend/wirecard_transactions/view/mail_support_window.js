// {namespace name="backend/wirecard_elastic_engine/support_mail"}
// {block name="backend/wireacard_transactions/view/mail_support_window"}
Ext.define('Shopware.apps.WirecardTransactions.view.MailSupportWindow', {
    extend: 'Enlight.app.Window',
    alias: 'widget.wirecardee-mail-support-window',
    layout: 'anchor',
    title: '{s name="MailSupportTitle"}{/s}',

    toolbar: null,

    initComponent: function() {
        var me = this;

        me.dockedItems = [me.createToolbar()];

        me.items = [me.createItems()];

        me.callParent(arguments);
    },

    createToolbar: function() {
        var me = this;

        me.toolbar = Ext.create('Shopware.apps.WirecardTransactions.view.MailToolbar');

        return me.toolbar;
    },

    createItems: function() {
        var form = Ext.create('Shopware.apps.WirecardTransactions.view.MailSupportForm');

        return form;
    }
});
// {/block}
