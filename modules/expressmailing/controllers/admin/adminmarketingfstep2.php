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

require_once 'session_api.php';

/**
 * Step 2 : Add pages to the fax campaign
 */
class AdminMarketingFStep2Controller extends ModuleAdminController
{
	private $campaign_id = null;
	private $settled_pages = null;
	private $import_folder = '';

	public function __construct()
	{
		$this->name = 'adminmarketingfstep2';
		$this->bootstrap = true;
		$this->module = 'expressmailing';
		$this->context = Context::getContext();
		$this->lang = false;
		$this->default_form_language = $this->context->language->id;
		$this->session_api = null;

		$this->campaign_id = (int)Tools::getValue('campaign_id');

		if (empty($this->campaign_id))
		{
			Tools::redirectAdmin('index.php?controller=AdminMarketingX&token='.Tools::getAdminTokenLite('AdminMarketingX'));
			exit;
		}

		$this->session_api = new SessionApi();
		$this->session_api->connectFromCredentials('fax');

		parent::__construct();

		$this->import_folder = _PS_MODULE_DIR_.$this->module->name.DIRECTORY_SEPARATOR.'import'.DIRECTORY_SEPARATOR;
	}

	public function setMedia()
	{
		parent::setMedia();
		$this->addCSS(_PS_MODULE_DIR_.'expressmailing/views/css/expressmailing.css');
		$this->addJqueryUI('ui.dialog');
	}

	public function initToolbarTitle()
	{
		parent::initToolbarTitle();
		$this->toolbar_title = Translate::getModuleTranslation('expressmailing', 'Send a fax-mailing', 'adminmarketingfstep1');
	}

	public function renderList()
	{
		$this->initFieldsValues();
		$this->setSmartyVars();

		$this->fields_form = array (
			'legend' => array (
				'title' => $this->module->l('Add document(s) to the campaign (step 2)', 'adminmarketingfstep2'),
				'icon' => 'icon-print'
			),
			'input' => array (
				array (
					'type' => _PS_MODE_DEV_ ? 'text' : 'hidden',
					'lang' => false,
					'label' => 'Ref :',
					'name' => 'campaign_id',
					'col' => 1,
					'readonly' => 'readonly'
				),
				array (
					'type' => 'file',
					'label' => $this->module->l('Choose a document (Pdf or Word file) :', 'adminmarketingfstep2'),
					'name' => 'document',
					'required' => false
				)
			),
			'submit' => array (
				'title' => $this->module->l('Add this document', 'adminmarketingfstep2'),
				'name' => 'uploadFaxStep2',
				'icon' => 'process-icon-duplicate'
			),
			'buttons' => array (
				array (
					'href' => 'index.php?controller=AdminMarketingFStep1&campaign_id='.
					$this->campaign_id.
					'&token='.Tools::getAdminTokenLite('AdminMarketingFStep1'),
					'title' => $this->module->l('Back', 'adminmarketingfstep2'),
					'icon' => 'process-icon-back'
				)
			)
		);

		$output = parent::renderForm();

		if (!is_null($this->settled_pages) && !empty($this->settled_pages))
		{
			$fax_preview = $this->getTemplatePath().'marketingf_step2/fax_preview.tpl';
			$output .= $this->context->smarty->fetch($fax_preview);
		}

		$footer = $this->getTemplatePath().'footer.tpl';
		$output .= $this->context->smarty->fetch($footer);

		return $output;
	}

