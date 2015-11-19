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
* @license   http://opensource.org/licenses/GPL-3.0  GNU General Public License, version 3 (GPL-3.0)
*}

{capture assign="l_sent"}{l s='Sent' mod='expressmailing'}{/capture}
{capture assign="l_not_sent"}{l s='Not[br]Sent' mod='expressmailing'}{/capture}
{capture assign="l_delivrered"}{l s='Delivered' mod='expressmailing'}{/capture}
{capture assign="l_not_delivrered"}{l s='Not[br]Delivered' mod='expressmailing'}{/capture}
{capture assign="l_opened"}{l s='Opened' mod='expressmailing'}{/capture}
{capture assign="l_not_opened"}{l s='Not[br]Opened' mod='expressmailing'}{/capture}
{capture assign="l_clickers"}{l s='Distinct[br]Clickers' mod='expressmailing'}{/capture}
{capture assign="l_clicks"}{l s='Total[br]Clicks' mod='expressmailing'}{/capture}
{capture assign="l_abuses"}{l s='Abuse[br]Reports' mod='expressmailing'}{/capture}
{capture assign="l_unsubscribes"}{l s='Unsub[br]scribes' mod='expressmailing'}{/capture}

<div class="row">
    <div class="col-lg-12">
		<form class="defaultForm form-horizontal AdminMarketingEStep6" action="#" method="post" enctype="multipart/form-data" novalidate="">
		<div class="panel" id="fieldset_0">
			<div class="panel-heading"><i class="icon-calendar-empty"></i> {l s='Broadcast days' mod='expressmailing'} <span class="badge">{count($days)|intval}</span></div>
			<section>
				<nav>
					<ul class="nav nav-pills">
						{foreach $days as $day}
							<li {if $select_day == $day['day_api']}class="active"{/if}>
								<a href="{$current_index|escape:'quotes'}&stat_date={$day['day_api']|intval}&token={$smarty.get.token|escape:'html':'UTF-8'}">
									<i class="icon-eye-open"></i>
									<span class="hidden-inline-xs">{$day['day_lang']|escape:'htmlall':'UTF-8'}</span>
								</a>
							</li>
						{/foreach}
					</ul>
				</nav>
			</section>
		</div>
		</form>
	</div>
</div>

