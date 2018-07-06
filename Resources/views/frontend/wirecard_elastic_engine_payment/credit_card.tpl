{extends file="parent:frontend/checkout/confirm.tpl"}

{block name='frontend_index_header'}
    {include file="frontend/wirecard_elastic_engine_payment/header.tpl"}
{/block}

{block name="frontend_index_content"}
    <div class="wirecard-credit-card-error-message" style="display: none;">
        {include file='frontend/_includes/messages.tpl' type='error' content="test"}
    </div>
    {if $threeDSecure}
        <div class="content content--checkout">
            <h2>Wirecard3DSecureInformation</h2>
            <form id="redirect--form" method="{$method}" action="{$url}">
                {foreach from=$formFields item='field' key='key'}
                    <input type="hidden" name="{$key}" value="{$field}" />
                {/foreach}
                <button class="btn is--primary is--large right is--icon-right" type="submit">Senden</button>
            </form>
            <script type="text/javascript">
             document.getElementById('redirect--form').submit();
            </script>
        </div>
    {else}
        <div class="content content--checkout">
            <form id="credit-card--form" method="post">
                <div id="credit-card--iframe-div" style="width: 100%; height: 550px;"></div>
                <button class="btn is--primary is--large right is--icon-right" type="submit">Senden</button>
            </form>
        </div>
    {/if}
{/block}

{block name="frontend_index_javascript_async_ready"}
    {$smarty.block.parent}
    {if not $threeDSecure}
        <script type="text/javascript">
         document.asyncReady(function () {
             var $ = jQuery;

             var logCallback = function(response) {
                 if (response.transaction_state === 'failed') {
                     $('.wirecard-credit-card-error-message .alert--content').html(response.status_description_1)
                     $('.wirecard-credit-card-error-message').show();
                 }
             }

             WirecardPaymentPage.seamlessRenderForm({
                 requestData: {$wirecardRequestData},
                 wrappingDivId: 'credit-card--iframe-div',
                 onSuccess: logCallback,
                 onError: logCallback
             });

             var setParentTransactionId = function (response) {
                 var form = document.getElementById('credit-card--form'),
                     hiddenField = null;

                 for (var key in response){
                     if(response.hasOwnProperty(key)) {
                         hiddenField = document.createElement('div');
                         hiddenField.innerHTML = "<input type='hidden' name='" + key + "' value='" + response[key] + "'>";
                         form.appendChild(hiddenField);
                     }
                 }

                 hiddenField = document.createElement('div');
                 hiddenField.innerHTML = "<input id='jsresponse' type='hidden' name='jsresponse' value='true'>";
                 form.appendChild(hiddenField);
                 form.submit();
             }

             document.getElementById('credit-card--form').addEventListener('submit', function(event){
                 if (document.getElementById('jsresponse') ) {
                     console.log('Sending the following request to your server..');
                 } else {

                     event.preventDefault();

                     WirecardPaymentPage.seamlessSubmitForm({
                         wrappingDivId: 'credit-card--iframe-div',
                         onSuccess: setParentTransactionId,
                         onError: logCallback
                     })
                 }
                 
             });

         });
        </script>
    {/if}
{/block}
