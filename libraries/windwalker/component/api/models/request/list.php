<?php 
/**
 * @package     Windwalker.Framework
 * @subpackage  Component
 * @author      Simon Asika <asika32764@gmail.com>
 * @copyright   Copyright (C) 2013 Asikart. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

include_once AKPATH_COMPONENT . '/modellist.php';

/**
 * Request list model.
 *
 * @package     Windwalker.Framework
 * @subpackage  Component
 */
class AKRequestModelList extends AKModelList
{
	public $request_item = '';
	public $request_list = '';

	public $result;

	/**
	 * Constructor.
	 *
	 * @param    array    An optional associative array of configuration settings.
	 *
	 * @see      JController
	 */
	public function __construct($config = array())
	{
		$this->service = AKHelper::_('api.getSDK', $this->option);

		$config['filter_fields'] = array(
			'filter_order_Dir', 'filter_order', '*'
		);

		$config['filter_fields'] = AKHelper::_('query.mergeAPIFilterFields', $config['filter_fields'], $this->request_item);

		parent::__construct($config);
	}

	/**
	 * function getAPIResult
	 *
	 * @param
	 */
	public function getAPIResult()
	{
		// Get a storage key.
		$store = $this->getStoreId('getAPIResult');

		// Try to load the data from internal storage.
		if (isset($this->cache[$store]))
		{
			return $this->cache[$store];
		}

		// Create a new query object.
		$db       = $this->getDbo();
		$q        = $db->getQuery(true);
		$order    = $this->getState('list.ordering', 'a.id');
		$dir      = $this->getState('list.direction', 'asc');
		$prefix   = $this->getState('list.orderingPrefix', array());
		$orderCol = $this->getState('list.orderCol', 'a.ordering');

		// Filter and Search
		$uriQuery['filter'] = $this->getState('filter', array());
		$uriQuery['search'] = array();

		// Ordering
		$uriQuery['filter_order']        = $order;
		$uriQuery['filter_order_prefix'] = $prefix;
		$uriQuery['filter_order_Dir']    = $dir;

		// Pagination
		$uriQuery['limit'] = $this->getState('list.limit');
		$uriQuery['start'] = $this->getState('list.start');

		$search = $this->getState('search');

		$search_fields = $this->getFullSearchFields();

		if (!empty($search['index']))
		{
			foreach ($search_fields as $field):
				$uriQuery['search'][(string) $field] = $search['index'];
			endforeach;
		}

		// Load API
		$service = $this->service;

		$result = $service->execute("/{$this->request_list}/getitems", $uriQuery);

		if (!$result)
		{
			$this->setError($service->getError());

			return null;
		}

		$this->cache[$store] = $result;

		return $this->cache[$store];
	}

	/**
	 * function getItems
	 *
	 * @param
	 */
	public function getItems()
	{
		// Get a storage key.
		$store = $this->getStoreId();

		// Try to load the data from internal storage.
		if (isset($this->cache[$store]))
		{
			return $this->cache[$store];
		}

		$result = $this->getAPIResult();

		$items = array();

		// Debug ----------------
		$first = AKHelper::_('array.getValue', $result->items, 1);
		AKHelper::_('system.mark', 'First Item: ' . print_r((array) $first, 1), 'WindWalker');
		// ----------------------

		foreach ($result->items as $item):
			$items[] = $this->_map($this->request_item, $item);
		endforeach;

		// Add the items to the internal cache.
		$this->cache[$store] = $items;

		return $this->cache[$store];
	}

	/**
	 * Method to get the total number of items for the data set.
	 *
	 * @return  integer  The total number of items available in the data set.
	 * @since   11.1
	 */
	public function getTotal()
	{
		// Get a storage key.
		$store = $this->getStoreId('getTotal');

		// Try to load the data from internal storage.
		if (isset($this->cache[$store]))
		{
			return $this->cache[$store];
		}

		// Load the total.
		$result = $this->getAPIResult();
		$total  = (int) $result->total;

		// Add the total to the internal cache.
		$this->cache[$store] = $total;

		return $this->cache[$store];
	}

	/**
	 * Method to get the starting number of items for the data set.
	 *
	 * @return  integer  The starting number of items available in the data set.
	 * @since   11.1
	 */
	public function getStart()
	{
		$store = $this->getStoreId('getstart');

		// Try to load the data from internal storage.
		if (isset($this->cache[$store]))
		{
			return $this->cache[$store];
		}

		$start = $this->getState('list.start');
		$limit = $this->getState('list.limit');
		$total = $this->getTotal();
		if ($start > $total - $limit)
		{
			$start = max(0, (int) (ceil($total / $limit) - 1) * $limit);
		}

		// Add the total to the internal cache.
		$this->cache[$store] = $start;

		return $this->cache[$store];
	}

	/**
	 * function update
	 *
	 * @param
	 */
	public function update()
	{
		// Try to load the data from internal storage.
		if (isset($this->cache['update']))
		{
			return $this->cache['update'];
		}

		// Load API
		$service = $this->service;

		$result = $service->execute("/{$this->request_list}/isChanged");

		if (!$result)
		{
			$this->setError($service->getError());

			return false;
		}

		if (!$result->changed)
		{
			return false;
		}

		// Do Update
		$uriQuery['limit'] = 0;
		$result            = $service->execute("/{$this->request_list}", $uriQuery);

		// Save
		$db         = JFactory::getDbo();
		$table_name = "#__{$this->component}_{$this->list_name}";
		$db->truncateTable($table_name);

		$table = $this->getTable();

		foreach ((array) $result->items as $item):
			$table->bind((array) $item);
			$db->insertObject($table_name, $table);
		endforeach;

		return true;
	}

	/**
	 * function _map
	 *
	 * @param $item_name
	 */
	public function _map($name, $item)
	{
		$map = AKHelper::_('system.getConfig', 'keyMap.' . $name);

		return AKHelper::_('array.mapKey', $item, $map);
	}
}