<div class="row">
    <div class="col-lg-12">
		<form class="defaultForm form-horizontal AdminMarketingEStep6" action="#" method="post" enctype="multipart/form-data" novalidate="">
		<div class="panel" id="fieldset_0">
			<div class="panel-heading"><i class="icon-bar-chart"></i> {l s='Statistics for campaign' mod='expressmailing'} &laquo;&nbsp;{$campaign_name|escape:'html':'UTF-8'}&nbsp;&raquo;</div>
			<table border="0" width="100%" cellspacing="5" cellpadding="5">
			<tr>
				<td class="stat_td">
					<div class="stat_list_block">
						<span class="stat_label">{$l_sent|escape:'htmlall':'UTF-8'|replace:'[br]':'<br/>'}</span>
						<span class="stat_value">
							<span>{$sent|intval}</span>
						</span>
					</div>
				</td>
				 <td class="stat_td">
					<div class="stat_list_block">
						<span class="stat_label">{$l_not_sent|escape:'htmlall':'UTF-8'|replace:'[br]':'<br/>'}</span>
						<span class="stat_value">
							<span>{$not_sent|intval}</span>
						</span>
					</div>
				</td>
				 <td class="stat_td">
					<div class="stat_list_block">
						<span class="stat_label">{$l_delivrered|escape:'htmlall':'UTF-8'|replace:'[br]':'<br/>'}</span>
						<span class="stat_value">
							<span>{$delivered|intval}</span>
						</span>
						<span class="ratio_label green">
							<span>{$ratio_delivered|intval}%</span>
						</span>
					</div>
				</td>
				 <td class="stat_td">
					<div class="stat_list_block">
						<span class="stat_label">{$l_not_delivrered|escape:'htmlall':'UTF-8'|replace:'[br]':'<br/>'}</span>
						<span class="stat_value">
							<span>{$not_delivered|intval}</span>
						</span>
						<span class="ratio_label red">
							<span>{$ratio_not_delivered|intval}%</span>
						</span>
					</div>
				</td>
				 <td class="stat_td">
					<div class="stat_list_block">
						<span class="stat_label">{$l_opened|escape:'htmlall':'UTF-8'|replace:'[br]':'<br/>'}</span>
						<span class="stat_value">
							<span>{$opened|intval}</span>
						</span>
						<span class="ratio_label green">
							<span>{$ratio_opened|intval}%</span>
						</span>
					</div>
				</td>
				 <td class="stat_td">
					<div class="stat_list_block">
						<span class="stat_label">{$l_not_opened|escape:'htmlall':'UTF-8'|replace:'[br]':'<br/>'}</span>
						<span class="stat_value">
							<span>{$not_opened|intval}</span>
						</span>
						<span class="ratio_label red">
							<span>{$ratio_not_opened|intval}%</span>
						</span>
					</div>
				</td>
				 <td class="stat_td">
					<div class="stat_list_block">
						<span class="stat_label">{$l_clickers|escape:'htmlall':'UTF-8'|replace:'[br]':'<br/>'}</span>
						<span class="stat_value">
							<span>{$unique_clickers|intval}</span>
						</span>
						<span class="ratio_label green">
							<span>{$ratio_unique_clickers|intval}%</span>
						</span>
					</div>
				</td>
				 <td class="stat_td">
					<div class="stat_list_block">
						<span class="stat_label">{$l_clicks|escape:'htmlall':'UTF-8'|replace:'[br]':'<br/>'}</span>
						<span class="stat_value">
							<span>{$all_clicks|intval}</span>
						</span>
					</div>
				</td>
				 <td class="stat_td">
					<div class="stat_list_block">
						<span class="stat_label">{$l_abuses|escape:'htmlall':'UTF-8'|replace:'[br]':'<br/>'}</span>
						<span class="stat_value">
							<span>{$abuses|intval}</span>
						</span>
						<span class="ratio_label red">
							<span>{$ratio_abuses|intval}%</span>
						</span>
					</div>
				</td>
				 <td class="stat_td">
					<div class="stat_list_block">
						<span class="stat_label">{$l_unsubscribes|escape:'htmlall':'UTF-8'|replace:'[br]':'<br/>'}</span>
						<span class="stat_value">
							<span>{$unsubscribes|intval}</span>
						</span>
						<span class="ratio_label red">
							<span>({$ratio_unsubscribes|intval}%)</span>
						</span>
					</div>
				</td>
			</tr>
			</table>
		</div>
		</form>
	</div>
</div>

<script type="text/javascript">

	// Graph des aboutis/npai sur 24 heures

	function myData()
	{
		return [
			{
				key: "{$l_delivrered|escape:'htmlall':'UTF-8'|replace:'[br]':'<br/>'}",
				values: series1,
				color: "green"
			}
		];
	}

	$(function ()
	{
		nv.addGraph(function()
		{
			var chart = nv.models.multiBarChart()
				.x(function(d) { return d.x; })
				.y(function(d) { return d.y; })
				.showLegend(true)
				.tooltips(true)
				.showControls(false)		// Allow user to switch between 'Grouped' and 'Stacked' mode.
				.reduceXTicks(true)			// If 'false', every single x-axis tick label will be rendered.
				.showXAxis(true)
				.showYAxis(true);

			chart.xAxis
				.tickFormat(function(d) { return d3.time.format('%e %b - %Hh%M')(new Date(d)); } )
				.showMaxMin(true);

			chart.yAxis
				.tickFormat(d3.format(',f'))
				.showMaxMin(true);

			d3.select('#stack')
				.datum(myData)
				.call(chart);

			nv.utils.windowResize(chart.update);
			return chart;
		});

	});

</script>