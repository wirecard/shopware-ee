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