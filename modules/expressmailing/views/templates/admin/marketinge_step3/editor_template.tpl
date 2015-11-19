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
<div class="form-wrapper" style="margin-left: -20px; margin-right: -20px;">
	<div class="form-group hidden" >
		<label class="control-label col-lg-3">
			Ref :
		</label>
		<div class="col-lg-1 ">
			<input type="text" name="campaign_id" id="campaign_id" value="{$campaign_id|escape:'html':'UTF-8'}" class="" readonly="readonly"/>
		</div>
	</div>
	<div class="form-group">
		<div class="col-lg-12">
			<textarea name="campaign_html" id="campaign_html"   class="rte autoload_rte">{$campaign_html|unescape}</textarea>
		</div>
	</div>
</div>
