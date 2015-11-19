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

class EMTools
{
	private static $prefixes_countries = array();
	private $date_months = array();
	private $date_suffix = array();
	private $date_format = array();

	private function l($string)
	{
		return Translate::getModuleTranslation('expressmailing', $string, 'em_tools');
	}

	public function __construct()
	{
		$this->date_months = array(1 => Translate::getAdminTranslation('January'),
			2 => Translate::getAdminTranslation('February'),
			3 => Translate::getAdminTranslation('March'),
			4 => Translate::getAdminTranslation('April'),
			5 => Translate::getAdminTranslation('May'),
			6 => Translate::getAdminTranslation('June'),
			7 => Translate::getAdminTranslation('July'),
			8 => Translate::getAdminTranslation('August'),
			9 => Translate::getAdminTranslation('September'),
			10 => Translate::getAdminTranslation('October'),
			11 => Translate::getAdminTranslation('November'),
			12 => Translate::getAdminTranslation('December'),
		);

		$this->date_suffix = array(1 => $this->l('st'),
			2 => $this->l('nd'),
			3 => $this->l('rd'),
			4 => $this->l('th')
		);

		/* FR/PL : |d| |m| |Y| */
		/* EN : |M| |d||S|, |Y| */
		$this->date_format = $this->l('|M| |d||S|, |Y|');
	}

	private static function loadPrefixesCountries()
	{
		$sql = new DbQuery();
		$sql->select('call_prefix');
		$sql->from('country');
		$tmp = Db::getInstance()->executeS($sql);

		foreach ($tmp as $prefixe)
			if ($prefixe['call_prefix']) self::$prefixes_countries[] = $prefixe['call_prefix'];
	}

	public static function getShopPrefixeCountry()
	{
		$prefix = Context::getContext()->country->call_prefix;
		$shop_prefixe = Configuration::get('PS_SHOP_COUNTRY_ID');

		if (!empty($shop_prefixe))
		{
			$sql = new DbQuery();
			$sql->select('call_prefix');
			$sql->from('country');
			$sql->where('id_country = '.(int)$shop_prefixe);
			$prefix = Db::getInstance()->getValue($sql);
		}

		return $prefix;
	}

	public static function cleanNumber($number, $code_iso_country)
	{
		$number = preg_replace('#^([o0-9+ .()/_-]+).*$#', '$1', $number); // Remove all after number (allow some separators)
		$number = str_replace('O', '0', $number); // Letter O to zero
		$number = preg_replace('#[^0-9+]+#', '', $number); // Remove allowed separators (above)
		$number = preg_replace('#^.*\+#', '+', $number); // Remove all before the + sign

		$number = preg_replace('#^00+#', '+', $number); // Replace 00 by a +
		$number = preg_replace('#^0#', '+'.$code_iso_country.' ', $number); // Local number with initial zero
		$number = preg_replace('#^([^0+])#', '+'.$code_iso_country.' $1', $number); // Local number without initial zero

		// Add a space char after known prefixes
		// -------------------------------------
		if (!Tools::strpos($number, ' '))
		{
			$prefixe = Tools::substr($number, 0, 4); // Try 0000 ou +000
			$prefixe_clean = ltrim($prefixe, '+');
			if (in_array($prefixe_clean, self::$prefixes_countries))
				$number = '+'.$prefixe_clean.' '.Tools::substr($number, 4);

			$prefixe = Tools::substr($number, 0, 3); // Try 000 ou +00 (most of Europe)
			$prefixe_clean = ltrim($prefixe, '+');
			if (in_array($prefixe_clean, self::$prefixes_countries))
				$number = '+'.$prefixe_clean.' '.Tools::substr($number, 3);

			$prefixe = Tools::substr($number, 0, 2); // Try 00 ou +0 (usa)
			$prefixe_clean = ltrim($prefixe, '+');
			if (in_array($prefixe_clean, self::$prefixes_countries))
				$number = '+'.$prefixe_clean.' '.Tools::substr($number, 2);
		}

		$number = str_replace(' 0', ' ', $number); // Remove the zero after the prefixe
		return $number;
	}

	public static function startsWith($haystack, $needle)
	{
		return $needle === '' || strrpos($haystack, $needle, -Tools::strlen($haystack)) !== false;
	}

