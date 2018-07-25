// {block name="backend/wirecard_extend_order/view/general_information_window"}
// {namespace name="backend/wirecard_elastic_engine/general_information"}
Ext.define('Shopware.apps.WirecardExtendOrder.view.GeneralInformationWindow', {
    extend: 'Enlight.app.Window',
    alias: 'widget.wirecard-extend-order-general-information-window',
    height: 600,
    title: '{s name="Title"}{/s}',
    layout: 'anchor',
    bodyPadding: 10,

    style: {
        background: '#EBEDEF'
    },

    snippets: {
        GeneralInformation: '{"{s name="Content"}{/s}"|escape|replace:"\n":"<br>"}'
    },

    initComponent: function () {
        var me = this;

        me.items = me.createItems();

        me.callParent(arguments);
    },

    createItems: function () {
        var me = this;

        return [{
            xtype: 'container',
            renderTpl: me.createGeneralInformationTemplate(),
            renderData: me.snippets
        }];
    },

    createGeneralInformationTemplate: function () {
        return Ext.create('Ext.XTemplate',
            '{literal}<tpl for=".">',
            '<p>{GeneralInformation}</p>',
            '</tpl>{/literal}'
        );
    }
});
// {/block}
