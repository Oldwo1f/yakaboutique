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

include_once 'html_cleaner.php';
include_once 'em_tools.php';

/**
 * Step 3 : Provide HTML content & Images upload
 */
class AdminMarketingEStep3Controller extends ModuleAdminController
{
	private $campaign_id = null;
	private $html_content = null;

	public function __construct()
	{
		$this->name = 'adminmarketingestep3';
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

		$this->context->smarty->assign('title', $this->module->l('Email content (Step 3)', 'adminmarketingestep3'));
		$this->context->smarty->assign('campaign_id', $this->campaign_id);
	}

	public function initToolbarTitle()
	{
		parent::initToolbarTitle();
		$this->toolbar_title = Translate::getModuleTranslation('expressmailing', 'Send an e-mailing', 'adminmarketingestep1');
	}

	public function setMedia()
	{
		parent::setMedia();
		$this->addCSS(_PS_MODULE_DIR_.'expressmailing/views/css/expressmailing.css');
		$this->addJS(_PS_JS_DIR_.'tiny_mce/tiny_mce.js');
		$this->addJqueryUI('ui.tabs');

		// Try to use employee language for Tiny Editor
		// --------------------------------------------
		$language = new Language($this->context->employee->id_lang);
		$tiny_file = _PS_MODULE_DIR_.'expressmailing/views/js/tinymce_'.$language->iso_code.'.js';

		if (Tools::file_exists_cache($tiny_file))
			$this->addJS($tiny_file);
		else
		{
			$language = new Language(Configuration::get('PS_LANG_DEFAULT'));
			$tiny_file = _PS_MODULE_DIR_.'expressmailing/views/js/tinymce_'.Configuration::get('PS_LANG_DEFAULT').'.js';

			if (Tools::file_exists_cache($tiny_file))
				$this->addJS($tiny_file);
			else
				$this->addJS(_PS_MODULE_DIR_.'expressmailing/views/js/tinymce_en.js');
		}

		// And add our Tiny config
		// -----------------------
		$this->addJS(_PS_MODULE_DIR_.'expressmailing/views/js/tinymce.js');
	}

	public function renderList()
	{
		$this->getHTMLContentDB();

		$images_to_upload = array();
		$images_to_upload = $this->parseImagesToUpload($this->html_content);

		$output = '';

		if (Tools::isSubmit('ignoreImagesEmailingStep3') || (count($images_to_upload) == 0))
			$output = $this->generateTabedImportForm();
		elseif (count($images_to_upload) > 0)
			$output .= $this->generateImagesUploadForm($images_to_upload);

		$footer = $this->getTemplatePath().'footer.tpl';
		$output .= $this->context->smarty->fetch($footer);

		return $output;
	}

	public function postProcess()
	{
		$this->getHTMLContentDB();

		if (Tools::isSubmit('uploadImagesEmailingStep3'))
		{
			if (isset($_FILES))
				$this->html_content = $this->copyImagesAndUpdateHTML($this->html_content, null, $_FILES);

			return $this->saveHTML();
		}

		if (Tools::isSubmit('nextEmailingStep3'))
		{
			$this->html_content = (string)Tools::getValue('campaign_html');

			if (!empty($this->html_content))
			{
				$this->html_content = $this->copyImagesAndUpdateHTML($this->html_content);

				if ($this->saveHTML())
				{
					if ($found = $this->checkLocalUrls($this->html_content))
					{
						$a = $this->module->l('You are currently testing your Prestashop on a local server :', 'adminmarketingestep1');
						$b = $this->module->l('To enjoy the full IMAGE & TRACKING features, you need use a Prestashop online server !', 'adminmarketingestep1');
						$this->errors[] = $a.' http'.$found;
						$this->errors[] = $b;
						return false;
					}

					$images_to_upload = $this->parseImagesToUpload($this->html_content);

					if (Tools::isSubmit('nextEmailingStep3') && (count($images_to_upload) == 0))
					{
						Tools::redirectAdmin('index.php?controller=AdminMarketingEStep4&campaign_id='.
							$this->campaign_id.
							'&token='.Tools::getAdminTokenLite('AdminMarketingEStep4'));
						exit;
					}
				}
			}
			else
			{
				$this->errors[] = $this->module->l('Please verify the required fields', 'adminmarketingestep3');
				return false;
			}
		}

		if (Tools::isSubmit('tab_id'))
		{
			switch (Tools::getValue('tab_id'))
			{
				case 'url':
					$this->html_url = (string)Tools::getValue('html_url');
					if (empty($this->html_url))
						$this->errors[] = $this->module->l('Please verify the required fields', 'adminmarketingestep3');
					if (!$this->importURL($this->html_url))
						$this->errors[] = $this->module->l('Unable to import this URL', 'adminmarketingestep3');
					break;

				case 'file':
					$this->html_file = isset($_FILES['html_file']) ? $_FILES['html_file'] : false;
					if (!empty($this->html_file) && !empty($this->html_file['tmp_name']))
					{
						if (!$this->importFile($_FILES['html_file']))
							$this->errors[] = $this->module->l('Unable to import this file', 'adminmarketingestep3');
					}
					else
						$this->errors[] = $this->module->l('Please verify the required fields', 'adminmarketingestep3');
					break;

				case 'editor':
					$this->html_content = (string)Tools::getValue('campaign_html');

					$this->html_content = $this->copyImagesAndUpdateHTML($this->html_content);
					if ($this->saveHTML())
					{
						if ($found = $this->checkLocalUrls($this->html_content))
						{
							$a = $this->module->l('You are currently testing your Prestashop on a local server :', 'adminmarketingestep1');
							$b = $this->module->l('To enjoy the full IMAGE & TRACKING features, you need use a Prestashop online server !', 'adminmarketingestep1');
							$this->errors[] = $a.' http'.$found;
							$this->errors[] = $b;
							return false;
						}

						$images_to_upload = $this->parseImagesToUpload($this->html_content);
					}
					break;
			}
		}
	}

