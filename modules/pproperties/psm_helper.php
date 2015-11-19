<?php
/**
* PS&More Extension Manager
*
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
*
* --- DO NOT REMOVE OR MODIFY THIS LINE PSM_VERSION[1.5] ---
*/

if (!defined('_PS_VERSION_'))
	exit;

if (!function_exists('psmIntegrateCore'))
{
	static $psm_clear_cache;
	function psmIntegrateCore($module, $file, array &$errors, $start_signature = 'PSM_VERSION[', $end_signature = ']')
	{
		$target = _PS_ROOT_DIR_.'/classes/'.basename($file);
		return psmIntegrateCopy($file, $target, $errors, $start_signature, $end_signature);
	}

	function psmIntegrateOverride($module, $file, array &$errors, $start_signature = 'PSM_VERSION[', $end_signature = ']')
	{
		$source = _PS_MODULE_DIR_.$module->name.'/setup/core/'.$file;
		$target = _PS_ROOT_DIR_.'/'.$file;
		return psmIntegrateCopy($source, $target, $errors, $start_signature, $end_signature);
	}

	function psmIntegrateTpl($module, $file, array &$errors, $start_signature = 'PSM_VERSION[', $end_signature = ']')
	{
		if (strpos($file, 'admin/') === 0)
		{
			$source = _PS_MODULE_DIR_.$module->name.'/views/templates/'.$file;
			$target = Context::getContext()->smarty->getTemplateDir(0).Tools::substr($file, 6);
		}
		if (isset($target))
			return psmIntegrateCopy($source, $target, $errors, $start_signature, $end_signature);
		return true;
	}

	function psmIntegrateCopy($source, $target, array &$errors, $start_signature, $end_signature)
	{
		if (!($copy_file = !file_exists($target)))
		{
			$source_ver = psmFindSignature(Tools::file_get_contents($source), $start_signature, $end_signature);
			$target_ver = psmFindSignature(Tools::file_get_contents($target), $start_signature, $end_signature);
			$copy_file = Tools::version_compare($source_ver, $target_ver, '>');
		}
		if ($copy_file)
		{		
			if (!file_exists(dirname($target)))
				mkdir(dirname($target), 0777, true);
			Tools::copy($source, $target);
			if (file_exists($target))
			{
				chmod($target, 0664);
				Tools::deleteFile(_PS_ROOT_DIR_.'/'.PrestaShopAutoload::INDEX_FILE);
			}
			else
			{
				$errors[] = 'Cannot create file "'.$target.'". Please check write permissions.';
				return false;
			}
		}
		return true;
	}

	function psmFindSignature($content, $start_signature, $end_signature)
	{
		if (($start = strpos($content, $start_signature)) !== false && ($end = strpos($content, $end_signature, $start)) !== false)
		{
			$length = Tools::strlen($start_signature);
			return Tools::substr($content, $start + $length, $end - $start - $length);
		}
		return false;
	}

	function psmClearCache()
	{
		Tools::clearSmartyCache();
		Media::clearCache();
		Tools::generateIndex();
	}

	function psmSetup($module)
	{
		$cache_id = 'psmSetup::'.$module->name;
		if (!Cache::isStored($cache_id))
		{
			require_once(_PS_MODULE_DIR_.'psmextmanager/psmsetup20.php');
			require_once(_PS_MODULE_DIR_.$module->name.'/'.$module->name.'setup.php');
			$classname = Tools::toCamelCase($module->name, true).'Setup';
			$instance = new $classname($module);
			Cache::store($cache_id, $instance);
		}
		return Cache::retrieve($cache_id);
	}

	function psmPPsetup($module, $name = '', $file = '')
	{
		if (is_array($module))
		{
			$name = '';
			$file = $module['ppsetup'];
			$module = $module['module'];
		}
		if ($name == '' && $module->name != 'pproperties')
			$name = $module->name;
		if ($file == '')
			$file = _PS_MODULE_DIR_.Tools::strtolower($name != '' ? $name : $module->name).'/ppsetup.php';
		$file = str_replace('\\', '/', $file);
		$cache_id = 'psmPPsetup::'.$module->name.':'.$name.'!'.$file;
		if (!Cache::isStored($cache_id))
		{
			$classname = 'PPSetup'.($name != '' ? Tools::toCamelCase($name, true) : '');
			if (is_file($file))
			{
				require_once(_PS_MODULE_DIR_.'pproperties/psmsetup16.php');
				if ($name != '' && $name != 'pproperties')
					require_once(_PS_MODULE_DIR_.'pproperties/ppsetup.php');
				require_once($file);
				$result = new $classname($module);
			}
			else
				$result = false;
			Cache::store($cache_id, $result);
		}
		return Cache::retrieve($cache_id);
	}

	function psmPPsetupExtraModulesDir()
	{
		return _PS_MODULE_DIR_.'pproperties/setup/extra/modules';
	}

	function psmPPsetupExtraModulesVars($module)
	{
		$vars = array();
		$vars['module'] = $module;
		$vars['root'] = psmPPsetupExtraModulesDir();
		$vars['base'] = $vars['root'].'/'.$module->name;
		$vars['dirname'] = $vars['base'].'/'.$module->version;
		$vars['ppsetup'] = $vars['dirname'].'/ppsetup.php';
		return $vars;
	}

	function psmppropertiesIntegration($vars, $install)
	{
		Tools::deleteDirectory($vars['base']);
		if ($install)
		{
			if (file_exists($vars['base']))
				return array('error_delete_directory' => $vars['base']);
			mkdir($vars['dirname'], 0755, true);
			Tools::copy(_PS_MODULE_DIR_.$vars['module']->name.'/ppsetup.php', $vars['ppsetup']);
			PSM::protectDirectory($vars['base']);
			if (!is_file($vars['ppsetup']))
				return array('error_create_file' => $vars['ppsetup']);
		}
		return true;
	}

	function psmTrace($var = null)
	{
		//die(var_dump(debug_backtrace()));
		if ($var)
		{
			if (is_object($var) || is_array($var))
				var_dump($var);
			else
				print_r($var);
		}
		die(psmTraceToString('<br/>'));
	}

	function psmTraceToString($separator = PHP_EOL)
	{
		$s = '';
		$backtrace = debug_backtrace();
		foreach ($backtrace as $trace)
		{
			if (isset($trace['file']))
				$s .= $separator.$trace['file'];
			if (isset($trace['line']))
				$s .= ':'.$trace['line'];
			$s .= ' => ';
			if (isset($trace['class']))
				$s .= $trace['class'];
			if (isset($trace['type']))
				$s .= $trace['type'];
			if (isset($trace['function']))
				$s .= $trace['function'];
		}
		return $s;
	}

	function psmDownloadModule($module_name)
	{
		$success = false;
		if (!is_dir(_PS_MODULE_DIR_.$module_name))
		{
			$content = Tools::file_get_contents('http://store.psandmore.com/query/download.php?ps='._PS_VERSION_.'&module='.$module_name);
			if ($content)
			{
				$zip_file = _PS_MODULE_DIR_.$module_name.'.zip';
				Tools::deleteFile($zip_file);
				if (file_put_contents($zip_file, $content) !== false)
				{
					if (Tools::ZipTest($zip_file))
						$success = Tools::ZipExtract($zip_file, _PS_MODULE_DIR_);
					Tools::deleteFile($zip_file);
				}
			}
		}
		return $success;
	}
}
