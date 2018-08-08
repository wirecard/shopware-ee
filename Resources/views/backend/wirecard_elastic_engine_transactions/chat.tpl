{**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 *}

{block name="backend/base/header/css"}
    {$smarty.block.parent}
    <style type="text/css">
        #lz_overlay_wm { display: none !important; }

        .sprite--wirecard {
            background-image: url('../custom/plugins/WirecardElasticEngine/plugin.png');
        }
    </style>
{/block}

{block name="backend/base/header/javascript"}
    {$smarty.block.parent}
    <script type="text/javascript" id="936f87cd4ce16e1e60bea40b45b0596a"
            src="https://www.provusgroup.com/livezilla/script.php?id=936f87cd4ce16e1e60bea40b45b0596a"></script>
    <script type="text/javascript">
        // Since the chat window is visible throughout the session we need to manually show the buttons again.
        var wirecardeeChatButtonCheckCounter = 15;
        var wirecardeeChatButtonInterval = setInterval(function () {
            var chatOverlay = document.getElementById('lz_overlay_chat');
            if (chatOverlay && chatOverlay.style.display === 'block') {
                clearInterval(wirecardeeChatButtonInterval);
                Ext.util.CSS.createStyleSheet("#lz_overlay_wm { display: inherit !important; }");
                return;
            }
            wirecardeeChatButtonCheckCounter--;
            if (wirecardeeChatButtonCheckCounter === 0) {
                clearInterval(wirecardeeChatButtonInterval);
            }
        }, 500);
        var wirecardeeChatOpen = function () {
            Ext.util.CSS.createStyleSheet('#lz_overlay_wm { display: inherit !important; }');
            var button = document.getElementById('livezilla_wm');
            if (button) {
                button.click();
            }
        };
    </script>
{/block}
