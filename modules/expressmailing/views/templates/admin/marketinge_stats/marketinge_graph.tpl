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

<script type="text/javascript">

	var series1 = [];

	{foreach from=$delivered item=deliver_stat}
    	series1.push({
            x: {$deliver_stat.x|intval} * 1000, y: {$deliver_stat.y|intval}
        });
	{/foreach}

</script>

<div class="row">
    <div class="col-lg-12">
		<form class="defaultForm form-horizontal AdminMarketingEStep6" action="#" method="post" enctype="multipart/form-data" novalidate="">
		<div class="panel" id="fieldset_0">
			<div class="panel-heading">
				<i class="icon-envelope-alt"></i> {l s='Broadcast evolution during last 24/48 hours' mod='expressmailing'}
			</div>
			<div class="form-wrapper">
				<div class="form-group">
					<div class="col-lg-12">
						<svg id="stack" style="height: 300px; width: 98%"></svg>
					</div>
				</div>
			</div>
		</div>
		</form>
    </div>
</div>