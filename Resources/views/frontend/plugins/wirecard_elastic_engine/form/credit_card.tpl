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

{namespace name="frontend/wirecard_elastic_engine/credit_card"}
{block name="wirecard_elastic_engine_credit_card_form"}
    {if $wirecardElasticEngineViewAssignments.vaultEnabled}
        {if !empty($wirecardElasticEngineViewAssignments.savedCards)}
            <p class="title">
                {s name="UseSavedCard"}{/s}
            </p>
            <ul class="list--radio list--unstyled">
                <li class="block-group">
                    <div class="block column--radio">
                        <input type="radio" name="wirecardElasticEngine[token]" id="wirecard--token-no-card" value="" checked="checked" />
                    </div>
                    <div class="block column--label">
                        <label for="wirecard--token-no-card">
                            {s name="NewCard"}{/s}
                        </label>
                    </div>
                </li>
                {foreach from=$wirecardElasticEngineViewAssignments.savedCards item='card'}
                    {if $card.acceptedCriteria}
                        <li class="block-group">
                            <div class="block column--radio">
                                <input type="radio" name="wirecardElasticEngine[token]" id="wirecard--token-{$card.token}" value="{$card.token}" />
                            </div>
                            <div class="block column--label">
                                <label for="wirecard--token-{$card.token}">
                                    <span class="wirecard--masked-account-number">
                                        {$card.maskedAccountNumber}
                                    </span>
                                    {if $card.additionalData}
                                        <span class="wirecard--card-info wirecard--card-holder">
                                            {$card.additionalData.firstName} {$card.additionalData.lastName}
                                        </span>
                                    {/if}
                                </label>
                            </div>
                            <div class="block column--button">
                                <button class="btn button--delete-token" type="button" data-token="{$card.token}">
                                    {s name="DeleteButton"}{/s}
                                </button>
                            </div>
                        </li>
                    {else}
                        <li class="block-group wirecard--disabled-card">
                            <div class="block column--radio">
                                <input type="radio" name="wirecardElasticEngine[token]" id="wirecard--token-{$card.token}" value="" disabled="disabled" />
                            </div>
                            <div class="block column--label">
                                <span class="wirecard--masked-account-number">
                                    {$card.maskedAccountNumber}
                                </span>
                                {if $card.additionalData}
                                    <span class="wirecard--card-info wirecard--card-holder">
                                        {$card.additionalData.firstName} {$card.additionalData.lastName}
                                    </span>
                                {/if}
                                <span class="wircard--disabled-note">
                                    {s name="WrongAddressNote"}{/s}
                                </span>
                            </div>
                            <div class="block column--button">
                                <button class="btn button--delete-token" type="button" data-token="{$card.token}">
                                    {s name="DeleteButton"}{/s}
                                </button>
                            </div>
                        </li>
                    {/if}
                {/foreach}
            </ul>
        {/if}
        <p class="title">
            {s name="NewCard"}{/s}
        </p>
        <ul class="list--checkbox list--unstyled">
            <li class="block-group">
                <span class="block column--checkbox">
                    <input id="wirecard--save-token" type="checkbox" name="wirecardElasticEngine[saveToken]" value="true" />
                </span>
                <span class="block column--label">
                    <label for="wirecard--save-token">{s name="SaveToken"}{/s}</label>
                </span>
            </li>
        </ul>
    {/if}
{/block}
