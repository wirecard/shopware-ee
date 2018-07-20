{extends file="parent:frontend/checkout/confirm.tpl"}

{block name='frontend_index_header'}
    {include file="frontend/wirecard_elastic_engine_payment/header.tpl"}
{/block}

{block name="frontend_index_content"}
    <div class="credit-card" style="padding-top: 50px;">
        <div class="wirecard-credit-card-error-message" style="display: none;">
            {include file='frontend/_includes/messages.tpl' type='error' content="test"}
        </div>
        <div class="content content--checkout">
            <h2>WirecardRedirect</h2>
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
    </div>
{/block}
