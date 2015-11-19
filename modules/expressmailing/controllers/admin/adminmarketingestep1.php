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
 * Step 1 : Campaign name, date and broadcast limitations
 */
class AdminMarketingEStep1Controller extends ModuleAdminController
{
	private $campaign_id = null;
	private $default_max_daily = 300;

	public function __construct()
	{
		$this->name = 'adminmarketingestep1';
		$this->bootstrap = true;
		$this->module = 'expressmailing';
		$this->context = Context::getContext();
		$this->lang = true;
		$this->default_form_language = $this->context->language->id;

		$this->campaign_id = (int)Tools::getValue('campaign_id');

		if (empty($this->campaign_id))
		{
			Tools::redirectAdmin('index.php?controller=AdminMarketingX&token='.Tools::getAdminTokenLite('AdminMarketingX'));
			exit;
		}

		parent::__construct();

		$online = true;
		$ip_string = (string)Tools::getRemoteAddr();
		$ip_long = ip2long($ip_string);

		if (Tools::substr(Configuration::get('PS_SHOP_DOMAIN'), 0, 9) == 'localhost')
			$online = false;
		if ($ip_long >= ip2long('10.0.0.0') && $ip_long <= ip2long('10.255.255.255'))
			$online = false;
		if ($ip_long >= ip2long('127.0.0.0') && $ip_long <= ip2long('127.255.255.255'))
			$online = false;
		if ($ip_long >= ip2long('172.16.0.0') && $ip_long <= ip2long('172.31.255.255'))
			$online = false;
		if ($ip_long >= ip2long('192.168.0.0') && $ip_long <= ip2long('192.168.255.255'))
			$online = false;
		elseif ($ip_string == '::1')
			$online = false; /* IPv6 */

		if (!$online)
		{
			$a = $this->module->l('You are currently testing your Prestashop on a local server :', 'adminmarketingestep1');
			$b = $this->module->l('To enjoy the full IMAGE & TRACKING features, you need use a Prestashop online server !', 'adminmarketingestep1');
			$this->warnings[] = $a.' '.Tools::getRemoteAddr();
			$this->warnings[] = $b;
		}

		// API initialization
		// ------------------
		include _PS_MODULE_DIR_.$this->module->name.'/controllers/admin/session_api.php';
		$this->session_api = new SessionApi();

		if ($this->session_api->connectFromCredentials('email'))
		{
			// On retrouve le max_daily depuis l'API Express-Mailing
			// -----------------------------------------------------
			$parameters = array(
				'account_id' => $this->session_api->account_id
			);
			$response_array = array();

			if ($this->session_api->call('email', 'account', 'get_formula', $parameters, $response_array))
				if ((int)$response_array['broadcast_max_campaign'] > 0)
					$this->default_max_daily = $response_array['broadcast_max_campaign'];
		}
	}

	public function initToolbarTitle()
	{
		parent::initToolbarTitle();
		$this->toolbar_title = $this->module->l('Send an e-mailing', 'adminmarketingestep1');
	}

	public function setMedia()
	{
		parent::setMedia();
		$this->addCSS(_PS_MODULE_DIR_.'expressmailing/views/css/expressmailing.css');
		$this->addJS(_PS_MODULE_DIR_.'expressmailing/views/js/marketinge_step1.js');
		$this->addJqueryUI('ui.slider');
		$this->addJqueryUI('ui.dialog');
		$this->addJqueryUI('ui.draggable');
		$this->addJqueryUI('ui.resizable');
	}

