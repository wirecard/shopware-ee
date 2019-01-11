{**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 *}

{namespace name="frontend/wirecard_elastic_engine/credit_card"}
{block name="wirecard_elastic_engine_credit_card_form"}
    {if $wirecardElasticEngineViewAssignments.vaultEnabled}
        {if !empty($wirecardElasticEngineViewAssignments.savedCards)}
            <p class="title">
                {s name="UseSavedCard"}{/s}
            </p>
            <ul class="list--radio list--unstyled">
                {assign var=cardVaultOptionSelected value=false}
                {foreach from=$wirecardElasticEngineViewAssignments.savedCards item='card'}
                    {if $card.acceptedCriteria}
                        <li class="block-group">
                            <div class="block column--radio">
                                <input type="radio" name="wirecardElasticEngine[token]"
                                       id="wirecardee--token-{$card.token}" value="{$card.token}"
                                       {if !$cardVaultOptionSelected}
                                            checked="checked"
                                            {$cardVaultOptionSelected=true}
                                       {/if}
                                />
                            </div>
                            <div class="block column--label">
                                <label for="wirecardee--token-{$card.token}">
                                    <span class="wirecardee--masked-account-number">
                                        {$card.maskedAccountNumber}
                                    </span>
                                    {if $card.additionalData}
                                        <span class="wirecardee--card-info wirecardee--card-holder">
                                            {$card.additionalData.firstName} {$card.additionalData.lastName}
                                        </span>
                                    {/if}
                                </label>
                            </div>
                            <div class="block column--button">
                                <button class="btn wirecardee--delete-token" type="button" data-token="{$card.token}">
                                    {s name="DeleteButton"}{/s}
                                </button>
                            </div>
                        </li>
                    {else}
                        <li class="block-group wirecardee--disabled-card">
                            <div class="block column--radio">
                                <input type="radio" name="wirecardElasticEngine[token]"
                                       id="wirecardee--token-{$card.token}" value="" disabled="disabled"/>
                            </div>
                            <div class="block column--label">
                                <span class="wirecardee--masked-account-number">
                                    {$card.maskedAccountNumber}
                                </span>
                                {if $card.additionalData}
                                    <span class="wirecardee--card-info wirecardee--card-holder">
                                        {$card.additionalData.firstName} {$card.additionalData.lastName}
                                    </span>
                                {/if}
                                <span class="wircardee--disabled-note">
                                    {s name="WrongAddressNote"}{/s}
                                </span>
                            </div>
                            <div class="block column--button">
                                <button class="btn wirecardee--delete-token" type="button" data-token="{$card.token}">
                                    {s name="DeleteButton"}{/s}
                                </button>
                            </div>
                        </li>
                    {/if}
                {/foreach}
                <li class="block-group">
                    <div class="block column--radio">
                        <input type="radio" name="wirecardElasticEngine[token]" id="wirecardee--token-no-card" value=""
                            {if !$cardVaultOptionSelected}
                                checked="checked"
                                {$cardVaultOptionSelected=true}
                            {/if}
                        />
                    </div>
                    <div class="block column--label">
                        <label for="wirecardee--token-no-card">
                            {s name="NewCard"}{/s}
                        </label>
                    </div>
                </li>
            </ul>
        {/if}
        <p class="title">
            {s name="NewCard"}{/s}
        </p>
        <ul class="list--checkbox list--unstyled">
            <li class="block-group">
                <span class="block column--checkbox">
                    <input id="wirecardee--save-token" type="checkbox" name="wirecardElasticEngine[saveToken]"
                           value="true"/>
                </span>
                <span class="block column--label">
                    <label for="wirecardee--save-token">{s name="SaveToken"}{/s}</label>
                </span>
            </li>
        </ul>
    {/if}
{/block}
