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

class AdminMarketingSListController extends ModuleAdminController
{
	private $session_api = null;

	const NEW_CMP = 1;
	const WAITING_SIGNATURE = 3;
	const SCHEDULED = 4;
	const WAITING_NEXT_SLOT = 5;
	const SLEEPING = 6;
	const SENDING = 7;
	const RETRYING = 8;
	const FINISHING = 9;
	const FINISHED = 10;
	const STORING = 11;
	const STORED = 12;
	const UNAVAILABLE_CREDIT = 13;

	public function __construct()
	{
		$this->name = 'adminmarketingslist';
		$this->bootstrap = true;
		$this->module = 'expressmailing';
		$this->context = Context::getContext();
		$this->lang = false;
		$this->default_form_language = $this->context->language->id;

		$this->table = 'expressmailing_sms';
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
		$this->toolbar_title = Translate::getModuleTranslation('expressmailing', 'My sms statistics', 'marketing_step0');
	}

	public function postProcess()
	{
		if (Tools::isSubmit('update'.$this->table))
		{
			$campaign_id = (int)Tools::getValue('campaign_id');
			if ($campaign_id > 0)
			{
				Tools::redirectAdmin('index.php?controller=AdminMarketingSStep1&campaign_id='.$campaign_id.
					'&token='.Tools::getAdminTokenLite('AdminMarketingSStep1'));
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
				Db::getInstance()->delete('expressmailing_sms_recipients', 'campaign_id = '.$campaign_id);

				Db::getInstance()->update($this->table, array (
					'campaign_state' => 6,
					'campaign_date_update' => date('Y-m-d H:i:s')
					), 'campaign_id = '.$campaign_id
				);
			}
		}
	}

	public function renderList()
	{
		// Campagne en cours de création (off-line)
		// ----------------------------------------
		if (!count($this->getLocalCampaigns()))
			$this->_list = array ();
		else
			$this->_list = $this->getLocalCampaigns(self::NEW_CMP);

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
		$helper->title = '<i class="icon-edit"></i> '.$this->module->l('Drafted messages (off-line)', 'adminmarketingslist');
		$helper->actions = array ('edit', 'delete');
		$helper->listTotal = count($this->_list);

		$helper->toolbar_btn['new'] = array (
			'href' => 'index.php?controller=AdminMarketingS&token='.Tools::getAdminTokenLite('AdminMarketingS'),
			'desc' => $this->module->l('New sms campaign', 'adminmarketingslist')
		);

		$this->fields_list = array (
			'campaign_id' => array (
				'title' => $this->module->l('ID', 'adminmarketingslist'),
				'width' => 140,
				'type' => 'text',
				'search' => false,
				'ajax' => true
			),
			'campaign_date_update' => array (
				'title' => $this->module->l('Last Update', 'adminmarketingslist'),
				'width' => 'auto',
				'type' => 'text',
				'search' => false,
				'ajax' => true
			),
			'campaign_name' => array (
				'title' => $this->module->l('Campaign name', 'adminmarketingslist'),
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
		if ($this->session_api->connectFromCredentials('sms'))
		{
			$response_array = array ();
			$parameters = array (
				'account_ids' => array ($this->session_api->account_id),
				'campaign_states' => array (
					self::SCHEDULED, self::WAITING_NEXT_SLOT,
					self::SLEEPING, self::SENDING,
					self::RETRYING, self::FINISHED,
					self::UNAVAILABLE_CREDIT, self::FINISHING
				)
			);

			if ($this->session_api->call('sms', 'campaign', 'enum_by_states', $parameters, $response_array))
			{
				// Campagnes en cours d'envoi, programmées ou en pause
				// ---------------------------------------------------
				$data = array_filter($response_array, function ($k)
				{
					$states = array ('scheduled', 'waiting_next_slot', 'sleeping', 'sending', 'retrying', 'unavailable_credit');
					return in_array($k['state'], $states);
				});

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
					$helper->token = Tools::getAdminTokenLite('AdminMarketingSStats');
					$helper->currentIndex = $this->context->link->getAdminLink('AdminMarketingSStats', false);
					$helper->allow_export = false;
					$helper->title = '<i class="icon-share"></i> '.
						$this->module->l('Messages in the outbox (on-line)', 'adminmarketingslist').
						' <span class="badge">'.count($data).'</span>';
					$helper->actions = array ('details');
					$helper->listTotal = count($data);

					$this->fields_list = array (
						'campaign_id' => array (
							'title' => $this->module->l('ID', 'adminmarketingslist'),
							'width' => 140,
							'type' => 'text',
							'search' => false
						),
						'start_date' => array (
							'title' => $this->module->l('Date send', 'adminmarketingslist'),
							'width' => 'auto',
							'type' => 'text',
							'callback' => 'callbackTime',
							'search' => false
						),
						'name' => array (
							'title' => $this->module->l('Campaign name', 'adminmarketingslist'),
							'width' => 'auto',
							'type' => 'text',
							'callback' => 'callbackTitle',
							'search' => false
						)
					);

					$output .= $helper->generateList($data, $this->fields_list);
				}

				$data = array_filter($response_array, function ($k)
				{
					return $k['state'] == 'finished';
				});

				if (count($data) > 0)
				{
					// Campagne archivées
					// ------------------
					$helper = new HelperList();
					$helper->no_link = true;
					$helper->shopLinkType = '';
					$helper->simple_header = false;
					$helper->table = $this->table;
					$helper->identifier = 'campaign_id';
					$helper->show_toolbar = false;
					$helper->toolbar_scroll = false;
					$helper->token = Tools::getAdminTokenLite('AdminMarketingSStats');
					$helper->currentIndex = $this->context->link->getAdminLink('AdminMarketingSStats', false);
					$helper->allow_export = false;
					$helper->title = '<i class="icon-folder-open"></i> '.
						$this->module->l('Sent & archived messages (on-line)', 'adminmarketingslist').
						' <span class="badge">'.count($data).'</span>';
					$helper->actions = array ('details');
					$helper->listTotal = count($response_array);

					$this->fields_list = array (
						'campaign_id' => array (
							'title' => $this->module->l('ID', 'adminmarketingslist'),
							'width' => 140,
							'type' => 'text',
							'search' => false
						),
						'start_date' => array (
							'title' => $this->module->l('Sent date', 'adminmarketingslist'),
							'width' => 'auto',
							'type' => 'text',
							'callback' => 'callbackDate',
							'search' => false
						),
						'name' => array (
							'title' => $this->module->l('Campaign name', 'adminmarketingslist'),
							'width' => 'auto',
							'type' => 'text',
							'callback' => 'callbackTitle',
							'search' => false
						)
					);

					$output .= $helper->generateList($data, $this->fields_list);
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
		if ($campaign_state)
			$req->where('campaign_state = '.(string)$campaign_state);
		$req->orderby('campaign_date_update DESC');
		$req->limit(5);

		$user_list = Db::getInstance()->executeS($req, true, false);
		return $user_list;
	}

	public function callbackTime($time)
	{
		if (!empty($time))
			return date('Y-m-d H:i', $time);
		else
			return $time;
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
			'" class=" btn btn-default" title="" href="index.php?controller=AdminMarketingSStats&campaign_id='.$id.
			'&token='.$token.'" target="_blank"><i class="icon-eye-open"></i> '.
			$this->module->l('See stats', 'adminmarketingslist').'</a>';
	}

}
