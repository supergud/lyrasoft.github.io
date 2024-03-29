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

include_once AKPATH_COMPONENT . '/modeladmin.php';

/**
 * Request item model.
 *
 * @package     Windwalker.Framework
 * @subpackage  Component
 */
class AKRequestModelItem extends AKModelAdmin
{
	public $request_item = '';
	public $request_list = '';

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

		parent::__construct($config);
	}

	/**
	 * Method to delete one or more records.
	 *
	 * @param   array &$pks An array of record primary keys.
	 *
	 * @return  boolean  True if successful, false if an error occurs.
	 * @since   11.1
	 */
	public function delete(&$pks = null)
	{
		// Initialise variables.
		$pks = (array) $pks;

		// Iterate the items to delete each one.
		$uriQuery['cid'] = $pks;

		$service = $this->service;
		$result  = $service->execute("/{$this->request_item}/delete", $uriQuery);

		return $this->_handleResult($result, $service);
	}

	/**
	 * Method to change the published state of one or more records.
	 *
	 * @param   array   &$pks  A list of the primary keys to change.
	 * @param   integer $value The value of the published state.
	 *
	 * @return  boolean  True on success.
	 * @since   11.1
	 */
	public function publish(&$pks, $value = 1)
	{
		// Initialise variables.
		$pks = (array) $pks;

		// Attempt to change the state of the records.
		$uriQuery['value'] = $value;
		$uriQuery['cid']   = $pks;

		$service = $this->service;
		$result  = $service->execute("/{$this->request_item}/publish", $uriQuery);

		return $this->_handleResult($result, $service);
	}

	/**
	 * Method to perform batch operations on an item or a set of items.
	 *
	 * @param   array $commands An array of commands to perform.
	 * @param   array $pks      An array of item ids.
	 * @param   array $contexts An array of item contexts.
	 *
	 * @return  boolean  Returns true on success, false on failure.
	 * @since   11.1
	 */
	public function batch($commands, $pks, $contexts)
	{
		$pks = (array) $pks;

		$uriQuery['commands'] = $commands;
		$uriQuery['cid']      = $pks;

		$service = $this->service;
		$result  = $service->execute("/{$this->request_item}/batch", $uriQuery);

		return $this->_handleResult($result, $service);
	}

	/**
	 * Method override to check-in a record or an array of record
	 *
	 * @param   mixed $pks The ID of the primary key or an array of IDs
	 *
	 * @return  mixed  Boolean false if there is an error, otherwise the count of records checked in.
	 * @since   11.1
	 */
	public function checkin($pks = array())
	{
		$pks = (array) $pks;

		$uriQuery['cid'] = $pks;

		$service = $this->service;
		$result  = $service->execute("/{$this->request_item}/checkin", $uriQuery);

		return $this->_handleResult($result, $service);
	}

	/**
	 * Method override to check-out a record.
	 *
	 * @param   integer $pk The ID of the primary key.
	 *
	 * @return  boolean  True if successful, false if an error occurs.
	 * @since   11.1
	 */
	public function checkout($pk = null)
	{
		$service = $this->service;
		$result  = $service->execute("/{$this->request_item}/checkout/" . $pk);

		return $this->_handleResult($result, $service);
	}

	/**
	 * Method to adjust the ordering of a row.
	 * Returns NULL if the user did not have edit
	 * privileges for any of the selected primary keys.
	 *
	 * @param   integer $pks   The ID of the primary key to move.
	 * @param   integer $delta Increment, usually +1 or -1
	 *
	 * @return  mixed  False on failure or error, true on success, null if the $pk is empty (no items selected).
	 * @since   11.1
	 */
	public function reorder($pks, $delta = 0)
	{
		$pks = (array) $pks;

		$uriQuery['cid']   = $pks;
		$uriQuery['delta'] = $delta;

		$service = $this->service;
		$result  = $service->execute("/{$this->request_item}/reorder", $uriQuery);

		return $this->_handleResult($result, $service);
	}

	/**
	 * Saves the manually set order of records.
	 *
	 * @param   array   $pks   An array of primary key ids.
	 * @param   integer $order +1 or -1
	 *
	 * @return  mixed
	 * @since   11.1
	 */
	public function saveorder($pks = null, $order = null)
	{
		$pks = (array) $pks;

		$uriQuery['cid']   = $pks;
		$uriQuery['order'] = $order;

		$service = $this->service;
		$result  = $service->execute("/{$this->request_item}/saveorder", $uriQuery);

		return $this->_handleResult($result, $service);
	}

	/**
	 * Method to duplicate items.
	 *
	 * @param   array &$pks An array of primary key IDs.
	 *
	 * @return  boolean  True if successful.
	 * @throws  Exception
	 */
	public function duplicate(&$pks)
	{
		$pks = (array) $pks;

		$uriQuery['cid'] = $pks;

		$service = $this->service;
		$result  = $service->execute("/{$this->request_item}/duplicate", $uriQuery);

		return $this->_handleResult($result, $service);
	}

	/**
	 * function _handleResult
	 *
	 * @param $result
	 */
	public function _handleResult($result, $service)
	{
		if (!$result)
		{
			$this->setError($service->getError());

			return false;
		}

		if (!$result->success)
		{
			$this->setError($result->errorMsg);

			return false;
		}

		// Clear the component's cache
		$this->cleanCache();

		return true;
	}
}