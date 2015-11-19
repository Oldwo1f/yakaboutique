{*
* Product Properties Extension
*
* @author    PS&More www.psandmore.com <support@psandmore.com>
* @copyright 2011-2015 PS&More
* @license   psandmore.com/licenses/sla
*}

{$id_pp_template = PP::getProductTemplateId($id_product)}
<div id="product-modulepproperties" class="panel product-tab">
	<input type="hidden" name="submitted_tabs[]" value="ModulePproperties" />
	<h3 class="tab"><i class="icon-template"></i> {$s_header}</h3>
	<div class="pp-tip pp-tip-container hint" style="display:none;">
		<div class="pp-tip-title"></div>
		<div class="pp-tip-description-container">
			<span class="pp-tip-description">{$s_advice}</span>
		</div>
	</div>
	<div class="alert alert-warning hint" style="display:none;">{$s_hint}</div>
	{if !$integrated}<div class="alert alert-warning">{$integration_warning}</div>{/if}
	{if !$multidimensional}<div class="alert alert-warning pp_multidimensional" style="display:none;">{$multidimensional_warning}</div>{/if}
	<div class="form-group">
		<label class="control-label col-lg-3" for="id_pp_template">{$s_product_template}:</label>
		<div class="col-lg-9">
			<div class="col-lg-9">
				<select name="id_pp_template" id="id_pp_template" style="min-width:_230px;"{if !$integrated}disabled{/if}>
					{assign var=boTemplates value=PP::getAdminProductsTemplates($id_pp_template)}
					{foreach from=$boTemplates item=template name=bo}
						<option value="{$template['id_pp_template']}"{if $id_pp_template == $template['id_pp_template']} selected{/if}>{$template['name']|pp_safeoutput:value}</option>
					{/foreach}
				</select>
			</div>
			<div class="col-lg-3 pp_template_toggle">
				<div class="btn-group-action">
					<div class="btn-group">
						<a class="btn btn-default edit-pp-template-link" href="index.php?controller=adminmodules&amp;configure=pproperties&amp;token={Tools::getAdminTokenLite('AdminModules')}&amp;tab_module=administration&amp;module_name=pproperties&amp;clickEditTemplate&amp;mode=edit&amp;pp=1&amp;id=" target="_blank"><i class="icon-pencil"></i> {$s_edit_template}</a>
						<button data-toggle="dropdown" class="btn btn-default dropdown-toggle"><span class="caret"></span>&nbsp;
						</button>
						<ul class="dropdown-menu">
							<li>
								<a href="index.php?controller=adminmodules&amp;configure=pproperties&amp;token={Tools::getAdminTokenLite('AdminModules')}&amp;tab_module=administration&amp;module_name=pproperties" target="_blank">
									<i class="icon-template"></i> {$s_configure_templates}
								</a>
							</li>
						</ul>
					</div>
				</div>
			</div>
			<p class="col-lg-12 pp_template_desc"> </p>
		</div>
	</div>
	<div class="form-group pp_template_toggle">
		<label class="control-label col-lg-3">{$s_pp_qty_policy}:</label>
		<div class="col-lg-9 pp_qty_policy_expl pp_template_value"></div>
	</div>
	<div class="form-group pp_template_toggle">
		<label class="control-label col-lg-3">{$s_pp_qty_mode}:</label>
		<div class="col-lg-9 pp_qty_mode_expl pp_template_value"></div>
	</div>
	<div class="form-group pp_template_toggle">
		<label class="control-label col-lg-3">{$s_pp_display_mode}:</label>
		<div class="col-lg-9 pp_display_mode_expl pp_template_value"></div>
	</div>
	<div class="form-group pp_template_toggle">
		<label class="control-label col-lg-3">{$s_pp_price_display_mode}:</label>
		<div class="col-lg-9 pp_price_display_mode_expl pp_template_value"></div>
	</div>
	<div class="form-group pp_template_toggle">
		<label class="control-label col-lg-3">{$s_pp_price_text}:</label>
		<div class="col-lg-9 pp_price_text pp_template_value"></div>
	</div>
	<div class="form-group pp_template_toggle">
		<label class="control-label col-lg-3">{$s_pp_qty_text}:</label>
		<div class="col-lg-9 pp_qty_text pp_template_value"></div>
	</div>
	<div class="form-group pp_template_toggle">
		<label class="control-label col-lg-3">{$s_pp_unity_text}:</label>
		<div class="col-lg-9 pp_unity_text pp_template_value"></div>
	</div>
	<div class="form-group pp_template_toggle">
		<label class="control-label col-lg-3">{$s_pp_unit_price_ratio}:</label>
		<div class="col-lg-9 pp_unit_price_ratio pp_template_value"></div>
	</div>

	<div class="form-group pp_template_toggle">
		<label class="control-label col-lg-3">{$s_pp_minimal_price_ratio}:</label>
		<div class="col-lg-9 pp_minimal_price_ratio pp_template_value"></div>
	</div>
	<div class="form-group pp_template_toggle">
		<label class="control-label col-lg-3">{$s_pp_minimal_quantity}:</label>
		<div class="col-lg-9"><span class="pp_minimal_quantity pp_template_value"></span> <span class="pp_bo_qty_text pp_template_value"></span></div>
	</div>
	<div class="form-group pp_template_toggle">
		<label class="control-label col-lg-3">{$s_pp_default_quantity}:</label>
		<div class="col-lg-9"><span class="pp_default_quantity pp_template_value"></span> <span class="pp_bo_qty_text pp_template_value"></span></div>
	</div>
	<div class="form-group pp_template_toggle">
		<label class="control-label col-lg-3">{$s_pp_qty_step}:</label>
		<div class="col-lg-9"><span class="pp_qty_step pp_template_value"></span></div>
	</div>
	<div class="form-group pp_template_toggle">
		<label class="control-label col-lg-3">{$s_pp_explanation}:</label>
		<div class="col-lg-9"><span class="pp_explanation pp_template_value"></span></div>
	</div>
</div>
{if isset($hook_html) && is_array($hook_html)}
	{foreach from=$hook_html item=html key=module}
		<div class="hook-{$module}">{$html}</div>
	{/foreach}
{/if}
