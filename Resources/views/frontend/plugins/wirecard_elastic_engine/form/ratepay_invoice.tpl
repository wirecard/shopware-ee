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

{namespace name="frontend/wirecard_elastic_engine/ratepay_invoice"}
{block name="wirecard_elastic_engine_ratepay_invoice_form"}
    <div>
        <strong class="birthday--label">{s name='RegisterPlaceholderBirthday' namespace="frontend/register/personal_fieldset"}{/s}{s name="RequiredField" namespace="frontend/register/index"}{/s}</strong>
    </div>
    <input class="wirecardee--hidden-age-field" type="number" size="4" name="age" min="18" required style="display:none;">
    <div class="wirecardee-ratepay-invoice--birthdate">
        <div class="field--select select-field">
            <select name="wirecardElasticEngine[birthday][day]" required="required" aria-required="true"
                    class="wirecardee--birthday-day is--required{if $errorFlags.birthday} has--error{/if}">
                <option disabled="disabled"value="">{s name='RegisterBirthdaySelectDay' namespace="frontend/register/personal_fieldset"}{/s}</option>

                {for $day = 1 to 31}
                    <option value="{$day}">{$day}</option>
                {/for}
            </select>
        </div>
        <div class="field--select select-field">
            <select name="wirecardElasticEngine[birthday][month]" required="required" aria-required="true"
                    class="wirecardee--birthday-month is--required{if $errorFlags.birthday} has--error{/if}">
                <option disabled="disabled" value="">{s name='RegisterBirthdaySelectMonth' namespace="frontend/register/personal_fieldset"}{/s}</option>

                {for $month = 1 to 12}
                    <option value="{$month}">{$month}</option>
                {/for}
            </select>
        </div>
        <div class="field--select select-field">
            <select name="wirecardElasticEngine[birthday][year]" required="required" aria-required="true"
                    class="wirecardee--birthday-year is--required{if $errorFlags.birthday} has--error{/if}">
                <option disabled="disabled" value="">{s name='RegisterBirthdaySelectYear' namespace="frontend/register/personal_fieldset"}{/s}</option>

                {for $year = date("Y") to date("Y")-120 step=-1}
                    <option value="{$year}">{$year}</option>
                {/for}
            </select>
        </div>
    </div>
    <div class="wirecardee--error-box">
        <div class="alert is--error is--rounded">
            <div class="alert--icon">
                <i class="icon--element icon--cross">
                </i>
            </div>
            <div class="alert--content">
                {s name="FormErrorTooYoung"}{/s}
            </div>
        </div>
    </div>
{/block}
