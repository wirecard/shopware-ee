{**
 * Shop System Plugins - Terms of Use
 *
 * The plugins offered are provided free of charge by Wirecard AG and are explicitly not part
 * of the Wirecard AG range of products and services.
 *
 * They have been tested and approved for full functionality in the standard configuration
 * (status on delivery) of the corresponding shop system. They are under General Public
 * License version 3 (GPLv3) and can be used, developed and passed on to third parties under
 * the same terms.
 *
 * However, Wirecard AG does not provide any guarantee or accept any liability for any errors
 * occurring when used in an enhanced, customized shop system configuration.
 *
 * Operation in an enhanced, customized configuration is at your own risk and requires a
 * comprehensive test phase by the user of the plugin.
 *
 * Customers use the plugins at their own risk. Wirecard AG does not guarantee their full
 * functionality neither does Wirecard AG assume liability for any disadvantages related to
 * the use of the plugins. Additionally, Wirecard AG does not guarantee the full functionality
 * for customized shop systems or installed plugins of other vendors of plugins within the same
 * shop system.
 *
 * Customers are responsible for testing the plugin's functionality before starting productive
 * operation.
 *
 * By installing the plugin into the shop system the customer agrees to these terms of use.
 * Please do not use the plugin if you do not agree to these terms of use!
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
