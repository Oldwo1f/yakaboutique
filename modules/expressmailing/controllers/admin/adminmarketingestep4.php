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

include_once 'tree_products.php';
include_once 'db_marketing.php';
include_once 'em_tools.php';

/**
 * Step 4 : Recipients selection
 */
class AdminMarketingEStep4Controller extends ModuleAdminController
{
	private $campaign_id = null;
	private $list_total = 0;
	private $checked_groups = array();
	private $checked_shops = array();
	private $checked_langs = array();
	private $checked_categories = array();
	private $checked_products = array();
	private $checked_campaign_optin = null;
	private $checked_campaign_newsletter = null;
	private $checked_campaign_active = null;
	private $import_folder = '';
	private $expiration_date = '';
	private $paying_filter_tpl = '';

	public function __construct()
	{
		$this->name = 'adminmarketingestep4';
		$this->bootstrap = true;
		$this->module = 'expressmailing';
		$this->context = Context::getContext();
		$this->lang = false;
		$this->default_form_language = $this->context->language->id;
		$this->table = 'customer';
		$this->simple_header = true;
		$this->campaign_id = (int)Tools::getValue('campaign_id');

		if (empty($this->campaign_id))
		{
			Tools::redirectAdmin('index.php?controller=AdminMarketingX&token='.Tools::getAdminTokenLite('AdminMarketingX'));
			exit;
		}

		parent::__construct();

		$this->import_folder = _PS_MODULE_DIR_.$this->module->name.DIRECTORY_SEPARATOR.'import'.DIRECTORY_SEPARATOR;

		// API initialization
		// ------------------
		include _PS_MODULE_DIR_.$this->module->name.'/controllers/admin/session_api.php';
		$this->session_api = new SessionApi();

	}

	public function initToolbarTitle()
	{
		parent::initToolbarTitle();
		$this->toolbar_title = Translate::getModuleTranslation('expressmailing', 'Send an e-mailing', 'adminmarketingestep1');
	}

	public function setMedia()
	{
		parent::setMedia();
		$this->addCSS(_PS_MODULE_DIR_.'expressmailing/views/css/expressmailing.css', 'all');
		$this->addJqueryUI('ui.slider');
		$this->addJqueryUI('ui.dialog');
		$this->addJqueryUI('ui.draggable');
		$this->addJqueryUI('ui.resizable');
		$this->addJqueryUI('ui.autocomplete');
	}

