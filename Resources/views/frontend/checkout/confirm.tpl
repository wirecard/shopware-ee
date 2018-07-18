{extends file="parent:frontend/checkout/confirm.tpl"}

{block name='frontend_checkout_confirm_information_wrapper'}
    {$smarty.block.parent}
    {if $wirecardFormFields}
        {if $wirecardFormFields.method == 'wirecard_elastic_engine_sepa'}
            <div class="panel has--border wirecard--aditional-form-fields">
                <div class="panel--title primary is--underline">
                    Additional Form Fields
                </div>
                <div class="panel--body is--wide">
                    {include file="frontend/plugins/wirecard_elastic_engine/form/sepa.tpl"}
                </div>
            </div>
        {/if}
    {/if}
{/block}

{block name="frontend_index_javascript_async_ready"}
    {$smarty.block.parent}
    {if $wirecardFormFields}
        {if $wirecardFormFields.method == 'wirecard_elastic_engine_sepa'}
            <script type="text/javascript">
             document.asyncReady(function() {
                 var $ = jQuery,
                     modalWindow = null;

                 var getMandateText = function() {
                     var firstName = $('#wirecard-sepa--first-name').val(),
                         lastName = $('#wirecard-sepa--last-name').val(),
                         iban = $('#wirecard-sepa--iban').val(),
                         bic = $('#wirecard-sepa--bic').val();
                     return `{include file="frontend/plugins/wirecard_elastic_engine/form/sepa_mandate.tpl"}`;
                 }

                 $('#confirm--form').on('submit', function(event) {
                     if ($('#wirecard-sepa--confirm-mandate').val() !== 'confirmed') {
                         event.preventDefault();
                         modalWindow = $.modal.open(getMandateText(), {
                             title: "Sepa Mandate",
                             closeOnOverlay: false,
                             showCloseButton: false
                         });
                         return false;
                     }
                 });

                 $(document).on('click', '#sepa--confirm-button', function() {
                     if ($('#sepa-check').is(':checked')) {
                         $('#wirecard-sepa--confirm-mandate').val('confirmed');
                         $('#confirm--form').submit();
                         return;
                     }

                     $('#sepa-check')[0].reportValidity();
                     $('label[for=sepa-check]').addClass('has--error').focus();
                 });

                 $(document).on('click', '#sepa--cancel-button', function() {
                     if (modalWindow) {
                         modalWindow.close();
                     }
                     $(".main--actions button[type=submit]").prop('disabled', false);
                     $(".main--actions button[type=submit] .js--loading").remove();
                     $(".main--actions button[type=submit]").append('<i class="icon--arrow-right">');
                 });
             });
            </script>
        {/if}
    {/if}
{/block}
