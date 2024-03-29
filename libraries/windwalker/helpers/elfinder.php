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
 * elFinder Connector & Displayer.
 *
 * @package     Windwalker.Framework
 * @subpackage  Helpers
 */
class AKHelperElfinder
{
	/**
	 * display
	 */
	public static function display($com_option = null, $option = array())
	{
		// Init some API objects
		// ================================================================================
		$date      = JFactory::getDate('now', JFactory::getConfig()->get('offset'));
		$uri       = JFactory::getURI();
		$user      = JFactory::getUser();
		$app       = JFactory::getApplication();
		$doc       = JFactory::getDocument();
		$lang      = JFactory::getLanguage();
		$lang_code = $lang->getTag();
		$lang_code = str_replace('-', '_', $lang_code);

		// Script
		self::_displayScript($com_option, $option);

		// Base Toolbar
		$toolbar_base = array(
			array('back', 'forward'),
			array('reload'),
			//array('home', 'up'),
			array('mkdir', 'mkfile', 'upload'),
			//array('open', 'download', 'getfile'),
			array('info'),
			array('quicklook'),
			array('copy', 'cut', 'paste'),
			array('rm'),
			array('duplicate', 'rename', 'edit', 'resize'),
			//array('extract', 'archive'),
			array('search'),
			array('view'),
			array('help')
		);

		// Get Request
		$com_option = $com_option ? $com_option : JRequest::getVar('option');
		$finder_id  = JRequest::getVar('finder_id');
		$modal      = (JRequest::getVar('tmpl') == 'component') ? true : false;
		$root       = JArrayHelper::getValue($option, 'root', JRequest::getVar('root', '/'));
		$start_path = JArrayHelper::getValue($option, 'start_path', JRequest::getVar('start_path', '/'));
		$site_root  = JURI::root(true) . '/';

		$toolbar = JArrayHelper::getValue($option, 'toolbar', $toolbar_base);
		$toolbar = $toolbar ? json_encode($toolbar) : json_encode($toolbar_base);

		$onlymimes = JArrayHelper::getValue($option, 'onlymimes', JRequest::getVar('onlymimes', null));
		$onlymimes = is_array($onlymimes) ? implode(',', $onlymimes) : $onlymimes;
		$onlymimes = $onlymimes ? "'" . str_replace(",", "','", $onlymimes) . "'" : '';

		// Get INI setting
		$upload_max = ini_get('upload_max_filesize');
		$upload_num = ini_get('max_file_uploads');

		$upload_limit = 'Max upload size: ' . $upload_max;
		$upload_limit .= ' | Max upload files: ' . $upload_num;

		// Set Script
		$getFileCallback = !$modal ? '' : "
            ,
            getFileCallback : function(file){
                if (window.parent) window.parent.AKFinderSelect( '{$finder_id}',AKFinderSelected, window.elFinder, '{$site_root}');
            }";

		$script = <<<SCRIPT
		var AKFinderSelected ;
        var elFinder ;
		
		// Init elFinder
        jQuery(document).ready(function($) {
            elFinder = $('#elfinder').elfinder({
                url         : 'index.php?option={$com_option}&task=elFinderConnector&root={$root}&start_path={$start_path}' ,
                width       : '100%' ,
                height      : 445 ,
                onlyMimes   : [$onlymimes],
                lang        : '{$lang_code}',
                uiOptions   : {
                    toolbar : {$toolbar}
                },
                handlers    : {
                    select : function(event, elfinderInstance) {
                        var selected = event.data.selected;

                        if (selected.length) {
                            AKFinderSelected = [];
                            jQuery.each(selected, function(i, e){
                                    AKFinderSelected[i] = elfinderInstance.file(e);
                            });
                        }

                    }
                }
                
                {$getFileCallback}
                
            }).elfinder('instance');
            
            elFinder.ui.statusbar.append( '<div class="akfinder-upload-limit">{$upload_limit}</div>' );
        });
SCRIPT;

		$doc->addScriptDeclaration($script);

		echo '<div class="row-fluid">
                <div id="elfinder" class="span12 ak-finder"></div>
            </div>';
	}

