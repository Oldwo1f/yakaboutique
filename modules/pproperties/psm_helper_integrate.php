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
* --- DO NOT REMOVE OR MODIFY THIS LINE PSM_VERSION[1.1] ---
*/

if (!defined('_PS_VERSION_'))
	exit;

if (!function_exists('psmHelperIntegrate'))
{
	function psmHelperIntegrate($modules)
	{
		static $s_modules = array('psmextmanager', 'pproperties');
		$s_modules = array_merge($s_modules, $modules);
		psmHelperIntegrateFile('psm_helper_integrate.php', $s_modules);
		psmHelperIntegrateFile('psm_helper.php', $s_modules);
	}

	function psmHelperIntegrateFile($target, $modules)
	{
		$version = '';
		$origin = '';
		$files = array();
		foreach ($modules as $module)
		{
			$file = _PS_MODULE_DIR_.$module.'/'.$target;
			if (file_exists($file))
			{
				$content = Tools::file_get_contents($file);
				$ver = (($start = strpos($content, 'PSM_VERSION[')) !== false && ($end = strpos($content, ']', $start)) !== false)
						? Tools::substr($content, $start + 12, $end - $start - 12) : false;
				if ($ver && Tools::version_compare($ver, $version, '>'))
				{
					$version = $ver;
					$origin = $file;
				}
				$files[$file] = $ver;
			}
		}
		foreach ($files as $file => $ver)
			if (Tools::version_compare($version, $ver, '>') && $origin != $file)
				Tools::copy($origin, $file);
	}
}
