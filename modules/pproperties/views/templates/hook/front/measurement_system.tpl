{*
* Product Properties Extension
*
* @author    PS&More www.psandmore.com <support@psandmore.com>
* @copyright 2011-2015 PS&More
* @license   psandmore.com/licenses/sla
*}

<script type="text/javascript">
	function setMeasurementSystem(measurement_system) {
		$.ajax({
			type: 'POST',
			headers: { "cache-control": "no-cache" },
			url: baseDir + 'index.php' + '?rand=' + new Date().getTime(),
			data: 'fc=module&module=pproperties&ajax=1&action=set_measurement_system&measurement_system='+ parseInt(measurement_system),
			success: function(msg) {
				location.reload(true);
			}
		});
	}
</script>

{$ms_current = PP::resolveMS()}
<div id="measurement_system_fo">
	<div class="current">
		{foreach from=$measurement_systems key=id item=ms}
			{if $ms_current == $id}{$ms.name}{/if}
		{/foreach}
	</div>
	<ul class="pp_measurement_systems toogle_content">
		{foreach from=$measurement_systems key=id item=ms}
			<li{if $ms_current == $id} class="selected"{/if}>
				<a href="javascript:setMeasurementSystem({$id});" rel="nofollow" title="{$ms.title}">
					{$ms.name}
				</a>
			</li>
		{/foreach}
	</ul>
</div>