	public function renderList()
	{
		$this->getFieldsValues();

		$this->context->smarty->assign(array(
			'campaign_id' => $this->campaign_id,
			'free_filter_inputs' => $this->renderFreeFilters(),
			'paying_filter_inputs' => $this->renderPayingFilters(),
			'tree_filter_inputs' => $this->renderTreeProducts()
		));

		// 1st panel : Search Criteria (2 blocs)
		// -------------------------------------
		$display = $this->getTemplatePath().'marketinge_step4/marketinge_step4.tpl';
		$output = $this->context->smarty->fetch($display);

		// Retrive the selected recipients
		// -------------------------------
		$recipients = $this->getRecipientsDB();

		// If no or empty search available, we display the default search (active=1)
		// -------------------------------------------------------------------------
		if (($this->list_total == 0) && !Tools::isSubmit('refreshEmailingStep4'))
		{
			$_POST['refreshEmailingStep4'] = 1;
			$_POST['subscriptions_campaign_active'] = 1;
			$this->storeSearchBD();
			$recipients = $this->getRecipientsDB();
		}

		// 2nd panel : Recipients preview
		// ------------------------------
		$fields_list = array(
			'id' => array(
				'title' => '#',
				'width' => 140,
				'type' => 'text'
			),
			'lang_iso' => array(
				'title' => $this->module->l('Lang', 'adminmarketingestep4'),
				'align' => 'center',
				'width' => 30,
				'type' => 'text'
			),
			'first_name' => array(
				'title' => $this->module->l('First name', 'adminmarketingestep4'),
				'width' => 140,
				'type' => 'text'
			),
			'last_name' => array(
				'title' => $this->module->l('Last name', 'adminmarketingestep4'),
				'width' => 140,
				'type' => 'text'
			),
			'target' => array(
				'title' => $this->module->l('Email', 'adminmarketingestep4'),
				'width' => 140,
				'type' => 'text'
			),
			'group_name' => array(
				'title' => $this->module->l('Group', 'adminmarketingestep4'),
				'width' => 140,
				'type' => 'text'
			)
		);

		$helper_list = new HelperList();
		$helper_list->no_link = true;
		$helper_list->shopLinkType = '';
		$helper_list->simple_header = true;
		$helper_list->identifier = 'id_customer';
		$helper_list->show_toolbar = false;
		$helper_list->table = 'customer';

		$html_list = $helper_list->generateList($recipients, $fields_list);
		if (!preg_match('/<table.*<\/table>/iUs', $html_list, $array_table))
			$output .= $html_list;

		// 3rd panel : Butons Prev/Next
		// ----------------------------
		$this->fields_form = array(
			'legend' => array(
				'title' => $this->module->l('Recipients preview', 'adminmarketingestep4').'<span class="badge">'.$this->list_total.'</span>',
				'icon' => 'icon-envelope'
			),
			'input' => array(
				array(
					'type' => 'free',
					'name' => 'html_list'
				),
				array (
					'type' => 'hidden',
					'lang' => false,
					'label' => 'Ref :',
					'name' => 'campaign_id',
					'col' => 1,
					'readonly' => 'readonly'
				)
			),
			'buttons' => array(
				array(
					'href' => 'index.php?controller=AdminMarketingEStep3&campaign_id='.
					$this->campaign_id.
					'&token='.Tools::getAdminTokenLite('AdminMarketingEStep3'),
					'title' => $this->module->l('Back', 'adminmarketingestep4'),
					'icon' => 'process-icon-back',
					'class' => 'pull-left'
				)
			),
			'submit' => array(
				'title' => $this->module->l('Validate this selection', 'adminmarketingestep4'),
				'name' => 'validateSelection',
				'icon' => 'process-icon-next'
			)
		);

		$html_boutons = (string)parent::renderForm();

		// Join list & buttons templates
		// -----------------------------
		if (count($array_table) > 0)
			$output .= preg_replace ('/<div class="form-group">/', '<div class="form-group">'.$array_table[0], $html_boutons, 1);
		else
			$output .= $html_boutons;

		// Finally we display the fusion of the 3 blocks + Footer
		// ------------------------------------------------------
		$footer = $this->getTemplatePath().'footer.tpl';
		$output .= $this->context->smarty->fetch($footer);

		// Pre-opens the treeview
		// ----------------------
		$open_tree = $this->getTemplatePath().'marketinge_step4/filters_tree.tpl';
		$output .= $this->context->smarty->fetch($open_tree);

		return $output;
	}

