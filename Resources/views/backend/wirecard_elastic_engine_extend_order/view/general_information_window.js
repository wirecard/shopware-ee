/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

// {block name="backend/wirecard_elastic_engine_extend_order/view/general_information_window"}
// {namespace name="backend/wirecard_elastic_engine/general_information"}
Ext.define('Shopware.apps.WirecardElasticEngineExtendOrder.view.GeneralInformationWindow', {
    extend: 'Enlight.app.Window',
    alias: 'widget.wirecardee-extend-order-general-information-window',
    height: 600,
    title: '{s name="Title"}{/s}',
    layout: 'anchor',
    bodyPadding: 10,
    autoScroll: true,

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

    /**
     * Returns an array containing the general information template.
     * @returns { *[] }
     */
    createItems: function () {
        var me = this;

        return [{
            xtype: 'container',
            renderTpl: Ext.create('Ext.XTemplate',
                '{literal}<tpl for=".">',
                '<p>{GeneralInformation}</p>',
                '</tpl>{/literal}'
            ),
            renderData: me.snippets
        }];
    }
});
// {/block}
