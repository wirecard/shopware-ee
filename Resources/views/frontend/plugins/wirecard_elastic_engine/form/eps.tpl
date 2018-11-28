{**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 *}

{namespace name="frontend/wirecard_elastic_engine/eps"}
{block name="wirecard_elastic_engine_eps_form"}
    <div class="wirecardee-eps--bic">
{*
* Disabled till further notice
*        <link type="text/css" rel="stylesheet" href="https://bankauswahl.giropay.de/eps/widget/v1/style.css" media="all"/>
*        <script src="https://bankauswahl.giropay.de/eps/widget/v1/jquery-1.10.2.min.js"></script>
*        <script src="https://bankauswahl.giropay.de/eps/widget/v1/epswidget.min.js"></script>
*}
        <form>
            <label for="ee_eps_widget">{s name="BIC"}{/s}:</label>
            <input id="ee_eps_widget" type="text" autocomplete="off" name="wirecardElasticEngine[epsBic]"/>
        </form>
{*
* Disabled till further notice
*        <script>
*            jQuery(document).ready(function(){
*                jQuery("#ee_eps_widget").eps_widget();
*            });
*        </script>
*}
    </div>
{/block}
