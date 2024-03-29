<?php 
/**
 *  @package     FrameworkOnFramework
 *  @subpackage  autoloader
 * @copyright   Copyright (C) 2010 - 2015 Nicholas K. Dionysopoulos / Akeeba Ltd. All rights reserved.
 *  @license     GNU General Public License version 2, or later
 */

defined('F0F_INCLUDED') or die();

/**
 * The main class autoloader for F0F itself
 *
 * @package     FrameworkOnFramework
 * @subpackage  autoloader
 * @since       2.1
 */
class F0FAutoloaderFof
{
	/**
	 * An instance of this autoloader
	 *
	 * @var   F0FAutoloaderFof
	 */
	public static $autoloader = null;

	/**
	 * The path to the F0F root directory
	 *
	 * @var   string
	 */
	public static $fofPath = null;

	/**
	 * Initialise this autoloader
	 *
	 * @return  F0FAutoloaderFof
	 */
	public static function init()
	{
		if (self::$autoloader == null)
		{
			self::$autoloader = new self;
		}

		return self::$autoloader;
	}

	/**
	 * Public constructor. Registers the autoloader with PHP.
	 */
	public function __construct()
	{
		self::$fofPath = realpath(__DIR__ . '/../');

		spl_autoload_register(array($this,'autoload_fof_core'));
	}

	/**
	 * The actual autoloader
	 *
	 * @param   string  $class_name  The name of the class to load
	 *
	 * @return  void
	 */
	public function autoload_fof_core($class_name)
	{
		// Make sure the class has a F0F prefix
		if (substr($class_name, 0, 3) != 'F0F')
		{
			return;
		}

		// Remove the prefix
		$class = substr($class_name, 3);

		// Change from camel cased (e.g. ViewHtml) into a lowercase array (e.g. 'view','html')
		$class = preg_replace('/(\s)+/', '_', $class);
		$class = strtolower(preg_replace('/(?<=\\w)([A-Z])/', '_\\1', $class));
		$class = explode('_', $class);

		// First try finding in structured directory format (preferred)
		$path = self::$fofPath . '/' . implode('/', $class) . '.php';

		if (@file_exists($path))
		{
			include_once $path;
		}

		// Then try the duplicate last name structured directory format (not recommended)

		if (!class_exists($class_name, false))
		{
			reset($class);
			$lastPart = end($class);
			$path = self::$fofPath . '/' . implode('/', $class) . '/' . $lastPart . '.php';

			if (@file_exists($path))
			{
				include_once $path;
			}
		}

		// If it still fails, try looking in the legacy folder (used for backwards compatibility)

		if (!class_exists($class_name, false))
		{
			$path = self::$fofPath . '/legacy/' . implode('/', $class) . '.php';

			if (@file_exists($path))
			{
				include_once $path;
			}
		}
	}
}
