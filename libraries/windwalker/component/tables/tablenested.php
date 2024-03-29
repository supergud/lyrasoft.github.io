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
 * A base Table class for Nested Item.
 * Not real use in component now.
 *
 * @package     Windwalker.Framework
 * @subpackage  Tables
 */
class AKTableNested extends JTableNested
{

	/**
	 * Method to return the title to use for the asset table.
	 *
	 * @return  string
	 */
	protected function _getAssetTitle()
	{
		if (property_exists($this, 'title') && $this->title)
		{
			return $this->title;
		}
		else
		{
			return $this->_getAssetName();
		}
	}

	/**
	 * Overloaded bind function to pre-process the params.
	 *
	 * @param    array        Named array
	 *
	 * @return   null|string  null is operation was satisfactory, otherwise returns an error
	 * @see      JTable:bind
	 */
	public function bind($array, $ignore = '')
	{
		// for Fields group
		// Convert jform[fields_group][field] to jform[field] or JTable cannot bind data.
		// ==========================================================================================
		$data  = array();
		$array = AKHelper::_('array.pivotFromTwoDimension', $array);

		// Set field['param_xxx'] to params
		// ==========================================================================================
		if (empty($array['params']))
		{
			$array['params'] = AKHelper::_('array.pivotFromPrefix', 'param_', $array, JArrayHelper::getValue($array, 'params', array()));
		}

		// set params to JRegistry
		// ==========================================================================================
		if (isset($array['params']) && is_array($array['params']))
		{
			$registry = new JRegistry();
			$registry->loadArray($array['params']);
			$array['params'] = (string) $registry;
		}

		// Bind the rules.
		// ==========================================================================================
		if (isset($array['rules']) && is_array($array['rules']))
		{
			$rules = new JAccessRules($array['rules']);
			$this->setRules($rules);
		}

		return parent::bind($array, $ignore);
	}

	/*
	 * Setting Nested table and rebuild.
	 */
	public function rebuild($parentId = null, $leftId = 0, $level = 0, $path = '')
	{
		return parent::rebuild($parentId, $leftId, $level, $path);
	}
}
