<?php
/**
* NOTICE OF LICENSE
*
* This source file is subject to the commercial software
* license agreement available through the world-wide-web at this URL:
* http://psandmore.com/licenses/sla
* If you are unable to obtain the license through the
* world-wide-web, please send an email to
* support@psandmore.com so we can send you a copy immediately.
*
* @author    PS&More www.psandmore.com <support@psandmore.com>
* @copyright 2011-2015 PS&More
* @license   psandmore.com/licenses/sla
*/

class AdminPpropertiesController extends ModuleAdminController
{
	public function __construct()
	{
		parent::__construct();
		if (!$this->module->active)
			Tools::redirectAdmin($this->context->link->getAdminLink('AdminHome'));
	}

	public function postProcess()
	{
		if ($this->ajax)
			return parent::postProcess();
		$this->redirect_after = $this->context->link->getAdminLink('AdminModules', true).'&module_name='.$this->module->name.
								'&tab_module='.$this->module->tab.'&configure='.$this->module->name;
	}

	public function ajaxProcessIntegrationModuleCheckForUpdates()
	{
		$this->content_only = true;
		$status = 'error';
		if ($json = Tools::getValue('json'))
		{
			$request = Tools::jsonDecode($json, true);
			$status = $this->module->setupInstance()->downloadExtraModule($request['module'], $request['ver']);
		}
		$json = array('status' => $status);
		$this->content = Tools::jsonEncode($json);
	}

	public function ajaxProcessIntegrationModuleIgnore()
	{
		$this->content_only = true;
		$this->content = $this->module->setupInstance()->processIntegrationModule(1);
	}

	public function ajaxProcessIntegrationModuleIntegrate()
	{
		$this->content_only = true;
		$this->content = $this->module->setupInstance()->processIntegrationModule(0);
	}

	public function ajaxProcessInfoQuery()
	{
		$this->content_only = true;
		$result = array('status' => false);

		if (time() > (int)Configuration::get('PP_INFO_CHECK_TIME'))
		{
			$protocol = Tools::getCurrentUrlProtocolPrefix();
			$iso_lang = Context::getContext()->language->iso_code;
			$iso_country = Context::getContext()->country->iso_code;
			$stream_context = @stream_context_create(array('http' => array('method'=> 'GET', 'timeout' => 3)));

			$old_content = $this->getInfo();
			$msg = ($old_content === false ? 0 : $old_content[0]);

			$shop_url = ShopUrl::getShopUrls($this->context->shop->id)->where('main', '=', 1)->getFirst();
			$shop = ($shop_url ? $shop_url->getURL() : Tools::getShopDomain());
			$date = Db::getInstance()->getValue('SELECT `date_add` FROM `'._DB_PREFIX_.'configuration` WHERE `name` = \'PSM_ID_'.
												Tools::strtoupper($this->module->name).'\'');
			$psm_date = ($date ? urlencode(date('Y-m-d H:i:s', strtotime($date))) : '');
			$plugins_string = '';
			$plugins = $this->module->plugins();
			foreach ($plugins as $name => $api_version)
				if (Module::isInstalled($name))
					$plugins_string .= '&'.$name.'='.$this->moduleVersion($name);

			$url = $protocol.'store.psandmore.com/query/?key='.$this->module->name.
												'&ver='.$this->module->version.
												'&psm='.PSM::getPSMId($this->module).
												'&psm_date='.$psm_date.$plugins_string.
												'&msg='.$msg.
												'&iso_country='.$iso_country.
												'&iso_lang='.$iso_lang.
												'&shop='.urlencode($shop);
			$contents = Tools::file_get_contents($url, false, $stream_context);
			$check_info_offset = 3600;
			if ($contents !== false)
			{
				$content = explode('|', $contents);
				if (is_numeric($content[0]))
				{
					if (!$this->infoIgnore(false, $content[0]))
					{
						if (Validate::isCleanHtml($content[1]))
						{
							$this->putInfo($contents);
							$check_info_offset = 86400;
						}
					}
				}
				else
				{
					if ($content[0] == 'hide')
						Configuration::deleteByName('PP_INFO_CONTENT');
				}
			}
			Configuration::updateValue('PP_INFO_CHECK_TIME', time() + $check_info_offset);
		}

		$content = $this->getInfo();
		if ($content !== false)
		{
			if (!$this->infoIgnore($content))
			{
				if (Validate::isCleanHtml($content[1]))
				{
					$result['status'] = 'success';
					$result['content'] = $content[1];
				}
			}
		}
		$this->content = Tools::jsonEncode($result);
	}

	public function ajaxProcessInfoIgnore()
	{
		$content = $this->getInfo();
		if ($content !== false)
			$this->putInfo($content[0].'|ignore');
	}

	private function infoIgnore($content = false, $id = 0)
	{
		if ($content === false)
			$content = $this->getInfo();
		if ($content !== false)
		{
			if ($content[1] == 'ignore')
			{
				if ((int)$id > 0)
					return ((int)$content[0] == (int)$id);
				return true;
			}
		}
		return false;
	}

	private function putInfo($str)
	{
		Configuration::updateValue('PP_INFO_CONTENT', bin2hex($str));
	}

	private function getInfo()
	{
		$contents = Configuration::get('PP_INFO_CONTENT');
		if ($contents !== false)
		{
			$content = explode('|', $this->hex2bin($contents));
			if (is_numeric($content[0]))
				return $content;
		}
		return false;
	}

	private function moduleVersion($name)
	{
		return (Db::getInstance()->getValue('SELECT version FROM `'._DB_PREFIX_.'module` WHERE `name` = \''.pSQL($name).'\''));
	}

	private function hex2bin($str)
	{
		if (function_exists('hex2bin'))
			return hex2bin($str);
		$sbin = '';
		$len = Tools::strlen($str);
		for ($i = 0; $i < $len; $i += 2)
			$sbin .= pack('H*', Tools::substr($str, $i, 2));
		return $sbin;
	}
}
