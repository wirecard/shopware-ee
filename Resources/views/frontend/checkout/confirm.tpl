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

{block name='frontend_checkout_confirm_information_wrapper'}
    {$smarty.block.parent}
<<<<<<< HEAD
    {if $wirecardElasticEngineViewAssignments}
        {if $wirecardElasticEngineViewAssignments.method == 'wirecard_elastic_engine_sepa'}
            <div class="panel has--border wirecardee--additional-form-fields">
                <div class="panel--title primary is--underline">
                    {s name="SepaPaymentFormHeader" namespace="frontend/wirecard_elastic_engine/sepa"}{/s}
                </div>
                <div class="panel--body is--wide">
                    {include file="frontend/plugins/wirecard_elastic_engine/form/sepa.tpl"}
                </div>
=======
    {if $wirecardElasticEngineViewAssignments and $wirecardElasticEngineViewAssignments.method == 'wirecard_elastic_engine_sepa'}
        <div class="panel has--border wirecardee--additional-form-fields">
            <div class="panel--title primary is--underline">
                {s name="SepaPaymentFormHeader" namespace="frontend/wirecard_elastic_engine/sepa_direct_debit"}{/s}
>>>>>>> dev
            </div>
        {elseif $wirecardElasticEngineViewAssignments.method == 'wirecard_elastic_engine_ideal'}
            <div class="panel has--border wirecardee--additional-form-fields">
                <div class="panel--title primary is--underline">
                    {s name="IdealPaymentFormHeader" namespace="frontend/wirecard_elastic_engine/ideal"}{/s}
                </div>
                <div class="panel--body is--wide">
                    {include file="frontend/plugins/wirecard_elastic_engine/form/ideal.tpl"}
                </div>
            </div>
        {/if}
    {/if}

    {if $wirecardElasticEngineIncludeDeviceFingerprintIFrame}
        <script type="text/javascript"
                src="https://h.wirecard.com/fp/tags.js?org_id=6xxznhva&session_id={$wirecardElasticEngineDeviceFingerprintId}">
        </script>
        <noscript>
            <iframe style="width: 100px; height: 100px; border: 0; position: absolute; top: -5000px;"
                    src="https://h.wirecard.com/tags?org_id=6xxznhva&session_id={$wirecardElasticEngineDeviceFingerprintId}"></iframe>
        </noscript>
    {/if}
{/block}

{block name="frontend_index_javascript_async_ready"}
    {$smarty.block.parent}
    {if $wirecardElasticEngineViewAssignments and $wirecardElasticEngineViewAssignments.method == 'wirecard_elastic_engine_sepa'}
        <div id="wirecardee-sepa--mandate-text" style="display:none;">
            {include file="frontend/plugins/wirecard_elastic_engine/form/sepa_mandate.tpl"}
        </div>
        <script type="text/javascript">
            document.asyncReady(function () {
                var $ = jQuery,
                    modalWindow = null,
                    $mandateText = $('#wirecardee-sepa--mandate-text'),
                    template = $mandateText.html();
                $mandateText.remove();

                var getMandateText = function () {
                    var firstName = $('#wirecardee-sepa--first-name').val(),
                        lastName = $('#wirecardee-sepa--last-name').val(),
                        iban = $('#wirecardee-sepa--iban').val(),
                        bic = $('#wirecardee-sepa--bic').val();
                    return template.replace('{literal}${firstName}{/literal}', firstName)
                        .replace('{literal}${lastName}{/literal}', lastName)
                        .replace('{literal}${iban}{/literal}', iban)
                        .replace('{literal}${bic}{/literal}', bic);
                };

                $('#confirm--form').on('submit', function (event) {
                    if ($('#wirecardee-sepa--confirm-mandate').val() !== 'confirmed') {
                        event.preventDefault();
                        modalWindow = $.modal.open(getMandateText(), {
                            title: "{s name="SepaMandateTitle" namespace="frontend/wirecard_elastic_engine/sepa_direct_debit"}{/s}",
                            closeOnOverlay: false,
                            showCloseButton: false
                        });
                        return false;
                    }
                });

                $(document).on('click', '#wirecardee-sepa--confirm-button', function () {
                    var $check = $('#wirecardee-sepa--confirm-check');
                    if ($check.is(':checked')) {
                        $('#wirecardee-sepa--confirm-mandate').val('confirmed');
                        $('#confirm--form').submit();
                        return;
                    }

                    if ($check.length && $check[0].reportValidity) {
                        $check[0].reportValidity();
                    }
                    $('label[for=wirecardee-sepa--confirm-check]').addClass('has--error').focus();
                });

                $(document).on('click', '#wirecardee-sepa--cancel-button', function () {
                    if (modalWindow) {
                        modalWindow.close();
                    }
                    var $submitButton = $(".main--actions button[type=submit]");
                    $submitButton.prop('disabled', false);
                    $submitButton.find('.js--loading').remove();
                    $submitButton.append('<i class="icon--arrow-right">');
                });
            });
        </script>
    {/if}
{/block}
