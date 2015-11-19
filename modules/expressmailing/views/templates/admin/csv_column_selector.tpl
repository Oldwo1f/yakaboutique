{*
* 2014-2015 (c) Axalone France - Express-Mailing
*
* This file is a commercial module for Prestashop
* Do not edit or add to this file if you wish to upgrade PrestaShop or
* customize PrestaShop for your needs please refer to
* http://www.express-mailing.com for more information.
*
* @author    Axalone France <info@express-mailing.com>
* @copyright 2014-2015 (c) Axalone France
* @license   http://www.express-mailing.com
*}

<style type="text/css">
	.highlighted {
		background-color: #00AFF0;
	}
	.highlight {
		background-color: #F7E69F;
	}
</style>

<div id="column_selector">
	<div class="panel" id="fieldset_0">
		<div class="panel-heading">
			<i class="icon-check"></i> {l s='%s column selector' mod='expressmailing' sprintf=$media}
		</div>
		<div class="form-wrapper">
			<div style="padding-bottom:1em;">
				<span style="display:block; font-weight: bold;text-align:center">{l s='Please select the « %s » column' mod='expressmailing' sprintf=$media} {l s='containing the %s numbers of your recipients :' mod='expressmailing' sprintf=$media}</span>
			</div>
			<div class="table-wrapper" style="overflow: auto; text-align: center; width: 100%">
				<table class="table-bordered" style="white-space: nowrap; cursor: pointer; margin-left: auto; margin-right: auto">
					{foreach $preview as $row}
						<tr>
							{assign var="j" value=1 nocache}
							{foreach $row as $cell}
								<td class="col{$j++|intval}" style="text-align: left; padding: 0.4em 0.7em 0.4em 0.7em">
									{$cell|escape:'html':'UTF-8'}
								</td>
							{/foreach}
						</tr>
					{/foreach}
				</table>
			</div>
		</div>
		<div class="panel-footer">
			<a align="left" style="display:inline-block" id="previous" href="index.php?controller={$prev_page|escape:'html':'UTF-8'}&campaign_id={$campaign_id|intval}&token={Tools::getAdminTokenLite($prev_page)|escape:'html':'UTF-8'}" class="btn btn-default">
				<i class="process-icon-back"></i> {l s='Back' mod='expressmailing'}
			</a>
			<form class="pull-right" id="formsubmit" style="display:inline-block"  method="post" enctype="multipart/form-data" novalidate="">
				<button type="submit" name="nom" id="configuration_form_submit_btn" class="btn btn-default">
					<i class="process-icon-next"></i> {l s='I validate the column position' mod='expressmailing'}
				</button>
			</form>
		</div>
	</div>
</div>

<script type="text/javascript">

	$(function ()
	{
		$('td').click(function ()
		{
			var $this = $(this), index = $this.index();
			var $columnCellsRemove = $this.parents('table').find('td');
			$columnCellsRemove.removeClass('highlighted');
			var $columnCells = $this.parents('table').find('tr td:nth-child(' + (index + 1) + ')');
			$columnCells.addClass('highlighted');
			document.getElementById("formsubmit").setAttribute("action", 'index.php?controller={$next_page|escape:'html':'UTF-8'}&campaign_id={$campaign_id|intval}&token={Tools::getAdminTokenLite($next_page)|escape:'html':'UTF-8'}&indexCol=' + index);

			$('.' + $(this).attr('class').split(' ')[0]).each(function ()
			{
				$(this).removeClass("highlight");
			});
		});

		// Begin -- Column hightlight --

		$('td').hover(
				function ()
				{
					$('.' + $(this).attr('class')).each(function ()
					{
						$(this).addClass("highlight");
					});
				},
				function ()
				{
					$('.' + $(this).attr('class').split(' ')[0]).each(function ()
					{
						$(this).removeClass("highlight");
					});
				}
		);

		// End -- Column hightlight --

	});

</script>

