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

if (!defined('_PS_VERSION_'))
	exit;

include_once 'controllers/admin/adminmarketingx.php';

class ExpressMailing extends Module
{
	private $html_preview_folder = null;
	private $ids_tabs = array ();

	public $default_remaining_email = 300;
	public $default_remaining_fax = 30;
	public $default_remaining_sms = 5;

	public function __construct()
	{
		$this->bootstrap = true;
		$this->name = 'expressmailing';
		$this->tab = 'emailing';
		$this->version = '1.1.7';
		$this->author = 'Axalone France';
		$this->need_instance = 0;
		$this->limited_countries = array ('fr', 'pl');

		parent::__construct();

		$this->author = $this->l('Axalone France');
		$this->displayName = 'Express-Mailing';
		$this->description = $this->l('First Marketing and Newletter module fully integrated with the PrestaShop interface, including emailing with 9,000 free emails per month i.e. 300 per day, and the ability to send FAX and SMS at very low prices.');
		$this->confirmUninstall = $this->l('Are you sure you want to uninstall ?');
		$this->html_preview_folder = _PS_MODULE_DIR_.'expressmailing'.DIRECTORY_SEPARATOR.'campaigns'.DIRECTORY_SEPARATOR;

		$this->context->controller->addCSS(_PS_MODULE_DIR_.'expressmailing/views/css/icon-marketing.css');
		$this->context->controller->addCSS(_PS_MODULE_DIR_.'expressmailing/views/css/expressmailing.css');
	}

	public function reset()
	{
		return $this->uninstall(false)
			&& $this->install(false);
	}

	public function install($alter_db = true)
	{
		if (!function_exists('curl_init'))
			$this->context->controller->informations[] = 'This module uses CURL, you should activate the PHP CURL extension on your server.';

		return parent::install()
			&& $this->installDB($alter_db)
			&& $this->installAdminTabs();
	}

	public function uninstall($alter_db = true)
	{
		return parent::uninstall()
			&& Configuration::deleteByName('adminmarketing_session_api')
			&& $this->uninstallDB($alter_db)
			&& $this->uninstallAdminTabs();
	}