	/**
	 * Check if the $text contains a local URL like http://127.0.0.1/
	 * @param string $text
	 * @return boolean
	 */
	private function checkLocalUrls($text)
	{
		if (preg_match_all('#://localhost#i', $text, $matches))
			return $matches[0][0];
		if (preg_match_all('#://10\.[0-9]{1,3}.[0-9]{1,3}.[0-9]{1,3}#', $text, $matches))
			return $matches[0][0];
		if (preg_match_all('#://127\.[0-9]{1,3}.[0-9]{1,3}.[0-9]{1,3}#', $text, $matches))
			return $matches[0][0];
		if (preg_match_all('#://172\.[(16)(17)(18)(19)(20)(21)(22)(23)(24)(25)(26)(27)(28)(29)(30)(31)].[0-9]{1,3}.[0-9]{1,3}#', $text, $matches))
			return $matches[0][0];
		if (preg_match_all('#://192\.168.[0-9]{1,3}.[0-9]{1,3}#', $text, $matches))
			return $matches[0][0];
		if (preg_match_all('#://192\.168.[0-9]{1,3}.[0-9]{1,3}#', $text, $matches))
			return $matches[0][0];
		if (preg_match_all('#://::1#', $text, $matches))
			return $matches[0][0];

		return false;
	}

	/**
	 * Generate the import form
	 * @return string The HTML string containing the form
	 */
	private function generateImportFileForm()
	{
		$display = $this->getTemplatePath().'marketinge_step3/file_template.tpl';
		return $this->context->smarty->fetch($display);
	}
	private function generateImportURLForm()
	{
		$display = $this->getTemplatePath().'marketinge_step3/url_template.tpl';
		return $this->context->smarty->fetch($display);
	}

	/**
	 * Generate the images upload form
	 * @param Array $images_to_upload An array containing files informations
	 * @return string The HTML string containing the form
	 */
	private function generateImagesUploadForm(Array $images_to_upload)
	{
		$desc = $this->module->l('Your uploaded file contains %d picture(s) that refer to your hard drive', 'adminmarketingestep3');
		$desc = sprintf($desc, count($images_to_upload));
		$desc .= $this->module->l(', you need to upload them on your Prestashop newsletter storage directory.', 'adminmarketingestep3');

		$this->fields_form = array(
			'legend' => array(
				'title' => $this->module->l('Images upload', 'adminmarketingestep3'),
				'icon' => 'icon-beaker'
			),
			'description' => $desc,
			'input' => array(
				array (
					'type' => _PS_MODE_DEV_ ? 'text' : 'hidden',
					'lang' => false,
					'label' => 'Ref :',
					'name' => 'campaign_id',
					'col' => 1,
					'readonly' => 'readonly'
				)
			),
			'submit' => array(
				'title' => sprintf($this->module->l('Upload this %d image%s', 'adminmarketingestep3'),
					count($images_to_upload), (count($images_to_upload) > 1) ? 's' : ''),
				'name' => 'uploadImagesEmailingStep3',
				'icon' => 'process-icon-configure'
			),
			'buttons' => array(
				array (
					'type' => 'submit',
					'title' => $this->module->l('Ignore', 'adminmarketingestep3'),
					'name' => 'ignoreImagesEmailingStep3',
					'icon' => 'process-icon-back'
				)
			)
		);

		sort($images_to_upload);
		foreach ($images_to_upload as $key => $unavailable_image)
		{
			$this->fields_form['input'][] = array(
				'type' => 'file',
				'label' => urldecode(basename($unavailable_image)),
				'name' => 'image_upload_'.$key,
				'required' => false
			);
		}

		return parent::renderForm();
	}

