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

include_once 'em_tools.php';

class AdminMarketingXController extends ModuleAdminController
{
	private $broadcast_max_daily = null;
	private $default_remaining_email = null;
	private $default_remaining_fax = null;
	private $default_remaining_sms = null;

	public function __construct()
	{
		$this->name = 'adminmarketingx';
		$this->bootstrap = true;
		$this->module = 'expressmailing';
		$this->context = Context::getContext();
		$this->lang = false;
		$this->default_form_language = $this->context->language->id;
		$this->display = 'view';

		parent::__construct();

		// Get default free credits
		// ------------------------
		$this->broadcast_max_daily = $this->module->default_remaining_email;
		$this->default_remaining_email = $this->module->default_remaining_email;
		$this->default_remaining_fax = $this->module->default_remaining_fax;
		$this->default_remaining_sms = $this->module->default_remaining_sms;

		// API initialization
		// ------------------
		include _PS_MODULE_DIR_.$this->module->name.'/controllers/admin/session_api.php';
		$this->session_api = new SessionApi();
	}

	public function initToolbarTitle()
	{
		parent::initToolbarTitle();
		$this->toolbar_title = $this->module->l('Prepare a new campaign', 'adminmarketingx');
	}

	public function setMedia()
	{
		parent::setMedia();
		$this->addCSS(_PS_MODULE_DIR_.'expressmailing/views/css/icon-marketing.css');
		$this->addCSS(_PS_MODULE_DIR_.'expressmailing/views/css/expressmailing.css');
		$this->addJqueryUI('ui.dialog');
		$this->addJqueryUI('ui.draggable');
		$this->addJqueryUI('ui.resizable');
	}