	private static function trimCSVLine(&$data)
	{
		// Remove stat and end quotes
		// This function can be call by an array_walk_recursive, so corrections must affect the &$data parameter
		// -----------------------------------------------------------------------------------------------------
		if ($data)
		{
			$data = trim($data, "\r\n ");
			if ((Tools::substr($data, 0, 1) == '"') && (Tools::substr($data, -1) == '"'))
				$data = Tools::substr($data, 1, -1);
		}
	}

	public static function readCSV($file_name, $max_lines = -1)
	{
		ini_set ('auto_detect_line_endings', 'On');

		// Open the file
		// -------------
		$fp = fopen($file_name, 'r');
		$buffer = fgets($fp);
		$lines = 0;
		$columns = 0;
		$separator = ';';

		$data = array();

		$quote_separator = 0;
		$semic_separator = 0;
		$comma_separator = 0;
		$tab_separator = 0;

		// Test the 20 first lines
		// -----------------------
		while (($lines < 20) && $buffer !== false)
		{
			// Test each separator
			// -------------------
			if (($tmp = count(explode('";"', $buffer))) > 1)
				$quote_separator++;
			elseif (($tmp = count(explode(';', $buffer))) > 1)
				$semic_separator++;
			elseif (($tmp = count(explode(',', $buffer))) > 1)
				$comma_separator++;
			elseif (($tmp = count(explode("\t", $buffer))) > 1)
				$tab_separator++;

			// Count max columns
			// -----------------
			if ($tmp > $columns)
				$columns = $tmp;

			// Next line
			// ---------
			$buffer = fgets($fp);
			$lines++;
		}

		$buffer = fgets($fp);

		// Determines the most likely separator
		// ------------------------------------
		switch (max($quote_separator, $semic_separator, $comma_separator, $tab_separator))
		{
			case $quote_separator:
				$separator = '";"';
				break;
			case $semic_separator:
				$separator = ';';
				break;
			case $comma_separator:
				$separator = ',';
				break;
			case $tab_separator:
				$separator = "\t";
				break;
		}

		// Return to the begining
		// ----------------------
		fseek($fp, SEEK_SET);

		// Read the entire file
		// --------------------
		$buffer = fgets($fp);

		while ($max_lines && ($buffer !== false))
		{
			$data[] = array_pad(explode($separator, utf8_encode($buffer)), $columns, '');
			$buffer = fgets($fp);
			$max_lines--;
		}

		fclose($fp);

		// For each cell, try to remove the beginning and ending quotes
		// ------------------------------------------------------------
		array_walk_recursive($data, array('EMTools', 'trimCSVLine'));

		// Return the clean array
		// ----------------------
		return $data;
	}

	public static function getCSVPreview($file_name)
	{
		// Get the 20 first lines
		// ----------------------
		return EMTools::readCSV($file_name, 20);
	}

	public static function getCSVInsertRequest($file_name, $campaign_id, $table, $idx_col, $code_iso_country)
	{
		// Load all countries prefixes
		// // ------------------------
		EMTools::loadPrefixesCountries();

		// Creatre the INSERT request
		// --------------------------
		$str_fields = '	campaign_id, target,
						col_0, col_1, col_2, col_3, col_4, col_5,
						col_6, col_7, col_8, col_9, col_10,
						col_11, col_12, col_13, col_14, col_15,
						col_16, col_17, col_18, col_19';
		$request = 'INSERT INTO '.bqSQL($table).' ('.$str_fields.') VALUES ';

		$data = EMTools::readCSV($file_name);

		foreach ($data as $line)
		{
			$data_sql = '';
			$data_value = '';
			$col_count = count($line);
			$data_num = $line[(int)$idx_col];
			$number = EMTools::cleanNumber($data_num, $code_iso_country);

			if (!empty($number))
			{
				$ligne = "('".(int)$campaign_id."', '".pSQL($number)."', ";

				for ($i = 0; $i <= 19; $i++)
				{
					if ($i < $col_count)
						$data_value = $line[$i];
					else
						$data_value = '';

					$data_sql .= "'".pSQL($data_value)."', ";
				}

				$data_sql = rtrim($data_sql, ', ')."),\r\n";
				$request .= $ligne.$data_sql;
			}
		}

		$request = rtrim($request, " ,\r\n").';';

		unlink($file_name);

		return $request;
	}

