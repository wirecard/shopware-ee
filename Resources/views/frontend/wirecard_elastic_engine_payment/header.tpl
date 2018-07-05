{extends file="parent:frontend/index/header.tpl"}

{block name="frontend_index_header_javascript_tracking"}
    {$smarty.block.parent}
    <script src="{$wirecardUrl}/engine/hpp/paymentPageLoader.js" type="text/javascript"></script>
{/block}
