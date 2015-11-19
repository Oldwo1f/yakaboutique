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

/**
 * Step 8 : Final validation
 */
class AdminMarketingEStep8Controller extends ModuleAdminController
{
	private $campaign_id = null;
	private $session_api = null;

	private $campaign_api_message_id = null;
	private $campaign_sended = false;

	public function __construct()
	{
		$this->name = 'adminmarketingestep8';
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

		// On retrouve l'ID du message sur l'API
		// -------------------------------------
		$this->campaign_api_message_id = $this->getApiMessageId();

		// API initialization
		// ------------------
		include _PS_MODULE_DIR_.$this->module->name.'/controllers/admin/session_api.php';
		$this->session_api = new SessionApi();

		// Checking the session
		// --------------------
		if (!$this->session_api->connectFromCredentials('email'))
		{
			Tools::redirectAdmin('index.php?controller=AdminMarketingEStep5&token='.Tools::getAdminTokenLite('AdminMarketingEStep5'));
			exit;
		}
	}

	public function initToolbarTitle()
	{
		parent::initToolbarTitle();
		$this->toolbar_title = Translate::getModuleTranslation('expressmailing', 'Send an e-mailing', 'adminmarketingestep1');
	}

	public function setMedia()
	{
		parent::setMedia();
		$this->addJS(_PS_MODULE_DIR_.$this->module->name.'/views/js/emcharts_e_step8.js', 'all');
	}

	public function renderList()
	{
		// On obtient les stats avant validation (donut)
		// ---------------------------------------------
		$validation_stats = $this->getValidationStatisticsAPI();

		$this->context->smarty->assign(array (
			'campaign_id' => $this->campaign_id,
			'nb_total' => $validation_stats['count_total_recipients'],
			'nb_redlist' => $validation_stats['count_recipients_on_personnal_redlist'],
			'nb_suspended' => $validation_stats['count_cancelled_recipients'],
			'nb_already_sent' => $validation_stats['count_sent_recipients'],
			'nb_to_send' => $validation_stats['count_recipients_to_send'],
			'mail_weight' => $this->humanFilesize($validation_stats['email_weight']),
			'mail_cost' => $validation_stats['email_cost'],
			'campaign_sended' => $this->campaign_sended
		));

		$display = $this->getTemplatePath().'footer_validation.tpl';
		$this->context->smarty->assign('footer_validation', $this->context->smarty->fetch($display));

		$diplay = $this->getTemplatePath().'marketinge_step8/marketinge_step8.tpl';
		$footer = $this->getTemplatePath().'footer.tpl';

		$output = $this->context->smarty->fetch($diplay);
		$output .= $this->context->smarty->fetch($footer);

		return $output;
	}

	public function postProcess()
	{
		if (Tools::isSubmit('sendCampaign'))
		{
			$yes = (string)Tools::getValue('YES', '');
			$yes = Tools::strtoupper($yes);

			if ($yes == Tools::strtoupper(Translate::getModuleTranslation('expressmailing', 'YES', 'footer_validation')))
			{
				if ($this->sendCampaignAPI())
				{
					$this->confirmations[] = $this->module->l('Your campaign is now sending ...', 'adminmarketingestep8');

					// Tracking Prestashop
					// -------------------
					return Db::getInstance()->update('expressmailing_email', array(
						'campaign_state' => '1',
						'campaign_api_validation' => '1'
						), 'campaign_id = '.$this->campaign_id
					);
				}
			}
			else
			{
				$this->errors[] = sprintf($this->module->l('Please fill the %s field', 'adminmarketingestep8'),
					'&laquo;&nbsp;'.
					Translate::getModuleTranslation('expressmailing', 'YES', 'footer_validation').
					'&nbsp;&raquo;');
			}

			return false;
		}
	}

	private function getValidationStatisticsAPI()
	{
		$response_array = array();
		$parameters = array(
			'account_id' => $this->session_api->account_id,
			'campaign_id' => $this->campaign_api_message_id
		);

		if ($this->session_api->call('email', 'campaign', 'get_validation_statistics', $parameters, $response_array))
			return $response_array;

		$this->errors[] = sprintf($this->module->l('Error during communication with Express-Mailing API : %s', 'adminmarketingestep8'),
							$this->session_api->getError());
		return false;
	}

	private function sendCampaignAPI()
	{
		$response_array = null;
		$parameters = array(
			'account_id' => $this->session_api->account_id,
			'campaign_id' => $this->campaign_api_message_id
		);

		if ($this->session_api->call('email', 'campaign', 'send', $parameters, $response_array))
		{
			$this->campaign_sended = true;
			return true;
		}
		else
		{
			$this->errors[] = sprintf($this->module->l('Error during communication with Express-Mailing API : %s', 'adminmarketingestep8'),
								$this->session_api->getError());
			return false;
		}
	}

	private function getApiMessageId()
	{
		$req = new DbQuery();
		$req->select('campaign_api_message_id');
		$req->from('expressmailing_email');
		$req->where('campaign_id = '.$this->campaign_id);
		return Db::getInstance()->getValue($req->build(), false);
	}

	private function humanFilesize($bytes, $decimals = 2)
	{
		$size = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
		$factor = floor((Tools::strlen($bytes) - 1) / 3);
		return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)).' '.$size[$factor];
	}

}
