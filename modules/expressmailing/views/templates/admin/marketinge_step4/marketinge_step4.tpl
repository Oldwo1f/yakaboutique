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

<div class="row">
    <div class="col-lg-12">
        <form id="configuration_form" class="defaultForm form-horizontal AdminMarketingEStep4" action="index.php?controller=AdminMarketingEStep4&token={Tools::getAdminTokenLite('AdminMarketingEStep4')|escape:'html':'UTF-8'}" method="post" enctype="multipart/form-data" novalidate="">
            <input type="hidden" name="submitAddconfiguration" value="1">
            <div class="panel" id="fieldset_0">
                <div class="panel-heading">
                    <i class="icon-envelope-alt"></i> {l s='Recipients configuration (step 4)' mod='expressmailing'}
                </div>
                <div class="form-wrapper">
                    <div class="form-group hide">
                        <label class="control-label col-lg-3">
                            Ref :
                        </label>
                        <div class="col-lg-1">
                            <input type="hidden" name="campaign_id" id="campaign_id" value="{$campaign_id|intval}" class="" readonly="readonly">
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-lg-4" style="margin-bottom: 0px">
							{$free_filter_inputs|unescape}
						</div>
						<div class="col-lg-4" style="margin-bottom: 0px">
							{$tree_filter_inputs|unescape}
                        </div>
						<div class="col-lg-4" style="margin-bottom: 0px">
							{$paying_filter_inputs|unescape}
                        </div>
                    </div>
                </div>
                <div class="panel-footer" align="center">
					<button type="submit" value="1"	id="customer_form_submit_btn" name="refreshEmailingStep4" class="btn btn-default">
						<i class="process-icon-refresh"></i> {l s='Apply settings' mod='expressmailing'}
					</button>
                </div>
            </div>
        </form>
    </div>
</div>