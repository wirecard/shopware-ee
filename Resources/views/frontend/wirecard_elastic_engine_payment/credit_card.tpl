{extends file="parent:frontend/checkout/confirm.tpl"}

{block name='frontend_index_header'}
    {include file="frontend/wirecard_elastic_engine_payment/header.tpl"}
{/block}

{block name="frontend_index_content"}
    <div class="content content--checkout">
        <form>
            <div id="credit-card--iframe-div" style="width: 100%; height: 600px;"></div>
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
             wrappingDivId: "credit-card--iframe-div",
             onSuccess: logCallback,
             onError: logCallback
         });
     })();
    </script>
{/block}
