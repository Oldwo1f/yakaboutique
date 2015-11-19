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
include_once 'tree_products.php';
include_once 'db_marketing.php';

/**
 * Step 2 : Upload CSV recipients file
 */
class AdminMarketingSStep2Controller extends ModuleAdminController
{
	private $campaign_id = null;
	private $list_total = 0;
	private $duplicate_count = 0;

	public function __construct()
	{
		$this->name = 'adminmarketingsstep2';
		$this->bootstrap = true;
		$this->module = 'expressmailing';
		$this->context = Context::getContext();
		$this->lang = false;
		$this->default_form_language = $this->context->language->id;
		$this->table = 'expressmailing_sms_recipients';
		$this->simple_header = true;
		$this->campaign_id = (int)Tools::getValue('campaign_id');

		if (empty($this->campaign_id))
		{
			Tools::redirectAdmin('index.php?controller=AdminMarketingX&token='.Tools::getAdminTokenLite('AdminMarketingX'));
			exit;
		}

		parent::__construct();

		$this->context->smarty->assign('mod_dev', _PS_MODE_DEV_);
		$this->context->smarty->assign('title', $this->module->l('Contacts importation (step 2)', 'adminmarketingsstep2'));
		$this->context->smarty->assign('campaign_id', $this->campaign_id);
	}

	public function initToolbarTitle()
	{
		parent::initToolbarTitle();
		$this->toolbar_title = Translate::getModuleTranslation('expressmailing', 'Send a sms-mailing', 'adminmarketingsstep1');
	}

	public function setMedia()
	{
		parent::setMedia();
		$this->addCSS(_PS_MODULE_DIR_.'expressmailing/views/css/expressmailing.css');
		$this->addJqueryUI('ui.tabs');
	}

	private function getFieldsValues()
	{
		$this->fields_value['campaign_id'] = $this->campaign_id;
		return true;
	}