	public function renderView()
	{
		$api_connected = false;

		$credential_email = '';
		$credential_fax = '';
		$credential_sms = '';

		$smarty_email_disabled = false;
		$smarty_fax_disabled = false;
		$smarty_sms_disabled = false;

		$smarty_email_stats_disabled = true;
		$smarty_fax_stats_disabled = true;
		$smarty_sms_stats_disabled = true;

		$smarty_remaining_email_credits = sprintf($this->module->l('%s credits', 'adminmarketingx'), $this->default_remaining_email);
		$smarty_remaining_fax_credits = sprintf($this->module->l('%s credits', 'adminmarketingx'), $this->default_remaining_fax);
		$smarty_remaining_sms_credits = sprintf($this->module->l('%s credits', 'adminmarketingx'), $this->default_remaining_sms);

		$smarty_email_promotion = '';
		$smarty_fax_promotion = '';
		$smarty_sms_promotion = '';

		$tool_tip = '';
		$output = '';

		// Checking the email session
		// ------------------------
		if ($this->session_api->connectFromCredentials('email'))
		{
			$credential_email = $this->session_api->account_login;
			$api_connected = true;

			// Enable/Disable 'Stat button'
			// ----------------------------
			$smarty_email_stats_disabled = (bool)Db::getInstance()->getValue('SELECT COUNT(*) = 0 FROM '._DB_PREFIX_.'expressmailing_email', false);
			$smarty_remaining_email_credits = '';

			// Recovering the max daily limit
			// ------------------------------
			$response_array = array();
			$parameters = array('account_id' => $this->session_api->account_id);

			if ($this->session_api->call('email', 'account', 'get_formula', $parameters, $response_array))
			{
				// Email account can have different name than API login !
				if (isset($response_array['account_name']))
					$credential_email = $response_array['account_name'];

				if (isset($response_array['broadcast_max_daily']))
					$this->broadcast_max_daily = $response_array['broadcast_max_daily'];

				if (isset($response_array['broadcast_restrictions']))
					if ($response_array['broadcast_restrictions'] == 'BLOCKED')
						$smarty_email_disabled = true;

				if (isset($response_array['balance']))
				{
					switch ((string)$response_array['balance'])
					{
						case '0':
							$smarty_remaining_email_credits = $this->module->l('0 credit', 'adminmarketingx');
							break;
						case '1':
							$smarty_remaining_email_credits = $this->module->l('1 credit', 'adminmarketingx');
							break;
						default:
							$smarty_remaining_email_credits = sprintf($this->module->l('%s credits', 'adminmarketingx'), (string)$response_array['balance']);
							break;
					}
				}
			}
		}

		// Checking the fax session
		// ------------------------
		if ($this->session_api->connectFromCredentials('fax'))
		{
			$credential_fax = $this->session_api->account_login;
			$api_connected = true;

			// Enable/Disable 'Stat button'
			// ----------------------------
			$smarty_fax_stats_disabled = (bool)Db::getInstance()->getValue('SELECT COUNT(*) = 0 FROM '._DB_PREFIX_.'expressmailing_fax', false);
			$smarty_remaining_fax_credits = '';

			// Recovering the credit balance
			// -----------------------------
			$response_array = array();
			$parameters = array('account_id' => array($this->session_api->account_id));

			if ($this->session_api->call('fax', 'account', 'enum_credit_balances', $parameters, $response_array))
			{
				if (empty($response_array))
					$smarty_remaining_fax_credits = $this->module->l('0 credit', 'adminmarketingx');

				foreach ($response_array as $credit)
				{
					switch ((string)$credit['balance'])
					{
						case '0':
							$smarty_fax_disabled = false;
							$remaining_tmp = $this->module->l('0 credit %s', 'adminmarketingx');
							break;
						case '1':
							$smarty_fax_disabled = false;
							$remaining_tmp = $this->module->l('1 credit %s', 'adminmarketingx');
							break;
						default:
							$smarty_fax_disabled = false;
							$remaining_tmp = sprintf($this->module->l('%s credits %s', 'adminmarketingx'), $credit['balance'], '%s');
							break;
					}
					$smarty_remaining_fax_credits .= sprintf($remaining_tmp, '&laquo;&nbsp;'.$credit['credit_name'].'&nbsp;&raquo;<br/>');
				}
			}
		}

		// Checking the sms session
		// ------------------------
		if ($this->session_api->connectFromCredentials('sms'))
		{
			$credential_sms = $this->session_api->account_login;
			$api_connected = true;

			// Enable/Disable 'Stat button'
			// ----------------------------
			$smarty_sms_stats_disabled = (bool)Db::getInstance()->getValue('SELECT COUNT(*) = 0 FROM '._DB_PREFIX_.'expressmailing_sms', false);
			$smarty_remaining_sms_credits = '';

			// Recovering the credit balance
			// -----------------------------
			$response_array = array();
			$parameters = array('account_id' => $this->session_api->account_id);

			if ($this->session_api->call('sms', 'account', 'enum_credit_balances', $parameters, $response_array))
			{
				if (empty($response_array))
					$smarty_remaining_sms_credits = $this->module->l('0 credit', 'adminmarketingx');

				foreach ($response_array as $credit)
				{
					switch ((string)$credit['balance'])
					{
						case '0':
							$smarty_sms_disabled = false;
							$remaining_tmp = $this->module->l('0 credit', 'adminmarketingx');
							break;
						case '1':
							$smarty_sms_disabled = false;
							$remaining_tmp = $this->module->l('1 credit %s', 'adminmarketingx');
							break;
						default:
							$smarty_sms_disabled = false;
							$remaining_tmp = sprintf($this->module->l('%s credits %s', 'adminmarketingx'), $credit['balance'], '%s');
							break;
					}
					$smarty_remaining_sms_credits .= sprintf($remaining_tmp, '&laquo;&nbsp;'.$credit['credit_name'].'&nbsp;&raquo;<br>');
				}
			}
		}

		if (!$api_connected)
		{
			$ajax = Tools::getValue('ajax');
			if (!$ajax)
				Tools::redirectAdmin('index.php?controller=AdminModules&token='.Tools::getAdminTokenLite('AdminModules').
					'&configure=expressmailing&tab_module=emailing&module_name=expressmailing');

			// Add default free credits
			// ------------------------
			$smarty_remaining_email_credits = sprintf($this->module->l('%d free credits per day', 'adminmarketingx'), $this->default_remaining_email);
			$smarty_remaining_fax_credits = sprintf($this->module->l('%d free credits', 'adminmarketingx'), $this->default_remaining_fax);
			$smarty_remaining_sms_credits = sprintf($this->module->l('%d free credits', 'adminmarketingx'), $this->default_remaining_sms);
		}
		else
		{
			// Tool tip with login informations
			// --------------------------------
			if (($credential_email == $credential_fax) && ($credential_fax == $credential_sms))
				$tool_tip = $this->module->l('Account ID :', 'adminmarketingx').'<br>'.$credential_email;
			else
			{
				$tool_tip = $this->module->l('Email account :', 'adminmarketingx').'&nbsp;';
				$tool_tip .= empty($credential_email) ? $this->module->l('None', 'adminmarketingx') : $credential_email;
				$tool_tip .= '<br />'.$this->module->l('Fax account :', 'adminmarketingx').'&nbsp;';
				$tool_tip .= empty($credential_fax) ? $this->module->l('None', 'adminmarketingx') : $credential_fax;
				$tool_tip .= '<br />'.$this->module->l('Sms account :', 'adminmarketingx').'&nbsp;';
				$tool_tip .= empty($credential_sms) ? $this->module->l('None', 'adminmarketingx') : $credential_sms;
			}
		}

		// Get all the tickets available for Prestashop
		// And check if there is an ongoing promotion
		// --------------------------------------------
		$smarty_email_promotion = false;
		$smarty_fax_promotion = false;
		$smarty_sms_promotion = false;

		$smarty_email_lowest_price = null;
		$smarty_fax_lowest_price = null;
		$smarty_sms_lowest_price = null;

		$response_array = array();
		$parameters = array(
			'application_id' => $this->session_api->application_id,
			'account_id' => $this->session_api->account_id
		);

		$prices = array();
		if ($this->session_api->callExternal('http://www.express-mailing.com/api/cart/ws.php',
											'common', 'account', 'enum_credits', $parameters, $prices))
		{
			if (isset($prices['email']))
			{
				foreach ($prices['email'] as $ticket)
				{
					$unit_price = null;
					if (isset($ticket['promo_ending']) && $ticket['promo_ending'] > time())
					{
						$smarty_fax_promotion = true;
						if (isset($ticket['promo_price'], $ticket['product_units']))
							$unit_price = $ticket['promo_price'] / $ticket['product_units'];
					}
					elseif (isset($ticket['normal_price'], $ticket['product_units']))
						$unit_price = $ticket['normal_price'] / $ticket['product_units'];

					if (!empty($unit_price) && ($smarty_email_lowest_price == null || $unit_price < $smarty_email_lowest_price))
						$smarty_email_lowest_price = $unit_price;
				}
			}

			if (isset($prices['fax']))
			{
				foreach ($prices['fax'] as $ticket)
				{
					$unit_price = null;
					if (isset($ticket['promo_ending']) && $ticket['promo_ending'] > time())
					{
						$smarty_fax_promotion = true;
						if (isset($ticket['promo_price'], $ticket['product_units']))
							$unit_price = $ticket['promo_price'] / $ticket['product_units'];
					}
					elseif (isset($ticket['normal_price'], $ticket['product_units']))
						$unit_price = $ticket['normal_price'] / $ticket['product_units'];

					if (!empty($unit_price) && ($smarty_fax_lowest_price == null || $unit_price < $smarty_fax_lowest_price))
						$smarty_fax_lowest_price = $unit_price;
				}
			}

			if (isset($prices['sms']))
			{
				foreach ($prices['sms'] as $ticket)
				{
					$unit_price = null;
					if (isset($ticket['promo_ending']) && $ticket['promo_ending'] > time())
					{
						$smarty_sms_promotion = true;
						if (isset($ticket['promo_price'], $ticket['product_units']))
							$unit_price = $ticket['promo_price'] / $ticket['product_units'];
					}
					elseif (isset($ticket['normal_price'], $ticket['product_units']))
						$unit_price = $ticket['normal_price'] / $ticket['product_units'];

					if (!empty($unit_price) && ($smarty_sms_lowest_price == null || $unit_price < $smarty_sms_lowest_price))
						$smarty_sms_lowest_price = $unit_price;
				}
			}
		}

		// Disable 'new mailing' buttons
		// -----------------------------
		if ($smarty_email_disabled)
			$smarty_remaining_email_credits = '<span class="red no-bold">'.$this->module->l('Your account is disabled', 'adminmarketingx').'</span>';
		if ($smarty_fax_disabled)
			$smarty_remaining_fax_credits = '<span class="red no-bold">'.$this->module->l('Your account is disabled', 'adminmarketingx').'</span>';
		if ($smarty_sms_disabled)
			$smarty_remaining_sms_credits = '<span class="red no-bold">'.$this->module->l('Your account is disabled', 'adminmarketingx').'</span>';

		// Smarty variables assign
		// -----------------------
		$tools = new EMTools;
		$this->context->smarty->assign(
			array(
				'smarty_email_disabled' => $smarty_email_disabled,
				'smarty_fax_disabled' => $smarty_fax_disabled,
				'smarty_sms_disabled' => $smarty_sms_disabled,
				'smarty_email_stats_disabled' => $smarty_email_stats_disabled,
				'smarty_fax_stats_disabled' => $smarty_fax_stats_disabled,
				'smarty_sms_stats_disabled' => $smarty_sms_stats_disabled,
				'smarty_remaining_email_credits' => $smarty_remaining_email_credits,
				'smarty_remaining_fax_credits' => $smarty_remaining_fax_credits,
				'smarty_remaining_sms_credits' => $smarty_remaining_sms_credits,
				'smarty_email_lowest_price' => $smarty_email_lowest_price,
				'smarty_fax_lowest_price' => $smarty_fax_lowest_price,
				'smarty_sms_lowest_price' => $smarty_sms_lowest_price,
				'smarty_email_promotion' => $smarty_email_promotion,
				'smarty_fax_promotion' => $smarty_fax_promotion,
				'smarty_sms_promotion' => $smarty_sms_promotion,
				'api_connected' => $api_connected,
				'broadcast_max_daily' => $this->broadcast_max_daily,
				'tool_tip' => $tool_tip,
				'tool_date' => $tools
			)
		);

		// And we display step 0
		// ---------------------
		$step0 = $this->getTemplatePath().'marketing_step0/marketing_step0.tpl';
		$output .= $this->context->smarty->fetch($step0);

		// And the lowest prices part
		// --------------------------
		$lowest = $this->getTemplatePath().'marketing_step0/buy_step0.tpl';
		$output .= $this->context->smarty->fetch($lowest);

		// And we end with the footer
		// --------------------------
		$footer = $this->getTemplatePath().'footer.tpl';
		$output .= $this->context->smarty->fetch($footer);

		return $output;
	}

