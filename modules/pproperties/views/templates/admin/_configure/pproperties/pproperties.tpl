{*
* Product Properties Extension
*
* @author    PS&More www.psandmore.com <support@psandmore.com>
* @copyright 2011-2015 PS&More
* @license   psandmore.com/licenses/sla
*}

<div id="pproperties-configure-context">
{hook h="ppropertiesAdmin" mode="displayPPropertiesHeader"}
{if (isset($psmextmanager_install))}
	<div class="alert alert-message">
		<h4>{l s='Please install PSM Extension Manager.' mod='pproperties'}</h4>
		<p>
			{l s='With the PSM Extension Manager you can quickly and easily manage PS&More products.' mod='pproperties'}
			{l s='The PSM Extension Manager adds additional functionality to PS&More products.' mod='pproperties'}
		</p>
		<p><a href="{$psmextmanager_install}">{l s='Install it now, it is FREE!' mod='pproperties'}</a></p>
	</div>
{/if}
<div class="head">
	<div class="user-guide">
		<a href="{$_path}readme_en.pdf" target="_blank"><i class="icon-book"></i>{$s_user_guide}</a>
	</div>
	<div class="version"><span>{$s_version}: {$version} [{$ppe_id}]</span><a href="http://psandmore.com" target="_blank">http://psandmore.com</a></div>
	<div id="pp_info_block" style="display: none;" class="ui-corner-all">
		<button class="close pp_info_close" type="button">×</button>
		<div class="clearfix"></div>
		<div class="pp_info_content"></div>
		<div class="clearfix"></div>
		<div class="pp_info_hide"><input id="pp_info_ignore" type="checkbox"/><label for="pp_info_ignore">{$s_pp_info_ignore}</label></div>
	</div>
	<div class="clearfix" style="margin-bottom:5px;"></div>
</div>
{$html}
<div id="tabs" style="visibility: hidden;">
	<ul>
	{foreach key=index item=tab from=$tabs}
		<li><a href="#pp-tabs-{$index}">{$tab.name}</a></li>
	{/foreach}
	</ul>
	{foreach key=index item=tab from=$tabs}
	<div id="pp-tabs-{$index}" class="tab-{$tab.type}">
		{$tab.html}
	</div>
	{/foreach}
</div>
<div class="clearfix"></div>
{hook h="ppropertiesAdmin" mode="displayPPropertiesFooter"}
</div>
{literal}
<script type="text/javascript">
	var ppFormParams = new Object();{/literal}
	{foreach key=key item=translation from=$jstranslations}
		ppFormParams.{$key} = "{$translation}";
	{/foreach}{literal}
	$(function() {
		function callAdminPproperties(userData, success, error) {
			var data = {
				controller : 'AdminPproperties',
				token: {/literal}'{$token_adminpproperties}'{literal},
			};

			if (userData != undefined) {
				$.extend(data, userData);
			}
			$.ajax({
				url: 'ajax-tab.php',
				type: 'POST',
				headers: {"cache-control": "no-cache"},
				cache: false,
				dataType: 'json',
				data: data,
				success: function(data) {
					if (success != undefined) {
						success(data);
					}
				},
				error: function(data) {
					if (error != undefined) {
						error(data);
					}
				}
			});
		}
		
		$("#tabs").tabs();
		$("#tabs").tabs("option", "active", {/literal}{$active}{literal});
		$("#tabs").css('visibility', 'visible');

		$('.pp-action-btn').on("click", function(event) {
			if ($(this).data('clicked')) {
				event.preventDefault();
			}
			else {
				$(this).data('clicked', true);
				$(this).css('opacity',0.5);
				setTimeout(function() {$('#ajax_running').fadeIn();}, 500);
			}
		});

		$('.pp-integration-module a').on("click", function(event) {
			event.preventDefault();
			$(this).blur();
			var module = $(this).closest('.pp-integration-module');
			var mode = $(this).data('mode');
			callAdminPproperties(
				{action: mode,
				  json: '{"module":"' + module.attr('data-module') + '","ver":"' + module.attr('data-ver') + '"}'
				},
				function(data) {
					var text = ppFormParams['integration_module_' + data.status + '_' + mode];
					if (data.status == 'success') {
						module.html(text).addClass('success');
						$('.runSetup').html(ppFormParams.rerun);
					}
					else if (data.status == 'rerun' || data.status == 'downloaded' || data.status == 'no_updates') {
						$('a[data-mode="' + mode + '"]', module).html(text).off('click').addClass('downloaded');
						$('.runSetup').html(ppFormParams.rerun);
					}
					else {
						module.html(ppFormParams.integration_module_error).addClass('failure');
					}
				},
				function(data) {
					module.html(ppFormParams.integration_module_error).addClass('failure');
				}
			);
		});

		$("#pp_info_block .pp_info_close").on("click", function(event) {
			event.preventDefault();
			$("#pp_info_block").fadeOut();
		});

		$("#pp_info_block #pp_info_ignore").on("click", function(event) {
			event.preventDefault();
			$("#pp_info_block").fadeOut();
			callAdminPproperties({action:'InfoIgnore'});
		});

		callAdminPproperties(
			{action:'InfoQuery'},
			function(json) {
				if (json.status == 'success') {
					$('#pp_info_block .pp_info_content').html(json.content);
					$('#pp_info_block').fadeIn('slow');
				}
			}
		);
	});
</script>
{/literal}
