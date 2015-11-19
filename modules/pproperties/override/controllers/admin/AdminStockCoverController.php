<?php
/**
* Product Properties Extension
*
* @author    PS&More www.psandmore.com <support@psandmore.com>
* @copyright 2011-2015 PS&More
* @license   psandmore.com/licenses/sla
*/

class AdminStockCoverController extends AdminStockCoverControllerCore
{

	public function __construct()
	{
		parent::__construct();
		$this->fields_list['qty_sold']['callback'] = 'callbackSoldQuantity';
		$this->fields_list['stock']['callback'] = 'callbackStockQuantity';
	}

	public static function callbackSoldQuantity($echo, $tr)
	{
		return PP::adminControllerDisplayListContentQuantity($echo, $tr, 'qty_sold', 'stock-cover sold');
	}

	public static function callbackStockQuantity($echo, $tr)
	{
		return PP::adminControllerDisplayListContentQuantity($echo, $tr, 'stock', 'stock-cover stock');
	}

	public function renderList()
	{
		$this->addRowAction('details');

		$this->toolbar_btn = array();

		// disables link
		$this->list_no_link = true;

		// query
		$this->_select = 'a.id_product as id, COUNT(pa.id_product_attribute) as variations, SUM(s.usable_quantity+s.usable_quantity_remainder) as stock';
		$this->_join = 'LEFT JOIN `'._DB_PREFIX_.'product_attribute` pa ON (pa.id_product = a.id_product)
						'.Shop::addSqlAssociation('product_attribute', 'pa', false).'
						INNER JOIN `'._DB_PREFIX_.'stock` s ON (s.id_product = a.id_product)';
		$this->_group = 'GROUP BY a.id_product';

		self::$currentIndex .= '&coverage_period='.(int)$this->getCurrentCoveragePeriod().'&warn_days='.(int)$this->getCurrentWarning();
		if ($this->getCurrentCoverageWarehouse() != -1)
		{
			$this->_where .= ' AND s.id_warehouse = '.(int)$this->getCurrentCoverageWarehouse();
			self::$currentIndex .= '&id_warehouse='.(int)$this->getCurrentCoverageWarehouse();
		}

		// Hack for multi shop ..
		$this->_where .= ' AND b.id_shop = 1';

		$this->tpl_list_vars['stock_cover_periods'] = $this->stock_cover_periods;
		$this->tpl_list_vars['stock_cover_cur_period'] = $this->getCurrentCoveragePeriod();
		$this->tpl_list_vars['stock_cover_warehouses'] = $this->stock_cover_warehouses;
		$this->tpl_list_vars['stock_cover_cur_warehouse'] = $this->getCurrentCoverageWarehouse();
		$this->tpl_list_vars['stock_cover_warn_days'] = $this->getCurrentWarning();
		$this->ajax_params = array(
			'period' => $this->getCurrentCoveragePeriod(),
			'id_warehouse' => $this->getCurrentCoverageWarehouse(),
			'warn_days' => $this->getCurrentWarning()
		);

		$this->displayInformation($this->l('Considering the coverage period chosen and the quantity of products/combinations that you sold.'));
		$this->displayInformation($this->l('this interface gives you an idea of when a product will run out of stock.'));

		return $this->adminControllerRenderList();
	}

	protected function getQuantitySold($id_product, $id_product_attribute, $coverage)
	{
		$query = new DbQuery();
		$query->select('SUM('.PP::sqlQty('product_quantity', 'od').')');
		$query->from('order_detail', 'od');
		$query->leftJoin('orders', 'o', 'od.id_order = o.id_order');
		$query->leftJoin('order_history', 'oh', 'o.date_upd = oh.date_add');
		$query->leftJoin('order_state', 'os', 'os.id_order_state = oh.id_order_state');
		$query->where('od.product_id = '.(int)$id_product);
		$query->where('od.product_attribute_id = '.(int)$id_product_attribute);
		$query->where('TO_DAYS(NOW()) - TO_DAYS(oh.date_add) <= '.(int)$coverage);
		$query->where('o.valid = 1');
		$query->where('os.logable = 1 AND os.delivery = 1 AND os.shipped = 1');

		$quantity = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query);
		return $quantity;
	}
}
