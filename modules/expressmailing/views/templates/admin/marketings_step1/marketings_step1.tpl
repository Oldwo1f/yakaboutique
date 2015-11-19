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
        <form id="configuration_form" class="defaultForm form-horizontal AdminMarketingSStep1" action="index.php?controller=AdminMarketingSStep1&token={Tools::getAdminTokenLite('AdminMarketingSStep1')|escape:'html':'UTF-8'}" method="post" enctype="multipart/form-data" novalidate="">
            <div class="panel" id="fieldset_0">
                <div class="panel-heading">
                    <i class="icon-cogs"></i> {l s='Campaign configuration (step 1)' mod='expressmailing'}
                </div>
                <div class="form-wrapper">
                    <div class="form-group">
                        <div class="col-lg-7">
                            <div class="panel">
                                <div class="panel-heading">{l s='Campaign parameters' mod='expressmailing'}</div>
								{$input_parameters|unescape}
                            </div>
                        </div>
                        <div class="col-lg-5">
							<div class="panel" style="min-height: 24em; padding-bottom: 0; margin-bottom: 0">
                                <div class="panel-heading">{l s='SMS content' mod='expressmailing'} <label class="control-label required" style=" padding-top: 0px;"></label></div>
								{$sms_content|unescape}
                            </div>

                        </div>

                    </div>
                </div>
                <div class="panel-footer" align="center">
					<button type="submit" value="1"	id="customer_form_submit_btn" name="submitSmsStep1" class="btn btn-default pull-right">
						<i class="process-icon-next"></i> {l s='Next' mod='expressmailing'}
					</button>
                </div>
            </div>
        </form>
    </div>
</div>