<?php
/**
* Product Properties Extension
*
* @author    PS&More www.psandmore.com <support@psandmore.com>
* @copyright 2011-2015 PS&More
* @license   psandmore.com/licenses/sla
*/

class AdminController extends AdminControllerCore
{
	public function adminControllerPostProcess()
	{
		return parent::postProcess();
	}

	public function adminControllerRenderList()
	{
		return parent::renderList();
	}
}
