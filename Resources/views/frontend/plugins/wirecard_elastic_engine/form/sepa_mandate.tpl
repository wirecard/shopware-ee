{namespace name="frontend/wirecard_elastic_engine/sepa"}
{block name="wirecard_elastic_engine_sepa_mandate"}
    <table border="0" cellpadding="0" cellspacing="0" class="stretch">
	<tr>
	<td class="text11justify">
	    <table border="0" width="100%">
		<tr>
		    <td class="text11justify">
			<i>{s name="Creditor"}{/s}</i><br />
	                FIXXXME $creditor_name  $creditor_store_city <br />
                        {s name="CreditorID"}{/s}FIXXXME $creditor_id . '<br />
		    </td>
		    <td width="10%">&nbsp;</td>
		</tr>
	    </table>
	</td>
	</tr>
	<tr>
	    <td>
		<table border="0" width="100%">
		    <tr>
			<td class="text11">
			    <i>{s name="Debtor"}{/s}</i><br />
	                    {s name="AccountOwner"}{/s}: <span class="first_last_name"></span><br />
	                    {s name="IBAN"}{/s}: <span class="bank_iban"></span><br />
                            {if $wirecardFormFields.showBic}
	                        {s name="BIC"}{/s}: <span class="bank_bic"></span><br />
                            {/if}
                        </td>
			<td width="10%">&nbsp;</td>
		    </tr>
		</table>
	    </td>
	</tr>
	<tr>
	    <td class="text11justify">
		<table border="0" width="100%">
		    <tr>
			<td class="text11justify">
	                    {s name="AuthorizeCreditorHead"}{/s}
                            FIXXXME $creditor_name 
	                    {s name="AuthorizeCreditorMid"}{/s}
	                    FIXXXME $creditor_name $additional_text
	                    {s name="AuthorizeCreditorTail"}{/s}
			</td>
			<td width="10%">&nbsp;</td>
		    </tr>
		    <tr>
			<td class="text11justify">
                            {s name="RefundEntitlementNote"}{/s}
			</td>
			<td width="10%">&nbsp;</td>
		    </tr>
		    <tr>
			<td class="text11justify">' .
	                    {s name="AgreementTextHead"}{/s}
	                    FIXXXME $creditor_name
	                    {s name="AgreementTextTail"}{/s}
			</td>
			<td width="10%">&nbsp;</td>
		    </tr>
		</table>
	    </td>
	</tr>
	<tr>
	    <td class="text11justify">
		<table border="0" width="100%">
		    <tr>
			<td class="text11justify">' .
	                    FIXXXME $creditor_store_city date( 'd.m.Y' ) . ' <span class="first_last_name"></span>
			</td>
			<td width="10%">&nbsp;</td>
		    </tr>
		    <tr>
			<td>
			    <input type="checkbox" id="sepa-check">&nbsp;<label for="sepa-check">{s name="CheckboxText"}{/s}</label>
			</td>
		    </tr>
		    <tr>
			<td style="text-align: right;"><button id="sepa-button">{s name="Cancel"}{/s}</button></td>
		    </tr>
		</table>
	    </td>
	</tr>
</table>
{/block}
