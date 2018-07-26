{extends file="parent:frontend/checkout/confirm.tpl"}

{block name='frontend_index_header'}
    {include file="frontend/wirecard_elastic_engine_payment/header.tpl"}
{/block}

{block name="frontend_index_content"}
    <div class="wirecardee-redirect" style="padding-top: 50px;">
        <div class="content content--checkout confirm--content">
            <h2>{s name="PaymentRedirectHeader" namespace="frontend/wirecard_elastic_engine/checkout"}WirecardRedirect{/s}</h2>
            <form id="wirecardee-redirect--form" method="{$method}" action="{$url}">
                {foreach from=$formFields item='field' key='key'}
                    <input type="hidden" name="{$key}" value="{$field}"/>
                {/foreach}
                <button class="btn is--primary is--large right is--icon-right" type="submit">
                    {s name="PaymentRedirectButton" namespace="frontend/wirecard_elastic_engine/checkout"}Send{/s}
                    {* loading spinner icon *}
                    <i class="js--loading"></i>
                </button>
            </form>
            <script type="text/javascript">
                document.getElementById('wirecardee-redirect--form').submit();
            </script>
        </div>
    </div>
{/block}
