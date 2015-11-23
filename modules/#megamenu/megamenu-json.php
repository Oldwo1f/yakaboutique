<?php
/*
*  @author Marcin Kubiak <support@add-ons.eu>
*  @copyright  Smart Soft
*  @license    Commercial license
*  International Registered Trademark & Property of Smart Soft
*/

require_once(dirname(__FILE__).'/../../config/config.inc.php');

if (Tools::getValue('mmtoken') != sha1('mm'._COOKIE_KEY_.'mmmegamenu'))
  die;
 
  
include(dirname(__FILE__).'/../../init.php');
require_once(dirname(__FILE__).'/megamenu.php');


$MM = new MegaMenu();
$MM->hookAjaxCall();
