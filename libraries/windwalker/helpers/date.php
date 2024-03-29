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
 * Date handler functions.
 *
 * @package     Windwalker.Framework
 * @subpackage  Helpers
 */
class AKHelperDate
{

	/**
	 * Set a date variable to format string.
	 *
	 * @param    mixed   $date   The initial time for the JDate object.
	 * @param    string  $format The date format specification string (see {@link PHP_MANUAL#strftime})
	 * @param    boolean $locale True to return the date string in the local time zone, false to return it in GMT.
	 *
	 * @return   type returnDesc
	 */
	public static function format($date = 'now', $format = '%Y-%m-%d %H:%M:%S', $locale = true)
	{
		return JFactory::getDate($date, JFactory::getConfig()->get('offset'))->format($format, $locale);
	}

	/**
	 * Get a new time offset from a origin time.
	 * Example: AKHelperDate::offset( '2012-01-01', '5', 'y' ); will get a 5 year offset to: '2017-01-01' .
	 *
	 * @param    mixed   $date         Time which type is string or JDate object.
	 * @param    integer $offset       The offset number.
	 * @param    string  $unit         Offset unit:
	 *                                 <br /> - 's', 'second'
	 *                                 <br /> - 'm', 'minute'
	 *                                 <br /> - 'h', 'hr', 'hour'
	 *                                 <br /> - 'd', 'day'
	 *                                 <br /> - 'w', 'week'
	 *                                 <br /> - 'mon', 'month'
	 *                                 <br /> - 'y', 'year'
	 *
	 * @return   DateTime    A DateTime object.
	 */
	public static function offset($origin = 'now', $offset = 0, $unit = 'd')
	{
		if ($origin instanceof JDate)
		{
			$origin = $origin;
		}
		else
		{
			$origin = JFactory::getDate($origin, JFactory::getConfig()->get('offset'));
		}

		switch ($unit)
		{
			case 's':
			case 'second':
			default:
				$offset = $offset;
				break;

			case 'm':
			case 'minute':
				$offset = $offset * 60;
				break;

			case 'h':
			case 'hr':
			case 'hour':
				$offset = $offset * 60 * 60;
				break;

			case 'd':
			case 'day':
				$offset = $offset * 60 * 60 * 24;
				break;

			case 'w':
			case 'week':
				$offset = $offset * 60 * 60 * 24 * 7;
				break;

			case 'mon':
			case 'month':
				$offset = $offset * 60 * 60 * 24 * 30;
				break;

			case 'y':
			case 'year':
				$offset = $offset * 60 * 60 * 24 * 365;
				break;
		}

		$origin->add(new DateInterval('PT' . $offset . 'S'));

		return $origin;
	}

	/**
	 * Get the gap period between two date.
	 * Example:
	 * TimeGap('year','now','2012-11-10');  Result = 2  ;
	 * TimeGap('month','2005-10-20');       Result = 49 ;
	 *
	 * @param   unit   $type  Return type: 'year' or 'month' or 'day' or 'second'
	 * @param   string $start Start time. Format: '2009-11-10' or 'now'.
	 * @param   string $end   End time, Format: '2009-11-10' or 'now', null.
	 *
	 * @return    string    The gap time.
	 */
	public static function timeGap($type, $start, $end = null)
	{
		// Start time convert to UNIX timestamp.
		if ($start == 'now')
		{
			$startSecond = mktime();
		}
		else
		{
			$start       = explode('-', $start);
			$startSecond = mktime(0, 0, 0, $start[1], $start[2], $start[0]);
		}

		// End time convert to UNIX timestamp.
		if ($end == 'now' || $end == null)
		{
			$endSecond = mktime();
		}
		else
		{
			$end       = explode('-', $end);
			$endSecond = mktime(0, 0, 0, $end[1], $end[2], $end[0]);
		}

		// Get the gap time in seconds.
		$Gap = $endSecond - $startSecond;

		// Convert to type.
		switch ($type)
		{
			case 'second' :
				$Gap = intval($Gap);
				break;

			case 'day' :
				$Gap = intval($Gap / 86400);
				break;

			case 'month' :
				$Gap = intval(($Gap / 86400) / 30);
				break;

			case 'year' :
				$Gap = intval(($Gap / 86400) / 365);
				break;

			case 'yearfloat' :
				$t = intval(($Gap / 86400) / 365);
				$d = $Gap - ($t * 365 * 86400);
				$d = round(($d / 86400));

				if ($d > 182)
				{
					$Gap = $t + 0.5;
				}
				else
				{
					$Gap = $t;
				}
				break;

		}

		return $Gap;

	}
}
