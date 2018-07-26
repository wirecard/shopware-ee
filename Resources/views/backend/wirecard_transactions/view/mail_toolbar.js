// {block name="backend/wireacard_transactions/view/mail_toolbar"}
Ext.define('Shopware.apps.WirecardTransactions.view.MailToolbar', {
    extend: 'Ext.toolbar.Toolbar',
    alias: 'widget.wirecardee-transactions-mail-toolbar',

    ui: 'shopware-ui',

    padding: '10 0 5',
    width: '100%',
    dock: 'bottom',

    initComponent: function() {
        var me = this;
        me.items = me.createItems();
        me.registerEvents();
        me.callParent(arguments);
    },

    registerEvents: function() {
        var me = this;
        me.addEvents('submitMail');
    },

    createItems: function() {
        var me = this;
        return [
            '->', // aligns the button to the right
            me.createSubmitButton()
        ];
    },

    createSubmitButton: function() {
        var me = this;
        return Ext.create('Shopware.apps.Base.view.element.Button', {
            text: 'Submit',
            cls: 'primary',
            handler: Ext.bind(me.onSubmitButtonClick, me)
        });
    },

    onSubmitButtonClick: function() {
        var me = this;
        me.fireEvent('submitMail');
    }
});
// {/block}
