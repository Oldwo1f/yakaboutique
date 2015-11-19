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

/**
 * Step 3 : Upload CSV recipients file
 */
class AdminMarketingFStep3Controller extends ModuleAdminController
{
	private $campaign_id = null;
	private $duplicate_count = 0;

	public function __construct()
	{
		$this->name = 'adminmarketingfstep3';
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
	}

	public function initToolbarTitle()
	{
		parent::initToolbarTitle();
		$this->toolbar_title = Translate::getModuleTranslation('expressmailing', 'Send a fax-mailing', 'adminmarketingfstep1');
	}

	public function setMedia()
	{
		parent::setMedia();
		$this->addCSS(_PS_MODULE_DIR_.'expressmailing/views/css/expressmailing.css', 'all');
	}

	private function getFieldsValues()
	{
		$this->fields_value['campaign_id'] = $this->campaign_id;
		return true;
	}

	public function renderList()
	{
		// Total recipients will be stored in $this->list_total
		// ----------------------------------------------------
		$recipients = $this->getRecipientsDB();

		// Count the duplicates
		// --------------------
		$request = 'SELECT SUM(duplic - 1)
					FROM
					(
						SELECT COUNT(target) as duplic
						FROM '._DB_PREFIX_.'expressmailing_fax_recipients
						WHERE campaign_id= '.$this->campaign_id.'
						GROUP BY target
						HAVING COUNT(target) > 1
					) as dd';
		$this->duplicate_count = Db::getInstance()->getValue($request, false);

		// Panel 1 : CSV import
		// --------------------
		$this->fields_form = array(
			'legend' => array(
				'title' => $this->module->l('Contacts importation (step 3)', 'adminmarketingfstep3'),
				'icon' => 'icon-beaker'
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
					'type' => 'file',
					'label' => $this->module->l('Import a csv file :', 'adminmarketingfstep3'),
					'name' => 'csv_file',
					'required' => true
				)
			),
			'submit' =>
			array(
				'title' => $this->module->l('Start analysis ...', 'adminmarketingfstep3'),
				'name' => 'importCsvStep3',
				'icon' => 'process-icon-cogs'
			),
			'buttons' => array(
				array(
					'type' => 'submit',
					'title' => $this->module->l('Clear selection', 'adminmarketingfstep3'),
					'icon' => 'process-icon-delete',
					'name' => 'clearRecipients',
					'class' => 'pull-left'
				),
				array(
					'type' => 'submit',
					'title' => sprintf($this->module->l('Clear duplicates (%d)', 'adminmarketingfstep3'), $this->duplicate_count),
					'icon' => 'process-icon-eraser',
					'name' => 'clearDuplicate',
					'class' => 'pull-left button-clear-duplicate'
				)
			)
		);

		$output = parent::renderForm();

		// Panel 2 : Recipients preview
		// ----------------------------
		$helper_list = new HelperList();
		$helper_list->no_link = true;
		$helper_list->shopLinkType = '';
		$helper_list->simple_header = true;
		$helper_list->identifier = 'ID';
		$helper_list->show_toolbar = false;
		$helper_list->table = 'expressmailing_fax_recipients';
		$helper_list->imageType = 'jpg';

		$fields_list = array(
			'target' => array(
				'title' => $this->module->l('Phone', 'adminmarketingfstep3'),
				'width' => 140,
				'search' => false,
				'type' => 'text'
			),
			'col_1' => array(
				'title' => $this->module->l('Col_1', 'adminmarketingfstep3'),
				'width' => 140,
				'search' => false,
				'type' => 'text'
			),
			'col_2' => array(
				'title' => $this->module->l('Col_2', 'adminmarketingfstep3'),
				'width' => 140,
				'search' => false,
				'type' => 'text'
			),
			'col_3' => array(
				'title' => $this->module->l('Col_3', 'adminmarketingfstep3'),
				'width' => 140,
				'search' => false,
				'type' => 'text'
			),
			'col_4' => array(
				'title' => $this->module->l('Col_4', 'adminmarketingfstep3'),
				'width' => 140,
				'search' => false,
				'type' => 'text'
			),
			'col_5' => array(
				'title' => $this->module->l('Col_5', 'adminmarketingfstep3'),
				'width' => 140,
				'search' => false,
				'type' => 'text'
			)
		);

		$html_list = $helper_list->generateList($recipients, $fields_list);

		if (!preg_match('/<table.*<\/table>/iUs', $html_list, $array_table))
			$output .= $html_list;

