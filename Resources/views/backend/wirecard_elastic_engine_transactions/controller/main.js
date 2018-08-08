/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
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