	public function renderList()
	{
		// Count the duplicates
		// --------------------
		$request = 'SELECT SUM(duplic - 1)
					FROM
					(
						SELECT COUNT(target) as duplic
						FROM '._DB_PREFIX_.'expressmailing_sms_recipients
						WHERE campaign_id= '.$this->campaign_id.'
						GROUP BY target
						HAVING COUNT(target) > 1
					) as dd';
		$this->duplicate_count = (int)Db::getInstance()->getValue($request, false);

		// Total recipients will be stored in $this->list_total
		// ----------------------------------------------------
		$this->initCustomerFilters();
		$recipients = $this->getRecipientsDB();

		// Panel 1 : contacts import
		// -------------------------
		$display = $this->getTemplatePath().'marketings_step2/tabcontacts_importation_sms.tpl';
		$this->context->smarty->assign(array (
			'customers_filters' => $this->generateCustomersFilters(),
			'duplicate_count' => $this->duplicate_count)
		);
		$output = $this->context->smarty->fetch($display);

		// Panel 2 : Recipients preview
		// ----------------------------
		$helper_list = new HelperList();
		$helper_list->no_link = true;
		$helper_list->shopLinkType = '';
		$helper_list->simple_header = true;
		$helper_list->identifier = 'ID';
		$helper_list->show_toolbar = false;
		$helper_list->table = 'expressmailing_sms_recipients';

		$fields_list = array (
			'target' => array (
				'title' => $this->module->l('Phone', 'adminmarketingsstep2'),
				'width' => 140,
				'search' => false,
				'type' => 'text'
			),
			'col_1' => array (
				'title' => $this->module->l('Col_1', 'adminmarketingsstep2'),
				'width' => 140,
				'search' => false,
				'type' => 'text'
			),
			'col_2' => array (
				'title' => $this->module->l('Col_2', 'adminmarketingsstep2'),
				'width' => 140,
				'search' => false,
				'type' => 'text'
			),
			'col_3' => array (
				'title' => $this->module->l('Col_3', 'adminmarketingsstep2'),
				'width' => 140,
				'search' => false,
				'type' => 'text'
			),
			'col_4' => array (
				'title' => $this->module->l('Col_4', 'adminmarketingsstep2'),
				'width' => 140,
				'search' => false,
				'type' => 'text'
			),
			'col_5' => array (
				'title' => $this->module->l('Col_5', 'adminmarketingsstep2'),
				'width' => 140,
				'search' => false,
				'type' => 'text'
			)
		);

		$html_list = $helper_list->generateList($recipients, $fields_list);

		if (!preg_match('/<table.*<\/table>/iUs', $html_list, $array_table))
			$output .= $html_list;

		$this->fields_form = array (
			'legend' => array (
				'title' => $this->module->l('Recipients preview', 'adminmarketingsstep2').'<span class="badge">'.$this->list_total.'</span>',
				'icon' => 'icon-phone'
			),
			'input' => array (
				array (
					'type' => 'hidden',
					'lang' => false,
					'label' => 'Ref :',
					'name' => 'campaign_id',
					'col' => 1,
					'readonly' => 'readonly'
				),
				array (
					'type' => 'free',
					'name' => 'html_list'
				)
			),
			'submit' => array (
				'title' => $this->module->l('Validate this selection', 'adminmarketingsstep2'),
				'name' => 'submitSmsStep2',
				'icon' => 'process-icon-next'
			),
			'buttons' => array (
				array (
					'href' => 'index.php?controller=AdminMarketingSStep1&campaign_id='.
					$this->campaign_id.
					'&token='.Tools::getAdminTokenLite('AdminMarketingSStep1'),
					'title' => $this->module->l('Back', 'adminmarketingsstep2'),
					'icon' => 'process-icon-back'
				)
			)
		);

		$this->getFieldsValues();

		$html_boutons = parent::renderForm();

		// Concatenate list and buttons
		// ----------------------------
		if (count($array_table) > 0)
			$output .= str_replace('<div class="form-group">', $array_table[0].'<div class="form-group">', $html_boutons);
		else
			$output .= $html_boutons;

		$footer = $this->getTemplatePath().'footer.tpl';
		$output .= $this->context->smarty->fetch($footer);

		$open_tree = $this->getTemplatePath().'marketinge_step4/filters_tree.tpl';
		$output .= $this->context->smarty->fetch($open_tree);

		return $output;
	}

	private function generateCustomersFilters()
	{
		$output = '';
		$display = $this->getTemplatePath().'marketings_step2/customers_filters.tpl';
		$this->context->smarty->assign('group_lang_filters', $this->generateGroupLangActiveFilters());
		$this->context->smarty->assign('product_tree_filters', $this->generateProductsTree($this->checked_categories, $this->checked_products));
		$this->context->smarty->assign('paying_filters', $this->generatePayingFilters());
		$output .= $this->context->smarty->fetch($display);

		return $output;
	}

