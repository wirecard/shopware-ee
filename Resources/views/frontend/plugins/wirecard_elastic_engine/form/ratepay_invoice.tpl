{**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 *}

{namespace name="frontend/wirecard_elastic_engine/ratepay_invoice"}
{block name="wirecard_elastic_engine_ratepay_invoice_form"}
    <div>
        <strong class="birthday--label">{s name='RegisterPlaceholderBirthday' namespace="frontend/register/personal_fieldset"}{/s}{s name="RequiredField" namespace="frontend/register/index"}{/s}</strong>
    </div>
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
{/block}