	private function generateTabedImportForm()
	{
		$display = $this->getTemplatePath().'marketinge_step3/tab_import.tpl';
		$this->context->smarty->assign(array (
			'editor_form' => $this->generateEditorForm(),
			'file_form' => $this->generateImportFileForm(),
			'url_form' => $this->generateImportURLForm())
		);
		return $this->context->smarty->fetch($display);
	}

	/**
	 * Generate the editor form
	 * @return string The HTML string containing the form
	 */
	private function generateEditorForm()
	{
		$this->context->smarty->assign('campaign_html', $this->html_content);

		$template_path = $this->getTemplatePath().'marketinge_step3/editor_template.tpl';
		return $this->context->smarty->fetch($template_path);
	}

	/**
	 * Import an html file into the current campaign and save to DB
	 * @param Array $file The array containing infos about the file to upload
	 * @return boolean true if success, false otherwise
	 */
	private function importFile(Array $file)
	{
		$error = $file['error'];
		$type = $file['type'];

		if (($error === 0) && ($type == 'text/html'))
		{
			$file_path = $file['tmp_name'];
			$content_html = Tools::file_get_contents($file_path);

			if (!empty($content_html))
			{
				$this->html_content = $content_html;
				return $this->saveHTML();
			}
		}

		return false;
	}

	/**
	 * Import an html file from an url into the current campaign and save to DB
	 * @param string $url The URL to import
	 * @return boolean true if success, false otherwise
	 */
	private function importURL($url)
	{
		$url = urldecode((string)$url);
		if ((strpos($url, '://') === false))
			$url = 'http://'.$url;

		$url = implode('/', array_map('rawurlencode', explode('/', $url)));
		$url = str_replace('%3A//', '://', $url);
		$html_content = EMTools::getHtmlContent($url);

		$enc = mb_detect_encoding($html_content, 'UTF-8, ISO-8859-1, ASCII');

		if ($enc != 'UTF-8')
			$html_content = mb_convert_encoding($html_content, 'UTF-8', $enc);

		$html_content = htmlspecialchars_decode(htmlentities($html_content, ENT_IGNORE, 'UTF-8', false));

		if (!empty($html_content))
		{
			$html_content = $this->copyImagesAndUpdateHTML($html_content, $url);
			$html_content = $this->updateRelativeLinks($html_content, $url);
			$this->html_content = $html_content;
			return $this->saveHTML();
		}

		return false;
	}

	/**
	 * Get the html content of the current campaign from database
	 * If HTML is empty, we use views/templates/admin/marketinge_step3/marketinge_template.tpl
	 * @return string The HTML content of the current campaign or marketinge_template.tpl
	 */
	private function getHTMLContentDB()
	{
		$sql = new DbQuery();
		$sql->select('campaign_html');
		$sql->from('expressmailing_email');
		$sql->where('campaign_id = '.$this->campaign_id);
		$result = Db::getInstance()->getRow($sql);

		if (empty($result['campaign_html']))
		{
			$shops = Shop::getShops();
			$current_shop = $shops[Shop::getCurrentShop()];

			if (Configuration::get('PS_SSL_ENABLED'))
			{
				$domain_name = $current_shop['domain_ssl'];
				$current_shop_url = 'https://'.$domain_name.$current_shop['uri'];
			}
			else
			{
				$domain_name = $current_shop['domain'];
				$current_shop_url = 'http://'.$domain_name.$current_shop['uri'];
			}

			$img_dir = Tools::str_replace_once(_PS_ROOT_DIR_.'/', '', _PS_IMG_DIR_);

			$this->context->smarty->assign('shop_name', $current_shop['name']);
			$this->context->smarty->assign('img_dir', $img_dir);
			$this->context->smarty->assign('base_url', $current_shop_url);
			$this->context->smarty->assign('domain_name', $domain_name);
			$this->context->smarty->assign('logo_name', Configuration::get('PS_LOGO'));
			$this->context->smarty->assign('logo_width', Configuration::get('SHOP_LOGO_WIDTH'));
			$this->context->smarty->assign('logo_height', Configuration::get('SHOP_LOGO_HEIGHT'));

			$template = $this->getTemplatePath().'marketinge_step3/marketinge_template.tpl';
			Tools::clearCache();
			$this->html_content = $this->context->smarty->fetch($template);
			return true;
		}
		else
		{
			$this->html_content = $result['campaign_html'];
			return true;
		}
	}