	/**
	 * Extract the form wrapper div from a prestashop generated template
	 * @param string $html The prestashop generated template (html)
	 * @return string The form-wrapper element extracted from $html
	 */
	public static function extractFormWrapperElement($html)
	{
		$html = (string)$html;
		$dom_original = new DOMDocument();
		$dom_output = new DOMDocument();

		$html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
		$dom_original->loadHTML($html);
		$xpath = new DOMXpath($dom_original);

		// Keep every javascript tags
		// --------------------------
		$result = $xpath->query('//script[@type="text/javascript"]');
		for ($i = 0; $i < $result->length; $i++)
		{
			$output_div = $result->item($i);
			$dom_output->appendChild($dom_output->importNode($output_div, true));
		}

		// Keep every alert tags
		// ---------------------
		$result = $xpath->query('//div[@class="alert alert-info"]');
		for ($i = 0; $i < $result->length; $i++)
		{
			$output_div = $result->item(0);
			$dom_output->appendChild($dom_output->importNode($output_div, true));
		}

		// Keep the form wrapper tag
		// -------------------------
		$result = $xpath->query('//div[@class="form-wrapper"]');
		$output_div = $result->item(0);
		$dom_output->appendChild($dom_output->importNode($output_div, true));

		return $dom_output->saveHTML();
	}

	public static function importFileSelectColumn(Array $file, $media, $campaign_id, $module_name)
	{
		switch ((string)$media)
		{
			case 'sms':
				$media_letter = 'S';
				$redirect_step = 3;
				break;

			case 'fax':
				$media_letter = 'F';
				$redirect_step = 4;
				break;

			default:
				Tools::redirectAdmin('index.php?controller=AdminMarketingX&token='.Tools::getAdminTokenLite('AdminMarketingX'));
				exit;
		}

		$error = $file['error'];

		if (($error === 0))
		{
			$file_path = $file['tmp_name'];
			$file_copy = _PS_MODULE_DIR_.(string)$module_name.DIRECTORY_SEPARATOR.'import'.DIRECTORY_SEPARATOR.basename($file_path).'_copy.csv';

			if (file_exists($file_path))
				Tools::copy($file_path, $file_copy);
			else
				return false;

			Db::getInstance()->update('expressmailing_'.$media, array(
					'campaign_date_update' => date('Y-m-d H:i:s'),
					'path_to_import' => str_replace('\\', '/', $file_copy)
				), 'campaign_id = '.(int)$campaign_id
			);

			Tools::redirectAdmin('index.php?controller=AdminMarketing'.$media_letter.'Step'.$redirect_step.'&campaign_id='.
				(int)$campaign_id.
				'&token='.Tools::getAdminTokenLite('AdminMarketing'.$media_letter.'Step'.$redirect_step));
			exit;
		}

		return false;
	}

	public static function importFile($idx_col, $media, $campaign_id, $call_prefix)
	{
		switch ((string)$media)
		{
			case 'sms':
				break;

			case 'fax':
				break;

			default:
				Tools::redirectAdmin('index.php?controller=AdminMarketingX&token='.Tools::getAdminTokenLite('AdminMarketingX'));
				exit;
		}

		$sql = new DbQuery();
		$sql->select('*');
		$sql->from('expressmailing_'.pSQL($media));
		$sql->where('campaign_id = '.(int)$campaign_id);

		$result = Db::getInstance()->getRow($sql);

		$file_path = $result['path_to_import'];

		if (file_exists($file_path))
		{
			$code_iso_country = $call_prefix;

			$request = EMTools::getCSVInsertRequest($file_path, $campaign_id, _DB_PREFIX_.'expressmailing_'.$media.'_recipients',
													(int)$idx_col, $code_iso_country);

			return Db::getInstance()->execute($request)
				&& Db::getInstance()->update('expressmailing_'.pSQL($media),
					array(
						'campaign_date_update' => date('Y-m-d H:i:s'),
						'recipients_modified' => '1',
						'path_to_import' => null
					), 'campaign_id = '.(int)$campaign_id
				);
		}

		return false;
	}

	/**
	 * Create a new file containing $content
	 * @param mixed $content The content of the file (string or binary)
	 * @param string $import_folder The path of the folder where will be created the file
	 * @param string $suffix The extension of the file to create
	 * @return string The path of the created file
	 */
	public static function createTempFileFromContent($content, $import_folder, $suffix)
	{
		$filename = uniqid('tmp_').'.'.$suffix;
		$path = $import_folder.$filename;
		$file_handle = fopen($path, 'w+');
		fwrite($file_handle, $content);
		fclose($file_handle);
		return $path;
	}

