{**
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
 *}

{block name="backend/base/header/javascript"}
    {$smarty.block.parent}
    <script type="text/javascript">
        // This function is called in config.xml on "Test credentials" button click and makes a backend ajax request to
        // test the current plugin configuration and payment credentials.
        var wirecardeeTestPaymentCredentials = function (button, method, title) {
            var formFields = button.up('panel').query('[isFormField]');
            var values = {
                method: method
            };

            Ext.Array.each(formFields, function (el, i) {
                values[el.elementName] = el.getSubmitValue();
            });

            Ext.Ajax.request({
                url: document.location.pathname + 'wirecardElasticEngineTransactions/testCredentials',
                params: values,
                success: function (response) {
                    var data = Ext.decode(response.responseText);
                    var text = '';
                    if (data.status === 'success') {
                        text += '<span style="color:green;font-weight:bold;">';
                        text += '{s name="TestCredentialsSuccessful" namespace="backend/wirecard_elastic_engine/common"}{/s}';
                        text += '</span>';
                    } else {
                        text += '<span style="color:red;font-weight:bold;">';
                        text += '{s name="TestCredentialsFailed" namespace="backend/wirecard_elastic_engine/common"}{/s}';
                        text += '</span>';
                        if (data.msg) {
                            text += '<br>' + data.msg;
                        }
                    }
                    Shopware.Notification.createStickyGrowlMessage({
                        title: title,
                        text: text,
                        width: 440,
                        log: false
                    });
                }
            })
        };
    </script>
{/block}
