<?php 
/**
 * @package     Windwalker.Framework
 * @subpackage  Base
 * @author      Simon Asika <asika32764@gmail.com>
 * @copyright   Copyright (C) 2013 Asikart. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

/**
 * A Plugin Base class to provide some useful methods.
 *
 * @package     Windwalker.Framework
 * @subpackage  Base
 *
 * @since       2.5
 */
class AKPlugin extends JPlugin
{
	/**
	 * A proxy for call class and functions
	 * Example: $this->call('folder1.folder2.function', $args) ;
	 * <br /> OR $this->call('folder1.folder2.Class::function', $args)
	 *
	 * @param   string  $uri  The class or function file path.
	 *
	 * @return  mixed
	 */
	public function call($uri)
	{
		// Split paths
		$path = explode('.', $uri);
		$func = array_pop($path);
		$func = explode('::', $func);

		// Set class name of function name.
		if (isset($func[1]))
		{
			$class_name = $func[0];
			$func_name  = $func[1];
			$file_name  = $class_name;
		}
		else
		{
			$class_name = null;
			$func_name  = $func[0];
			$file_name  = $func_name;
		}

		$func_path    = implode('/', $path) . '/' . $file_name;
		$include_path = dirname(__FILE__) . '/lib';

		// Include file.
		if (!function_exists($func_name) && !class_exists($class_name))
		{
			$file = AKHelper::_('path.getAdmin') . '/class/' . $func_path . '.php';

			if (!file_exists($file))
			{
				$file = dirname(__FILE__) . '/lib/' . $func_path . '.php';
			}

			if (file_exists($file))
			{
				include_once $file;
			}
		}

		// Handle args
		$args = func_get_args();

		array_shift($args);

		// Call Function
		if (isset($class_name) && is_callable(array($class_name, $func_name)))
		{
			return call_user_func_array(array($class_name, $func_name), $args);
		}
		elseif (function_exists($func_name))
		{
			return call_user_func_array($func_name, $args);
		}

	}

	/**
	 * Return a file path which name is same as this function.
	 *
	 * @param   string  $func  Function name.
	 *
	 * @return  string  File path.
	 */
	public function includeEvent($func)
	{
		$include_path = JPATH_ROOT . '/' . $this->params->get('include_path', 'easyset');
		$event        = trim($include_path, '/') . '/' . 'events' . DS . $func . '.php';

		if (file_exists($event))
		{
			return $event;
		}
	}

	/**
	 * If an array contain false, return false.
	 * If all elements are true, return true.
	 *
	 * @param   array  $result  An array contain event results.
	 *
	 * @return  boolean True if all true.
	 */
	public function resultBool($result = array())
	{
		foreach ($result as $result)
		{
			if (!$result)
			{
				return false;
			}
		}

		return true;
	}
}