	/**
	 * Parse the $html_content and return an array containing images that need to be uploaded
	 * @param string $html_content
	 * @return Array
	 */
	private function parseImagesToUpload($html_content)
	{
		$images = $this->getImgTags($html_content);
		$images_src = $images[2];

		$images_to_upload = array();
		foreach ($images_src as $image_src)
		{
			$parsed_url = parse_url($image_src);
			if (isset($parsed_url['scheme']) === false)
				$images_to_upload[] = $image_src;
		}

		return array_unique($images_to_upload);
	}

	/**
	 * Parse $html_content and updates all relative links to absolute links
	 * @param string $html_content
	 * @param string $imported_url
	 * @return string
	 */
	private function updateRelativeLinks($html_content, $imported_url)
	{
		$html_content = (string)$html_content;
		$imported_url = (string)$imported_url;

		$links = $this->getLinkTags($html_content);
		$hrefs = $links[2];

		foreach ($hrefs as $key => $href)
		{
			$absolute_link = $this->getAbsoluteFromRelativeURL($href, $imported_url);
			$html_content = str_replace($links[0][$key], $links[1][$key].$absolute_link.$links[3][$key], $html_content);
		}

		return $html_content;
	}

	/**
	 * Get all link tags from $html_content
	 * @param string $html_content
	 * @return Array
	 */
	private function getLinkTags($html_content)
	{
		$links = null;
		preg_match_all('/(<[^>]*href=[\'\"])((?<=href=\")[^\"]*|(?<=href=\')[^\']*)([\'\"][^>]*>)/i', (string)$html_content, $links);
		return $links;
	}

	/**
	 * Get all image tags from $html_content
	 * @param string $html_content
	 * @return Array
	 */
	private function getImgTags($html_content)
	{
		$images = null;
		preg_match_all('/(<img[^>]*src=[\'\"])((?<=src=\")[^\"]*|(?<=src=\')[^\']*)([\'\"][^>]*>)/i', (string)$html_content, $images);
		return $images;
	}

	/**
	 * Copy images included in html_content to local campaign storage and update html with new links
	 * @param string $html_content
	 * @param string $imported_url
	 * @param Array $files
	 * @return string
	 */
	private function copyImagesAndUpdateHTML($html_content, $imported_url = null, Array $files = null)
	{
		$html_content = (string)$html_content;
		if ($imported_url) $imported_url = (string)$imported_url;

		$images = $this->getImgTags($html_content);
		$complete_tags = $images[0];
		$before_src = $images[1];
		$srcs = $images[2];
		$after_src = $images[3];

		foreach ($srcs as $key => $src)
		{
			$filename = null;
			if (is_array($files))
			{
				foreach ($files as $file)
				{
					if (urldecode(basename($src)) == $file['name'])
					{
						$filename = $file['name'];
						$image_url = 'file://'.$file['tmp_name'];
						break;
					}
				}

				if (!isset($filename) || !isset($image_url))
					continue;
			}
			else
			{
				$image_url = '';
				if (strpos($src, '://') > -1)
					$image_url = $src;
				elseif ($imported_url)
					$image_url = $this->getAbsoluteFromRelativeURL($src, $imported_url);
				elseif (strpos($src, '/') === 0)
				{
					$image_url = _PS_BASE_URL_.__PS_BASE_URI__;
					if (Tools::substr($image_url, -1) == '/')
						$image_url .= Tools::substr($src, 1);
					else
						$image_url .= $src;
				}
			}

			if (!empty($image_url) && ($image_url = $this->copyFileToStorage($image_url, $filename)))
			{
				if (Configuration::get('PS_SSL_ENABLED') == 0)
					$final_img_url = _PS_BASE_URL_.__PS_BASE_URI__;
				else
					$final_img_url = _PS_BASE_URL_SSL_.__PS_BASE_URI__;

				if (Tools::substr($final_img_url, -1) != '/')
					$final_img_url .= '/';
				$final_img_url .= 'modules/expressmailing/campaigns/'.$this->campaign_id.'/';
					$final_img_url .= basename($image_url);

				$final_tag = $before_src[$key].$final_img_url.$after_src[$key];
				$html_content = str_replace($complete_tags[$key], $final_tag, $html_content);
			}
		}

		return $html_content;
	}