	public function getLocalizableDate($unix_time = '')
	{
		if (empty($unix_time) || ($unix_time <= 0))
			$unix_time = time();

		$jj = date('j', $unix_time); /* j = d witout initial zero */
		$nn = date('n', $unix_time); /* n = m witout initial zero */
		$yy = date('Y', $unix_time);

		$localized_time = $this->date_format;
		$localized_time = str_replace('|Y|', $yy, $localized_time);
		$localized_time = str_replace('|d|', $jj, $localized_time);

		if (strpos($localized_time, '|m|') !== false)
			$localized_time = str_replace('|m|', Tools::strtolower($this->date_months[$nn]), $localized_time);

		if (strpos($localized_time, '|M|') !== false)
			$localized_time = str_replace('|M|', Tools::ucfirst($this->date_months[$nn]), $localized_time);

		if (strpos($localized_time, '|S|') !== false)
		{
			if (isset($this->date_suffix[$jj]))
				$localized_time = str_replace('|S|', $this->date_suffix[$jj], $localized_time);
			else
				$localized_time = str_replace('|S|', $this->date_suffix[4], $localized_time);
		}

		return str_replace('  ', ' ', $localized_time);
	}

	public static function removeAccents($string)
	{
		if (!preg_match('/[\x80-\xff<> &=]/', (string)$string))
			return $string;

		$chars = array(
			// Table Latin-1
			chr(195).chr(128) => 'a', chr(195).chr(129) => 'a',
			chr(195).chr(130) => 'a', chr(195).chr(131) => 'a',
			chr(195).chr(132) => 'a', chr(195).chr(133) => 'a',
			chr(195).chr(135) => 'c', chr(195).chr(136) => 'e',
			chr(195).chr(137) => 'e', chr(195).chr(138) => 'e',
			chr(195).chr(139) => 'e', chr(195).chr(140) => 'i',
			chr(195).chr(141) => 'i', chr(195).chr(142) => 'i',
			chr(195).chr(143) => 'i', chr(195).chr(145) => 'n',
			chr(195).chr(146) => 'o', chr(195).chr(147) => 'o',
			chr(195).chr(148) => 'o', chr(195).chr(149) => 'o',
			chr(195).chr(150) => 'o', chr(195).chr(153) => 'u',
			chr(195).chr(154) => 'u', chr(195).chr(155) => 'u',
			chr(195).chr(156) => 'u', chr(195).chr(157) => 'y',
			chr(195).chr(159) => 's', chr(195).chr(160) => 'a',
			chr(195).chr(161) => 'a', chr(195).chr(162) => 'a',
			chr(195).chr(163) => 'a', chr(195).chr(164) => 'a',
			chr(195).chr(165) => 'a', chr(195).chr(167) => 'c',
			chr(195).chr(168) => 'e', chr(195).chr(169) => 'e',
			chr(195).chr(170) => 'e', chr(195).chr(171) => 'e',
			chr(195).chr(172) => 'i', chr(195).chr(173) => 'i',
			chr(195).chr(174) => 'i', chr(195).chr(175) => 'i',
			chr(195).chr(177) => 'n', chr(195).chr(178) => 'o',
			chr(195).chr(179) => 'o', chr(195).chr(180) => 'o',
			chr(195).chr(181) => 'o', chr(195).chr(182) => 'o',
			chr(195).chr(182) => 'o', chr(195).chr(185) => 'u',
			chr(195).chr(186) => 'u', chr(195).chr(187) => 'u',
			chr(195).chr(188) => 'u', chr(195).chr(189) => 'y',
			chr(195).chr(191) => 'y',
			// Table Latin Extendue
			chr(196).chr(128) => 'a', chr(196).chr(129) => 'a',
			chr(196).chr(130) => 'a', chr(196).chr(131) => 'a',
			chr(196).chr(132) => 'a', chr(196).chr(133) => 'a',
			chr(196).chr(134) => 'c', chr(196).chr(135) => 'c',
			chr(196).chr(136) => 'c', chr(196).chr(137) => 'c',
			chr(196).chr(138) => 'c', chr(196).chr(139) => 'c',
			chr(196).chr(140) => 'c', chr(196).chr(141) => 'c',
			chr(196).chr(142) => 'd', chr(196).chr(143) => 'd',
			chr(196).chr(144) => 'd', chr(196).chr(145) => 'd',
			chr(196).chr(146) => 'e', chr(196).chr(147) => 'e',
			chr(196).chr(148) => 'e', chr(196).chr(149) => 'e',
			chr(196).chr(150) => 'e', chr(196).chr(151) => 'e',
			chr(196).chr(152) => 'e', chr(196).chr(153) => 'e',
			chr(196).chr(154) => 'e', chr(196).chr(155) => 'e',
			chr(196).chr(156) => 'g', chr(196).chr(157) => 'g',
			chr(196).chr(158) => 'g', chr(196).chr(159) => 'g',
			chr(196).chr(160) => 'g', chr(196).chr(161) => 'g',
			chr(196).chr(162) => 'g', chr(196).chr(163) => 'g',
			chr(196).chr(164) => 'h', chr(196).chr(165) => 'h',
			chr(196).chr(166) => 'h', chr(196).chr(167) => 'h',
			chr(196).chr(168) => 'i', chr(196).chr(169) => 'i',
			chr(196).chr(170) => 'i', chr(196).chr(171) => 'i',
			chr(196).chr(172) => 'i', chr(196).chr(173) => 'i',
			chr(196).chr(174) => 'i', chr(196).chr(175) => 'i',
			chr(196).chr(176) => 'i', chr(196).chr(177) => 'i',
			chr(196).chr(178) => 'ij', chr(196).chr(179) => 'ij',
			chr(196).chr(180) => 'j', chr(196).chr(181) => 'j',
			chr(196).chr(182) => 'k', chr(196).chr(183) => 'k',
			chr(196).chr(184) => 'k', chr(196).chr(185) => 'l',
			chr(196).chr(186) => 'l', chr(196).chr(187) => 'l',
			chr(196).chr(188) => 'l', chr(196).chr(189) => 'l',
			chr(196).chr(190) => 'l', chr(196).chr(191) => 'l',
			chr(197).chr(128) => 'l', chr(197).chr(129) => 'l',
			chr(197).chr(130) => 'l', chr(197).chr(131) => 'n',
			chr(197).chr(132) => 'n', chr(197).chr(133) => 'n',
			chr(197).chr(134) => 'n', chr(197).chr(135) => 'n',
			chr(197).chr(136) => 'n', chr(197).chr(137) => 'n',
			chr(197).chr(138) => 'n', chr(197).chr(139) => 'n',
			chr(197).chr(140) => 'o', chr(197).chr(141) => 'o',
			chr(197).chr(142) => 'o', chr(197).chr(143) => 'o',
			chr(197).chr(144) => 'o', chr(197).chr(145) => 'o',
			chr(197).chr(146) => 'oe', chr(197).chr(147) => 'oe',
			chr(197).chr(148) => 'r', chr(197).chr(149) => 'r',
			chr(197).chr(150) => 'r', chr(197).chr(151) => 'r',
			chr(197).chr(152) => 'r', chr(197).chr(153) => 'r',
			chr(197).chr(154) => 's', chr(197).chr(155) => 's',
			chr(197).chr(156) => 's', chr(197).chr(157) => 's',
			chr(197).chr(158) => 's', chr(197).chr(159) => 's',
			chr(197).chr(160) => 's', chr(197).chr(161) => 's',
			chr(197).chr(162) => 't', chr(197).chr(163) => 't',
			chr(197).chr(164) => 't', chr(197).chr(165) => 't',
			chr(197).chr(166) => 't', chr(197).chr(167) => 't',
			chr(197).chr(168) => 'u', chr(197).chr(169) => 'u',
			chr(197).chr(170) => 'u', chr(197).chr(171) => 'u',
			chr(197).chr(172) => 'u', chr(197).chr(173) => 'u',
			chr(197).chr(174) => 'u', chr(197).chr(175) => 'u',
			chr(197).chr(176) => 'u', chr(197).chr(177) => 'u',
			chr(197).chr(178) => 'u', chr(197).chr(179) => 'u',
			chr(197).chr(180) => 'w', chr(197).chr(181) => 'w',
			chr(197).chr(182) => 'y', chr(197).chr(183) => 'y',
			chr(197).chr(184) => 'y', chr(197).chr(185) => 'z',
			chr(197).chr(186) => 'z', chr(197).chr(187) => 'z',
			chr(197).chr(188) => 'z', chr(197).chr(189) => 'z',
			chr(197).chr(190) => 'z', chr(197).chr(191) => 's',
			'<' => '', '>' => '', '&' => '',
			' ' => '_', '=' => '_'
		);

		return strtr($string, $chars);
	}

	public static function getHtmlContent($url)
	{
		if (function_exists('curl_version'))
		{
			$user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Mozilla/5.0';

			$ch = curl_init((string)$url);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_CRLF, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			$raw = curl_exec($ch);

			curl_close ($ch);
			return $raw;
		}
		else
			return Tools::file_get_contents($url);
	}

}