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

{extends file="parent:frontend/checkout/confirm.tpl"}

{block name="frontend_index_header"}
    {include file="frontend/wirecard_elastic_engine_payment/header.tpl"}
{/block}

{block name="frontend_index_content"}
    <div class="wirecardee-credit-card" style="padding-top: 50px;">
        <div class="wirecardee-credit-card-error-message" style="display: none; margin-bottom: 20px;">
            {include file='frontend/_includes/messages.tpl' type='error' content="error"}
        </div>
        {if $threeDSecure}
            <div class="content content--checkout confirm--content">
                <h2>{s name="CreditCard3DSRedirectTitle" namespace="frontend/wirecard_elastic_engine/checkout"}{/s}</h2>
                <form id="wirecardee-credit-card--redirect-form" method="{$method}" action="{$url}">
                    {foreach from=$formFields item='field' key='key'}
                        <input type="hidden" name="{$key}" value="{$field}"/>
                    {/foreach}
                    <button class="btn is--primary is--large right is--icon-right" type="submit">
                        {s name="CreditCardSendButtonLabel" namespace="frontend/wirecard_elastic_engine/checkout"}Send{/s}
                        {* loading spinner icon *}
                        <i class="js--loading"></i>
                    </button>
                </form>
                <script type="text/javascript">
                    document.getElementById('wirecardee-credit-card--redirect-form').submit();
                </script>
            </div>
        {else}
            <div class="content content--checkout confirm--content">
                <form id="wirecardee-credit-card--form" method="post" action="{$url}">
                    <div id="wirecardee-credit-card--iframe-div" style="width: 100%; height: 550px;"></div>
                    <button id="wirecardee-credit-card--form-submit"
                            class="btn is--primary is--large right is--icon-right" type="submit">
                        {s name="CreditCardSendButtonLabel" namespace="frontend/wirecard_elastic_engine/checkout"}Send{/s}
                        <i class="icon--arrow-right"></i>
                    </button>
                </form>
            </div>
        {/if}
    </div>
{/block}

{block name="frontend_index_javascript_async_ready"}
    {$smarty.block.parent}
    {if not $threeDSecure}
        <script type="text/javascript">
            document.asyncReady(function () {
                var $ = jQuery;
                var $formSubmit = $('#wirecardee-credit-card--form-submit');
                var $formSubmitIcon = $formSubmit.find('i');

                var handleFailedFormResponse = function (response) {
                    $formSubmit.prop('disabled', false);
                    $formSubmitIcon.attr('class', 'icon--arrow-right');
                    if (response.transaction_state === 'failed') {
                        $('.wirecardee-credit-card-error-message .alert--content').html(response.status_description_1);
                        $('.wirecardee-credit-card-error-message').show();
                    }
                };

                WirecardPaymentPage.seamlessRenderForm({
                    requestData: {$wirecardRequestData},
                    wrappingDivId: 'wirecardee-credit-card--iframe-div',
                    onSuccess: handleFailedFormResponse,
                    onError: handleFailedFormResponse
                });

                var setParentTransactionId = function (response) {
                    var form = document.getElementById('wirecardee-credit-card--form'),
                        formField = null;

                    for (var key in response) {
                        if (!response.hasOwnProperty(key)) {
                            continue;
                        }
                        formField = document.createElement('div');
                        formField.innerHTML = '<input type="hidden" name="' + key + '" value="' + response[key] + '">';
                        form.appendChild(formField);
                    }

                    formField = document.createElement('div');
                    formField.innerHTML = '<input id="jsresponse" type="hidden" name="jsresponse" value="true">';
                    form.appendChild(formField);
                    form.submit();
                };

                document.getElementById('wirecardee-credit-card--form').addEventListener('submit', function (event) {
                    // We check if the response fields are already set
                    if (!document.getElementById('jsresponse')) {
                        // If not, we will prevent the submission of the form and submit the credit card UI form instead
                        event.preventDefault();
                        // disable submit button and replace icon with loading spinner
                        $formSubmit.prop('disabled', true);
                        $formSubmitIcon.attr('class', 'js--loading');
                        WirecardPaymentPage.seamlessSubmitForm({
                            wrappingDivId: 'wirecardee-credit-card--iframe-div',
                            onSuccess: function (response) {
                                setParentTransactionId(response);
                            },
                            onError: handleFailedFormResponse
                        });
                    }
                });
            });
        </script>
    {/if}
{/block}
