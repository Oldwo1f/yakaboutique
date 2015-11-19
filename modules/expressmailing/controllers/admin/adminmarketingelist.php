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

class AdminMarketingEListController extends ModuleAdminController
{
	const WRITING = 0;
	const PROGRAMMED = 1;
	const SENDING = 2;
	const PAUSED = 3;
	const VALIDATION = 4;
	const ARCHIVED = 5;
	const SUPRESSED = 6;
	const TEMPLATE = 8;
	const BLOCKED = 9;

	private $session_api = null;

	public function __construct()
	{
		$this->name = 'adminmarketingelist';
		$this->bootstrap = true;
		$this->module = 'expressmailing';
		$this->context = Context::getContext();
		$this->lang = false;
		$this->default_form_language = $this->context->language->id;

		$this->table = 'expressmailing_email';
		$this->identifier = 'campaign_id';
		$this->display = 'list';

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

	public function postProcess()
	{
		if (Tools::isSubmit('update'.$this->table))
		{
			$campaign_id = (int)Tools::getValue('campaign_id');
			if ($campaign_id > 0)
			{
				Tools::redirectAdmin('index.php?controller=AdminMarketingEStep1&campaign_id='.$campaign_id.
					'&token='.Tools::getAdminTokenLite('AdminMarketingEStep1'));
				exit;
			}
		}

		if (Tools::isSubmit('delete'.$this->table))
		{
			$campaign_id = (int)Tools::getValue('campaign_id');
			if ($campaign_id > 0)
			{
				// On masque la campagne via son champ 'state'
				// -------------------------------------------
				Db::getInstance()->update('expressmailing_email', array(
					'campaign_state' => 6,
					'campaign_date_update' => date('Y-m-d H:i:s')
					), 'campaign_id = '.(int)$campaign_id
				);
			}
		}
	}

	public function renderList()
	{
		// Campagne en cours de création (off-line)
		// ----------------------------------------
		if (!count($this->getLocalCampaigns()))
			$this->_list = array();
		else
			$this->_list = $this->getLocalCampaigns(self::WRITING);

		$helper = new HelperList();
		$helper->no_link = true;
		$helper->shopLinkType = '';
		$helper->simple_header = false;
		$helper->table = $this->table;
		$helper->identifier = 'campaign_id';
		$helper->show_toolbar = true;
		$helper->toolbar_scroll = false;
		$helper->token = $this->token;
		$helper->currentIndex = AdminController::$currentIndex;
		$helper->allow_export = false;
		$helper->title = '<i class="icon-edit"></i> '.$this->module->l('Drafted messages (off-line)', 'adminmarketingelist');
		$helper->actions = array('edit', 'delete');
		$helper->listTotal = count($this->_list);

		$helper->toolbar_btn['new'] = array(
			'href' => 'index.php?controller=AdminMarketingE&token='.Tools::getAdminTokenLite('AdminMarketingE'),
			'desc' => $this->module->l('New emailing campaign', 'adminmarketingelist')
		);

		$this->fields_list = array(
			'campaign_id' => array(
				'title' => $this->module->l('ID', 'adminmarketingelist'),
				'width' => 140,
				'type' => 'text',
				'search' => false,
				'ajax' => true
			),
			'campaign_date_update' => array(
				'title' => $this->module->l('Last Update', 'adminmarketingelist'),
				'width' => 'auto',
				'type' => 'text',
				'search' => false,
				'ajax' => true
			),
			'campaign_name' => array(
				'title' => $this->module->l('Subject', 'adminmarketingelist'),
				'width' => 'auto',
				'type' => 'text',
				'search' => false,
				'callback' => 'callbackTitle',
				'ajax' => true
			)
		);

		$output = $helper->generateList($this->_list, $this->fields_list);

		// Campagnes on-line
		// -----------------
		if ($this->session_api->connectFromCredentials('email'))
		{
			$response_array = array();
			$parameters = array(
				'account_id' => $this->session_api->account_id,
				'campaign_states' => array(
					self::PROGRAMMED, self::SENDING,
					self::PAUSED, self::VALIDATION,
					self::ARCHIVED, self::BLOCKED
				)
			);

			if ($this->session_api->call('email', 'campaign', 'group_by_states', $parameters, $response_array))
			{
				// Campagnes en cours d'envoi, différées ou en pause
				// -------------------------------------------------
				$data = array();
				$data = array_merge($data, $response_array['state_'.self::PROGRAMMED]);
				$data = array_merge($data, $response_array['state_'.self::SENDING]);
				$data = array_merge($data, $response_array['state_'.self::PAUSED]);
				$data = array_merge($data, $response_array['state_'.self::VALIDATION]);
				$data = array_merge($data, $response_array['state_'.self::BLOCKED]);

				if (count($data) > 0)
				{
					$helper = new HelperList();
					$helper->no_link = true;
					$helper->shopLinkType = '';
					$helper->simple_header = false;
					$helper->table = $this->table;
					$helper->identifier = 'campaign_id';
					$helper->show_toolbar = true;
					$helper->toolbar_scroll = false;
					$helper->token = Tools::getAdminTokenLite('AdminMarketingEStats');
					$helper->currentIndex = $this->context->link->getAdminLink('AdminMarketingEStats', false);
					$helper->allow_export = false;
					$helper->title = '<i class="icon-share"></i> '.$this->module->l('Messages in the outbox (on-line)', 'adminmarketingelist');
					$helper->actions = array('details');
					$helper->listTotal = count($data);

					$this->fields_list = array(
						'campaign_id' => array(
							'title' => $this->module->l('ID', 'adminmarketingelist'),
							'width' => 140,
							'type' => 'text',
							'search' => false
						),
						'date_send' => array(
							'title' => $this->module->l('Date send', 'adminmarketingelist'),
							'width' => 'auto',
							'type' => 'text',
							'callback' => 'callbackDate',
							'search' => false
						),
						'name' => array(
							'title' => $this->module->l('Subject', 'adminmarketingelist'),
							'width' => 'auto',
							'type' => 'text',
							'callback' => 'callbackTitle',
							'search' => false
						)
					);

					$output .= $helper->generateList($data, $this->fields_list);
				}

				// On extrait les archives (un peu plus bas, on ne conservera que les 30 premières)
				// --------------------------------------------------------------------------------
				$data = $response_array['state_'.self::ARCHIVED];

				if (count($data) > 0)
				{
					// Campagne archivées
					// ------------------
					$helper = new HelperList();
					$helper->no_link = true;
					$helper->shopLinkType = '';
					$helper->simple_header = true;
					$helper->table = $this->table;
					$helper->identifier = 'campaign_id';
					$helper->show_toolbar = true;
					$helper->toolbar_scroll = false;
					$helper->token = Tools::getAdminTokenLite('AdminMarketingEStats');
					$helper->currentIndex = $this->context->link->getAdminLink('AdminMarketingEStats', false);
					$helper->allow_export = false;
					$helper->title = '<i class="icon-folder-open"></i> '.
										$this->module->l('Sent & archived messages (on-line)', 'adminmarketingelist').
										' <span class="badge">'.count($data).'</span>';
					$helper->actions = array('details');
					$helper->listTotal = count($response_array);

					$this->fields_list = array(
						'campaign_id' => array(
							'title' => $this->module->l('ID', 'adminmarketingelist'),
							'width' => 140,
							'type' => 'text',
							'search' => false
						),
						'sent_date' => array(
							'title' => $this->module->l('Last sent date', 'adminmarketingelist'),
							'width' => 'auto',
							'type' => 'date',
							'callback' => 'callbackDate',
							'search' => false
						),
						'name' => array(
							'title' => $this->module->l('Subject', 'adminmarketingelist'),
							'width' => 'auto',
							'type' => 'text',
							'callback' => 'callbackTitle',
							'search' => false
						)
					);

					// Le comptage (le BADGE) affichera bien le total, mais on limite l'affichage du HelperList aux 30 dernières campagnes
					// -------------------------------------------------------------------------------------------------------------------
					array_splice($data, 30);
					$output .= $helper->generateList($data, $this->fields_list, false);
				}
			}
		}

		$footer = $this->getTemplatePath().'footer.tpl';
		$output .= $this->context->smarty->fetch($footer);

		return $output;
	}

	private function getLocalCampaigns($campaign_state = null)
	{
		$req = new DbQuery();
		$req->select('campaign_id, campaign_state, campaign_date_update, campaign_date_send, campaign_name');
		$req->from($this->table);
		if ($campaign_state !== null)
			$req->where('campaign_state = \''.(int)$campaign_state.'\'');
		$req->orderby('campaign_date_update DESC');
		$req->limit(5);

		$user_list = Db::getInstance()->executeS($req, true, false);
		return $user_list;
	}

	public function callbackDate($date)
	{
		if (!empty($date))
			return date('Y-m-d', $date);
		else
			return $date;
	}

	public function callbackTitle($title)
	{
		return html_entity_decode((string)$title);
	}

	public function displayDetailsLink($token = null, $id = null)
	{
		return '<a id="stat_details_'.$id.
			'" class=" btn btn-default" title="" href="index.php?controller=AdminMarketingEStats&campaign_id='.$id.
			'&token='.$token.'" target="_blank"><i class="icon-eye-open"></i> '.
			$this->module->l('See stats', 'adminmarketingelist').'</a>';
	}

}
