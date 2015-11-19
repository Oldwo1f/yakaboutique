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
* --- DO NOT REMOVE OR MODIFY THIS LINE PSM_VERSION[1.6] --- *
*/

if (!defined('_PS_VERSION_'))
	exit;

class PSMCore
{
	private static $s_cache = array();
	private static $append = null;

	public static function cacheSet($key, $value)
	{
		self::$s_cache[$key] = $value;
	}

	public static function cacheGet($key)
	{
		return (isset(self::$s_cache[$key]) ? self::$s_cache[$key] : false);
	}

	public static function clearCache()
	{
		Tools::clearSmartyCache();
		Tools::clearXMLCache();
		Media::clearCache();
		Tools::generateIndex();
	}

	public static function md5Compare($file, $md5)
	{
		return (is_file($file) && (md5_file($file) == $md5));
	}

	public static function md5filesCompare($file1, $file2)
	{
		return (is_file($file1) && is_file($file2) && (md5_file($file1) == md5_file($file2)));
	}

	public static function normalizePath($path, $option = null)
	{
		if ($option !== null)
		{
			switch ($option)
			{
				case 'relative':
					$path = str_replace(array(_PS_ROOT_DIR_.'/', _PS_ROOT_DIR_.'\\'), '', $path);
					break;
				default:
					break;
			}
		}
		return str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $path);
	}

	public static function protectDirectory($dir, $excludes = false)
	{
		if (is_dir($dir))
		{
			if (is_array($excludes))
			{
				foreach ($excludes as &$d)
				{
					if (Tools::substr($d, -1, 1) == '/')
						$d = Tools::substr($d, 0, Tools::strlen($d) - 1);
					$d = self::normalizePath($d);
				}
			}
			return self::protectDirectoryInternal($dir, $excludes);
		}
	}

	public static function integrationVersion($instance)
	{
		if (is_object($instance))
		{
			if (method_exists($instance, 'integrationVersion'))
				return $instance->integrationVersion();
			else if ($instance instanceof Module)
				return $instance->version;
			else if (method_exists($instance, 'version'))
				return $instance->version();
		}
		return false;
	}

	public static function getAdminModuleLink($module, $params = null)
	{
		return 'index.php?controller=adminmodules&configure='.$module->name.'&tab_module='.$module->tab.'&module_name='.$module->name.
				'&token='.Tools::getAdminTokenLite('AdminModules').($params == null ? '' : '&'.$params);
	}

	public static function getPSMId($module)
	{
		$key = 'PSM_ID_'.Tools::strtoupper($module->name);
		if (!isset(self::$s_cache[$key]))
		{
			self::$s_cache[$key] = Configuration::getGlobalValue($key);
			if (self::$s_cache[$key] === false)
			{
				self::$s_cache[$key] = '';
				for ($i = 0; $i < 3; $i++)
					self::$s_cache[$key] .= Tools::passwdGen(4, 'NUMERIC').'-';
				self::$s_cache[$key] .= Tools::passwdGen(4, 'NUMERIC');
				Configuration::updateGlobalValue($key, self::$s_cache[$key]);
			}
		}
		return self::$s_cache[$key];
	}

	public static function getPlugin($name, $base = null)
	{
		$cache_id = 'psm.plugin:'.$name.'!'.$base;
		if (!Cache::isStored($cache_id))
		{
			if ($base == null && !Module::isEnabled($name))
				Cache::store($cache_id, false);
			else
			{
				$classname = Tools::toCamelCase($name, true).'Plugin';
				$basedir = ($base == null ? $name : $base.'/plugins/'.Tools::strtolower($name));
				$file = _PS_MODULE_DIR_.$basedir.'/'.$classname.'.php';
				$file = self::normalizePath($file);
				if (is_file($file))
				{
					require_once($file);
					Cache::store($cache_id, new $classname());
				}
				else
					Cache::store($cache_id, false);
			}
		}
		return Cache::retrieve($cache_id);
	}

	private static function protectDirectoryInternal($dir, $excludes = false)
	{
		if (is_array($excludes))
			if (in_array(self::normalizePath($dir), $excludes))
				return;

		$files = scandir($dir);
		foreach ($files as $file)
		{
			if ($file != '.' && $file != '..' && is_dir($dir.'/'.$file))
				self::protectDirectoryInternal($dir.'/'.$file, $excludes);
		}
		if (!is_file($dir.'/index.php'))
		{
			@file_put_contents($dir.'/index.php', '<?php
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Location: ../");
exit;'
			);
		}
	}

	public static function amendCSS(&$css_files, $files = false)
	{
		foreach ($css_files as $url => $media)
		{
			if (strpos($url, '?') === false)
			{
				$replace = !is_array($files);
				if (!$replace)
				{
					foreach ($files as $file)
						if (strripos($url, $file, 0) === (Tools::strlen($url) - Tools::strlen($file)))
						{
							$replace = true;
							break;
						}
				}
				if ($replace)
				{
					$path = _PS_ROOT_DIR_.Tools::str_replace_once(__PS_BASE_URI__, '/', $url);
					if (file_exists($path))
					{
						$css_files[$url.'?'.@filemtime($path)] = $css_files[$url];
						unset($css_files[$url]);
					}
				}
			}
		}
	}

	public static function amendJS(&$js_files, $files = false)
	{
		foreach ($js_files as $key => $url)
		{
			if (strpos($url, '?') === false)
			{
				$replace = !is_array($files);
				if (!$replace)
				{
					foreach ($files as $file)
						if (strripos($url, $file, 0) === (Tools::strlen($url) - Tools::strlen($file)))
						{
							$replace = true;
							break;
						}
				}
				if ($replace)
				{
					$path = _PS_ROOT_DIR_.Tools::str_replace_once(__PS_BASE_URI__, '/', $url);
					if (file_exists($path))
					{
						$js_files[$key] .= '?'.@filemtime($path);
						unset($js_files[$url]);
					}
				}
			}
		}
	}

	public static function getDB()
	{
		static $db = null;
		if ($db === null)
			$db = Db::getInstance();
		return $db;
	}

	public static function trace($var = null)
	{
		//die(var_dump(debug_backtrace()));
		if ($var)
		{
			if (is_object($var) || is_array($var))
				var_dump($var);
			else
				print_r($var);
		}
		die(self::traceToString('<br/>'));
	}

	public static function traceToString($separator = PHP_EOL)
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

	public static function log()
	{
		static $file = null;
		if ($file === null)
		{
			$file = _PS_ROOT_DIR_.'/log/psm.log';
			if (self::$append === null)
				self::$append = (Tools::getValue('ajax') || Configuration::get('PSM_LOG'));
			if (!self::$append)
				Tools::deleteFile($file);
		}
		$formatted_message = '----------------------------- '.date('Y/m/d - H:i:s').PHP_EOL;
		foreach (func_get_args() as $message)
		{
			if (!is_string($message))
				if (is_bool($message))
					$message = ($message ? 'true' : 'false');
				elseif ($message === null)
					$message = 'null';
				else
					$message = print_r($message, true);
			$formatted_message .= $message.PHP_EOL;
		}
		file_put_contents($file, $formatted_message, FILE_APPEND);
	}

	public static function logAppend($append = true, $persist = null)
	{
		self::$append = $append;
		if (is_bool($persist))
			Configuration::updateValue('PSM_LOG', (int)$persist);
	}

	public static function logTrace()
	{
		$args = func_get_args();
		$args[] = self::traceToString();
		call_user_func_array('self::log', $args);
	}

	/**
	* Reproduce array_column function before php version 5.5.0 
	*/
	public static function arrayColumn($input, $column_key = null, $index_key = null)
	{
		if (!is_array($input))
			return false;
		
		$column_key = ($column_key !== null) ? (string)$column_key : null;
		if ($index_key !== null)
			if (is_float($index_key) || is_int($index_key))
				$index_key = (int)$index_key;
			else
				$index_key = (string)$index_key;

		$result_array = array();
		foreach ($input as $row)
		{
			$key = $value = null;
			$key_set = $value_set = false;
			if ($index_key !== null && array_key_exists($index_key, $row))
			{
				$key_set = true;
				$key = (string)$row[$index_key];
			}
			if ($column_key === null)
			{
				$value_set = true;
				$value = $row;
			}
			elseif (is_array($row) && array_key_exists($column_key, $row))
			{
				$value_set = true;
				$value = $row[$column_key];
			}
			if ($value_set)
			{
				if ($key_set)
					$result_array[$key] = $value;
				else
					$result_array[] = $value;
			}
		}
		return $result_array;
	}
}
