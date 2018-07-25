Ext.define('Shopware.apps.WirecardTransactions.controller.Main', {
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

    createComponentControl: function() {
        var me = this;

        me.control({
            'wirecardee-transactions-mail-toolbar': {
                'submitMail': me.onSubmitMail
            }
        });
    },

    onSubmitMail: function() {
        var me = this,
            form = me.getMailSupportForm().getForm();
        me.mainWindow.setLoading(true);

        if (!form.isValid()) {
            Shopware.Notification.createGrowlMessage('{s name="MailSupportTitle" namespace="backend/wirecard_elastic_engine/support_mail"}{/s}', '{s name="FormValidationError" namespace="backend/wirecard_elastic_engine/support_mail"}{/s}', me.mainWindow.title);
            me.mainWindow.setLoading(false);
            return;
        }

        Ext.Ajax.request({
            url: '{url action=submitMail}',
            params: form.getValues(),
            success: function(response) {
                var data = Ext.decode(response.responseText);
                console.log(data);
                if (data.success) {
                    Shopware.Notification.createGrowlMessage('{s name="MailSupportTitle" namespace="backend/wirecard_elastic_engine/support_mail"}{/s}', '{s name="SuccessfullySend" namespace="backend/wirecard_elastic_engine/support_mail"}{/s}', me.mainWindow.title);
                    me.mainWindow.close();
                    return;
                }

                Shopware.Notification.createGrowlMessage('{s name="MailSupportTitle" namespace="backend/wirecard_elastic_engine/support_mail"}{/s}', '{s name="SendingFailed" namespace="backend/wirecard_elastic_engine/support_mail"}{/s}', me.mainWindow.title);
                me.mainWindow.setLoading(false);
            }
        });
    }
});
