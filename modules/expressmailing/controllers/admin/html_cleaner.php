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

class HtmlCleaner
{
	private $regexps_filters = array();
	private $session_api = null;

	public function __construct()
	{
		// API initialization
		// ------------------
		include 'session_api.php';
		$this->session_api = new SessionApi();

		// Si l'utilisateur est déjà connecté à un compte Express-Mailing
		// alors on télécharge les Regex depuis l'api
		// sinon, on utilise les Regex locales
		// --------------------------------------------------------------
		// if ($this->session_api->connectFromCredentials('email'))
		// 	$this->regexps_filters = $this->getRegexpFiltersFromAPI();
		// else
		$this->regexps_filters = $this->getRegexpFiltersFromLocal();
	}

	private function getRegexpFiltersFromAPI()
	{
		$regexp_filters = array();
		$parameters = array();
		$response_array = array();

		if ($this->session_api->call('email', 'tools', 'get_regexp_cleaners', $parameters, $response_array))
			$regexp_filters = $response_array;

		if (!is_array($regexp_filters) || (count($regexp_filters) == 0))
			$regexp_filters = $this->getRegexpFiltersFromLocal();

		return $regexp_filters;
	}

	private function getRegexpFiltersFromLocal()
	{
		$regexp_filters = array();

		$regexp_filters[] = array('type' => 'regexp', 'pattern' => '#<script.*?</script>#is', 'replacement' => '');

		$regexp_filters[] = array('type' => 'str', 'pattern' => 'Saisissez ici le <b>corps de votre emailing</b> HTML', 'replacement' => '&nbsp;');
		$regexp_filters[] = array('type' => 'str', 'pattern' => '<HTML>', 'replacement' => '<html>');
		$regexp_filters[] = array('type' => 'str', 'pattern' => '<BODY', 'replacement' => '<body');
		$regexp_filters[] = array('type' => 'str', 'pattern' => '</HTML>', 'replacement' => '</html>');
		$regexp_filters[] = array('type' => 'str', 'pattern' => '</BODY>', 'replacement' => '</body>');
		$regexp_filters[] = array('type' => 'str', 'pattern' => '[*link.webversion_url*]', 'replacement' => '##URL##');
		$regexp_filters[] = array('type' => 'str', 'pattern' => '[[%WEBVERSION%]]', 'replacement' => '##URL##');
		$regexp_filters[] = array('type' => 'str', 'pattern' => '[[%UNSUBSCRIBE%]]', 'replacement' => '##DESABO##');
		$regexp_filters[] = array('type' => 'regexp', 'pattern' => '#</html>.*?<#', 'replacement' => '<');
		$regexp_filters[] = array('type' => 'regexp', 'pattern' => '#<!--\[.*\]?-->#smU', 'replacement' => '');
		$regexp_filters[] = array('type' => 'regexp', 'pattern' => '#&lt;!--\[.*\]?--&gt;#smU', 'replacement' => '');
		$regexp_filters[] = array('type' => 'regexp', 'pattern' => '#<!-- saved from url.*-->#iUs', 'replacement' => '');
		$regexp_filters[] = array(
			'type' => 'regexp',
			'condition' => array(
				'require' => false,
				'pattern' => '#<html#i'
			),
			'pattern' => '#^(.)#is',
			'replacement' => '<html>$1'
		);
		$regexp_filters[] = array(
			'type' => 'str',
			'condition' => array(
				'require' => false,
				'pattern' => '#<body#is'
			),
			'pattern' => '</head>',
			'replacement' => '</head><body>'
		);
		$regexp_filters[] = array(
			'type' => 'str',
			'condition' => array(
				'require' => false,
				'pattern' => '#<body#i'
			),
			'pattern' => '<html>',
			'replacement' => '<html><body>'
		);
		$regexp_filters[] = array(
			'type' => 'str',
			'condition' => array(
				'require' => false,
				'pattern' => '#</html>#i'
			),
			'pattern' => '</body>',
			'replacement' => '</body></html>'
		);
		$regexp_filters[] = array(
			'type' => 'regexp',
			'condition' => array(
				'require' => false,
				'pattern' => '#</html>#i'
			),
			'pattern' => '#(.)$#is',
			'replacement' => '$1</html>'
		);
		$regexp_filters[] = array(
			'type' => 'str',
			'condition' => array(
				'require' => false,
				'pattern' => '#</body>#i'
			),
			'pattern' => '</html>',
			'replacement' => '</body></html>'
		);

		$regexp_filters[] = array('type' => 'chr', 'pattern' => '146', 'replacement' => '\'');
		$regexp_filters[] = array('type' => 'chr', 'pattern' => '150', 'replacement' => '-');
		$regexp_filters[] = array('type' => 'chr', 'pattern' => '8220', 'replacement' => '"');
		$regexp_filters[] = array('type' => 'chr', 'pattern' => '8221', 'replacement' => '"');
		$regexp_filters[] = array('type' => 'str', 'pattern' => '&amp;', 'replacement' => '&');
		$regexp_filters[] = array('type' => 'str', 'pattern' => '€', 'replacement' => '&euro;');

		$regexp_filters[] = array('type' => 'chr', 'pattern' => '194,8211', 'replacement' => '-');
		$regexp_filters[] = array('type' => 'chr', 'pattern' => '195,63', 'replacement' => '');
		$regexp_filters[] = array('type' => 'chr', 'pattern' => '195,160', 'replacement' => '&agrave; ');
		$regexp_filters[] = array('type' => 'chr', 'pattern' => '195,168', 'replacement' => '&egrave;');
		$regexp_filters[] = array('type' => 'chr', 'pattern' => '195,169', 'replacement' => '&eacute;');
		$regexp_filters[] = array('type' => 'chr', 'pattern' => '195,170', 'replacement' => '&ecirc;');
		$regexp_filters[] = array('type' => 'chr', 'pattern' => '195,180', 'replacement' => '&ocirc;');
		$regexp_filters[] = array('type' => 'chr', 'pattern' => '226,8218,172', 'replacement' => '&euro;');
		$regexp_filters[] = array('type' => 'chr', 'pattern' => '239,187,191', 'replacement' => '');
		$regexp_filters[] = array('type' => 'chr', 'pattern' => '195,131,194,160', 'replacement' => '&agrave;');
		$regexp_filters[] = array('type' => 'chr', 'pattern' => '195,131,194,169', 'replacement' => '&eacute;');

		$regexp_filters[] = array('type' => 'str', 'pattern' => '</o:p>', 'replacement' => '');
		$regexp_filters[] = array('type' => 'str', 'pattern' => '<o:p>', 'replacement' => '');
		$regexp_filters[] = array('type' => 'regexp', 'pattern' => '#mso-bidi-font-weight: normal,?#i', 'replacement' => '');
		$regexp_filters[] = array('type' => 'regexp', 'pattern' => '#BORDER-COLLAPSE: collapse,?#i', 'replacement' => '');

		$regexp_filters[] = array('type' => 'regexp', 'pattern' => '#<span lang=".."#i', 'replacement' => '<span');
		$regexp_filters[] = array('type' => 'regexp', 'pattern' => '# id="AutoNumber[0-9]*"#i', 'replacement' => '');
		$regexp_filters[] = array('type' => 'regexp', 'pattern' => '# id="table[0-9]*"#i', 'replacement' => '');
		$regexp_filters[] = array('type' => 'regexp', 'pattern' => '#; mso-bidi-font-size: [0-9.]*pt#i', 'replacement' => '');
		$regexp_filters[] = array('type' => 'regexp', 'pattern' => '#<meta name="GENERATOR" .*?>#i', 'replacement' => '');
		$regexp_filters[] = array('type' => 'regexp', 'pattern' => '#<meta name="ProgId" .*?>#i', 'replacement' => '');

		$regexp_filters[] = array('type' => 'regexp', 'pattern' => "#\r[\r\n\t ]*#", 'replacement' => "\r\n");
		$regexp_filters[] = array('type' => 'regexp', 'pattern' => "#>[\r\n\t]*<#", 'replacement' => '><');
		$regexp_filters[] = array('type' => 'regexp', 'pattern' => "#<p>(\r|\n)#i", 'replacement' => '<p>');
		$regexp_filters[] = array('type' => 'regexp', 'pattern' => "#(\r|\n)<\/p>#i", 'replacement' => '</p>');
		$regexp_filters[] = array('type' => 'regexp', 'pattern' => '#onmouseover="[^"]*"#i', 'replacement' => '');
		$regexp_filters[] = array('type' => 'regexp', 'pattern' => '#onmouseout="[^"]*"#i', 'replacement' => '');

		$regexp_filters[] = array('type' => 'regexp', 'pattern' => '#href[ ="\']*www\.([^>"\']*)["\']?#i', 'replacement' => 'href="http://www.$1"');

		$regexp_filters[] = array('type' => 'str', 'pattern' => 'http://http://', 'replacement' => 'http://');
		$regexp_filters[] = array('type' => 'str', 'pattern' => 'https://https://', 'replacement' => 'https://');
		$regexp_filters[] = array('type' => 'str', 'pattern' => 'ftp://ftp://', 'replacement' => 'ftp://');

		return $regexp_filters;
	}