	private function generateGroupLangActiveFilters()
	{
		$sql = 'SELECT `'._DB_PREFIX_.'customer_group`.id_group, `'._DB_PREFIX_.'group_lang`.name, `'._DB_PREFIX_.'customer_group`.id_group as val
					FROM `'._DB_PREFIX_.'customer_group`
					LEFT JOIN `'._DB_PREFIX_.'group_lang` ON `'._DB_PREFIX_.'customer_group`.id_group = `'._DB_PREFIX_.'group_lang`.id_group
					GROUP BY `'._DB_PREFIX_.'customer_group`.id_group';
		$field_groups = db::getInstance()->executeS($sql, true, false);

		$sql = 'SELECT `'._DB_PREFIX_.'lang`.id_lang, `'._DB_PREFIX_.'lang`.name, `'._DB_PREFIX_.'lang`.id_lang as val
					FROM `'._DB_PREFIX_.'customer`
					LEFT JOIN `'._DB_PREFIX_.'lang` ON `'._DB_PREFIX_.'customer`.id_lang = `'._DB_PREFIX_.'lang`.id_lang
					GROUP BY `'._DB_PREFIX_.'lang`.id_lang';
		$field_langs = db::getInstance()->executeS($sql, true, false);

		$field_subscriptions = array (
			array (
				'id' => 'campaign_active',
				'val' => '1',
				'name' => $this->module->l('All Active customers', 'adminmarketingsstep2')
			)
		);

		$this->fields_form = array (
			'legend' => array (
				'title' => $this->module->l('Recipients configuration (step 2)', 'adminmarketingsstep2'),
				'icon' => 'icon-shopping-cart'
			),
			'input' => array (
				array (
					'type' => 'checkbox',
					'label' => $this->module->l('Groups :', 'adminmarketingsstep2'),
					'class' => 'checkbox-inline',
					'name' => 'groups[]',
					'values' => array (
						'query' => $field_groups,
						'id' => 'id_group',
						'class' => 'checkbox-inline',
						'name' => 'name'
					)
				),
				array (
					'type' => 'checkbox',
					'label' => $this->module->l('Purchase language :', 'adminmarketingsstep2'),
					'class' => 'checkbox-inline',
					'name' => 'langs[]',
					'values' => array (
						'query' => $field_langs,
						'id' => 'id_lang',
						'name' => 'name'
					)
				),
				array (
					'type' => 'checkbox',
					'label' => $this->module->l('Subscription filters :', 'adminmarketingsstep2'),
					'class' => 'checkbox-inline',
					'name' => 'subscriptions',
					'values' => array (
						'query' => $field_subscriptions,
						'id' => 'id',
						'name' => 'name'
					)
				)
			)
		);

		$output = parent::renderForm();
		return EMTools::extractFormWrapperElement($output);
	}

	private function generateProductsTree(array $selected_categories = array (), array $selected_products = array ())
	{
		$this->context->smarty->assign(array (
			'selected_categories_em' => $selected_categories,
			'selected_products_em' => $selected_products
		));

		$root = Category::getRootCategory();
		$tree = new HelperTreeCategoriesProducts('categories-products-treeview', $this->module->l('Purchased products', 'adminmarketingsstep2'));
		$tree->setUseCheckBox(true)->setRootCategory($root->id)->setUseSearch(false)->setUseCheckBoxToolbarLink(false);
		return $tree->render();
	}

	private function generatePayingFilters()
	{

	}

	public function postProcess()
	{
		if (Tools::isSubmit('import-prestashop-customers'))
		{
			Db::getInstance()->update('expressmailing_sms', array (
				'recipients_modified' => 1
				), 'campaign_id = '.$this->campaign_id
			);
			$this->importPrestashopCustomer();
		}

		if (Tools::isSubmit('import-xls'))
			$this->importXLSFile();

		if (Tools::isSubmit('import-csv'))
			$this->importCSVFile();

		if (Tools::isSubmit('quick-import'))
			$this->importQuick();

		if (Tools::isSubmit('clearRecipients'))
		{
			Db::getInstance()->update('expressmailing_sms', array (
				'recipients_modified' => 1
				), 'campaign_id = '.$this->campaign_id
			);

			Db::getInstance()->delete('expressmailing_sms_recipients', 'campaign_id = '.$this->campaign_id);

			$this->confirmations[] = $this->module->l('Clear succeed !', 'adminmarketingsstep2');
			return true;
		}

		if (Tools::isSubmit('clearDuplicate'))
		{
			$request = 'DELETE source
				FROM `'._DB_PREFIX_.'expressmailing_sms_recipients` AS source
				LEFT OUTER JOIN (
					SELECT MIN(id) as id, target
					FROM `'._DB_PREFIX_.'expressmailing_sms_recipients`
					WHERE campaign_id = '.$this->campaign_id.'
					GROUP BY target
				) AS duplicates
				ON source.id = duplicates.id
				WHERE duplicates.id IS NULL';

			if (Db::getInstance()->execute($request))
				$this->confirmations[] = $this->module->l('Clear succeed !', 'adminmarketingfstep3');

			Db::getInstance()->update('expressmailing_sms', array (
				'campaign_date_update' => date('Y-m-d H:i:s'),
				'recipients_modified' => '1'
				), 'campaign_id = '.$this->campaign_id
			);
		}

		if (Tools::isSubmit('indexCol'))
		{
			$index_col = (int)Tools::getValue('indexCol');
			$prefix = EMTools::getShopPrefixeCountry();

			return EMTools::importFile($index_col, 'sms', $this->campaign_id, $prefix);
		}

		if (Tools::isSubmit('submitSmsStep2'))
		{
			// Selection must contain recipients
			// ---------------------------------
			if (count($this->getRecipientsDB()))
			{
				Tools::redirectAdmin('index.php?controller=AdminMarketingSStep4&campaign_id='.
					$this->campaign_id.
					'&token='.Tools::getAdminTokenLite('AdminMarketingSStep4'));
				exit;
			}
			else
			{
				$this->errors[] = $this->module->l('Your recipients selection is empty !', 'adminmarketingsstep2');
				return false;
			}
		}
	}

