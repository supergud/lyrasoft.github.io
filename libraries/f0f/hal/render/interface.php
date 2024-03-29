<?php 
/**
 * @package     FrameworkOnFramework
 * @subpackage  hal
 * @copyright   Copyright (C) 2010 - 2015 Nicholas K. Dionysopoulos / Akeeba Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('F0F_INCLUDED') or die;

/**
 * Interface for HAL document renderers
 *
 * @package  FrameworkOnFramework
 * @since    2.1
 */
interface F0FHalRenderInterface
{
	/**
	 * Render a HAL document into a representation suitable for consumption.
	 *
	 * @param   array  $options  Renderer-specific options
	 *
	 * @return  void
	 */
	public function render($options = array());
}
