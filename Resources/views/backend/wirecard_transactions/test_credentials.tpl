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
                url: document.location.pathname + 'wirecardTransactions/testCredentials',
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
