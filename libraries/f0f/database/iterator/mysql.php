<?php 
/**
 * @package     FrameworkOnFramework
 * @subpackage  database
 * @copyright   Copyright (C) 2010 - 2015 Nicholas K. Dionysopoulos / Akeeba Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 *
 * This file is adapted from the Joomla! Platform. It is used to iterate a database cursor returning F0FTable objects
 * instead of plain stdClass objects
 */

// Protect from unauthorized access
defined('F0F_INCLUDED') or die;

/**
 * MySQL database iterator.
 */
class F0FDatabaseIteratorMysql extends F0FDatabaseIterator
{
	/**
	 * Get the number of rows in the result set for the executed SQL given by the cursor.
	 *
	 * @return  integer  The number of rows in the result set.
	 *
	 * @see     Countable::count()
	 */
	public function count()
	{
		return @mysql_num_rows($this->cursor);
	}

	/**
	 * Method to fetch a row from the result set cursor as an object.
	 *
	 * @return  mixed   Either the next row from the result set or false if there are no more rows.
	 */
	protected function fetchObject()
	{
		return @mysql_fetch_object($this->cursor, $this->class);
	}

	/**
	 * Method to free up the memory used for the result set.
	 *
	 * @return  void
	 */
	protected function freeResult()
	{
		@mysql_free_result($this->cursor);
	}
}
