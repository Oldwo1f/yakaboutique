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

class AdminMarketingFListController extends ModuleAdminController
{
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

	private $session_api = null;

	public function __construct()
	{
		$this->name = 'adminmarketingflist';
		$this->bootstrap = true;
		$this->module = 'expressmailing';
		$this->context = Context::getContext();
		$this->lang = false;
		$this->default_form_language = $this->context->language->id;

		$this->table = 'expressmailing_fax';
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
		$this->toolbar_title = Translate::getModuleTranslation('expressmailing', 'My fax statistics', 'marketing_step0');
	}

	public function postProcess()
	{
		if (Tools::isSubmit('update'.$this->table))
		{
			$campaign_id = (int)Tools::getValue('campaign_id');
			if ($campaign_id > 0)
			{
				Tools::redirectAdmin('index.php?controller=AdminMarketingFStep1&campaign_id='.$campaign_id.
					'&token='.Tools::getAdminTokenLite('AdminMarketingFStep1'));
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
				Db::getInstance()->delete('expressmailing_fax_recipients', 'campaign_id = '.$campaign_id);
				$req = new DbQuery();
				$req->select('*');
				$req->from('expressmailing_fax_pages');
				$req->where('campaign_id = '.$campaign_id);
				$req->orderBy('id');

				$pages_db = Db::getInstance()->executeS($req, true, false);
				foreach ($pages_db as $page)
				{
					unlink($page['page_path']);
					unlink($page['page_path_original']);
				}

				Db::getInstance()->delete('expressmailing_fax_pages', 'campaign_id = '.$campaign_id);

				Db::getInstance()->update($this->table, array (
					'campaign_state' => self::FINISHED,
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
		$helper->title = '<i class="icon-edit"></i> '.$this->module->l('Drafted messages (off-line)', 'adminmarketingflist');
		$helper->actions = array ('edit', 'delete');
		$helper->listTotal = count($this->_list);

		$helper->toolbar_btn['new'] = array (
			'href' => 'index.php?controller=AdminMarketingF&token='.Tools::getAdminTokenLite('AdminMarketingF'),
			'desc' => $this->module->l('New fax campaign', 'adminmarketingflist')
		);

		$this->fields_list = array (
			'campaign_id' => array (
				'title' => $this->module->l('ID', 'adminmarketingflist'),
				'width' => 140,
				'type' => 'text',
				'search' => false,
				'ajax' => true
			),
			'campaign_date_update' => array (
				'title' => $this->module->l('Last Update', 'adminmarketingflist'),
				'width' => 'auto',
				'type' => 'text',
				'search' => false,
				'ajax' => true
			),
			'campaign_name' => array (
				'title' => $this->module->l('Campaign name', 'adminmarketingflist'),
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
		if ($this->session_api->connectFromCredentials('fax'))
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

			if ($this->session_api->call('fax', 'campaign', 'enum_by_state', $parameters, $response_array))
			{
				// Campagnes en cours d'envoi, programmées ou en pause
				// -------------------------------------------------
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
					$helper->token = Tools::getAdminTokenLite('AdminMarketingFStats');
					$helper->currentIndex = $this->context->link->getAdminLink('AdminMarketingFStats', false);
					$helper->allow_export = false;
					$helper->title = '<i class="icon-share"></i> '.
						$this->module->l('Messages in the outbox (on-line)', 'adminmarketingflist').
						' <span class="badge">'.count($data).'</span>';
					$helper->actions = array ('details');
					$helper->listTotal = count($data);

					$this->fields_list = array (
						'campaign_id' => array (
							'title' => $this->module->l('ID', 'adminmarketingflist'),
							'width' => 140,
							'type' => 'text',
							'search' => false
						),
						'send_date' => array (
							'title' => $this->module->l('Date send', 'adminmarketingflist'),
							'width' => 'auto',
							'type' => 'text',
							'callback' => 'callbackTime',
							'search' => false
						),
						'name' => array (
							'title' => $this->module->l('Campaign name', 'adminmarketingflist'),
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
					$helper->simple_header = true;
					$helper->table = $this->table;
					$helper->identifier = 'campaign_id';
					$helper->show_toolbar = true;
					$helper->toolbar_scroll = false;
					$helper->token = Tools::getAdminTokenLite('AdminMarketingFStats');
					$helper->currentIndex = $this->context->link->getAdminLink('AdminMarketingFStats', false);
					$helper->allow_export = false;
					$helper->title = '<i class="icon-folder-open"></i> '.
						$this->module->l('Sent & archived messages (on-line)', 'adminmarketingflist').
						' <span class="badge">'.count($data).'</span>';
					$helper->actions = array ('details');
					$helper->listTotal = count($response_array);

					$this->fields_list = array (
						'campaign_id' => array (
							'title' => $this->module->l('ID', 'adminmarketingflist'),
							'width' => 140,
							'type' => 'text',
							'search' => false
						),
						'finished_date' => array (
							'title' => $this->module->l('Sent date', 'adminmarketingflist'),
							'width' => 'auto',
							'type' => 'text',
							'callback' => 'callbackDateArchived',
							'search' => false
						),
						'name' => array (
							'title' => $this->module->l('Campaign name', 'adminmarketingflist'),
							'width' => 'auto',
							'type' => 'text',
							'callback' => 'callbackTitle',
							'search' => false
						)
					);

					// Le comptage (le BADGE) affichera bien le total, mais on limite l'affichage du HelperList aux 30 dernières campagnes
					// -------------------------------------------------------------------------------------------------------------------
					array_splice($data, 30);
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
		if ($campaign_state !== null)
			$req->where('campaign_state = \''.(int)$campaign_state.'\'');
		$req->orderby('campaign_date_update DESC');
		$req->limit(5);

		$user_list = Db::getInstance()->executeS($req->build(), true, false);
		return $user_list;
	}

	public function callbackTime($time)
	{
		if (!empty($time))
			return date('Y-m-d H:i', $time);
		else
			return $time;
	}

	public function callbackDateArchived($date)
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
			'" class=" btn btn-default" title="" href="index.php?controller=AdminMarketingFStats&campaign_id='.$id.
			'&token='.$token.'" target="_blank"><i class="icon-eye-open"></i> '.
			$this->module->l('See stats', 'adminmarketingflist').'</a>';
	}

}
