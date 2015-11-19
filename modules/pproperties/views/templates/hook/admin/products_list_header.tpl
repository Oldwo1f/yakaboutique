{*
* @author    PS&More www.psandmore.com <support@psandmore.com>
* @copyright 2011-2015 PS&More
* @license   psandmore.com/licenses/sla
*}

<form action="{$REQUEST_URI}" method="post" class="form-horizontal">
	<div class="panel">
		<div class="panel-heading">
			<i class="icon-template"></i> {l s='Manage Product templates' mod='pproperties'}
		</div>
		{if isset($error_no_template)}
			<div class="alert alert-warning">{l s='Please choose template.' mod='pproperties'}</div>
		{/if}
		<div class="alert alert-info">{l s='You can assign or remove template for several products at once. Please choose the template below and select products from the list.' mod='pproperties'}</div>
		<div class="form-group">
			<div class="col-lg-6">
				<label class="control-label col-lg-3" for="id_pp_template">{l s='Template' mod='pproperties'}:</label>
				<div class="col-lg-9">
					<select name="id_pp_template" id="id_pp_template">
						{assign var=boTemplates value=PP::getAdminProductsTemplates(0)}
						{foreach from=$boTemplates item=template name=bo}
							<option value="{$template['id_pp_template']}">{$template['name']|pp_safeoutput:value}</option>
						{/foreach}
					</select>
				</div>
			</div>
			<div class="col-lg-6">
			</div>
		</div>
		<div class="clearfix"></div>
		<div class="panel-footer">
			<button type="submit" class="btn btn-default" name="submitAssignTemplate">
				<i class="icon-plus-sign"></i> {l s='Assign template' mod='pproperties'}
			</button>
			<button type="submit" class="btn btn-default" name="submitRemoveTemplate">
				<i class="icon-trash"></i> {l s='Remove template' mod='pproperties'}
			</button>
			<button type="submit" name="cancel" class="btn btn-default">
				<i class="icon-remove"></i>	{l s='Cancel' mod='pproperties'}
			</button>
		</div>
	</div>
</form>
<script type="text/javascript">
	$('[name="submitAssignTemplate"],[name="submitRemoveTemplate"]').on('click', function() {
		var form = $(this).closest('form');
		$('[name="manageTemplates[]"]', form).remove();
		$('input[type="checkbox"][name="productBox[]"]:checked').each(function() {
			form.append('<input type="hidden" name="manageTemplates[]" value="' + $(this).attr('value') + '" />');
		});
	});
</script>
