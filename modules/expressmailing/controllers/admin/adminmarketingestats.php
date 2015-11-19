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

include_once 'em_tools.php';

class AdminMarketingEStatsController extends ModuleAdminController
{
	private $session_api = null;

	public function __construct()
	{
		$this->name = 'adminmarketingestats';
		$this->bootstrap = true;
		$this->module = 'expressmailing';
		$this->context = Context::getContext();
		$this->lang = false;
		$this->default_form_language = $this->context->language->id;
		$this->identifier = 'campaign_id';

		parent::__construct();

		// API initialization
		// ------------------
		include _PS_MODULE_DIR_.$this->module->name.'/controllers/admin/session_api.php';
		$this->session_api = new SessionApi();
	}

	public function setMedia()
	{
		$this->addCSS(_PS_MODULE_DIR_.'expressmailing/views/css/expressmailing.css', 'all');
		parent::setMedia();
	}

	public function initToolbarTitle()
	{
		parent::initToolbarTitle();
		$this->toolbar_title = Translate::getModuleTranslation('expressmailing', 'My emailing statistics', 'marketing_step0');
	}

	public function renderList()
	{
		$output = '';

		if ($this->session_api->connectFromCredentials('email'))
		{
			Tools::clearCache($this->context->smarty);

			$response_array = array();
			$parameters = array(
				'account_id' => $this->session_api->account_id,
				'max_lines' => 20,
				'campaign_id' => (int)Tools::getValue('campaign_id')
			);

			if ($this->session_api->call('email', 'campaign', 'enum_last_sent', $parameters, $response_array))
			{
				if (is_array($response_array) && count($response_array))
				{
					// On retrouve tous les jours d'envoi (envois fractionnés sur plusieurs jours)
					// ---------------------------------------------------------------------------
					$tools = new EMTools();
					$days = array();
					foreach ($response_array as $day)
						$days[] = array('day_api' => $day['stat_date'], 'day_lang' => $tools->getLocalizableDate($day['stat_date']));

					$this->context->smarty->assign(array (
						'days' => $days,
						'current_index' => AdminController::$currentIndex.'&campaign_id='.$response_array[0]['campaign_id'],
						'campaign_id' => $response_array[0]['campaign_id'],
						'campaign_name' => Tools::htmlentitiesDecodeUTF8($response_array[0]['name'])
					));

					// S'il y a un stat_date, il faut ré-intéroger l'API pour obtenir les stats du jour sélectionné
					// --------------------------------------------------------------------------------------------
					if (Tools::getValue('stat_date'))
					{
						$stat_array = array();
						$parameters = array(
							'account_id' => $this->session_api->account_id,
							'campaign_id' => (int)Tools::getValue('campaign_id'),
							'stat_date' => (int)Tools::getValue('stat_date')
						);

						if ($this->session_api->call('email', 'campaign', 'get_statistics', $parameters, $stat_array))
							$response_array = array($stat_array); // Pour rester compatible avec le code ci-dessous
					}

					// S'il n'y a pas de stat_date, l'API nous retourne directement les stats du dernier envoi
					// ---------------------------------------------------------------------------------------
					$this->context->smarty->assign(array (
						'select_day' => $response_array[0]['stat_date'],
						'sent' => $response_array[0]['sent'],
						'not_sent' => $response_array[0]['not_sent'],
						'delivered' => $response_array[0]['delivered'],
						'not_delivered' => $response_array[0]['not_delivered'],
						'opened' => $response_array[0]['opened'],
						'not_opened' => $response_array[0]['not_opened'],
						'unique_clickers' => $response_array[0]['unique_clickers'],
						'all_clicks' => $response_array[0]['all_clicks'],
						'unsubscribes' => $response_array[0]['unsubscribes'],
						'abuses' => $response_array[0]['abuses'],
						'ratio_sent' => $response_array[0]['ratio_sent'],
						'ratio_not_sent' => $response_array[0]['ratio_not_sent'],
						'ratio_delivered' => $response_array[0]['ratio_delivered'],
						'ratio_not_delivered' => $response_array[0]['ratio_not_delivered'],
						'ratio_opened' => $response_array[0]['ratio_opened'],
						'ratio_not_opened' => $response_array[0]['ratio_not_opened'],
						'ratio_unique_clickers' => $response_array[0]['ratio_unique_clickers'],
						'ratio_unsubscribes' => $response_array[0]['ratio_unsubscribes'],
						'ratio_abuses' => $response_array[0]['ratio_abuses']
					));

					// On affiche le tableau des stats
					// -------------------------------
					$diplay = $this->getTemplatePath().'marketinge_stats/marketinge_stats.tpl';
					$output = $this->context->smarty->fetch($diplay);

					// On charge les données du graphique des "opened"
					// -----------------------------------------------
					$delivered = array(); /* ne pas utiliser le nom response_array SVP */

					$parameters = array(
						'account_id' => $this->session_api->account_id,
						'campaign_id' => $response_array[0]['campaign_id'],
						'stat_date' => $response_array[0]['stat_date']
					);

					$this->session_api->call('email', 'campaign', 'get_graph_delivered_per_hour', $parameters, $delivered);
					$this->context->smarty->assign('delivered', $delivered);
					$graph = $this->getTemplatePath().'marketinge_stats/marketinge_graph.tpl';
					$output .= $this->context->smarty->fetch($graph);
				}
				else
				{
					// On affiche une liste vide
					// -------------------------
					$helper = new HelperList();
					$helper->no_link = true;
					$helper->shopLinkType = '';
					$helper->simple_header = false; // Mettre 'search' => false dans chaque fields_list
					$helper->table = $this->table;
					$helper->identifier = 'campaign_id';
					$helper->show_toolbar = true;
					$helper->toolbar_scroll = false;
					$helper->token = Tools::getAdminTokenLite('AdminMarketingEStats');
					$helper->currentIndex = $this->context->link->getAdminLink('AdminMarketingEStats', false);
					$helper->allow_export = false;
					$helper->title = '<i class="icon-bar-chart"></i> '.
						$this->module->l('Broadcast evolution during last 24/48 hours', 'adminmarketingestats');

					$helper->toolbar_btn = array(
						'back' => array(
							'href' => 'index.php?controller=AdminMarketingEList&token='.Tools::getAdminTokenLite('AdminMarketingEList'),
							'desc' => $this->module->l('Back to list', 'adminmarketingestats')
						)
					);

					$helper->actions = array('details');
					$this->fields_list = array();

					$output .= $helper->generateList($this->fields_list, $this->fields_list);
				}
			}
		}

		$footer = $this->getTemplatePath().'footer.tpl';
		$output .= $this->context->smarty->fetch($footer);

		return $output;
	}

}
