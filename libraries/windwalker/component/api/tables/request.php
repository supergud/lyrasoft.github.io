<?php 
/**
 * @package     Windwalker.Framework
 * @subpackage  Tables
 * @author      Simon Asika <asika32764@gmail.com>
 * @copyright   Copyright (C) 2013 Asikart. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

jimport('joomla.database.tablenested');

/**
 * resident Table class
 */
class AKRequestTable extends JTable
{
	/**
	 * Constructor
	 *
	 * @param JDatabase A database connector object
	 */
	public function __construct($table, $key, $db)
	{
		$this->_service = AKHelper::_('api.getSDK', $this->_option);

		parent::__construct($table, $key, $db);
	}

	/**
	 * Get the columns from database table.
	 *
	 * @return  mixed  An array of the field names, or false if an error occurs.
	 * @since   11.1
	 */
	public function getFields()
	{
		static $cache = null;

		if ($cache === null)
		{
			// Lookup the fields for this table only once.
			$fields = (array) AKHelper::_('system.getConfig', 'keyMap.' . $this->_tbl);

			if (empty($fields))
			{
				$e = new JException(JText::_('JLIB_DATABASE_ERROR_COLUMNS_NOT_FOUND'));
				$this->setError($e);

				return false;
			}

			foreach ($fields as $key => &$field):
				$obj          = new StdClass;
				$obj->Default = '';
				$obj->Field   = $key;
				$obj->Map     = $field;
				$field        = $obj;
			endforeach;

			$cache = $fields;
		}

		return $cache;
	}

	/**
	 * Method to load a row from the database by primary key and bind the fields
	 * to the JTable instance properties.
	 *
	 * @param   mixed   $keys  An optional primary key value to load the row by, or an array of fields to match.  If not
	 *                         set the instance property value is used.
	 * @param   boolean $reset True to reset the default values before loading the new row.
	 *
	 * @return  boolean  True if successful. False if row not found or on error (internal error state set in that case).
	 * @link    http://docs.joomla.org/JTable/load
	 * @since   11.1
	 */
	public function load($keys = null, $reset = true)
	{
		if (empty($keys))
		{
			// If empty, use the value of the current key
			$keyName  = $this->_tbl_key;
			$keyValue = $this->$keyName;

			// If empty primary key there's is no need to load anything
			if (empty($keyValue))
			{
				return true;
			}

			$keys = array($keyName => $keyValue);
		}
		elseif (!is_array($keys))
		{
			// Load by primary key.
			$keys = array($this->_tbl_key => $keys);
		}

		if ($reset)
		{
			$this->reset();
		}

		// Initialise the query.
		$service = $this->_service;
		$pk      = JArrayHelper::getValue($keys, $this->_tbl_key);
		unset($keys[$this->_tbl_key]);

		$result = $service->execute("/{$this->_tbl}/getitem/" . $pk, $keys);

		if (!$result)
		{
			$e = new JException($service->getError());
			$this->setError($e);

			return false;
		}

		$result = (array) $result;

		// Bind the object with the row and return.
		return $this->bind($result);
	}

	/**
	 * Method to store a row in the database from the JTable instance properties.
	 * If a primary key value is set the row with that primary key value will be
	 * updated with the instance property values.  If no primary key value is set
	 * a new row will be inserted into the database with the properties from the
	 * JTable instance.
	 *
	 * @param   boolean $updateNulls True to update fields even if they are null.
	 *
	 * @return  boolean  True on success.
	 * @link    http://docs.joomla.org/JTable/store
	 * @since   11.1
	 */
	public function store($updateNulls = false)
	{
		// Initialise variables.
		$k    = $this->_tbl_key;
		$keys = get_object_vars($this);

		unset($keys->asset_id);

		$service = $this->_service;
		$pk      = JArrayHelper::getValue($keys, $this->_tbl_key);
		$pk      = $pk ? $pk : null;
		unset($keys[$this->_tbl_key]);

		$result = $service->execute("/{$this->_tbl}/save/" . $pk, $keys, 'post');

		if (!$result)
		{
			$e = new JException($service->getError());
			$this->setError($e);

			return false;
		}

		if ($result->success)
		{
			$this->bind((array) $result->item);

			return true;
		}
		else
		{
			$this->setError($result->errorMsg);

			return false;
		}

		return false;
	}

	/**
	 * Method to delete a row from the database table by primary key value.
	 *
	 * @param   mixed $pk An optional primary key value to delete.  If not set the instance property value is used.
	 *
	 * @return  boolean  True on success.
	 * @link    http://docs.joomla.org/JTable/delete
	 * @since   11.1
	 */
	public function delete($pk = null)
	{
		// Initialise variables.
		$k    = $this->_tbl_key;
		$pk   = (is_null($pk)) ? $this->$k : $pk;
		$keys = get_object_vars($this);
		unset($keys->asset_id);

		// If no primary key is given, return false.
		if ($pk === null)
		{
			$e = new JException(JText::_('JLIB_DATABASE_ERROR_NULL_PRIMARY_KEY'));
			$this->setError($e);

			return false;
		}

		unset($keys[$this->_tbl_key]);

		$service = $this->_service;
		$result  = $service->execute("/{$this->_tbl}/delete/" . $pk);

		if (!$result)
		{
			$e = new JException($service->getError());
			$this->setError($e);

			return false;
		}

		if ($result->success)
		{
			return true;
		}
		else
		{
			$this->setError($result->errorMsg);

			return false;
		}

		return false;
	}
}
