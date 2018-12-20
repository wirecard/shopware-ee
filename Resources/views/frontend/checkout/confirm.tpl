{**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 *}

{extends file="parent:frontend/checkout/confirm.tpl"}

{block name='frontend_checkout_confirm_information_wrapper'}
    {$smarty.block.parent}
    {if $wirecardElasticEngineViewAssignments}
        {if $wirecardElasticEngineViewAssignments.method == 'wirecard_elastic_engine_sepa'}
            <div class="panel has--border wirecardee--additional-form-fields">
                <div class="panel--title primary is--underline">
                    {s name="SepaPaymentFormHeader" namespace="frontend/wirecard_elastic_engine/sepa_direct_debit"}{/s}
                </div>
                <div class="panel--body is--wide">
                    {include file="frontend/plugins/wirecard_elastic_engine/form/sepa.tpl"}
                </div>
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
        {elseif $wirecardElasticEngineViewAssignments.method == 'wirecard_elastic_engine_eps'}
            <div class="panel has--border wirecardee--additional-form-fields">
                <div class="panel--title primary is--underline">
                    {s name="EpsPaymentFormHeader" namespace="frontend/wirecard_elastic_engine/eps"}{/s}
                </div>
                <div class="panel--body is--wide">
                    {include file="frontend/plugins/wirecard_elastic_engine/form/eps.tpl"}
                </div>
            </div>
        {elseif $wirecardElasticEngineViewAssignments.method == 'wirecard_elastic_engine_ratepay_invoice'}
            {if $wirecardElasticEngineViewAssignments.showForm}
                <div class="panel has--border wirecardee--additional-form-fields">
                    <div class="panel--title primary is--underline">
                        {s name="RatepayInvoiceFormHeader" namespace="frontend/wirecard_elastic_engine/ratepay_invoice"}{/s}
                    </div>
                    <div class="panel--body is--wide">
                        {include file="frontend/plugins/wirecard_elastic_engine/form/ratepay_invoice.tpl"}
                    </div>
                </div>
            {/if}
            <div class="tos--panel panel has--border">
                <div class="panel--body is--wide">
                    <ul class="list--checkbox list--unstyled">
                        <li class="block-group row--tos">
                            <span class="block column--checkbox">
                                <input id="wirecardee--tac" type="checkbox" name="wirecardElasticEngine[tac]" required="required" aria-required="true" data-invalid-tos-jump="true" />
                            </span>
                            <span class="block column--label">
                                <label for="wirecardElasticEngine[tac]" data-modalbox="true" data-height="500" data-width="750">{s name="RatepayTAC" namespace="frontend/wirecard_elastic_engine/ratepay_invoice"}{/s}</label>
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
            <script language="JavaScript">
                var di = { t: "{$wirecardElasticEngineDeviceFingerprintId}", v: "WDWL", l: "Checkout" };
            </script>
            <script type="text/javascript" src="//d.ratepay.com/WDWL/di.js"></script>
            <noscript>
                <link rel="stylesheet" type="text/css"
                      href="//d.ratepay.com/di.css?t={$wirecardElasticEngineDeviceFingerprintId}&v=WDWL&l=Checkout">
            </noscript>
            <object type="application/x-shockwave-flash" data="//d.ratepay.com/WDWL/c.swf" width="0" height="0">
                <param name="movie" value="//d.ratepay.com/WDWL/c.swf"/>
                <param name="flashvars" value="t={$wirecardElasticEngineDeviceFingerprintId}&v=WDWL"/>
                <param name="AllowScriptAccess" value="always"/>
            </object>
        {elseif $wirecardElasticEngineViewAssignments.method == 'wirecard_elastic_engine_credit_card'}
            {if $wirecardElasticEngineViewAssignments.vaultEnabled}
                <div class="panel has--border wirecardee--additional-form-fields">
                    <div class="panel--title primary is--underline">
                        {s name="CreditCardVaultFormHeader" namespace="frontend/wirecard_elastic_engine/credit_card"}{/s}
                    </div>
                    <div class="panel--body is--wide">
                        {include file="frontend/plugins/wirecard_elastic_engine/form/credit_card.tpl"}
                    </div>
                </div>
            {/if}
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
    {elseif $wirecardElasticEngineViewAssignments and $wirecardElasticEngineViewAssignments.method == 'wirecard_elastic_engine_credit_card'}
        {block name="wirecard_elastic_engine_credit_card_form_javascript"}
            <script type="text/javascript">
                document.asyncReady(function () {
                    var $ = jQuery,
                        url = "{url controller="wirecardElasticEnginePayment" action="deleteCreditCardToken"}";

                    $(".wirecardee--delete-token").click(function () {
                        window.location.href = url + '/token/' + $(this).data('token');
                    })
                });
            </script>
        {/block}
    {/if}
{/block}