	/**
	 * Set Script for Finder Display
	 */
	public static function _displayScript($com_option, $option = array())
	{
		$doc       = JFactory::getDocument();
		$lang      = JFactory::getLanguage();
		$lang_code = $lang->getTag();
		$lang_code = str_replace('-', '_', $lang_code);

		// Include elFinder and JS
		// ================================================================================
		JHtml::_('behavior.framework', true);

		if (JVERSION >= 3)
		{

			// jQuery
			JHtml::_('jquery.framework', true);
			JHtml::_('bootstrap.framework', true);

		}
		else
		{
			$doc->addStyleSheet(JURI::base() . '/components/' . $com_option . '/includes/bootstrap/css/bootstrap.min.css');

			// jQuery
			AKHelper::_('include.addJS', 'jquery/jquery.js', 'ww');
			$doc->addScriptDeclaration('jQuery.noConflict();');
		}

		$assets_url = AKHelper::_('path.getWWUrl') . '/assets';

		// elFinder includes
		$doc->addStylesheet($assets_url . '/js/jquery-ui/css/smoothness/jquery-ui-1.8.24.custom.css');
		$doc->addStylesheet($assets_url . '/js/elfinder/css/elfinder.min.css');
		$doc->addStylesheet($assets_url . '/js/elfinder/css/theme.css');

		$doc->addscript($assets_url . '/js/jquery-ui/js/jquery-ui.min.js');
		$doc->addscript($assets_url . '/js/elfinder/js/elfinder.min.js');
		JHtml::script($assets_url . '/js/elfinder/js/i18n/elfinder.' . $lang_code . '.js');
		AKHelper::_('include.core');
	}

	/**
	 * connector
	 */
	public static function connector($com_option = null, $option = array())
	{
		error_reporting(JArrayHelper::getValue($option, 'error_reporting', 0)); // Set E_ALL for debuging

		$elfinder_path = AKPATH_ASSETS . '/js/elfinder/php/';

		include_once $elfinder_path . 'elFinderConnector.class.php';
		include_once $elfinder_path . 'elFinder.class.php';
		include_once $elfinder_path . 'elFinderVolumeDriver.class.php';

		/**
		 * Simple function to demonstrate how to control file access using "accessControl" callback.
		 * This method will disable accessing files/folders starting from '.' (dot)
		 *
		 * @param  string $attr attribute name (read|write|locked|hidden)
		 * @param  string $path file path relative to volume root directory started with directory separator
		 *
		 * @return bool|null
		 **/
		function access($attr, $path, $data, $volume)
		{
			return strpos(basename($path), '.') === 0 // if file/folder begins with '.' (dot)
				? !($attr == 'read' || $attr == 'write') // set read+write to false, other (locked+hidden) set to true
				: null; // else elFinder decide it itself
		}

		// Get Some Request
		$com_option = $com_option ? $com_option : JRequest::getVar('option');
		$root       = JRequest::getVar('root', '/');
		$start_path = JRequest::getVar('start_path', '/');

		$opts = array(
			// 'debug' => true,
			'roots' => array(
				array(
					'driver'        => 'LocalFileSystem', // driver for accessing file system (REQUIRED)
					'path'          => JPath::clean(JPATH_ROOT . '/' . $root, '/'), // path to files (REQUIRED)
					'startPath'     => JPath::clean(JPATH_ROOT . '/' . $root . '/' . $start_path . '/'),
					'URL'           => JPath::clean(JURI::root(true) . '/' . $root . '/' . $start_path, '/'), // URL to files (REQUIRED)
					'tmbPath'       => JPath::clean(JPATH_CACHE . '/AKFinderThumb'),
					'tmbURL'        => JURI::root() . 'cache/AKFinderThumb',
					//'tmbSize'       => 128,
					'tmp'           => JPath::clean(JPATH_CACHE . '/AKFinderTemp'),
					'accessControl' => 'access', // disable and hide dot starting files (OPTIONAL)
					'uploadDeny'    => array('text/x-php'),
					//'uploadAllow'   => array('image'),
					'disabled'      => array('archive', 'extract', 'rename', 'mkfile')
				)
			)
		);

		$opts = array_merge($opts, $option);

		foreach ($opts['roots'] as $driver)
		{
			include_once $elfinder_path . 'elFinderVolume' . $driver['driver'] . '.class.php';
		}

		// run elFinder
		$connector = new elFinderConnector(new elFinder($opts));
		$connector->run();
	}
}