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

class PpropertiesDefaultModuleFrontController extends ModuleFrontController
{
	public function __construct()
	{
		parent::__construct();
		if ($this->ajax)
			$this->content_only = true;
	}

	public function initContent()
	{
		if ($this->ajax)
			return;
	}

	protected function displayAjaxSetMeasurementSystem()
	{
		$this->context->cookie->pp_measurement_system_fo = PP::resolveMS((int)Tools::getValue('measurement_system'));
		die('1');
	}
}
