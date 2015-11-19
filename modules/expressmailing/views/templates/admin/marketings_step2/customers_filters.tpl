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

<div class="form-group">
	<div class="col-lg-6">
		<div class="panel">
			<div class="panel-heading">
				{l s='Groups, Languages, Actives' mod='expressmailing'}
			</div>
			{$group_lang_filters|unescape}
		</div>
	</div>
	<div class="col-lg-6">{$product_tree_filters|unescape}</div>
	<div class="col-lg-4">{$paying_filters|unescape}</div>
</div>