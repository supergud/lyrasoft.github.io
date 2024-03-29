<?php 
/**
 * @package     Windwalker.Framework
 * @subpackage  Helpers
 * @author      Simon Asika <asika32764@gmail.com>
 * @copyright   Copyright (C) 2013 Asikart. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

/**
 * SQL helper to handle some query strings.
 *
 * @package     Windwalker.Framework
 * @subpackage  Helpers
 */
class AKHelperQuery
{
	/**
	 * A cache to store Table columns.
	 *
	 * @var    array
	 */
	static public $columns;

	/**
	 * Get select query from tables array.
	 *
	 * @param    array   $tables Tables name to get columns.
	 * @param    boolean $all    Contain a.*, b.* etc.
	 *
	 * @return   array    Select column list.
	 */
	public static function getSelectList($tables = array(), $all = true)
	{
		$db = JFactory::getDbo();

		$select = array();
		$fields = array();
		$i      = 'a';

		foreach ($tables as $k => $table)
		{

			if (empty(self::$columns[$table]))
			{
				self::$columns[$table] = $db->getTableColumns($table);
			}

			$columns = self::$columns[$table];

			if ($all)
			{
				$select[] = "`{$k}`.*";
			}

			foreach ($columns as $key => $var)
			{
				$fields[] = $db->qn("{$k}.{$key}", "{$k}_{$key}");
			}

			$i = ord($i);
			$i++;
			$i = chr($i);
		}

		return $final = implode(",", $select) . ",\n" . implode(",\n", $fields);
	}

	/**
	 * Merge filter_fields with table columns. A proxy for AKHelperQuery::mergeFilterFields().
	 *
	 * @param    array $filter_fields Filter fields from Model.
	 * @param    array $tables        Tables name to get columns.
	 *
	 * @return   array    Filter fields list.
	 */
	public static function mergeFilterFields($filter_fields, $tables = array(), $option = array())
	{
		$db     = JFactory::getDbo();
		$fields = array();
		$i      = 'a';

		$ignore = array(
			'params'
		);

		// Ignore some columns
		if (!empty($option['ignore']))
		{
			$ignore = array_merge($ignore, $option['ignore']);
		}

		foreach ($tables as $k => $table)
		{

			if (empty(self::$columns[$table]))
			{
				self::$columns[$table] = $db->getTableColumns($table);
			}

			$columns = self::$columns[$table];

			foreach ($columns as $key => $var)
			{
				if (in_array($key, $ignore))
				{
					continue;
				}

				$fields[] = "{$k}.{$key}";
				//$fields[] = $key ;
			}

			$i = ord($i);
			$i++;
			$i = chr($i);
		}

		return array_merge($filter_fields, $fields);
	}

	/**
	 * Map API Response Filter Fields
	 *
	 * @param $filter_fields    array   Filter fields array.
	 * @param $name             string  Class name.
	 *
	 * @return array Merged filter fields.
	 */
	public static function mapAPIFilterFields($filter_fields, $name)
	{
		$map = (array) AK::_('system.getConfig', 'keyMap.' . $name);
		$map = array_keys($map);

		$filter_fields = array_merge($filter_fields, $map);

		return $filter_fields;
	}

	/**
	 * Map API Response Filter Fields
	 *
	 * @param $filter_fields    array   Filter fields array.
	 * @param $name             string  Class name.
	 *
	 * @return array Merged filter fields.
	 */
	public static function mergeAPIFilterFields($filter_fields, $name)
	{
		$map = (array) AK::_('system.getConfig', 'keyMap.' . $name);
		$map = array_keys($map);

		$filter_fields = array_merge($filter_fields, $map);

		return $filter_fields;
	}

	/**
	 * Get a query string to filter the publishing items now.
	 * Will return: "( publish_up < 'xxxx-xx-xx' OR publish_up = '0000-00-00' )
	 *                     AND ( publish_down > 'xxxx-xx-xx' OR publish_down = '0000-00-00' )"
	 *
	 * @param    string $prefix Prefix to columns name, eg: 'a.' will use `a`.`publish_up`.
	 *
	 * @return   string    Query string.
	 */
	public static function publishingPeriod($prefix = '')
	{
		$db       = JFactory::getDbo();
		$nowDate  = $date = JFactory::getDate('now', JFactory::getConfig()->get('offset'))->toSQL();
		$nullDate = $db->getNullDate();

		$date_where = " ( {$prefix}publish_up < '{$nowDate}' OR  {$prefix}publish_up = '{$nullDate}') AND " .
			" ( {$prefix}publish_down > '{$nowDate}' OR  {$prefix}publish_down = '{$nullDate}') ";

		return $date_where;
	}

	/**
	 * Get a query string to filter the publishing items now, and the published > 0.
	 * Will return: "( publish_up < 'xxxx-xx-xx' OR publish_up = '0000-00-00' )
	 *                     AND ( publish_down > 'xxxx-xx-xx' OR publish_down = '0000-00-00' )
	 *                     AND published >= '1' "
	 *
	 * @param    string $prefix        Prefix to columns name, eg: 'a.' will use `a.publish_up`.
	 * @param    string $published_col The published column name. Usually 'published' or 'state' for com_content.
	 *
	 * @return   string    Query string.
	 */
	public static function publishingItems($prefix = '', $published_col = 'published')
	{
		return self::publishingPeriod($prefix) . " AND {$prefix}{$published_col} >= '1' ";
	}

}
