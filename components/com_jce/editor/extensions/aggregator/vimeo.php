<?php 

/**
 * @package   	JCE
 * @copyright 	Copyright (c) 2009-2015 Ryan Demmer. All rights reserved.
 * @license   	GNU/GPL 2 or later - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * JCE is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

defined('_JEXEC') or die('RESTRICTED');

class WFAggregatorExtension_Vimeo extends WFAggregatorExtension 
{
	/**
	* Constructor activating the default information of the class
	*
	* @access	protected
	*/
	function __construct()
	{
		parent::__construct(array(
			'format' => 'video'	
		));
	}	
		
	function display()
	{
		$document = WFDocument::getInstance();
		$document->addScript('vimeo', 'extensions/aggregator/vimeo/js');
	}
	
	function isEnabled()
	{
		$plugin = WFEditorPlugin::getInstance();
		return $plugin->checkAccess('aggregator.vimeo.enable', 1);
	}
	
	function getParams()
	{
		$plugin = WFEditorPlugin::getInstance();
	
		return array(
			'width'		=>	$plugin->getParam('aggregator.vimeo.width', 400),
			'height'	=>	$plugin->getParam('aggregator.vimeo.height', 225)
		);
	}
}