<?php

/**

* Product Properties Extension

*

* @author    PS&More www.psandmore.com <support@psandmore.com>

* @copyright 2011-2015 PS&More

* @license   psandmore.com/licenses/sla

*/



class FrontController extends FrontControllerCore

{

	/*
	* module: pproperties
	* date: 2015-10-30 20:57:18
	* version: 2.14
	*/
	public function display()

	{

		if (!Configuration::get('PS_CSS_THEME_CACHE'))

			PSM::amendCSS($this->css_files);

		if (!Configuration::get('PS_JS_THEME_CACHE'))

			PSM::amendJS($this->js_files);

		return parent::display();

	}

}

