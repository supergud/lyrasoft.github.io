<?php 
/**
 * @package    Windwalker.Framework
 * @author     Simon Asika <asika32764@gmail.com>
 * @copyright  Copyright (C) 2013 Asikart. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

define('AKPATH_ROOT',       dirname(__FILE__));
define('AKPATH_ADMIN',      AKPATH_ROOT . '/admin');
define('AKPATH_BASE',       AKPATH_ROOT . '/base');
define('AKPATH_HELPERS',    AKPATH_ROOT . '/helpers');
define('AKPATH_COMPONENT',  AKPATH_ROOT . '/component');
define('AKPATH_FORM',       AKPATH_ROOT . '/form');
define('AKPATH_HTML',       AKPATH_ROOT . '/html');
define('AKPATH_LANGUAGE',   AKPATH_ROOT . '/language');
define('AKPATH_LAYOUTS',    AKPATH_ROOT . '/layouts');

define('AKPATH_ASSETS',     AKPATH_ROOT . '/assets');
define('AKPATH_TABLES',     AKPATH_COMPONENT . '/tables');

if (!defined('DS'))
{
	define('DS', DIRECTORY_SEPARATOR);
}

// Include joomla api
// ========================================================================
jimport('joomla.html.toolbar');
jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');

include_once AKPATH_BASE . "/proxy.php";
include_once AKPATH_HELPERS . "/akhelper.php";

// Set Default helper prefix for AKProxy
AKHelper::setPrefix('AKHelper');
AKHelper::addIncludePath(AKPATH_HELPERS);

function akLoader($uri, $option = null)
{
	return AKHelper::_('loader.import', $uri, $option);
}

include_once AKPATH_ADMIN . "/toolbar.php";
include_once AKPATH_BASE . "/text.php";

// Load Language
$lang = JFactory::getLanguage();
$lang->load('lib_windwalker', JPATH_BASE, null, false, false)
|| $lang->load('lib_windwalker', AKPATH_ROOT, null, false, false)
|| $lang->load('lib_windwalker', JPATH_BASE, $lang->getDefault(), false, false)
|| $lang->load('lib_windwalker', AKPATH_ROOT, $lang->getDefault(), false, false);