	public function postProcess()
	{
		if (Tools::isSubmit('campaign_type'))
		{
			if (Tools::getValue('campaign_type') == 'marketing_f')
			{
				// Create a new fax campaign
				// -------------------------
				Db::getInstance()->insert('expressmailing_fax', array(
					'campaign_state' => 0,
					'campaign_date_create' => date('Y-m-d H:i:s'),
					'campaign_date_send' => date('Y-m-d H:i:00', time() + 60),
					'campaign_week_limit' => 'LMCJVS'
				));
				$this->campaign_id = Db::getInstance()->Insert_ID();

				// Redirect to send fax mailing
				// ----------------------------
				Tools::redirectAdmin('index.php?controller=AdminMarketingFStep1&campaign_id='.
					$this->campaign_id.
					'&token='.Tools::getAdminTokenLite('AdminMarketingFStep1'));
				exit;
			}
			elseif (Tools::getValue('campaign_type') == 'marketing_s')
			{
				// Create a new sms campaign
				// -------------------------
				Db::getInstance()->insert('expressmailing_sms', array(
					'campaign_state' => 0,
					'campaign_date_create' => date('Y-m-d H:i:s'),
					'campaign_date_send' => date('Y-m-d H:i:00', time() + 60)
				));
				$this->campaign_id = Db::getInstance()->Insert_ID();

				// Redirect to send sms mailing
				// ----------------------------
				Tools::redirectAdmin('index.php?controller=AdminMarketingSStep1&campaign_id='.
					$this->campaign_id.
					'&token='.Tools::getAdminTokenLite('AdminMarketingSStep1'));
				exit;
			}
			elseif (Tools::getValue('campaign_type') == 'marketing_e')
			{
				// Recovering the max broadcast limit per day
				// ------------------------------------------

				if ($this->session_api->connectFromCredentials('email'))
				{
					$response_array = array();
					$parameters = array('account_id' => $this->session_api->account_id);

					if ($this->session_api->call('email', 'account', 'get_formula', $parameters, $response_array))
						if (isset($response_array['broadcast_max_campaign']))
							$this->broadcast_max_daily = (int)$response_array['broadcast_max_campaign'];
				}

				// Create a new emailing campaign
				// ------------------------------
				Db::getInstance()->insert('expressmailing_email', array(
					'campaign_state' => 0,
					'campaign_lang' => Context::getContext()->country->iso_code,
					'campaign_date_create' => date('Y-m-d H:i:s'),
					'campaign_date_send' => date('Y-m-d H:i:00', time() + 60),
					'campaign_day_limit' => $this->broadcast_max_daily * 75 / 100,
					'campaign_week_limit' => 'LMCJVS'
				));

				$this->campaign_id = Db::getInstance()->Insert_ID();

				// Redirect to emailing step1
				// --------------------------
				Tools::redirectAdmin('index.php?controller=AdminMarketingEStep1&campaign_id='.
					$this->campaign_id.
					'&token='.Tools::getAdminTokenLite('AdminMarketingEStep1'));
				exit;
			}
			else
			{
				// Redirect to home
				// ----------------
				Tools::redirectAdmin('index.php?controller=AdminMarketingX&token='.Tools::getAdminTokenLite('AdminMarketingX'));
				exit;
			}
		}
	}

