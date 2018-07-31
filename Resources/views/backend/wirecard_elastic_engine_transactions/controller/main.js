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

Ext.define('Shopware.apps.WirecardElasticEngineTransactions.controller.Main', {
    extend: 'Enlight.app.Controller',

    refs: [
        { ref: 'mailSupportForm', selector: 'wirecardee-transactions-mail-support-form' }
    ],

    init: function () {
        var me = this;

        if (me.subApplication.action && me.subApplication.action.toLowerCase() === 'mailsupport') {
            me.mainWindow = me.getView('MailSupportWindow').create({}).show();
        } else {
            me.mainWindow = me.getView('Window').create({}).show();
        }

        me.createComponentControl();
    },

    /**
     * Creates the toolbar for the mail support window.
     */
    createComponentControl: function () {
        var me = this;

        me.control({
            'wirecardee-transactions-mail-toolbar': {
                'submitMail': me.onSubmitMail
            }
        });
    },

    /**
     * Sends the support mail.
     */
    onSubmitMail: function () {
        var me = this,
            form = me.getMailSupportForm().getForm();

        me.mainWindow.setLoading(true);

        if (!form.isValid()) {
            Shopware.Notification.createGrowlMessage(
                '{s name="Title" namespace="backend/wirecard_elastic_engine/support_mail"}{/s}',
                '{s name="FormValidationError" namespace="backend/wirecard_elastic_engine/support_mail"}{/s}',
                me.mainWindow.title
            );
            me.mainWindow.setLoading(false);
            return;
        }

        Ext.Ajax.request({
            url: '{url action=submitMail}',
            params: form.getValues(),
            success: function (response) {
                var data = Ext.decode(response.responseText);
                if (data.success) {
                    Shopware.Notification.createGrowlMessage(
                        '{s name="Title" namespace="backend/wirecard_elastic_engine/support_mail"}{/s}',
                        '{s name="SuccessfullySend" namespace="backend/wirecard_elastic_engine/support_mail"}{/s}',
                        me.mainWindow.title
                    );
                    me.mainWindow.close();
                    return;
                }

                Shopware.Notification.createGrowlMessage(
                    '{s name="Title" namespace="backend/wirecard_elastic_engine/support_mail"}{/s}',
                    '{s name="SendingFailed" namespace="backend/wirecard_elastic_engine/support_mail"}{/s}',
                    me.mainWindow.title
                );
                me.mainWindow.setLoading(false);
            }
        });
    }
});
