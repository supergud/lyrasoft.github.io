<?php 
/**
 * @package    FrameworkOnFramework
 * @subpackage form
 * @copyright   Copyright (C) 2010 - 2015 Nicholas K. Dionysopoulos / Akeeba Ltd. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// Protect from unauthorized access
defined('F0F_INCLUDED') or die;

/**
 * Row selection checkbox
 *
 * @package  FrameworkOnFramework
 * @since    2.0
 */
class F0FFormHeaderRowselect extends F0FFormHeader
{
	/**
	 * Get the header
	 *
	 * @return  string  The header HTML
	 */
	protected function getHeader()
	{
		return '<input type="checkbox" name="checkall-toggle" value="" title="'
			. JText::_('JGLOBAL_CHECK_ALL')
			. '" onclick="Joomla.checkAll(this)" />';
	}
}
