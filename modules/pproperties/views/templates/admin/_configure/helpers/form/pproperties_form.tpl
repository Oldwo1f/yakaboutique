{*
* Product Properties Extension
*
* @author    PS&More www.psandmore.com <support@psandmore.com>
* @copyright 2011-2015 PS&More
* @license   psandmore.com/licenses/sla
*}

{extends file="helpers/form/form.tpl"}
{block name="input" append}
	{if $input.type == 'div'}
		<div{if isset($input.class)} class="{$input.class}"{/if}>{$input.name}</div>
	{elseif $input.type == 'radio'}
		{if isset($input.checkboxes)}
			{foreach $input.checkboxes as $checkbox}
				{foreach $checkbox.values.query as $value}
					{assign var=id_checkbox value=$input.name|cat:'_'|cat:$value[$checkbox.values.id]}
					<div class="checkbox">
						<label for="{$id_checkbox}">
							<input type="checkbox"
								name="{$id_checkbox}"
								id="{$id_checkbox}"
								class="{if isset($input.class)}{$input.class}{/if}"
								{if isset($value.val)}value="{$value.val|escape:'html':'UTF-8'}"{/if}
								{if isset($fields_value[$id_checkbox]) && $fields_value[$id_checkbox]}checked="checked"{/if} />
							{$value[$checkbox.values.name]}
						</label>
					</div>
				{/foreach}
			{/foreach}
		{/if}
	{elseif $input.type == 'clearcache'}
		<div class="clearfix row-padding-top">
			<a class="btn btn-default pp-action-btn" href="{$current}&amp;token={$token}&amp;clickClearCache=1">
				<i class="icon-eraser"></i>
				{$input.name}
			</a>
		</div>
	{/if}
{/block}
{block name="other_input" append}
	{if $key == 'warning'}
		<div class="alert alert-warning{if isset($field.class)} {$field.class}{/if}">{$field.text}</div>
	{elseif $key == 'multidimensional-feature'}
		<div class="multidimensional-feature">
			{if $fields.form.multidimensional}
				<a class="readme_url" target="_blank" href="{$field.readme_url}"><i class="icon-book"></i>{$field.readme_pdf}</a>
			{/if}
			<span class="feature">{$field.text}</span>
			<span class="clear"></span>
			{if !$fields.form.multidimensional}
				<div class="alert alert-warning dimensions-toggle">{$field.disabled} <a href="http://store.psandmore.com" target="_blank">store.psandmore.com</a>.</div>
			{/if}
		</div>
	{elseif $key == 'dimensions-table'}
		<table id="multidimensional-table" class="table dimensions-toggle">
			<thead>
				{foreach $field.th as $th}
				<th>{$th}</th>
				{/foreach}
				{if $fields.form.multidimensional}
					{hook h="multidimensionalAdmin" mode="thead" id_pp_template=$fields.form.id_pp_template}
				{/if}
			</thead>
			{foreach from=$field.tbody item=tbody name=tbody_loop}
				<tbody>
				{foreach $tbody as $tr}
					<tr>
					{foreach $tr as $td}
						{foreach $td as $input}
						<td class="td-{$input.type}">
							{if $input.type == 'text'}
								{assign var='value_text' value=$fields_value[$input.name]}
								<input type="text"
									name="{$input.name}"
									class="{if isset($input.class)}{$input.class|escape:'html':'UTF-8'}{/if}"
									id="{if isset($input.id)}{$input.id}{else}{$input.name}{/if}"
									{if isset($input.data_type)} data-type="{$input.data_type}"{/if}
									{if isset($input.data_position)} data-position="{$input.data_position}"{/if}
									value="{if isset($input.string_format) && $input.string_format}{$value_text|string_format:$input.string_format|escape:'html':'UTF-8'}{else}{$value_text|escape:'html':'UTF-8'}{/if}"
									{if isset($input.size)} size="{$input.size}"{/if}
									{if isset($input.class)} class="{$input.class}"{/if}
									{if isset($input.readonly) && $input.readonly} readonly="readonly"{/if}
									{if isset($input.disabled) && $input.disabled} disabled="disabled"{/if}
									/>
							{elseif $input.type == 'select'}
								<select name="{$input.name|escape:'html':'UTF-8'}"
										class="{if isset($input.class)}{$input.class|escape:'html':'UTF-8'}{/if}"
										id="{if isset($input.id)}{$input.id|escape:'html':'UTF-8'}{else}{$input.name|escape:'html':'UTF-8'}{/if}"
										{if isset($input.data_type)} data-type="{$input.data_type}"{/if}
										{if isset($input.data_position)} data-position="{$input.data_position}"{/if}>
										{foreach $input.options.query AS $option}
											<option value="{$option[$input.options.id]}"
													{if $fields_value[$input.name] == $option[$input.options.id]}
														selected="selected"
													{/if}
											>{$option[$input.options.name]}</option>
										{/foreach}
								</select>
							{/if}
						</td>
						{/foreach}
					{/foreach}
					{if $fields.form.multidimensional}
						{hook h="multidimensionalAdmin" mode="tbody" id_pp_template=$fields.form.id_pp_template iteration=$smarty.foreach.tbody_loop.iteration}
					{/if}
					</tr>
				{/foreach}
				</tbody>
			{/foreach}
		</table>
	{elseif $key == 'help-block'}
		<p class="help-block{if isset($field.class)} {$field.class|escape:'html':'UTF-8'}{/if}">
		{foreach $field.text as $v}
			{$v}<br/>
		{/foreach}
		</p>
	{elseif $key == 'multidimensionalAdmin'}
		{hook h="multidimensionalAdmin" mode="afterTable" id_pp_template=$fields.form.id_pp_template}
	{/if}
{/block}
{block name="after" append}
	{if isset($fields.form.script)}
		{foreach $fields.form.script as $script}
			{if $script == 'multidimensional'}
				<script type="text/javascript">
					$("select#pp_ext_method").on("change keyup", function () {
						if ($(this).get(0).selectedIndex > 0)
							$(".dimensions-toggle, #fieldset_dimensions_form .panel-footer").fadeIn("slow");
						else
							$(".dimensions-toggle, #fieldset_dimensions_form .panel-footer").fadeOut("slow");
					});
					{if !$fields.form.multidimensional}
						$(".dimensions-toggle input, .dimensions-toggle select").attr("disabled", "disabled");
						$("#fieldset_dimensions_form .panel-footer").remove();
					{/if}
					$(document).ready(function() {
						$("select#pp_ext_method").change();
					});
				</script>
			{/if}
		{/foreach}
	{/if}
{/block}