	public function displayAjax()
	{
		$media = Tools::getValue('media');

		switch ($media)
		{
			case 'email':
				$category_code = 'email_daily';
				break;
			case 'fax':
				$category_code = 'fax_tickets';
				break;
			case 'sms':
				$category_code = 'sms_tickets';
				break;
			default:
				die(Tools::displayError($this->module->l('Unable to get product list', 'adminmarketingestep1'),
						$this->session_api->getError()));
		}

		$response_array = null;
		$parameters = array(
			'application_id' => $this->session_api->application_id,
			'category_code' => $category_code,
			'module_version' => $this->module->version,
			'prestashop_version' => _PS_VERSION_,
			'language' => $this->context->language->iso_code
		);

		if ($this->session_api->connectFromCredentials('email'))
			$parameters['account_id'] = $this->session_api->account_id;

		if ($this->session_api->callExternal('http://www.express-mailing.com/api/cart/ws.php', 'common', 'order', 'get_products_tpl',
			$parameters, $response_array))
		{
			if (isset($response_array['template']) && !empty($response_array['template']))
			{
				$template_content = mb_convert_encoding($response_array['template'], 'UTF-8', 'BASE64');
				die($this->context->smarty->fetch('string:'.$template_content));
			}
		}

		die(Tools::displayError(sprintf($this->module->l('Unable to get product list : %s', 'adminmarketingestep1'),
						$this->session_api->getError())));
	}
}