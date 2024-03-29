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
 * Show some icon and function.
 *
 * @package     Windwalker.Framework
 * @subpackage  Helpers
 */
class AKHelperIcon
{

	/**
	 * Show a boolean icon.
	 *
	 * @param   mixed  $value   A variable has value or not.
	 * @param   string $task    Click to call a component task. Not available yet.
	 * @param   array  $options Some options.
	 *
	 * @return  string  A boolean icon HTML string.
	 */
	public static function boolean($value, $task = '', $options = array())
	{
		if (JVERSION >= 3)
		{
			$class = $value ? 'icon-publish' : 'icon-unpublish';

			return "<i class=\"{$class}\"></i>";
		}
		else
		{
			$img = $value ? 'tick.png' : 'publish_x.png';
			$img = 'templates/bluestork/images/admin/' . $img;

			$alt = $value ? JArrayHelper::getValue($alt, 'true_alt', 'Yes') : JArrayHelper::getValue($alt, 'false_alt', 'No');

			return JHtml::_($img, $alt);
		}
	}

}


