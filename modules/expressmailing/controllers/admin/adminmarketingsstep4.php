<?php
/**
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
 */

include 'session_api.php';

/**
 * Step 4 : Inscription (no connected) and send campaign settings to the API (if connected)
 */
class AdminMarketingSStep4Controller extends ModuleAdminController
{
	private $campaign_id = null;

	public function __construct()
	{
		$this->name = 'adminmarketingsstep4';
		$this->bootstrap = true;
		$this->module = 'expressmailing';
		$this->context = Context::getContext();
		$this->lang = false;
		$this->default_form_language = $this->context->language->id;

		$this->campaign_id = (int)Tools::getValue('campaign_id');

		if (empty($this->campaign_id))
		{
			Tools::redirectAdmin('index.php?controller=AdminMarketingX&token='.Tools::getAdminTokenLite('AdminMarketingX'));
			exit;
		}

		parent::__construct();

		$this->session_api = new SessionApi();

		// On regarde si le compte est toujours en activité
		// --------------------------------------------------------------------------------
		if ($this->session_api->connectFromCredentials('sms'))
		{
			Tools::redirectAdmin('index.php?controller=AdminMarketingSStep5&campaign_id='.
				$this->campaign_id.
				'&token='.Tools::getAdminTokenLite('AdminMarketingSStep5'));
			exit;
		}
	}

	public function initToolbarTitle()
	{
		parent::initToolbarTitle();
		$this->toolbar_title = Translate::getModuleTranslation('expressmailing', 'Send a sms-mailing', 'adminmarketingsstep1');
	}
}