	public function postProcess()
	{
		if (Tools::isSubmit('uploadFaxStep2'))
		{
			if (!empty($_FILES))
			{
				$file = $_FILES['document'];
				if ($fax_document = $this->convertDocToFaxAPI($file['name'], $file['tmp_name']))
				{
					$this->settled_pages = $this->copyPagesToStorageAndDB($fax_document);
					return true;
				}
			}

			$this->errors[] = sprintf($this->module->l('Error while converting document to Black & White fax : %s'), $this->session_api->getError());
			return false;
		}

		if (Tools::isSubmit('delete_page'))
		{
			$page_id = (int)Tools::getValue('delete_page');
			Db::getInstance()->delete('expressmailing_fax_pages', 'id = '.$page_id);
			return;
		}

		if (Tools::isSubmit('deleteAllPages'))
		{
			Db::getInstance()->delete('expressmailing_fax_pages', 'campaign_id = '.$this->campaign_id);
			return;
		}

		if (Tools::isSubmit('submitFaxStep2'))
		{
			Tools::redirectAdmin('index.php?controller=AdminMarketingFStep3&campaign_id='.
				$this->campaign_id.
				'&token='.Tools::getAdminTokenLite('AdminMarketingFStep3'));
			exit;
		}
	}

	private function initFieldsValues()
	{
		$this->fields_value['campaign_id'] = $this->campaign_id;
	}

	private function setSmartyVars()
	{
		$smarty_assigns = array (
			'mod_dev' => _PS_MODE_DEV_,
			'campaign_id' => $this->campaign_id
		);

		$this->settled_pages = $this->getPagesDB();

		$smarty_assigns['settled_pages'] = $this->settled_pages;
		$this->context->smarty->assign($smarty_assigns);
	}

	private function copyPagesToStorageAndDB($fax_document)
	{
		$settled_pages = array ();

		foreach ($fax_document['pages'] as $page)
		{
			$original_file_name = uniqid('tmp_').'.png';
			$original_page_path = $this->import_folder.$original_file_name;
			$original_handle = fopen($original_page_path, 'w+');
			fwrite($original_handle, $page['content']);
			fclose($original_handle);
			$original_image = imagecreatefrompng($original_page_path);
			$original_image_size = getimagesize($original_page_path);

			$final_filename = uniqid('document_').'.png';
			$final_page_path = $this->import_folder.$final_filename;
			$final_page_url = '../modules/expressmailing/import/'.$final_filename;
			$final_image = imagecreatetruecolor($original_image_size[0], $original_image_size[1] * 2);

			imagecopyresampled($final_image, $original_image, 0, 0, 0, 0, $original_image_size[0], $original_image_size[1] * 2, //150 caractères
				$original_image_size[0], $original_image_size[1]);

			imagepng($final_image, $final_page_path);

			Db::getInstance()->insert('expressmailing_fax_pages', array (
				'campaign_id' => $this->campaign_id,
				'page_path' => pSQL($final_page_path),
				'page_url' => pSQL($final_page_url),
				'page_path_original' => pSQL($original_page_path)
				)
			);

			$page_id = Db::getInstance()->Insert_ID();
			$settled_pages[] = array ('id' => $page_id, 'page_path' => $final_page_path, 'page_url' => $final_page_url, //150 caractères
				'page_path_original' => $original_page_path);
		}

		return $settled_pages;
	}

	private function convertDocToFaxAPI($filename, $file_path)
	{
		$suffix = pathinfo((string)$filename, PATHINFO_EXTENSION);
		$file_data = Tools::file_get_contents((string)$file_path);
		$encoded_file_data = mb_convert_encoding($file_data, 'BASE64', 'UTF-8');

		$response_array = null;
		$parameters = array (
			'application_id' => Translate::getModuleTranslation('expressmailing', '3320', 'session_api'),
			'file_suffix' => 'prestashop.'.$suffix,
			'document' => $encoded_file_data,
			'return_format' => 'Png'
		);

		if ($this->session_api->call('fax', 'tools', 'convert_doc_to_fax', $parameters, $response_array))
			return $response_array;
		else
			return false;
	}

	private function getPagesDB()
	{
		$req = new DbQuery();
		$req->select('id, page_path, page_url');
		$req->from('expressmailing_fax_pages');
		$req->where('campaign_id = '.$this->campaign_id);
		$req->orderBy('id ASC');

		return Db::getInstance()->executeS($req, true, false);
	}

}