	private function renderFreeFilters()
	{
		// Multishop: selection
		// --------------------
		$sql = new DbQuery();
		$sql->select('shop_group.id_shop_group, shop_group.share_customer as shop_group_share_customer, shop_group.name as shop_group_name, 
			GROUP_CONCAT(DISTINCT shop.id_shop SEPARATOR \'|\') as shop_ids, GROUP_CONCAT(DISTINCT shop.name SEPARATOR \'|\') as shop_names,
			count(*) as shop_count');
		$sql->from('shop', 'shop');
		$sql->leftJoin('shop_group', 'shop_group', 'shop_group.id_shop_group = shop.id_shop_group');
		$sql->leftJoin('customer', 'customer', 'customer.id_shop = shop.id_shop');
		$sql->groupBy('if(shop_group.share_customer = 1, shop.id_shop_group, shop.id_shop)');

		$shops_by_sharing_shop_group = db::getInstance()->executeS($sql, true, false);

		foreach ($shops_by_sharing_shop_group as $key => $shop_group)
		{
			$shops_by_sharing_shop_group[$key]['shop_ids'] = explode('|', $shop_group['shop_ids']);
			$shops_by_sharing_shop_group[$key]['shop_names'] = explode('|', $shop_group['shop_names']);
		}

		// Multishop: checked shops and groups
		// -----------------------------------
		$sql = new DbQuery();
		$sql->select('shop_group_id, shop_id');
		$sql->from('expressmailing_email_shops_groups');
		$sql->where('campaign_id = '.$this->campaign_id);

		$res = db::getInstance()->executeS($sql, true, false);

		$checked_groups_shops = array(
			'shop_groups' => array(),
			'shops' => array()
		);
		foreach ($res as $row)
		{
			if (!in_array($row['shop_group_id'], $checked_groups_shops['shop_groups']))
				$checked_groups_shops['shop_groups'][] = $row['shop_group_id'];
			if (!in_array($row['shop_id'], $checked_groups_shops['shops']))
				$checked_groups_shops['shops'][] = $row['shop_id'];
		}

		// Include all groups and selected ones
		// ------------------------------------
		$sql = new DbQuery();
		$sql->select('CG.id_group, GL.name, XPM.group_id as checked, count(DISTINCT CG.id_customer) AS total');
		$sql->from('customer_group', 'CG');
		$sql->leftJoin('group_lang', 'GL', 'GL.id_group = CG.id_group');
		$sql->leftJoin('expressmailing_email_groups', 'XPM', 'XPM.campaign_id = '.$this->campaign_id.' AND XPM.group_id = CG.id_group');
		$sql->groupBy('CG.id_group');
		$customers_groups = db::getInstance()->executeS($sql, true, false);

		// We find all the languages and those selected
		// --------------------------------------------
		$sql = new DbQuery();
		$sql->select('CS.id_lang, LG.name, XPM.lang_id as checked, count(CS.id_customer) AS total');
		$sql->from('customer', 'CS');
		$sql->leftJoin('lang', 'LG', 'LG.id_lang = CS.id_lang');
		$sql->leftJoin('expressmailing_email_langs', 'XPM', 'XPM.campaign_id = '.$this->campaign_id.' AND XPM.lang_id = CS.id_lang');
		$sql->groupBy('CS.id_lang');
		$customers_langs = db::getInstance()->executeS($sql, true, false);

		// Count the number of optin subscribers
		// -------------------------------------
		$sql = new DbQuery();
		$sql->select('SUM(optin) as total_optin, SUM(newsletter) as total_newsletter, SUM(active) as total_active');
		$sql->from('customer');
		$customers_suscriptions = db::getInstance()->getRow($sql, false);

		// Count the number of guest subscribers
		// -------------------------------------
		$sql = new DbQuery();
		$sql->select('count(*) as total');
		$sql->where('active = 1');
		$sql->from('newsletter');
		$guest_newsletter = db::getInstance()->getRow($sql, false);
		$customers_suscriptions['total_guest'] = $guest_newsletter['total'];

		$this->context->smarty->assign(array(
			'shops_by_sharing_shop_group' => $shops_by_sharing_shop_group,
			'customers_groups' => $customers_groups,
			'customers_langs' => $customers_langs,
			'customers_suscriptions' => $customers_suscriptions,
			'checked_groups_shops' => $checked_groups_shops
		));

		$template_path = $this->getTemplatePath().'marketinge_step4/filters_free.tpl';
		return $this->context->smarty->fetch($template_path);
	}

	private function renderPayingFilters()
	{
		$available_filters = array(
			'birthday', 'civility', 'country', 'postal code', 'bying date',
			/* 'cancelled order', */ 'account creation', 'promotion code'/* , 'loyalty points' */
		);
		$this->context->smarty->assign('available_filters', $available_filters);

		$paying_filters_values = DBMarketing::getPayingFiltersEmailDB($this->campaign_id);
		$this->context->smarty->assign('filters_values', $paying_filters_values);

		return $this->getPayingFiltersTplAPI();
	}

	private function getPayingFiltersTplAPI()
	{
		if (!empty($this->paying_filter_tpl))
			return $this->paying_filter_tpl;

		if ($this->session_api->connectFromCredentials('email'))
		{
			$response_array = null;
			$parameters = array(
				'account_id' => $this->session_api->account_id,
				'language' => $this->context->language->iso_code,
				'module_version' => $this->module->version,
				'prestashop_version' => _PS_VERSION_
			);

			if ($this->session_api->call('email', 'prestashop', 'get_customers_filters_tpl', $parameters, $response_array))
			{
				if (isset($response_array['template'], $response_array['expiration_date'])
					&& !empty($response_array['template']))
				{
					$this->expiration_date = $response_array['expiration_date'];
					$template_content = mb_convert_encoding($response_array['template'], 'UTF-8', 'BASE64');
					$this->context->smarty->assign('campaign_id', $this->campaign_id);
					return $this->context->smarty->fetch('string:'.$template_content);
				}
			}

			$this->errors[] = sprintf($this->module->l('Unable to get customer filters : %s', 'adminmarketingestep4'), $this->session_api->getError());
		}
		else
		{
			$this->errors[] = $this->module->l('Unable to connect to API', 'adminmarketingestep4');
			return '';
		}
	}

	private function renderTreeProducts()
	{
		$this->context->smarty->assign(array(
			'selected_categories_em' => $this->checked_categories,
			'selected_products_em' => $this->checked_products
		));

		$root = Category::getRootCategory();
		$tree = new HelperTreeCategoriesProducts('categories-products-treeview', $this->module->l('Purchased products', 'adminmarketingestep4'));
		$tree->setUseCheckBox(true)->setRootCategory($root->id)->setUseSearch(false)->setUseCheckBoxToolbarLink(false);
		return $tree->render();
	}

	public function postProcess()
	{
		if (Tools::isSubmit('refreshEmailingStep4'))
		{
			// Clear the previous search and store the new recipients
			// ------------------------------------------------------
			$this->storeSearchBD();
			return;
		}

		if (Tools::isSubmit('validateSelection'))
		{
			$this->getRecipientsDB();
			if ($this->list_total == 0)
				$this->errors[] = $this->module->l('Your recipients selection is empty !', 'adminmarketingestep4');
			else
			{
				Tools::redirectAdmin('index.php?controller=AdminMarketingEStep5&campaign_id='.
					$this->campaign_id.
					'&token='.Tools::getAdminTokenLite('AdminMarketingEStep5'));
				exit;
			}
		}
	}

	private function getFieldsValues()
	{
		// Campaign id
		// -----------
		$this->fields_value['campaign_id'] = $this->campaign_id;

		// Obtain the 3 values Optin/ Newsletter /Active
		// ---------------------------------------------
		$sql = new DbQuery();
		$sql->select('campaign_optin, campaign_newsletter, campaign_active, campaign_guest');
		$sql->from('expressmailing_email');
		$sql->where('campaign_id = '.$this->campaign_id);
		$result = Db::getInstance()->getRow($sql);

		if (_PS_MODE_DEV_)
			echo '<!-- OK expressmailing_email -->';
		$this->checked_campaign_optin = $result['campaign_optin'];
		$this->checked_campaign_newsletter = $result['campaign_newsletter'];
		$this->checked_campaign_active = $result['campaign_active'];
		$this->checked_campaign_guest = $result['campaign_guest'];

		$this->fields_value['subscriptions_campaign_optin'] = $this->checked_campaign_optin;
		$this->fields_value['subscriptions_campaign_newsletter'] = $this->checked_campaign_newsletter;
		$this->fields_value['subscriptions_campaign_active'] = $this->checked_campaign_active;
		$this->fields_value['subscriptions_campaign_guest'] = $this->checked_campaign_guest;

		$this->context->smarty->assign(array(
			'campaign_id' => $this->campaign_id,
			'subscriptions_campaign_optin' => $this->checked_campaign_optin,
			'subscriptions_campaign_newsletter' => $this->checked_campaign_newsletter,
			'subscriptions_campaign_active' => $this->checked_campaign_active,
			'subscriptions_campaign_guest' => $this->checked_campaign_guest
		));

		// Retrieve selected products
		// --------------------------
		$sql = new DbQuery();
		$sql->select('product_id');
		$sql->from('expressmailing_email_products');
		$sql->where('campaign_id = '.$this->campaign_id);

		if ($result = Db::getInstance()->ExecuteS($sql, true, false))
			foreach ($result as $row)
				$this->checked_products[] = $row['product_id'];
		if (_PS_MODE_DEV_)
			echo '<!-- OK expressmailing_email_products -->';

		// Retrieve selected categories
		// ----------------------------
		$sql = new DbQuery();
		$sql->select('category_id');
		$sql->from('expressmailing_email_categories');
		$sql->where('campaign_id = '.$this->campaign_id);

		if ($result = Db::getInstance()->ExecuteS($sql, true, false))
			foreach ($result as $row)
				$this->checked_categories[] = $row['category_id'];
		if (_PS_MODE_DEV_)
			echo '<!-- OK expressmailing_email_categories -->';
	}

	public function getGendersDB()
	{
		$req = new DbQuery();
		$req->select('gender_lang.id_gender, gender_lang.name');
		$req->from('customer', 'customer');
		$req->leftJoin('gender', 'gender', 'customer.id_gender = gender.id_gender');
		$req->leftJoin('gender_lang', 'gender_lang', 'gender_lang.id_gender = gender.id_gender');
		$req->where('gender_lang.id_lang = '.$this->context->language->id);
		$req->groupby('gender.id_gender');

		$gender_list = Db::getInstance()->executeS($req, true, false);
		if (_PS_MODE_DEV_)
			echo '<!-- OK getGendersDB -->';
		return $gender_list;
	}

	public function getCountriesDB()
	{
		$req = new DbQuery();
		$req->select('country_lang.id_country, country_lang.name, country.iso_code, country.zip_code_format');
		$req->from('address', 'address');
		$req->leftJoin('country', 'country', 'country.id_country = address.id_country');
		$req->leftJoin('country_lang', 'country_lang', 'country_lang.id_country = address.id_country');
		$req->where('address.id_customer > 0 AND address.active = 1 AND address.deleted = 0');
		$req->groupby('address.id_country');

		$country_list = Db::getInstance()->executeS($req, true, false);
		if (_PS_MODE_DEV_)
			echo '<!-- OK getCountriesDB -->';
		return $country_list;
	}

	public function getCartRulesDB()
	{
		$req = new DbQuery();
		$req->select('cart_rule.id_cart_rule, cart_rule.code, cart_rule.description, cart_cart_rule.id_cart as used_on_id_cart');
		$req->from('cart_rule', 'cart_rule');
		$req->leftJoin('cart_cart_rule', 'cart_cart_rule', 'cart_cart_rule.id_cart_rule = cart_rule.id_cart_rule');
		$req->where('cart_rule.code != ""');
		$req->groupby('cart_rule.id_cart_rule');
		$req->orderBy('used_on_id_cart DESC');

		$cart_rules = Db::getInstance()->executeS($req, true, false);
		if (_PS_MODE_DEV_)
			echo '<!-- OK getCartRulesDB -->';
		return $cart_rules;
	}

	private function getRecipientsDB()
	{
		// Calculating the total number of recipients
		// ------------------------------------------
		$req = new DbQuery();
		$req->select('SQL_CALC_FOUND_ROWS id, lang_iso, target, last_name, first_name, group_name');
		$req->from('expressmailing_email_recipients');
		$req->where('campaign_id = '.$this->campaign_id);
		$req->limit(10);

		$user_list = Db::getInstance()->executeS($req, true, false);

		$this->list_total = Db::getInstance()->getValue('SELECT FOUND_ROWS()', false);
		if (_PS_MODE_DEV_)
			echo '<!-- OK getRecipientsDB ('.$this->list_total.') -->'."\r\n";
		return $user_list;
	}

	private function storeSearchBD()
	{
		// Clear the previous search
		// -------------------------
		DB::getInstance()->delete('expressmailing_email_recipients', 'campaign_id = '.$this->campaign_id, 0, false);

		// Store shop selection
		// --------------------
		DB::getInstance()->delete('expressmailing_email_shops_groups', 'campaign_id = '.$this->campaign_id, 0, false);

		if ($groups_shops_ids = Tools::getValue('groups_shops_ids'))
		{
			$db_entries = array();
			foreach ($groups_shops_ids as $shop_group_id => $shop_ids)
			{
				foreach ($shop_ids as $shop_id => $checked)
				{
					if ($checked)
					{
						$this->checked_shops[] = $shop_id;
						$db_entries[] = array(
							'campaign_id' => (int)$this->campaign_id,
							'shop_group_id' => (int)$shop_group_id,
							'shop_id' => $shop_id
						);
					}
				}
			}
			DB::getInstance()->insert('expressmailing_email_shops_groups', $db_entries);
		}

		// Store selection's Groups
		// ------------------------
		DB::getInstance()->delete('expressmailing_email_groups', 'campaign_id = '.$this->campaign_id, 0, false);

		if ($groups = Tools::getValue('groups'))
		{
			$db_entries = array();
			foreach (array_keys($groups) as $group)
			{
				$this->checked_groups[] = (int)$group;
				$db_entries[] = array(
					'campaign_id' => (int)$this->campaign_id,
					'group_id' => (int)$group
				);
			}
			DB::getInstance()->insert('expressmailing_email_groups', $db_entries);
		}

		// Store selection's Languages
		// ---------------------------
		DB::getInstance()->delete('expressmailing_email_langs', 'campaign_id = '.$this->campaign_id, 0, false);

		if ($langs = Tools::getValue('langs'))
		{
			$db_entries = array();
			foreach (array_keys($langs) as $lang)
			{
				$this->checked_langs[] = (int)$lang;
				$db_entries[] = array(
					'campaign_id' => (int)$this->campaign_id,
					'lang_id' => (int)$lang
				);
			}
			DB::getInstance()->insert('expressmailing_email_langs', $db_entries);
		}

		// Store categorie's Products
		// --------------------------
		DB::getInstance()->delete('expressmailing_email_categories', 'campaign_id = '.$this->campaign_id, 0, false);

		if ($categories = Tools::getValue('tree-categories'))
		{
			$db_entries = array();
			foreach ($categories as $category)
			{
				$this->selected_categories[] = (int)$category;
				$db_entries[] = array(
					'campaign_id' => (int)$this->campaign_id,
					'category_id' => (int)$category
				);
			}
			DB::getInstance()->insert('expressmailing_email_categories', $db_entries);
		}

		// Store Products
		// --------------
		DB::getInstance()->delete('expressmailing_email_products', 'campaign_id = '.$this->campaign_id, 0, false);

		if ($products = Tools::getValue('tree-products'))
		{
			$db_entries = array();
			foreach ($products as $product)
			{
				$this->selected_products[] = (int)$product;
				$db_entries[] = array(
					'campaign_id' => (int)$this->campaign_id,
					'product_id' => (int)$product
				);
			}
			DB::getInstance()->insert('expressmailing_email_products', $db_entries);
		}

		// Store options Optin/ Newsletter/ Active
		// ---------------------------------------
		$this->checked_campaign_optin = (int)Tools::getValue('subscriptions_campaign_optin', '0');
		$this->checked_campaign_newsletter = (int)Tools::getValue('subscriptions_campaign_newsletter', '0');
		$this->checked_campaign_active = (int)Tools::getValue('subscriptions_campaign_active', '0');
		$this->checked_campaign_guest = (int)Tools::getValue('subscriptions_campaign_guest', '0');

		Db::getInstance()->update('expressmailing_email',
			array(
			'campaign_optin' => $this->checked_campaign_optin,
			'campaign_newsletter' => $this->checked_campaign_newsletter,
			'campaign_active' => $this->checked_campaign_active,
			'campaign_guest' => $this->checked_campaign_guest
			), 'campaign_id = '.$this->campaign_id
		);

		// Store birthday filter
		// ---------------------
		DB::getInstance()->delete('expressmailing_email_birthdays', 'campaign_id = '.$this->campaign_id, 0, false);

		if ($birthday = (string)Tools::getValue('birthday'))
		{
			$values = explode('|', $birthday);
			Db::getInstance()->insert('expressmailing_email_birthdays',
				array(
				'campaign_id' => $this->campaign_id,
				'birthday_type' => pSQL($values[0]),
				'birthday_start' => pSQL($values[1]),
				'birthday_end' => pSQL($values[2])
			));
		}

		// Store civility filter
		// ---------------------
		DB::getInstance()->delete('expressmailing_email_civilities', 'campaign_id = '.$this->campaign_id, 0, false);

		if ($civilities = Tools::getValue('civility'))
		{
			$inserts = array();
			foreach ($civilities as $civility_id)
				$inserts[] = array(
					'campaign_id' => $this->campaign_id,
					'civility_id' => pSQL((int)$civility_id)
				);
			Db::getInstance()->insert('expressmailing_email_civilities', $inserts);
		}

		// Store country filter
		// --------------------
		DB::getInstance()->delete('expressmailing_email_countries', 'campaign_id = '.$this->campaign_id, 0, false);

		if ($countries = Tools::getValue('selected_countries'))
		{
			$inserts = array();
			foreach ($countries as $country_id)
				$inserts[] = array(
					'campaign_id' => $this->campaign_id,
					'country_id' => pSQL((int)$country_id)
				);
			Db::getInstance()->insert('expressmailing_email_countries', $inserts);
		}

		// Store postalcode filter
		// -----------------------
		DB::getInstance()->delete('expressmailing_email_postcodes', 'campaign_id = '.$this->campaign_id, 0, false);

		if ($post_codes = Tools::getValue('selected_postcodes'))
		{
			$inserts = array();
			foreach ($post_codes as $value)
			{
				$values = explode('|', $value);
				$country_id = $values[0];
				$post_code = $values[1];
				$inserts[] = array(
					'campaign_id' => $this->campaign_id,
					'country_id' => pSQL($country_id),
					'postcode' => pSQL($post_code)
				);
			}
			Db::getInstance()->insert('expressmailing_email_postcodes', $inserts);
		}

		// Store buyingdate filter
		// -----------------------
		DB::getInstance()->delete('expressmailing_email_buyingdates', 'campaign_id = '.$this->campaign_id, 0, false);

		if (($min_bying_date = (string)Tools::getValue('min_bying_date')) && ($max_bying_date = (string)Tools::getValue('max_bying_date')))
			Db::getInstance()->insert('expressmailing_email_buyingdates',
				array(
				'campaign_id' => $this->campaign_id,
				'min_buyingdate' => pSQL($min_bying_date),
				'max_buyingdate' => pSQL($max_bying_date)
			));

		// Store account creation filter
		// -----------------------------
		DB::getInstance()->delete('expressmailing_email_accountdates', 'campaign_id = '.$this->campaign_id, 0, false);

		if (($min_account_date = (string)Tools::getValue('min_account_creation_date'))
			&& ($max_account_date = (string)Tools::getValue('max_account_creation_date')))
			Db::getInstance()->insert('expressmailing_email_accountdates',
				array(
				'campaign_id' => $this->campaign_id,
				'min_accountdate' => pSQL($min_account_date),
				'max_accountdate' => pSQL($max_account_date)
			));

		// Store promotion code filter
		// ---------------------------
		DB::getInstance()->delete('expressmailing_email_promocodes', 'campaign_id = '.$this->campaign_id, 0, false);

		if ($promocode_type = (string)Tools::getValue('promocode'))
		{
			switch ($promocode_type)
			{
				case 'any':
				case 'never':
					Db::getInstance()->insert('expressmailing_email_promocodes',
						array(
						'campaign_id' => $this->campaign_id,
						'promocode_type' => pSQL($promocode_type),
						'promocode' => null
					));
					break;
				case 'specific':
					$promocodes_values = Tools::getValue('promocode_values');
					$inserts = array();
					foreach ($promocodes_values as $value)
						if (!empty($value))
							$inserts[] = array(
								'campaign_id' => $this->campaign_id,
								'promocode_type' => pSQL($promocode_type),
								'promocode' => pSQL($value)
							);
					Db::getInstance()->insert('expressmailing_email_promocodes', $inserts);
					break;
				default:
					break;
			}
		}

		// Rebuild the recipients selection
		// --------------------------------
		$extended = true;
		$paying_filters = DBMarketing::getPayingFiltersEmailDB($this->campaign_id);

		$req = 'INSERT IGNORE INTO '._DB_PREFIX_.'expressmailing_email_recipients
		(campaign_id, lang_iso, target, last_name, first_name, ip_address, last_connexion_date, group_name)
		SELECT campaign_id, iso_code, email, lastname, firstname, ip_address, last_connexion_date, group_name
		FROM ('
			.DBMarketing::getCustomersEmailRequest($this->campaign_id, $this->checked_langs, $this->checked_groups,
				$this->checked_campaign_optin, $this->checked_campaign_newsletter, $this->checked_campaign_active, $this->checked_campaign_guest,
				$this->checked_products, $this->checked_categories, $this->checked_shops, $paying_filters, $extended).'			 
		) AS recipients';

		if (!DB::getInstance()->execute($req))
			$this->errors[] = DB::getInstance()->getMsgError();

		// And informe the step7 that the selection has changed
		// ----------------------------------------------------
		Db::getInstance()->update('expressmailing_email', array (
			'campaign_date_update' => date('Y-m-d H:i:s'),
			'recipients_modified' => 1
			), 'campaign_id = '.$this->campaign_id
		);
	}

}
