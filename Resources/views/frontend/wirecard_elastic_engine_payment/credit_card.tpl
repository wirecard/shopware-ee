{extends file="parent:frontend/checkout/confirm.tpl"}

{block name='frontend_index_header'}
    {include file="frontend/wirecard_elastic_engine_payment/header.tpl"}
{/block}

{block name="frontend_index_content"}
    <div class="content content--checkout">
        <form id="credit-card--form" method="post">
            <div id="credit-card--iframe-div" style="width: 100%; height: 600px;"></div>
            <button class="btn is--primary is--large right is--icon-right" type="submit">Senden</button>
        </form>
    </div>
{/block}

{block name="frontend_index_javascript_async_ready"}
    {$smarty.block.parent}
    <script type="text/javascript">
     window.requestData = {$wirecardRequestData};
     (function() {

         var logCallback = function(response) {
             console.log(response);
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
             event.preventDefault();
             
             if (document.getElementById('jsresponse') ) {
                 console.log('Sending the following request to your server..');
             } else {

                 event.preventDefault();

                 WirecardPaymentPage.seamlessSubmitForm({
                     onSuccess: setParentTransactionId,
                     onError: logCallback
                 })
             }
             
         });

     }) ();
    </script>
{/block}
