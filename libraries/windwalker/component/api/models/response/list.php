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
 * API Response Model for ModelList.
 */
class AKResponseModelList extends AKModelList
{
	public $default_method;

	/**
	 * Constructor.
	 *
	 * @param    array    An optional associative array of configuration settings.
	 *
	 * @see        JController
	 * @since      1.6
	 */
	public function __construct($config = array())
	{
		$config['filter_fields'] = AKHelper::_('query.mapAPIFilterFields', $config['filter_fields'], $this->item_name);

		$this->config = $config;

		parent::__construct($config);
	}

	/**
	 * Method to auto-populate the model state.
	 * Note. Calling getState in this method will result in recursion.
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		// Reset Session
		$doc = JFactory::getDocument();

		if ($doc->getType() == 'json')
		{
			$session = JFactory::getSession();
			$session->destroy();
		}

		$this->setState('search', JRequest::getVar('search'));

		parent::populateState($ordering, $direction);
	}

	/**
	 * Method to get an array of data items.
	 *
	 * @return  mixed  An array of data items on success, false on failure.
	 * @since   11.1
	 */
	public function getItems()
	{
		$items = parent::getItems();

		$result        = new JObject();
		$result->items = $items ? $items : array();
		$result->total = $this->getTotal();

		return $result;
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return    JDatabaseQuery
	 * @since    1.6
	 */
	protected function getListQuery()
	{
		static $executed;

		if (!$executed) :

			// Filter and Search
			$filter = $this->getState('filter', array());
			$search = $this->getState('search', array());
			$order  = $this->getState('list.ordering', 'a.id');

			// Key Mapping
			// ========================================================================
			$map    = AKHelper::_('system.getConfig', 'keyMap.' . $this->item_name);
			$filter = AKHelper::_('array.mapKey', $filter, $map);
			$search = AKHelper::_('array.mapKey', $search, $map);
			$order  = !empty($map->$order) ? $map->$order : $order;

			$this->setState('filter', $filter);
			$this->setState('search', $search);
			$this->setState('list.ordering', $order);

			$executed = true;
		endif;
	}

	/**
	 * Set search condition to support multiple search inputs.
	 *
	 * @param   array          $search Search fields and values.
	 * @param   JDatabaseQuery $q      The query object.
	 * @param   array          $ignore An array for ignore fields.
	 *
	 * @return  JDatabaseQuery
	 */
	public function searchCondition($search, $q = null, $ignore = array())
	{
		// Set ignore fields, and you can set yourself search later.
		$ignore[] = '*';

		return parent::searchCondition($search, $q, $ignore);
	}

	/**
	 * function isChanged
	 *
	 * @param
	 */
	public function isChanged()
	{
		$result     = new JObject();
		$cache_path = JPATH_CACHE . '/{$this->component}_{$this->list_name}/list_hash';
		$cache_file = $cache_path . '/' . $this->list_name;

		// Get Total list
		$this->setState('list.limit', 0);
		$this->setState('filter', null);
		$this->setState('search', null);
		$this->setState('query.where', null);
		$this->setState('query.having', null);

		$items = $this->getItems();
		$items = json_encode($items);
		$hash  = md5($items);

		if (!JFile::exists($cache_file))
		{
			JFolder::create($cache_path);
			JFile::write($cache_file, $hash);

			$result->changed = true;
			$result->hash    = $hash;

			return $result;
		}

		$cache = JFile::read($cache_file);

		if ($hash != $cache)
		{
			JFile::write($cache_file, $hash);

			$result->changed = true;
			$result->hash    = $hash;

			return $result;
		}

		$result->changed = false;
		$result->hash    = $hash;

		return $result;
	}
}
