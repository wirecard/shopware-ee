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