	/**
	 * Clean an html string using a set of regexp and replacement strings
	 * @param string $html The html string to clean
	 * @return string The cleaned HTML
	 */
	public function cleanHTML($html)
	{
		foreach ($this->regexps_filters as $filter)
		{
			if (isset($filter['condition']) && is_array($filter['condition']) && !empty($filter['condition']))
			{
				$condition = $filter['condition'];

				if ((bool)$condition['require'] !== (bool)preg_match($condition['pattern'], $html))
					continue;
			}

			$type = $filter['type'];
			$pattern = $filter['pattern'];
			$replacement = $filter['replacement'];
			$tmp_html = null;

			switch ($type)
			{
				case 'regexp':
					$tmp_html = preg_replace($pattern, $replacement, $html);
					break;

				case 'str':
					$tmp_html = str_replace($pattern, $replacement, $html);
					break;

				case 'chr':
					$chars = explode(',', $pattern);
					$pattern = '';
					foreach ($chars as $char)
						$pattern .= chr((int)$char);
					$tmp_html = str_replace($pattern, $replacement, $html);
					break;

				default:
					$tmp_html = str_replace($pattern, $replacement, $html);
					break;
			}

			if ($tmp_html !== null)
				$html = $tmp_html;
		}

		return $html;
	}

}
