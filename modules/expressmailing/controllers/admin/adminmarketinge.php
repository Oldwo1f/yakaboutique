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

include_once 'adminmarketingx.php';

class AdminMarketingEController extends AdminMarketingXController
{
	public function __construct()
	{
		$this->name = 'adminmarketinge';
		$this->bootstrap = true;
		$this->context = Context::getContext();
		$this->module = 'expressmailing';

		parent::__construct();
	}

}