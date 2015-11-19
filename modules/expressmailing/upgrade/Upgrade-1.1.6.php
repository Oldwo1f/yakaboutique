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

function upgrade_module_1_1_6($module)
{
	$return = Db::getInstance()->execute('ALTER TABLE `'._DB_PREFIX_.'expressmailing_email`
		CHANGE COLUMN `campaign_optin` `campaign_optin` ENUM(\'1\',\'0\') NOT NULL DEFAULT \'0\' AFTER `campaign_selected_recipients`,
		CHANGE COLUMN `campaign_newsletter` `campaign_newsletter` ENUM(\'1\',\'0\') NOT NULL DEFAULT \'0\' AFTER `campaign_optin`,
		ADD COLUMN `campaign_guest` ENUM(\'1\',\'0\') NOT NULL DEFAULT \'0\' AFTER `campaign_active`;');

	$return &= Db::getInstance()->execute('ALTER TABLE `'._DB_PREFIX_.'expressmailing_email_recipients`
	ADD COLUMN `group_name` VARCHAR(32) NULL AFTER `source`;');

	$return &= Db::getInstance()->execute('CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'expressmailing_email_shops_groups` (
		`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
		`campaign_id` INT(10) UNSIGNED NOT NULL,
		`shop_group_id` INT(10) UNSIGNED NOT NULL,
		`shop_id` INT(10) UNSIGNED NOT NULL,
		PRIMARY KEY (`id`)
	) DEFAULT CHARSET=utf8');

	return $return;
}