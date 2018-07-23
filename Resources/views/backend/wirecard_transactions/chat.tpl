{block name="backend/base/header/css"}
    {$smarty.block.parent}
    <style type="text/css">
        #lz_overlay_wm { display: none !important; }
    </style>
{/block}

{block name="backend/base/header/javascript"}
    {$smarty.block.parent}
    <script type="text/javascript" id="936f87cd4ce16e1e60bea40b45b0596a" src="https://www.provusgroup.com/livezilla/script.php?id=936f87cd4ce16e1e60bea40b45b0596a"></script>
    <script>
        // Since the chat window is visible throughout the session we need to manually show the buttons again.
        var wirecardee_button_check_counter = 15;
        var wirecardee_button_interval = setInterval(function() {
            var chatOverlay = document.getElementById('lz_overlay_chat');
            if (chatOverlay && chatOverlay.style.display === 'block') {
                clearInterval(wirecardee_button_interval);
                Ext.util.CSS.createStyleSheet("#lz_overlay_wm { display: inherit !important; }");
                return ;
            }

            wirecardee_button_check_counter--;

            if (wirecardee_button_check_counter === 0) {
                clearInterval(wirecardee_button_interval);
            }
        }, 500);
    </script>
{/block}