	public function installDB($alter_db = true)
	{
		$return = true;

		if ((bool)$alter_db)
		{
			/* To store api credentials */

			$return &= Db::getInstance()->execute('
				CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'expressmailing` (
					`api_media` ENUM(\'all\',\'email\',\'fax\',\'sms\') NOT NULL DEFAULT \'all\',
					`api_login` VARCHAR(255) NULL DEFAULT NULL,
					`api_password` VARCHAR(255) NULL DEFAULT NULL,
					PRIMARY KEY (`api_login`)
				) DEFAULT CHARSET=utf8');

			/* To store e-mailing campaigns */

			$return &= Db::getInstance()->execute('
				CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'expressmailing_email` (
					`campaign_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
					`campaign_state` INT UNSIGNED NOT NULL DEFAULT 1,
					`campaign_date_create` TIMESTAMP NULL DEFAULT NULL,
					`campaign_date_update` TIMESTAMP NULL DEFAULT NULL,
					`campaign_date_send` TIMESTAMP NULL DEFAULT NULL,
					`campaign_name` VARCHAR(255) NULL DEFAULT NULL,
					`campaign_tracking` ENUM(\'1\',\'0\') NOT NULL DEFAULT \'1\',
					`campaign_linking` ENUM(\'1\',\'0\') NOT NULL DEFAULT \'1\',
					`campaign_redlist` ENUM(\'1\',\'0\') NOT NULL DEFAULT \'1\',
					`campaign_day_limit` INT(11) UNSIGNED NOT NULL DEFAULT 0,
					`campaign_max_limit` INT(11) UNSIGNED NOT NULL DEFAULT 0,
					`campaign_week_limit` VARCHAR(7) NULL DEFAULT NULL,
					`campaign_lang` VARCHAR(2) NOT NULL DEFAULT \'fr\',
					`campaign_html` LONGTEXT NULL,
					`campaign_sender_email` VARCHAR(255) NULL DEFAULT NULL,
					`campaign_sender_name` VARCHAR(255) NULL DEFAULT NULL,
					`campaign_api_list_id` INT UNSIGNED NULL DEFAULT NULL,
					`campaign_api_message_id` INT UNSIGNED NULL DEFAULT NULL,
					`campaign_api_validation` ENUM(\'1\',\'0\') NOT NULL DEFAULT \'0\',
					`campaign_last_tester` VARCHAR(255) NULL DEFAULT NULL,
					`campaign_selected_recipients` INT(11) UNSIGNED NOT NULL DEFAULT 0,
					`campaign_optin` ENUM(\'1\',\'0\') NOT NULL DEFAULT \'0\',
					`campaign_newsletter` ENUM(\'1\',\'0\') NOT NULL DEFAULT \'0\',
					`campaign_active` ENUM(\'1\',\'0\') NOT NULL DEFAULT \'1\',
					`campaign_guest` ENUM(\'1\',\'0\') NOT NULL DEFAULT \'0\',
					`recipients_modified` ENUM(\'1\',\'0\') NOT NULL DEFAULT \'0\',
					PRIMARY KEY (`campaign_id`),
					INDEX `index_state` (`campaign_state`)
				) DEFAULT CHARSET=utf8');

			$return &= Db::getInstance()->execute('
				CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'expressmailing_email_recipients` (
					`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
					`campaign_id` INT UNSIGNED NOT NULL,
					`target` TEXT NULL,
					`uploaded` BIT(1) NOT NULL DEFAULT b\'0\',
					`lang_iso` VARCHAR(3) NULL DEFAULT NULL,
					`last_name` TEXT NULL,
					`first_name` TEXT NULL,
					`ip_address` TEXT NULL,
					`last_connexion_date` INT UNSIGNED NULL DEFAULT NULL,
					`source` VARCHAR(10) NULL,
					`group_name` VARCHAR(32) NULL,
					PRIMARY KEY (`id`),
					INDEX `campaign_id` (`campaign_id`)
				) DEFAULT CHARSET=utf8');

			$return &= Db::getInstance()->execute('
				CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'expressmailing_email_groups` (
					`campaign_id` INT UNSIGNED NOT NULL,
					`group_id` INT UNSIGNED NOT NULL,
					PRIMARY KEY (`campaign_id`, `group_id`)
				) DEFAULT CHARSET=utf8');

			$return &= Db::getInstance()->execute('
				CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'expressmailing_email_langs` (
					`campaign_id` INT UNSIGNED NOT NULL,
					`lang_id` INT UNSIGNED NOT NULL,
					PRIMARY KEY (`campaign_id`, `lang_id`)
				) DEFAULT CHARSET=utf8');

			$return &= Db::getInstance()->execute('
				CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'expressmailing_email_categories` (
					`campaign_id` INT(10) UNSIGNED NOT NULL,
					`category_id` INT(10) UNSIGNED NOT NULL,
					PRIMARY KEY (`campaign_id`, `category_id`)
				) DEFAULT CHARSET=utf8');

			$return &= Db::getInstance()->execute('
				CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'expressmailing_email_products` (
					`campaign_id` INT(10) UNSIGNED NOT NULL,
					`product_id` INT(10) UNSIGNED NOT NULL,
					PRIMARY KEY (`campaign_id`, `product_id`)
				) DEFAULT CHARSET=utf8');

			$return &= Db::getInstance()->execute('
				CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'expressmailing_email_birthdays` (
					`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
					`campaign_id` INT UNSIGNED NOT NULL,
					`birthday_type` VARCHAR(50) NOT NULL,
					`birthday_start` VARCHAR(50) NOT NULL,
					`birthday_end` VARCHAR(50) NOT NULL,
					PRIMARY KEY (`id`)
				) DEFAULT CHARSET=utf8');

			$return &= Db::getInstance()->execute('
				CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'expressmailing_email_civilities` (
					`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
					`campaign_id` INT UNSIGNED NOT NULL,
					`civility_id` INT UNSIGNED NOT NULL,
					PRIMARY KEY (`id`)
				) DEFAULT CHARSET=utf8');

			$return &= Db::getInstance()->execute('
				CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'expressmailing_email_countries` (
					`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
					`campaign_id` INT UNSIGNED NOT NULL,
					`country_id` INT UNSIGNED NOT NULL,
					PRIMARY KEY (`id`)
				) DEFAULT CHARSET=utf8');

			$return &= Db::getInstance()->execute('
				CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'expressmailing_email_postcodes` (
					`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
					`campaign_id` INT UNSIGNED NOT NULL,
					`country_id` INT UNSIGNED NOT NULL,
					`postcode` INT UNSIGNED NOT NULL,
					PRIMARY KEY (`id`)
				) DEFAULT CHARSET=utf8');

			$return &= Db::getInstance()->execute('
				CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'expressmailing_email_buyingdates` (
					`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
					`campaign_id` INT UNSIGNED NOT NULL,
					`min_buyingdate` DATE NOT NULL,
					`max_buyingdate` DATE NOT NULL,
					PRIMARY KEY (`id`)
				) DEFAULT CHARSET=utf8');

			$return &= Db::getInstance()->execute('
				CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'expressmailing_email_accountdates` (
					`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
					`campaign_id` INT UNSIGNED NOT NULL,
					`min_accountdate` DATE NOT NULL,
					`max_accountdate` DATE NOT NULL,
					PRIMARY KEY (`id`)
				) DEFAULT CHARSET=utf8');

			$return &= Db::getInstance()->execute('
				CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'expressmailing_email_promocodes` (
					`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
					`campaign_id` INT(10) UNSIGNED NOT NULL,
					`promocode_type` VARCHAR(50) NOT NULL,
					`promocode` VARCHAR(50) NULL DEFAULT NULL,
					PRIMARY KEY (`id`)
				) DEFAULT CHARSET=utf8');

			$return &= Db::getInstance()->execute('
				CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'expressmailing_email_shops_groups` (
					`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
					`campaign_id` INT(10) UNSIGNED NOT NULL,
					`shop_group_id` INT(10) UNSIGNED NOT NULL,
					`shop_id` INT(10) UNSIGNED NOT NULL,
					PRIMARY KEY (`id`)
				) DEFAULT CHARSET=utf8');

			/* To store fax-mailing campaigns */

			$return &= Db::getInstance()->execute('
				CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'expressmailing_fax` (
					`campaign_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
					`campaign_state` INT UNSIGNED NOT NULL DEFAULT 1,
					`campaign_date_create` TIMESTAMP NULL DEFAULT NULL,
					`campaign_date_update` TIMESTAMP NULL DEFAULT NULL,
					`campaign_date_send` TIMESTAMP NULL DEFAULT NULL,
					`campaign_name` VARCHAR(255) NULL DEFAULT NULL,
					`campaign_day_limit` INT(11) UNSIGNED NOT NULL DEFAULT 0,
					`campaign_max_limit` INT(11) UNSIGNED NOT NULL DEFAULT 0,
					`campaign_week_limit` VARCHAR(7) NULL DEFAULT \'LMCJVSD\',
					`campaign_start_hour` INT(11) NOT NULL DEFAULT 0,
					`campaign_end_hour` INT(11) NOT NULL DEFAULT 1440,
					`campaign_api_message_id` INT UNSIGNED NULL DEFAULT NULL,
					`campaign_api_validation` ENUM(\'1\',\'0\') NOT NULL DEFAULT \'0\',
					`campaign_last_tester` VARCHAR(255) NULL DEFAULT NULL,
					`path_to_import` VARCHAR(255) NULL DEFAULT NULL,
					`recipients_modified` ENUM(\'1\',\'0\') NOT NULL DEFAULT \'0\',
					`campaign_selected_recipients` INT(11) UNSIGNED NOT NULL DEFAULT 0,
					`campaign_active` ENUM(\'1\',\'0\') NOT NULL DEFAULT \'1\',
					PRIMARY KEY (`campaign_id`),
					INDEX `index_state` (`campaign_state`)
				) DEFAULT CHARSET=utf8');

			$return &= Db::getInstance()->execute('
				CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'expressmailing_fax_recipients` (
					`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
					`campaign_id` INT UNSIGNED NOT NULL,
					`target` TEXT NULL,
					`uploaded` BIT(1) NOT NULL DEFAULT b\'0\',
					`col_0` TEXT NULL,
					`col_1` TEXT NULL,
					`col_2` TEXT NULL,
					`col_3` TEXT NULL,
					`col_4` TEXT NULL,
					`col_5` TEXT NULL,
					`col_6` TEXT NULL,
					`col_7` TEXT NULL,
					`col_8` TEXT NULL,
					`col_9` TEXT NULL,
					`col_10` TEXT NULL,
					`col_11` TEXT NULL,
					`col_12` TEXT NULL,
					`col_13` TEXT NULL,
					`col_14` TEXT NULL,
					`col_15` TEXT NULL,
					`col_16` TEXT NULL,
					`col_17` TEXT NULL,
					`col_18` TEXT NULL,
					`col_19` TEXT NULL,
					PRIMARY KEY (`id`),
					INDEX `campaign_id` (`campaign_id`)
				) DEFAULT CHARSET=utf8');

			$return &= Db::getInstance()->execute('
				CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'expressmailing_fax_pages` (
					`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
					`campaign_id` INT UNSIGNED NOT NULL,
					`page_path` VARCHAR(255) NOT NULL,
					`page_url` VARCHAR(255) NOT NULL,
					`page_path_original` VARCHAR(255) NOT NULL,
					PRIMARY KEY (`id`),
					INDEX `campaign_id` (`campaign_id`)
				) DEFAULT CHARSET=utf8');

			/* To store sms-mailing campaigns */

			$return &= Db::getInstance()->execute('
				CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'expressmailing_sms` (
					`campaign_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
					`campaign_state` INT UNSIGNED NOT NULL DEFAULT 1,
					`campaign_date_create` TIMESTAMP NULL DEFAULT NULL,
					`campaign_date_update` TIMESTAMP NULL DEFAULT NULL,
					`campaign_date_send` TIMESTAMP NULL DEFAULT NULL,
					`campaign_name` VARCHAR(255) NULL DEFAULT NULL,
					`campaign_day_limit` INT(11) UNSIGNED NOT NULL DEFAULT 0,
					`campaign_max_limit` INT(11) UNSIGNED NOT NULL DEFAULT 0,
					`campaign_week_limit` VARCHAR(7) NULL DEFAULT \'LMCJVS\',
					`campaign_start_hour` INT(11) NOT NULL DEFAULT 480,
					`campaign_end_hour` INT(11) NOT NULL DEFAULT 1200,
					`campaign_sms_text` LONGTEXT NULL,
					`campaign_api_message_id` INT UNSIGNED NULL DEFAULT NULL,
					`campaign_api_validation` ENUM(\'1\',\'0\') NOT NULL DEFAULT \'0\',
					`campaign_last_tester` VARCHAR(255) NULL DEFAULT NULL,
					`recipients_modified` TINYINT NOT NULL DEFAULT 0,
					`path_to_import` VARCHAR(255) NULL DEFAULT NULL,
					`campaign_selected_recipients` INT(11) UNSIGNED NOT NULL DEFAULT 0,
					`campaign_active` ENUM(\'1\',\'0\') NOT NULL DEFAULT \'1\',
					PRIMARY KEY (`campaign_id`),
					INDEX `index_state` (`campaign_state`)
				) DEFAULT CHARSET=utf8');

			$return &= Db::getInstance()->execute('
				CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'expressmailing_sms_recipients` (
					`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
					`campaign_id` INT UNSIGNED NOT NULL,
					`target` TEXT NULL,
					`uploaded` BIT(1) NOT NULL DEFAULT b\'0\',
					`col_0` TEXT NULL,
					`col_1` TEXT NULL,
					`col_2` TEXT NULL,
					`col_3` TEXT NULL,
					`col_4` TEXT NULL,
					`col_5` TEXT NULL,
					`col_6` TEXT NULL,
					`col_7` TEXT NULL,
					`col_8` TEXT NULL,
					`col_9` TEXT NULL,
					`col_10` TEXT NULL,
					`col_11` TEXT NULL,
					`col_12` TEXT NULL,
					`col_13` TEXT NULL,
					`col_14` TEXT NULL,
					`col_15` TEXT NULL,
					`col_16` TEXT NULL,
					`col_17` TEXT NULL,
					`col_18` TEXT NULL,
					`col_19` TEXT NULL,
					`source` VARCHAR(10) NULL,
					PRIMARY KEY (`id`),
					INDEX `campaign_id` (`campaign_id`)
				) DEFAULT CHARSET=utf8');

			$return &= Db::getInstance()->execute('
				CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'expressmailing_sms_groups` (
					`campaign_id` INT UNSIGNED NOT NULL,
					`group_id` INT UNSIGNED NOT NULL,
					PRIMARY KEY (`campaign_id`, `group_id`)
				) DEFAULT CHARSET=utf8');

			$return &= Db::getInstance()->execute('
				CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'expressmailing_sms_langs` (
					`campaign_id` INT UNSIGNED NOT NULL,
					`lang_id` INT UNSIGNED NOT NULL,
					PRIMARY KEY (`campaign_id`, `lang_id`)
				) DEFAULT CHARSET=utf8');

			$return &= Db::getInstance()->execute('
				CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'expressmailing_sms_categories` (
					`campaign_id` INT(10) UNSIGNED NOT NULL,
					`category_id` INT(10) UNSIGNED NOT NULL,
					PRIMARY KEY (`campaign_id`, `category_id`)
				) DEFAULT CHARSET=utf8');

			$return &= Db::getInstance()->execute('
				CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'expressmailing_sms_products` (
					`campaign_id` INT(10) UNSIGNED NOT NULL,
					`product_id` INT(10) UNSIGNED NOT NULL,
					PRIMARY KEY (`campaign_id`, `product_id`)
				) DEFAULT CHARSET=utf8');

			/* To store cart & billing address */

			$return &= Db::getInstance()->execute('
				CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'expressmailing_order_address` (
					`id_address` INT(10) NOT NULL,
					`company_name` VARCHAR(200) NULL DEFAULT NULL,
					`company_email` VARCHAR(200) NULL DEFAULT NULL,
					`company_address1` VARCHAR(200) NULL DEFAULT NULL,
					`company_address2` VARCHAR(200) NULL DEFAULT NULL,
					`company_zipcode` VARCHAR(200) NULL DEFAULT NULL,
					`company_city` VARCHAR(200) NULL DEFAULT NULL,
					`country_id` INT(11) NULL DEFAULT NULL,
					`company_country` VARCHAR(200) NULL DEFAULT NULL,
					`company_phone` VARCHAR(200) NULL DEFAULT NULL,
					`product` VARCHAR(200) NULL DEFAULT NULL,
					PRIMARY KEY (`id_address`)
				) DEFAULT CHARSET=utf8');

			$return &= Db::getInstance()->execute('
				CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'expressmailing_order_cart` (
					`order_session` VARCHAR(100) NOT NULL DEFAULT \'\',
					`order_product` VARCHAR(100) NULL DEFAULT NULL,
					`order_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
					`campaign_media` VARCHAR(50) NOT NULL DEFAULT \'AdminMarketingX\',
					`campaign_id` INT(11) NULL DEFAULT NULL,
					PRIMARY KEY (`order_session`)
				) DEFAULT CHARSET=utf8');
		}

		return $return;
	}

	public function uninstallDB($alter_db = true)
	{
		if ((bool)$alter_db)
		{
			return Db::getInstance()->execute(
					'DROP TABLE IF EXISTS
					`'._DB_PREFIX_.'expressmailing_order_address`,
					`'._DB_PREFIX_.'expressmailing_order_cart`,
					`'._DB_PREFIX_.'expressmailing_sms_products`,
					`'._DB_PREFIX_.'expressmailing_sms_categories`,
					`'._DB_PREFIX_.'expressmailing_sms_langs`,
					`'._DB_PREFIX_.'expressmailing_sms_groups`,
					`'._DB_PREFIX_.'expressmailing_sms_recipients`,
					`'._DB_PREFIX_.'expressmailing_sms`,
					`'._DB_PREFIX_.'expressmailing_fax_pages`,
					`'._DB_PREFIX_.'expressmailing_fax_recipients`,
					`'._DB_PREFIX_.'expressmailing_fax`,
					`'._DB_PREFIX_.'expressmailing_email_promocodes`,
					`'._DB_PREFIX_.'expressmailing_email_accountdates`,
					`'._DB_PREFIX_.'expressmailing_email_buyingdates`,
					`'._DB_PREFIX_.'expressmailing_email_postcodes`,
					`'._DB_PREFIX_.'expressmailing_email_countries`,
					`'._DB_PREFIX_.'expressmailing_email_civilities`,
					`'._DB_PREFIX_.'expressmailing_email_birthdays`,
					`'._DB_PREFIX_.'expressmailing_email_products`,
					`'._DB_PREFIX_.'expressmailing_email_categories`,
					`'._DB_PREFIX_.'expressmailing_email_langs`,
					`'._DB_PREFIX_.'expressmailing_email_groups`,
					`'._DB_PREFIX_.'expressmailing_email_recipients`,
					`'._DB_PREFIX_.'expressmailing_email`,
					`'._DB_PREFIX_.'expressmailing`');
		}

		return true;
	}

	public function installAdminTabs()
	{
		/* Add main Tab (visible into Customer) */
		return $this->installAdminTab('AdminMarketingX', true, $this->displayName, 'AdminPriceRule')
		/* Add media controllers */
		&& $this->installAdminTab('AdminMarketingE', false, $this->displayName, 'AdminMarketingX')
		&& $this->installAdminTab('AdminMarketingF', false, $this->displayName, 'AdminMarketingX')
		&& $this->installAdminTab('AdminMarketingS', false, $this->displayName, 'AdminMarketingX')
		/* Add order/subscription controllers */
		&& $this->installAdminTab('AdminMarketingInscription', false, $this->l('Inscription'), 'AdminMarketingX')
		&& $this->installAdminTab('AdminMarketingBuy', false, $this->l('Cart'), 'AdminMarketingX')
		/* Add emailing controllers */
		&& $this->installAdminTab('AdminMarketingEStep1', false, $this->l('E-Mailing'), 'AdminMarketingE')
		&& $this->installAdminTab('AdminMarketingEStep2', false, $this->l('E-Mailing'), 'AdminMarketingE')
		&& $this->installAdminTab('AdminMarketingEStep3', false, $this->l('E-Mailing'), 'AdminMarketingE')
		&& $this->installAdminTab('AdminMarketingEStep4', false, $this->l('E-Mailing'), 'AdminMarketingE')
		&& $this->installAdminTab('AdminMarketingEStep5', false, $this->l('E-Mailing'), 'AdminMarketingE')
		&& $this->installAdminTab('AdminMarketingEStep6', false, $this->l('E-Mailing'), 'AdminMarketingE')
		&& $this->installAdminTab('AdminMarketingEStep7', false, $this->l('E-Mailing'), 'AdminMarketingE')
		&& $this->installAdminTab('AdminMarketingEStep8', false, $this->l('E-Mailing'), 'AdminMarketingE')
		/* Add fax controllers */
		&& $this->installAdminTab('AdminMarketingFStep1', false, $this->l('Fax-Mailing'), 'AdminMarketingF')
		&& $this->installAdminTab('AdminMarketingFStep2', false, $this->l('Fax-Mailing'), 'AdminMarketingF')
		&& $this->installAdminTab('AdminMarketingFStep3', false, $this->l('Fax-Mailing'), 'AdminMarketingF')
		&& $this->installAdminTab('AdminMarketingFStep4', false, $this->l('Fax-Mailing'), 'AdminMarketingF')
		&& $this->installAdminTab('AdminMarketingFStep5', false, $this->l('Fax-Mailing'), 'AdminMarketingF')
		&& $this->installAdminTab('AdminMarketingFStep6', false, $this->l('Fax-Mailing'), 'AdminMarketingF')
		&& $this->installAdminTab('AdminMarketingFStep7', false, $this->l('Fax-Mailing'), 'AdminMarketingF')
		&& $this->installAdminTab('AdminMarketingFStep8', false, $this->l('Fax-Mailing'), 'AdminMarketingF')
		/* Add sms controllers */
		&& $this->installAdminTab('AdminMarketingSStep1', false, $this->l('Sms-Mailing'), 'AdminMarketingS')
		&& $this->installAdminTab('AdminMarketingSStep2', false, $this->l('Sms-Mailing'), 'AdminMarketingS')
		&& $this->installAdminTab('AdminMarketingSStep3', false, $this->l('Sms-Mailing'), 'AdminMarketingS')
		&& $this->installAdminTab('AdminMarketingSStep4', false, $this->l('Sms-Mailing'), 'AdminMarketingS')
		&& $this->installAdminTab('AdminMarketingSStep5', false, $this->l('Sms-Mailing'), 'AdminMarketingS')
		&& $this->installAdminTab('AdminMarketingSStep6', false, $this->l('Sms-Mailing'), 'AdminMarketingS')
		&& $this->installAdminTab('AdminMarketingSStep7', false, $this->l('Sms-Mailing'), 'AdminMarketingS')
		/* Add statistics controllers */
		&& $this->installAdminTab('AdminMarketingEList', false, $this->l('E-Mailing'), 'AdminMarketingE')
		&& $this->installAdminTab('AdminMarketingFList', false, $this->l('Fax-Mailing'), 'AdminMarketingF')
		&& $this->installAdminTab('AdminMarketingSList', false, $this->l('Sms-Mailing'), 'AdminMarketingS')
		&& $this->installAdminTab('AdminMarketingEStats', false, $this->l('E-Mailing'), 'AdminMarketingE')
		&& $this->installAdminTab('AdminMarketingFStats', false, $this->l('Fax-Mailing'), 'AdminMarketingF')
		&& $this->installAdminTab('AdminMarketingSStats', false, $this->l('Sms-Mailing'), 'AdminMarketingS');
	}

	public function uninstallAdminTabs()
	{
		return $this->uninstallAdminTab('AdminMarketingX')
			&& $this->uninstallAdminTab('AdminMarketingE')
			&& $this->uninstallAdminTab('AdminMarketingF')
			&& $this->uninstallAdminTab('AdminMarketingS')
			&& $this->uninstallAdminTab('AdminMarketingInscription')
			&& $this->uninstallAdminTab('AdminMarketingBuy')
			&& $this->uninstallAdminTab('AdminMarketingEList')
			&& $this->uninstallAdminTab('AdminMarketingFList')
			&& $this->uninstallAdminTab('AdminMarketingSList')
			&& $this->uninstallAdminTab('AdminMarketingEStats')
			&& $this->uninstallAdminTab('AdminMarketingFStats')
			&& $this->uninstallAdminTab('AdminMarketingSStats')
			&& $this->uninstallAdminTab('AdminMarketingEStep1')
			&& $this->uninstallAdminTab('AdminMarketingEStep2')
			&& $this->uninstallAdminTab('AdminMarketingEStep3')
			&& $this->uninstallAdminTab('AdminMarketingEStep4')
			&& $this->uninstallAdminTab('AdminMarketingEStep5')
			&& $this->uninstallAdminTab('AdminMarketingEStep6')
			&& $this->uninstallAdminTab('AdminMarketingEStep7')
			&& $this->uninstallAdminTab('AdminMarketingEStep8')
			&& $this->uninstallAdminTab('AdminMarketingFStep1')
			&& $this->uninstallAdminTab('AdminMarketingFStep2')
			&& $this->uninstallAdminTab('AdminMarketingFStep3')
			&& $this->uninstallAdminTab('AdminMarketingFStep4')
			&& $this->uninstallAdminTab('AdminMarketingFStep5')
			&& $this->uninstallAdminTab('AdminMarketingFStep6')
			&& $this->uninstallAdminTab('AdminMarketingFStep7')
			&& $this->uninstallAdminTab('AdminMarketingFStep8')
			&& $this->uninstallAdminTab('AdminMarketingSStep1')
			&& $this->uninstallAdminTab('AdminMarketingSStep2')
			&& $this->uninstallAdminTab('AdminMarketingSStep3')
			&& $this->uninstallAdminTab('AdminMarketingSStep4')
			&& $this->uninstallAdminTab('AdminMarketingSStep5')
			&& $this->uninstallAdminTab('AdminMarketingSStep6')
			&& $this->uninstallAdminTab('AdminMarketingSStep7');
	}

	public function installAdminTab($tab_class, $tab_active, $tab_name, $tab_class_parent)
	{
		if (!Tab::getIdFromClassName((string)$tab_class))
		{
			// Get tab parent id
			// -----------------
			if (in_array((string)$tab_class_parent, $this->ids_tabs))
				$id_tab_parent = $this->ids_tabs[(string)$tab_class_parent];
			else
				$id_tab_parent = Tab::getIdFromClassName((string)$tab_class_parent);

			// Add the new tab
			// ---------------
			$tab = new Tab();
			$tab->name = array ();

			foreach (Language::getLanguages() as $language)
				$tab->name[$language['id_lang']] = Translate::getAdminTranslation((string)$tab_name, 'expressmailing', false, false);

			$tab->class_name = (string)$tab_class;
			$tab->module = $this->name;
			$tab->id_parent = (int)$id_tab_parent;
			$tab->active = (bool)$tab_active ? 1 : 0;

			if (!$tab->save())
				return false;

			$this->ids_tabs[(string)$tab_class] = $tab->id;
		}

		// Else, Tab already installed
		// ---------------------------
		return true;
	}

	public function uninstallAdminTab($tab_class)
	{
		$id_tab = Tab::getIdFromClassName((string)$tab_class);

		if ($id_tab != 0)
		{
			// We remove the Tab
			// -----------------
			$tab = new Tab($id_tab);
			return $tab->delete();
		}

		// Else, Tab already uninstalled
		// -----------------------------
		return true;
	}

	public function getContent()
	{
		$this->context->controller->addJqueryUI('ui.dialog');
		$this->context->controller->addJqueryUI('ui.draggable');
		$this->context->controller->addJqueryUI('ui.resizable');

		$broadcast_max_daily = 300;
		$smarty_email_disabled = false;
		$smarty_fax_disabled = false;
		$smarty_sms_disabled = false;

		include _PS_MODULE_DIR_.$this->name.'/controllers/admin/session_api.php';
		$this->session_api = new SessionApi();

		if ($this->session_api->connectFromCredentials('email'))
		{
			$response_array = array();
			$parameters = array('account_id' => $this->session_api->account_id);
			if ($this->session_api->call('email', 'account', 'get_formula', $parameters, $response_array))
			{
				if (isset($response_array['broadcast_max_daily']))
					$broadcast_max_daily = $response_array['broadcast_max_daily'];
				if (isset($response_array['broadcast_restrictions']))
					$smarty_email_disabled = $response_array['broadcast_restrictions'] == 'BLOCKED';
			}
		}
		if ($this->session_api->connectFromCredentials('fax'))
		{
			$response_array = array();
			$parameters = array('account_id' => array($this->session_api->account_id));
			if ($this->session_api->call('fax', 'account', 'enum_credit_balances', $parameters, $response_array))
			{
				foreach ($response_array as $credit)
				{
					switch ((string)$credit['balance'])
					{
						case '0':
							$smarty_fax_disabled = false;
							break;
						case '1':
							$smarty_fax_disabled = false;
							break;
						default:
							$smarty_fax_disabled = false;
							break;
					}
				}
			}
		}
		if ($this->session_api->connectFromCredentials('sms'))
		{
			$response_array = array();
			$parameters = array('account_id' => $this->session_api->account_id);
			if ($this->session_api->call('sms', 'account', 'enum_credit_balances', $parameters, $response_array))
			{
				foreach ($response_array as $credit)
				{
					switch ((string)$credit['balance'])
					{
						case '0':
							$smarty_sms_disabled = false;
							break;
						case '1':
							$smarty_sms_disabled = false;
							break;
						default:
							$smarty_sms_disabled = false;
							break;
					}
				}
			}
		}

		// Lowest prices
		$smarty_email_promotion = false;
		$smarty_fax_promotion = false;
		$smarty_sms_promotion = false;

		$smarty_email_lowest_price = null;
		$smarty_fax_lowest_price = null;
		$smarty_sms_lowest_price = null;

		$response_array = array();
		$parameters = array(
			'application_id' => $this->session_api->application_id,
			'account_id' => $this->session_api->account_id
		);

		$prices = array();
		if ($this->session_api->callExternal('http://www.express-mailing.com/api/cart/ws.php',
											'common', 'account', 'enum_credits', $parameters, $prices))
		{
			if (isset($prices['email']))
			{
				foreach ($prices['email'] as $ticket)
				{
					$unit_price = null;
					if (isset($ticket['promo_ending']) && $ticket['promo_ending'] > time())
					{
						$smarty_email_promotion = true;
						if (isset($ticket['promo_price'], $ticket['product_units']))
							$unit_price = $ticket['promo_price'] / $ticket['product_units'];
					}
					elseif (isset($ticket['normal_price'], $ticket['product_units']))
						$unit_price = $ticket['normal_price'] / $ticket['product_units'];

					if (!empty($unit_price) && ($smarty_email_lowest_price == null || $unit_price < $smarty_email_lowest_price))
						$smarty_email_lowest_price = $unit_price;
				}
			}

			if (isset($prices['fax']))
			{
				foreach ($prices['fax'] as $ticket)
				{
					$unit_price = null;
					if (isset($ticket['promo_ending']) && $ticket['promo_ending'] > time())
					{
						$smarty_fax_promotion = true;
						if (isset($ticket['promo_price'], $ticket['product_units']))
							$unit_price = $ticket['promo_price'] / $ticket['product_units'];
					}
					elseif (isset($ticket['normal_price'], $ticket['product_units']))
						$unit_price = $ticket['normal_price'] / $ticket['product_units'];

					if (!empty($unit_price) && ($smarty_fax_lowest_price == null || $unit_price < $smarty_fax_lowest_price))
						$smarty_fax_lowest_price = $unit_price;
				}
			}

			if (isset($prices['sms']))
			{
				foreach ($prices['sms'] as $ticket)
				{
					$unit_price = null;
					if (isset($ticket['promo_ending']) && $ticket['promo_ending'] > time())
					{
						$smarty_sms_promotion = true;
						if (isset($ticket['promo_price'], $ticket['product_units']))
							$unit_price = $ticket['promo_price'] / $ticket['product_units'];
					}
					elseif (isset($ticket['normal_price'], $ticket['product_units']))
						$unit_price = $ticket['normal_price'] / $ticket['product_units'];

					if (!empty($unit_price) && ($smarty_sms_lowest_price == null || $unit_price < $smarty_sms_lowest_price))
						$smarty_sms_lowest_price = $unit_price;
				}
			}
		}

		$this->context->smarty->assign(array(
			'broadcast_max_daily' => $broadcast_max_daily,
			'smarty_email_disabled' => $smarty_email_disabled,
			'smarty_email_lowest_price' => $smarty_email_lowest_price,
			'smarty_fax_lowest_price' => $smarty_fax_lowest_price,
			'smarty_fax_disabled' => $smarty_fax_disabled,
			'smarty_sms_lowest_price' => $smarty_sms_lowest_price,
			'smarty_sms_disabled' => $smarty_sms_disabled,
			'smarty_email_promotion' => $smarty_email_promotion,
			'smarty_fax_promotion' => $smarty_fax_promotion,
			'smarty_sms_promotion' => $smarty_sms_promotion
		));

		$output = _PS_MODULE_DIR_.$this->name.'/views/templates/admin/configuration.tpl';
		return $this->context->smarty->fetch($output);
	}

	public function getPreviewFolder()
	{
		return $this->html_preview_folder;
	}

}