	/**
	 * Transform a relative link to an absolute link
	 * @param string $relative The relative link
	 * @param string $base_url The base url of the link
	 * @return string The absolute link
	 */
	private function getAbsoluteFromRelativeURL($relative, $base_url)
	{
		$relative = (string)$relative;
		$base_url = (string)$base_url;

		if (strpos($relative, '//') === 0)
			return 'http:'.$relative;

		if (parse_url($relative, PHP_URL_SCHEME) != '')
			return $relative;

		if ($relative[0] == '#' || $relative[0] == '?')
			return $base_url.$relative;

		$parsed_url = parse_url($base_url);
		$scheme = $parsed_url['scheme'];
		$host = $parsed_url['host'];
		$path = isset($parsed_url['path']) ? $parsed_url['path'] : '';
		$path = preg_replace('#/[^/]*$#', '', $path);

		if ($relative[0] == '/')
			$path = '';

		$absolute = $host.$path.'/'.$relative;

		$re = array('#(/.?/)#', '#/(?!..)[^/]+/../#');
		$n = 1;
		while ($n > 0)
			$absolute = preg_replace($re, '/', $absolute, -1, $n);

		return $scheme.'://'.$absolute;
	}

	/**
	 * Copy a remote or local file to the local campaign storage
	 * @param string $url
	 * @param string $filename The name of the resulting file
	 * @return mixed Null if fail or a string containing the path of the resulting image.
	 */
	private function copyFileToStorage($url, $filename = null)
	{
		$dest = $this->module->getPreviewFolder();

		$dest .= $this->campaign_id.DIRECTORY_SEPARATOR;
		if (!Tools::file_exists_no_cache($dest))
		{
			mkdir($dest, 0777, true);
			Tools::copy(_PS_MODULE_DIR_.'expressmailing/index.php', $dest.'index.php');
		}

		if ($filename)
			$dest .= (string)$filename;
		else
			$dest .= basename((string)$url);

		$dest = urldecode($dest);
		$dest = str_replace(' ', '_', $dest);

		$dest = EMTools::removeAccents($dest);
		if (($pos = strpos($dest, '?')) !== false)
			$dest = Tools::substr ($dest, 0, $pos);

		if (function_exists('curl_version'))
		{
			$user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Mozilla/5.0';

			$ch = curl_init((string)$url);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
			curl_setopt($ch, CURLOPT_CRLF, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			$raw = curl_exec($ch);

			curl_close ($ch);
			if (file_exists($dest))
				unlink($dest);

			$fp = fopen($dest, 'x');
			fwrite($fp, $raw);
			fclose($fp);

			if (file_exists($dest))
				return $dest;
		}
		else
			if (Tools::copy((string)$url, $dest))
				return $dest;

		return null;
	}

	private function cleanHTML()
	{
		$html_cleaner = new HtmlCleaner();
		$this->html_content = $html_cleaner->cleanHTML($this->html_content);
	}

	private function encodeURLs()
	{
		$elements = $this->getImgTags($this->html_content);
		foreach ($this->getLinkTags($this->html_content) as $key => $value)
			$elements[$key] = array_merge($elements[$key], $value);

		$srcs = array_unique($elements[2]);
		foreach ($srcs as $key => $src)
		{
			$corrected_src = implode('/', array_map('rawurlencode', explode('/', $src)));
			$corrected_src = str_replace(array('%3A//', '%23%23'), array('://', '##'), $corrected_src);
			$this->html_content = str_replace($elements[0][$key], $elements[1][$key].$corrected_src.$elements[3][$key], $this->html_content);
		}
	}

	/**
	 * Saves the html_content into DB
	 */
	private function saveHTML()
	{
		$this->encodeURLs();
		$this->cleanHTML();

		return Db::getInstance()->update('expressmailing_email', array(
			'campaign_html' => pSQL($this->html_content, true)
			), 'campaign_id = '.pSQL($this->campaign_id)
		);
	}

}