	public function renderList()
	{
		$field_days = array(
			array(
				'short_day' => 'L',
				'name' => Translate::getAdminTranslation('Monday')
			),
			array(
				'short_day' => 'M',
				'name' => Translate::getAdminTranslation('Tuesday')
			),
			array(
				'short_day' => 'C',
				'name' => Translate::getAdminTranslation('Wednesday')
			),
			array(
				'short_day' => 'J',
				'name' => Translate::getAdminTranslation('Thursday')
			),
			array(
				'short_day' => 'V',
				'name' => Translate::getAdminTranslation('Friday')
			),
			array(
				'short_day' => 'S',
				'name' => Translate::getAdminTranslation('Saturday')
			),
			array(
				'short_day' => 'D',
				'name' => Translate::getAdminTranslation('Sunday')
			)
		);

		$this->fields_form = array(
			'legend' => array(
				'title' => $this->module->l('Campaign configuration (step 1)', 'adminmarketingestep1'),
				'icon' => 'icon-cogs'
			),
			'input' => array(
				array(
					'type' => _PS_MODE_DEV_ ? 'text' : 'hidden',
					'lang' => false,
					'label' => 'Ref :',
					'name' => 'campaign_id',
					'col' => 1,
					'readonly' => 'readonly'
				),
				array(
					'type' => 'text',
					'lang' => false,
					'label' => $this->module->l('Emailing subject / object :', 'adminmarketingestep1'),
					'name' => 'campaign_name',
					'col' => 5,
					'required' => true
				),
				array(
					'type' => 'datetime',
					'lang' => false,
					'name' => 'campaign_date_send',
					'label' => $this->module->l('Sending date :', 'adminmarketingestep1'),
					'col' => 5,
					'required' => true
				),
				array(
					'type' => 'switch',
					'lang' => false,
					'class' => 'chosen',
					'label' => $this->module->l('Activate opening tracking :', 'adminmarketingestep1'),
					'name' => 'campaign_tracking',
					'values' => array(
						array(
							'id' => 'active_on',
							'value' => 1,
							'label' => $this->module->l('yes'),
						),
						array(
							'id' => 'active_off',
							'value' => 0,
							'label' => $this->module->l('no'),
						)
					)
				),
				array(
					'type' => 'switch',
					'lang' => false,
					'label' => $this->module->l('Activate links tracking :', 'adminmarketingestep1'),
					'name' => 'campaign_linking',
					'values' => array(
						array(
							'id' => 'active_on',
							'value' => 1,
							'label' => $this->module->l('yes'),
						),
						array(
							'id' => 'active_off',
							'value' => 0,
							'label' => $this->module->l('no'),
						)
					)
				),
				array(
					'type' => 'switch',
					'lang' => false,
					'label' => $this->module->l('In case of unsubscription, add the subscriber on red list :', 'adminmarketingestep1'),
					'name' => 'campaign_redlist',
					'values' => array(
						array(
							'id' => 'active_on',
							'value' => 1,
							'label' => $this->module->l('yes'),
						),
						array(
							'id' => 'active_off',
							'value' => 0,
							'label' => $this->module->l('no'),
						)
					)
				),
				array(
					'type' => 'free',
					'name' => 'limit_daily',
					'label' => $this->module->l('Daily sending limit :', 'adminmarketingestep1'),
				),
				/* Week_limite : L=Lundi M=Mardi C=Mercredi J=Jeudi V=Vendredi S=Samedi D=Dimanche */
				array(
					'type' => 'checkbox',
					'label' => $this->module->l('Limit by day of the week :', 'adminmarketingestep1'),
					'class' => 'checkbox-inline',
					'name' => 'week_day_limit',
					'values' => array(
						'query' => $field_days,
						'id' => 'short_day',
						'name' => 'name', /* Label */
						'class' => 'checkbox-inline'
					)
				)
			),
			'submit' => array(
				'title' => $this->module->l('Next', 'adminmarketingestep1'),
				'name' => 'submitEmailingStep1',
				'icon' => 'process-icon-next'
			)
		);

		$this->getFieldsValues();
		$output = parent::renderForm();

		$footer = $this->getTemplatePath().'footer.tpl';
		$output .= $this->context->smarty->fetch($footer);
		return $output;
	}

