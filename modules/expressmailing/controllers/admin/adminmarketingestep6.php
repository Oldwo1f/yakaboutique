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

include_once 'db_marketing.php';

/**
 * Step 6 : Upload recipients to API
 */
class AdminMarketingEStep6Controller extends ModuleAdminController
{
	private $campaign_id = null;
	private $session_api = null;
	private $campaign_infos = array ();

	public function __construct()
	{
		$this->name = 'adminmarketingestep6';
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

		// Get campaign informations
		// -------------------------
		$this->campaign_infos = $this->getCampaignInfos();
	}

	public function initToolbarTitle()
	{
		parent::initToolbarTitle();
		$this->toolbar_title = Translate::getModuleTranslation('expressmailing', 'Send an e-mailing', 'adminmarketingestep1');
	}

	public function displayAjax()
	{
		// Need a valid mailing-list id
		// ----------------------------
		if (empty($this->campaign_infos['campaign_api_list_id']))
			die('Empty campaign_api_list_id !');

		// Get a bloc of 500 customers (formated)
		// --------------------------------------
		$req = new DbQuery();
		$req->select('*');
		$req->from('expressmailing_email_recipients');
		$req->where('campaign_id = '.$this->campaign_id);
		$req->where('uploaded = \'0\'');
		$req->limit(1500);
		$recipients_list = Db::getInstance()->executeS($req, true, false);

		if (count($recipients_list) == 0)
		{
			if (Db::getInstance()->update('expressmailing_email', array (
					'campaign_date_update' => date('Y-m-d H:i:s'),
					'recipients_modified' => 0
				), 'campaign_id = '.$this->campaign_id, 0, false, false
			))
				die('ended');
		}

		$recipients = array ();
		$uploaded_id = array ();

		foreach ($recipients_list as $customer)
		{
			array_push($uploaded_id, $customer['id']);

			$recipients[] = array (
				'target' => $customer['target'],
				'lastname' => $customer['last_name'],
				'firstname' => $customer['first_name'],
				'ip_address' => $customer['ip_address'],
				'last_connexion_date' => $customer['last_connexion_date']
			);
		}

		// Upload the bloc
		// ---------------
		$response_array = array ();
		$parameters = array (
			'account_id' => $this->session_api->account_id,
			'list_id' => $this->campaign_infos['campaign_api_list_id'],
			'recipients' => $recipients
		);

		if ($this->session_api->call('email', 'recipients', 'add', $parameters, $response_array))
		{
			// Mark as uploaded the recipients treated
			// ---------------------------------------
			if (Db::getInstance()->update('expressmailing_email_recipients', array (
					'uploaded' => '1'
				), 'campaign_id = '.$this->campaign_id.' AND id IN ('.implode(',', array_map('intval', $uploaded_id)).')', 0, false, false
			))
				die('continue');
			else
				echo Db::getInstance()->getMsgError();
		}

		// Return the error to the AJAX process
		// ------------------------------------
		die(sprintf($this->module->l('Error during communication with Express-Mailing API : %s', 'adminmarketingestep6'),
				$this->session_api->getError()));
	}

	public function renderList()
	{
		if ($this->campaign_infos['recipients_modified'] == '1')
		{
			// Reset the uploaded flag on all recipients
			// -----------------------------------------
			Db::getInstance()->execute('UPDATE '._DB_PREFIX_.'expressmailing_email_recipients SET uploaded = 0 WHERE campaign_id = '.
										$this->campaign_id, false);

			// Then empty the list
			// -------------------
			$response_array = array ();
			$parameters = array(
				'account_id' => $this->session_api->account_id,
				'list_id' => $this->campaign_infos['campaign_api_list_id']
			);
			if (!$this->session_api->call('email', 'list', 'clear', $parameters, $response_array))
			{
				$this->errors[] = sprintf($this->module->l('Error during communication with Express-Mailing API : %s', 'adminmarketingestep5'),
									$this->session_api->getError());
				return false;
			}

			// Then display the upload ajax page
			// ---------------------------------
			$this->context->smarty->assign('campaign_id', $this->campaign_id);
			$ajax_upload = $this->getTemplatePath().'marketinge_step6/ajax_upload.tpl';
			$output = $this->context->smarty->fetch($ajax_upload);
		}
		else
		{
			Tools::redirectAdmin('index.php?controller=AdminMarketingEStep7&campaign_id='.
				$this->campaign_id.
				'&token='.Tools::getAdminTokenLite('AdminMarketingEStep7'));
			exit;
		}

		// Display the footer
		// ------------------
		$footer = $this->getTemplatePath().'footer.tpl';
		$output .= $this->context->smarty->fetch($footer);

		return $output;
	}

	private function getCampaignInfos()
	{
		$req = new DbQuery();
		$req->select('*');
		$req->from('expressmailing_email');
		$req->where('campaign_id = '.$this->campaign_id);
		return Db::getInstance()->getRow($req->build(), false);
	}

}
