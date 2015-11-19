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
*/

if (!defined('_PS_VERSION_'))
	exit;

class PSMSetup16
{
	protected $module;

	public function __construct($module)
	{
		$this->module = $module;
	}

	public function installAdminTab($class_name, $parent = null, $active = true)
	{
		$tab = new Tab();
		$tab->class_name = $class_name;
		$tab->active = $active;
		$tab->name = array();
		foreach (Language::getLanguages(true) as $lang)
			$tab->name[$lang['id_lang']] = $this->module->displayName;
		$tab->id_parent = (int)Tab::getIdFromClassName($parent == null ? 'AdminParentModules' : $parent);
		$tab->module = $this->module->name;
		return $tab->add();
	}

	public function uninstallAdminTab($class_name)
	{
		$id_tab = (int)Tab::getIdFromClassName($class_name);
		if ($id_tab)
		{
			$tab = new Tab($id_tab);
			return $tab->delete();
		}
		return false;
	}

	public function setupDB()
	{
		$result = true;
		$db = PSM::getDB();
		$db_data = $this->dbData();
		foreach ($db_data as $data)
		{
			reset($data);
			switch (key($data))
			{
				case 'table':
					$table = $data['table'];
					if (!$this->dbTableExists($table))
					{
						$sql = 'CREATE TABLE IF NOT EXISTS`'._DB_PREFIX_.$table.'` (';
						$sql .= $data['sql'];
						$sql .= ') ENGINE = '._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci';
						if (isset($data['options']))
							$sql .= ' '.$data['options'];
						$sql .= ';';
						if ($db->execute($sql) === false)
							$result = false;
					}
					break;
				case 'column':
					$table = $data['table'];
					$column = $data['column'];
					if (!$this->dbColumnExists($table, $column))
						if (!$this->dbTableExists($table) || $db->execute('ALTER TABLE `'._DB_PREFIX_.$table.'` ADD `'.$column.'` '.$data['sql']) === false)
							$result = false;
					break;
				case 'func':
					$ret = (isset($data['param']) ? call_user_func($data['func'], $data['param']) : call_user_func($data['func']));
					if ($ret && ($db->execute($data['sql']) === false))
						$result = false;
				default:
					break;
			}
		}
		return $result;
	}

	public function checkDbIntegrity()
	{
		$result = array();
		$db_data = $this->dbData();
		foreach ($db_data as $data)
		{
			reset($data);
			switch (key($data))
			{
				case 'table':
					$table = $data['table'];
					if (!$this->dbTableExists($table))
						$result[] = array('key' => 'table_not_found', 'table' => $table);
					break;
				case 'column':
					$table = $data['table'];
					$column = $data['column'];
					if (!$this->dbColumnExists($table, $column))
						$result[] = array('key' => 'column_not_found', 'table' => $table, 'column' => $column);
					break;
				default:
					break;
			}
		}
		return $result;
	}

	protected function dbData()
	{
		return array();
	}

	public function replaceStrings($filename, $param, $install_mode)
	{
		if (!file_exists($filename))
			return 'file_not_found';
		$count = 0;
		$content = Tools::file_get_contents($filename);
		foreach ($param['replace'] as $replace_args)
		{
			if ($install_mode)
			{
				$search = $replace_args[0];
				$replace = $replace_args[1];
			}
			else
			{
				if (!isset($replace_args['uninstall']) || $replace_args['uninstall'] !== false)
				{
					$search = $replace_args[1];
					$replace = $replace_args[0];
				}
			}
			$content = str_replace($search, $replace, $content, $cnt);
			$count += $cnt;
		}
		if ($count > 0)
			file_put_contents($filename, $content);
		return '';
	}

	public function checkReplacedStrings($filename, $param)
	{
		$result = array();
		if (!file_exists($filename))
		{
			$result[] = array('file_not_found' => $filename);
			return $result;
		}
		$content = Tools::file_get_contents($filename);
		foreach ($param['replace'] as $replace_args)
		{
			$count = (isset($replace_args['count']) ? (int)$replace_args['count'] : 0);
			if ($count >= 0 || _PS_MODE_DEV_ === true)
			{
				str_replace($replace_args[1], '', $content, $cnt);
				if ($count == 0)
				{
					if ($cnt == 0)
						$result[] = array('string_not_found' => array($replace_args[0], $replace_args[1]));
				}
				else if ($count > 0)
				{
					if ($count != $cnt)
						$result[] = array('string_count' => array($replace_args[0], $replace_args[1], $count, $cnt));
				}
				else if ($cnt == 0 && _PS_MODE_DEV_ === true)
					$result[] = array('string_not_found_note' => array($replace_args[0], $replace_args[1]));
			}
		}
		return $result;
	}

	public function dbTableExists($table)
	{
		$result = PSM::getDB()->executeS('SHOW TABLE STATUS FROM `'._DB_NAME_.'` like \''._DB_PREFIX_.$table.'\'');
		return (count($result) > 0);
	}

	public function dbColumnExists($table, $column)
	{
		$result = PSM::getDB()->executeS('SHOW COLUMNS FROM `'._DB_NAME_.'`.`'._DB_PREFIX_.$table.'` like \''.$column.'\'');
		return (count($result) > 0);
	}

	public function setupSmarty($install)
	{
		$line = $this->smartyIntegrationString();
		if ($line !== false)
		{
			$file = $this->smartyConfigFile();
			if ($install)
				$this->appendLine($file, $line);
			else
				$this->removeLine($file, $line);
		}
	}

	public function checkSmartyIntegrity()
	{
		$line = $this->smartyIntegrationString();
		if ($line !== false)
		{
			$file = $this->smartyConfigFile();
			return (strpos(Tools::file_get_contents($file), $line) !== false);
		}
		return true;
	}

	public function smartyIntegrationString()
	{
		return false;
	}

	public function smartyConfigFile($fullpath = true)
	{
		return (($fullpath ? _PS_ROOT_DIR_.'/' : '').'config/smarty.config.inc.php');
	}

	protected function appendInitFunctionLine($source_file, $target_file, $func, $signature)
	{
		if (file_exists($source_file) && file_exists($target_file))
		{
			$content = Tools::file_get_contents($source_file);
			if (strpos($content, 'function '.$func) !== false)
			{
				$class = basename($target_file, '.php');
				$str = $class.'::'.$func.';';
				$content = Tools::file_get_contents($target_file);
				if (strpos($content, $str) === false)
				{
					if ($signature)
						$str .= ' // '.$signature;
					file_put_contents($target_file, PHP_EOL.$str.PHP_EOL, FILE_APPEND);
				}
			}
		}
	}

	protected function appendLine($file, $line)
	{
		if (file_exists($file))
		{
			$content = Tools::file_get_contents($file);
			if (strpos($content, $line) === false)
				file_put_contents($file, PHP_EOL.$line.PHP_EOL, FILE_APPEND);
		}
	}

	protected function removeLine($file, $line)
	{
		if (file_exists($file))
		{
			$new_content = '';
			$empty_lines = 0;
			$has_changes = false;
			$content = file($file);
			foreach ($content as $l)
			{
				$empty_lines = (trim($l) == false ? $empty_lines + 1 : 0);
				if (strpos($l, $line) !== false || $empty_lines > 1)
					$has_changes = true;
				else
					$new_content .= $l;
			}
			if ($has_changes)
				file_put_contents($file, $new_content);
		}
	}
}