		$this->fields_form = array(
			'legend' => array(
				'title' => $this->module->l('Recipients preview', 'adminmarketingfstep3').'<span class="badge">'.$this->list_total.'</span>',
				'icon' => 'icon-phone'
			),
			'input' => array(
				array(
					'type' => 'hidden',
					'lang' => false,
					'label' => 'Ref :',
					'name' => 'campaign_id',
					'col' => 1,
					'readonly' => 'readonly'
				),
				array(
					'type' => 'free',
					'name' => 'html_list'
				)
			),
			'submit' => array(
				'title' => $this->module->l('Validate this selection', 'adminmarketingfstep3'),
				'name' => 'submitFaxStep3',
				'icon' => 'process-icon-next'
			),
			'buttons' => array(
				array(
					'href' => 'index.php?controller=AdminMarketingFStep2&campaign_id='.
					$this->campaign_id.
					'&token='.Tools::getAdminTokenLite('AdminMarketingFStep2'),
					'title' => $this->module->l('Back', 'adminmarketingfstep3'),
					'icon' => 'process-icon-back'
				)
			)
		);

		$this->getFieldsValues();

		// Concatenate list and buttons
		// ----------------------------
		$html_boutons = parent::renderForm();
		$output .= preg_replace('/<div class="form-group">/', $array_table[0].'<div class="form-group">', $html_boutons, 1);

		$footer = $this->getTemplatePath().'footer.tpl';
		$output .= $this->context->smarty->fetch($footer);

		return $output;
	}

	private function getRecipientsDB()
	{
		// Count total recipients
		// ----------------------
		$req = new DbQuery();
		$req->select('SQL_NO_CACHE SQL_CALC_FOUND_ROWS	recipient.target,
														recipient.col_0, recipient.col_1, recipient.col_2,
														recipient.col_3, recipient.col_4, recipient.col_5');
		$req->from('expressmailing_fax_recipients', 'recipient');
		$req->where('recipient.campaign_id = '.$this->campaign_id);
		$req->limit(20);

		$user_list = Db::getInstance()->executeS($req, true, false);

		$this->list_total = Db::getInstance()->getValue('SELECT FOUND_ROWS()', false);
		$this->fields_value['total_recipients'] = (string)$this->list_total;

		$formated_user_list = array();
		foreach ($user_list as $user)
			$formated_user_list[] = $user;

		return $formated_user_list;
	}

	public function postProcess()
	{
		if (Tools::isSubmit('importCsvStep3'))
		{
			$this->csv_file = isset($_FILES['csv_file']) ? $_FILES['csv_file'] : false;
			if (empty($this->csv_file['tmp_name']))
				$this->errors[] = Tools::displayError('No file has been specified.');
			else
			{
				if (!empty($this->csv_file) && !empty($this->csv_file['tmp_name']))
					if (!EMTools::importFileSelectColumn($_FILES['csv_file'], 'fax', $this->campaign_id, $this->module->name))
						$this->errors[] = Tools::displayError('Cannot read the .CSV file');
			}
		}

		if (Tools::isSubmit('clearRecipients'))
		{
			if (Db::getInstance()->delete('expressmailing_fax_recipients', 'campaign_id = '.$this->campaign_id))
				$this->confirmations[] = $this->module->l('Clear succeed !', 'adminmarketingfstep3');

			return Db::getInstance()->update('expressmailing_fax', array(
				'campaign_date_update' => date('Y-m-d H:i:s'),
				'recipients_modified' => 1
				), 'campaign_id = '.$this->campaign_id
			);
		}

		if (Tools::isSubmit('indexCol'))
		{
			$index_col = (int)Tools::getValue('indexCol');
			$prefix = EMTools::getShopPrefixeCountry();

			return EMTools::importFile($index_col, 'fax', $this->campaign_id, $prefix);
		}

		if (Tools::isSubmit('clearDuplicate'))
		{
			$request = 'DELETE source
				FROM `'._DB_PREFIX_.'expressmailing_fax_recipients` AS source
				LEFT OUTER JOIN (
					SELECT MIN(id) as id, target
					FROM `'._DB_PREFIX_.'expressmailing_fax_recipients`
					WHERE campaign_id = '.$this->campaign_id.'
					GROUP BY target
				) AS duplicates
				ON source.id = duplicates.id
				WHERE duplicates.id IS NULL';

			if (Db::getInstance()->execute($request))
				$this->confirmations[] = $this->module->l('Clear succeed !', 'adminmarketingfstep3');

			Db::getInstance()->update('expressmailing_fax', array(
				'campaign_date_update' => date('Y-m-d H:i:s'),
				'recipients_modified' => '1'
				), 'campaign_id = '.$this->campaign_id
			);

			return;
		}

		if (Tools::isSubmit('submitFaxStep3'))
		{
			// Selection must contain recipients
			// ---------------------------------
			if (count($this->getRecipientsDB()))
			{
				Tools::redirectAdmin('index.php?controller=AdminMarketingFStep5&campaign_id='.
					$this->campaign_id.
					'&token='.Tools::getAdminTokenLite('AdminMarketingFStep5'));
				exit;
			}
			else
			{
				$this->errors[] = $this->module->l('Your recipients selection is empty !', 'adminmarketingfstep3');
				return false;
			}
		}
	}

}