	private function initCustomerFilters()
	{
		$this->checked_groups = array ();
		$req = new DbQuery();
		$req->select('group_id');
		$req->from('expressmailing_sms_groups');
		$req->where('campaign_id = '.$this->campaign_id);

		if ($res = DB::getInstance()->executeS($req, true, false))
		{
			foreach ($res as $row)
			{
				$this->fields_value['groups[]_'.$row['group_id']] = '1';
				$this->checked_groups[] = $row['group_id'];
			}
		}

		$this->checked_langs = array ();
		$req = new DbQuery();
		$req->select('lang_id');
		$req->from('expressmailing_sms_langs');
		$req->where('campaign_id = '.$this->campaign_id);

		if ($res = DB::getInstance()->executeS($req, true, false))
		{
			foreach ($res as $row)
			{
				$this->fields_value['langs[]_'.$row['lang_id']] = '1';
				$this->checked_langs[] = $row['lang_id'];
			}
		}

		$this->checked_categories = array ();
		$req = new DbQuery();
		$req->select('category_id');
		$req->from('expressmailing_sms_categories');
		$req->where('campaign_id = '.$this->campaign_id);

		if ($res = DB::getInstance()->executeS($req, true, false))
		{
			foreach ($res as $row)
				$this->checked_categories[] = $row['category_id'];
		}

		$this->checked_products = array ();
		$req = new DbQuery();
		$req->select('product_id');
		$req->from('expressmailing_sms_products');
		$req->where('campaign_id = '.$this->campaign_id);

		if ($res = DB::getInstance()->executeS($req, true, false))
		{
			foreach ($res as $row)
				$this->checked_products[] = $row['product_id'];
		}

		$req = new DbQuery();
		$req->select('campaign_active');
		$req->from('expressmailing_sms');
		$req->where('campaign_id = '.$this->campaign_id);

		$res = DB::getInstance()->executes($req, true, false);
		$this->active_address = $res[0]['campaign_active'];
		$this->fields_value['subscriptions_campaign_active'] = $this->active_address;
	}

