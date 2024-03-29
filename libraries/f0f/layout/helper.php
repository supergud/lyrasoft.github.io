<?php 
/**
 * @package     FrameworkOnFramework
 * @subpackage  layout
 * @copyright   Copyright (C) 2010 - 2015 Nicholas K. Dionysopoulos / Akeeba Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
// Protect from unauthorized access
defined('F0F_INCLUDED') or die;

/**
 * Helper to render a F0FLayout object, storing a base path
 *
 * @package  FrameworkOnFramework
 * @since    x.y
 */
class F0FLayoutHelper extends JLayoutHelper
{
	/**
	 * Method to render the layout.
	 *
	 * @param   string  $layoutFile   Dot separated path to the layout file, relative to base path
	 * @param   object  $displayData  Object which properties are used inside the layout file to build displayed output
	 * @param   string  $basePath     Base path to use when loading layout files
	 *
	 * @return  string
	 */
	public static function render($layoutFile, $displayData = null, $basePath = '')
	{
		$basePath = empty($basePath) ? self::$defaultBasePath : $basePath;

		// Make sure we send null to F0FLayoutFile if no path set
		$basePath = empty($basePath) ? null : $basePath;
		$layout = new F0FLayoutFile($layoutFile, $basePath);
		$renderedLayout = $layout->render($displayData);

		return $renderedLayout;
	}
}