	public function postProcess()
	{
		if (Tools::isSubmit('submitEmailingStep1'))
		{
			// Vérification de la date
			// -----------------------
			$now = time();
			$date_send = DateTime::createFromFormat('Y-m-d H:i:s', (string)Tools::getValue('campaign_date_send'));
			if ($date_send !== false)
				$campaign_date_send = max($now, $date_send->getTimestamp());
			else
				$campaign_date_send = $now;

			$limit_week = '';
			if (Tools::getValue('week_day_limit_L'))
				$limit_week .= 'L';
			if (Tools::getValue('week_day_limit_M'))
				$limit_week .= 'M';
			if (Tools::getValue('week_day_limit_C'))
				$limit_week .= 'C';
			if (Tools::getValue('week_day_limit_J'))
				$limit_week .= 'J';
			if (Tools::getValue('week_day_limit_V'))
				$limit_week .= 'V';
			if (Tools::getValue('week_day_limit_S'))
				$limit_week .= 'S';
			if (Tools::getValue('week_day_limit_D'))
				$limit_week .= 'D';

			$campaign_date_send = date('Y-m-d H:i:s', $campaign_date_send);
			$campaign_name = Tools::ucfirst((string)Tools::getValue('campaign_name'));
			$campaign_tracking = (string)Tools::getValue('campaign_tracking');
			$campaign_linking = (string)Tools::getValue('campaign_linking');
			$campaign_redlist = (string)Tools::getValue('campaign_redlist');
			$limit_daily = (int)Tools::getValue('limit_daily');
			$limit_max = (int)Tools::getValue('limit_max');

			if (empty($this->campaign_id) || empty($campaign_name) || empty($campaign_date_send))
				$this->errors[] = $this->module->l('Please verify the required fields', 'adminmarketingestep1');
			else
			{
				// On mémorise les info, même si la date n'est pas bonne
				// -----------------------------------------------------
				Db::getInstance()->update('expressmailing_email',
					array(
					'campaign_state' => 0,
					'campaign_date_update' => date('Y-m-d H:i:s'),
					'campaign_date_send' => $campaign_date_send,
					'campaign_name' => pSQL($campaign_name),
					'campaign_tracking' => pSQL($campaign_tracking),
					'campaign_linking' => pSQL($campaign_linking),
					'campaign_redlist' => pSQL($campaign_redlist),
					'campaign_day_limit' => $limit_daily,
					'campaign_max_limit' => $limit_max,
					'campaign_week_limit' => $limit_week
					), 'campaign_id = '.$this->campaign_id
				);

				if ($campaign_date_send > mktime(0, 0, 0, date('m') + 3, date('d'), date('Y')))
				{
					$this->errors[] = $this->module->l('Invalid date (max 3 months)', 'adminmarketingestep1');
					return false;
				}

				Tools::redirectAdmin('index.php?controller=AdminMarketingEStep2&campaign_id='.
					$this->campaign_id.
					'&token='.Tools::getAdminTokenLite('AdminMarketingEStep2'));
				exit;
			}
		}
	}

	private function getFieldsValues()
	{
		$sql = new DbQuery();
		$sql->select('*');
		$sql->from('expressmailing_email');
		$sql->where('campaign_id = '.$this->campaign_id);
		$result = Db::getInstance()->getRow($sql);

		$this->fields_value['campaign_id'] = $this->campaign_id;
		$this->fields_value['campaign_name'] = $result['campaign_name'];
		$this->fields_value['campaign_date_send'] = $result['campaign_date_send'];
		$this->fields_value['campaign_tracking'] = $result['campaign_tracking'];
		$this->fields_value['campaign_linking'] = $result['campaign_linking'];
		$this->fields_value['campaign_redlist'] = $result['campaign_redlist'];
		$this->fields_value['limit_daily'] = $this->generateSlider('limit_daily', 100, $this->default_max_daily, $result['campaign_day_limit'], 100)
			.$this->generateByingLink();
		$this->fields_value['limit_max'] = $result['campaign_max_limit'];
		$this->fields_value['week_day_limit_L'] = strpos($result['campaign_week_limit'], 'L') !== false ? 'on' : null;
		$this->fields_value['week_day_limit_M'] = strpos($result['campaign_week_limit'], 'M') !== false ? 'on' : null;
		$this->fields_value['week_day_limit_C'] = strpos($result['campaign_week_limit'], 'C') !== false ? 'on' : null;
		$this->fields_value['week_day_limit_J'] = strpos($result['campaign_week_limit'], 'J') !== false ? 'on' : null;
		$this->fields_value['week_day_limit_V'] = strpos($result['campaign_week_limit'], 'V') !== false ? 'on' : null;
		$this->fields_value['week_day_limit_S'] = strpos($result['campaign_week_limit'], 'S') !== false ? 'on' : null;
		$this->fields_value['week_day_limit_D'] = strpos($result['campaign_week_limit'], 'D') !== false ? 'on' : null;

		return true;
	}

	private function generateSlider($field_name, $min_value, $max_value, $preset_value, $step)
	{
		$this->context->smarty->assign('campaign_id', $this->campaign_id);
		$this->context->smarty->assign('field_name', $field_name);
		$this->context->smarty->assign('min_value', $min_value);
		$this->context->smarty->assign('max_value', $max_value);
		$this->context->smarty->assign('preset_value', $preset_value);
		$this->context->smarty->assign('step', $step);

		$template_path = $this->getTemplatePath().'marketinge_step1/marketinge_slider.tpl';
		return $this->context->smarty->fetch($template_path);
	}

	private function generateByingLink()
	{
		$template_path = $this->getTemplatePath().'marketinge_step1/bying_link.tpl';
		return $this->context->smarty->fetch($template_path);
	}

	public function displayAjax()
	{
		$response_array = null;
		$parameters = array(
			'application_id' => $this->session_api->application_id,
			'category_code' => 'email_daily',
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