	private function importPrestashopCustomer()
	{
		$this->clearCustomerFilters();

		$this->checked_groups = Tools::getValue('groups');
		$this->checked_langs = Tools::getValue('langs');
		$this->checked_categories = Tools::getValue('tree-categories');
		$this->checked_products = Tools::getValue('tree-products');
		$this->active_address = Tools::getValue('subscriptions_campaign_active');

		$inserts = array ();
		if ($this->checked_groups)
		{
			foreach ($this->checked_groups as $group)
			{
				$inserts[] = array (
					'campaign_id' => $this->campaign_id,
					'group_id' => (int)$group
				);
			}
		}

		if (!empty($inserts))
			Db::getInstance()->insert('expressmailing_sms_groups', $inserts);

		$inserts = array ();
		if ($this->checked_langs)
		{
			foreach ($this->checked_langs as $lang)
			{
				$inserts[] = array (
					'campaign_id' => $this->campaign_id,
					'lang_id' => (int)$lang
				);
			}
		}

		if (!empty($inserts))
			Db::getInstance()->insert('expressmailing_sms_langs', $inserts);

		$inserts = array ();
		if ($this->checked_categories)
		{
			foreach ($this->checked_categories as $category)
			{
				$inserts[] = array (
					'campaign_id' => $this->campaign_id,
					'category_id' => (int)$category
				);
			}
		}

		if (!empty($inserts))
			Db::getInstance()->insert('expressmailing_sms_categories', $inserts);

		$inserts = array ();
		if ($this->checked_products)
		{
			foreach ($this->checked_products as $product)
			{
				$inserts[] = array (
					'campaign_id' => $this->campaign_id,
					'product_id' => (int)$product
				);
			}
		}

		if (!empty($inserts))
			Db::getInstance()->insert('expressmailing_sms_products', $inserts);

		DB::getInstance()->update('expressmailing_sms', array ('campaign_active' => (int)$this->active_address), 'campaign_id = '.$this->campaign_id);

		// Rebuilt the recipients selection
		// --------------------------------
		$req = 'INSERT IGNORE INTO '._DB_PREFIX_.'expressmailing_sms_recipients
			(campaign_id, target, col_0, col_1, col_2, col_3, col_4, source)
			SELECT campaign_id, target, col_0, col_1, col_2, col_3, col_4, source
			FROM ('
			.DBMarketing::getCustomersSmsRequest($this->campaign_id,
												$this->checked_langs, $this->checked_groups,
												$this->active_address,
												$this->checked_products, $this->checked_categories).'
			) AS recipients';

		if (!DB::getInstance()->execute($req))
			$this->errors[] = DB::getInstance()->getMsgError ();
	}

	private function clearCustomerFilters()
	{
		DB::getInstance()->delete('expressmailing_sms_groups', 'campaign_id = '.$this->campaign_id);
		DB::getInstance()->delete('expressmailing_sms_langs', 'campaign_id = '.$this->campaign_id);
		DB::getInstance()->delete('expressmailing_sms_categories', 'campaign_id = '.$this->campaign_id);
		DB::getInstance()->delete('expressmailing_sms_products', 'campaign_id = '.$this->campaign_id);
		DB::getInstance()->update('expressmailing_sms', array ('campaign_active' => 0), 'campaign_id = '.$this->campaign_id);
		DB::getInstance()->delete('expressmailing_sms_recipients', 'campaign_id = '.$this->campaign_id.' AND source = "prestashop"');
	}

	private function importXLSFile()
	{
		// TODO
	}

	private function importCSVFile()
	{
		$this->csv_file = isset($_FILES['csv_file']) ? $_FILES['csv_file'] : false;

		if (empty($this->csv_file['tmp_name']))
		{
			$this->errors[] = Tools::displayError('No file has been specified.');
			return false;
		}

		if (!empty($this->csv_file) && !empty($this->csv_file['tmp_name']))
			if (!EMTools::importFileSelectColumn($_FILES['csv_file'], 'sms', $this->campaign_id, $this->module->name))
				$this->errors[] = Tools::displayError('Cannot read the .CSV file');
	}

	private function importQuick()
	{
		// TODO
	}

	private function getRecipientsDB()
	{
		// Calcul nombre destinataires total
		// ---------------------------------
		$req = new DbQuery();
		$req->select('SQL_NO_CACHE SQL_CALC_FOUND_ROWS recipient.target, recipient.col_0,
					recipient.col_1, recipient.col_2, recipient.col_3, recipient.col_4, recipient.col_5');
		$req->from('expressmailing_sms_recipients', 'recipient');
		$req->where('recipient.campaign_id = '.$this->campaign_id);
		$req->limit(20);

		$user_list = Db::getInstance()->executeS($req, true, false);

		$this->list_total = Db::getInstance()->getValue('SELECT FOUND_ROWS()', false);

		return $user_list;
	}

}
