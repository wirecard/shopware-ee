{extends file="parent:frontend/checkout/confirm.tpl"}

{* Override body classes *}
{block name="frontend_index_body_classes"}
    {strip}
        is--ctl-checkout is--act-confirm is--minimal-header is--ctl-{controllerName|lower}
        {if $sUserLoggedIn} is--user{/if}
        {if $sOneTimeAccount} is--one-time-account{/if}
        {if $sTarget} is--target-{$sTarget|escapeHtml}{/if}
        {if !$theme.displaySidebar} is--no-sidebar{/if}
    {/strip}
{/block}

{* Support Info *}
{block name='frontend_index_logo_supportinfo'}
    <div class="logo--supportinfo block">
        {s name='RegisterSupportInfo' namespace='frontend/register/index'}{/s}
    </div>
{/block}
