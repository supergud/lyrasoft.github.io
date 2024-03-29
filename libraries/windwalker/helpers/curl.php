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
 * A CURL helper to get remote pages.
 *
 * @package     Windwalker.Framework
 * @subpackage  Helpers
 */
class AKHelperCurl
{
	/**
	 * To Save error code & message when every request.
	 *
	 * @var  array
	 */
	public static $errors = array();

	/**
	 * Request a page and return it as string.
	 *
	 * @param    string $url    A url to request.
	 * @param    mixed  $method Request method, GET or POST. If is array, equal to $option.
	 * @param    string $query  Query string. eg: 'option=com_content&id=11&Itemid=125'. <br /> Only use for POST.
	 * @param    array  $option An option array to override CURL OPT.
	 *
	 * @return    mixed    If success, return string, or return false.
	 */
	public static function getPage($url = '', $method = 'get', $query = '', $option = array())
	{
		if (!$url)
		{
			return;
		}

		if ((!function_exists('curl_init') || !is_callable('curl_init')) && ini_get('allow_url_fopen'))
		{
			return file_get_contents($url);
		}

		if (is_array($method))
		{
			$option = $method;
		}

		$ch = curl_init();

		$options = array(
			CURLOPT_URL            => AKHelper::_('uri.safe', $url),
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_USERAGENT      => "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/535.1 (KHTML, like Gecko) Chrome/14.0.835.163 Safari/535.1",
			CURLOPT_FOLLOWLOCATION => !ini_get('open_basedir') ? true : false,
			CURLOPT_SSL_VERIFYPEER => false
		);

		if (strtolower($method) == 'post')
		{
			$options[CURLOPT_POST] = true;

			if (is_array($query))
			{
				$query = http_build_query($query);
			}

			$options[CURLOPT_POSTFIELDS] = $query;
		}

		// Merge option
		foreach ($option as $key => $opt)
		{
			if (isset($option[$key]))
			{
				$options[$key] = $option[$key];
			}
		}

		curl_setopt_array($ch, $options);
		$output = curl_exec($ch);

		$errno  = curl_errno($ch);
		$errmsg = curl_error($ch);

		curl_close($ch);

		if ($output)
		{
			return $output;
		}
		elseif ($errno)
		{
			self::$errors[] = $errno . ' - ' . $errmsg;

			return false;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Get a page and save it as file.
	 *
	 * @param    string $url    A url to request.
	 * @param    string $path   A system path with file name to save it.
	 * @param    array  $option An option array to override CURL OPT.
	 *
	 * @return   boolean   Success or Fail.
	 */
	public static function getFile($url = null, $path = null, $option = array())
	{
		if (!$url)
		{
			return;
		}

		jimport('joomla.filesystem.file');
		jimport('joomla.filesystem.folder');
		jimport('joomla.filesystem.path');

		$url  = JFactory::getURI($url);
		$path = JPath::clean($path);

		//$folder_path = JPATH_ROOT.DS.'files'.DS.$url->task_id ;
		if (substr($path, -1) == DIRECTORY_SEPARATOR)
		{
			$file_name   = JFile::getName($url);
			$file_path   = $path . $file_name;
			$folder_path = $path;
		}
		else
		{
			$file_path   = $path;
			$folder_path = str_replace(JFile::getName($path), '', $file_path);
		}

		JPath::setPermissions($folder_path, 644, 755);
		if (!JFolder::exists($folder_path))
		{
			JFolder::create($folder_path);
		}

		$fp = fopen($file_path, 'w+');
		$ch = curl_init();

		$options = array(
			CURLOPT_URL            => AKHelper::_('uri.safe', $url),
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_USERAGENT      => "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/535.1 (KHTML, like Gecko) Chrome/14.0.835.163 Safari/535.1",
			CURLOPT_FOLLOWLOCATION => !ini_get('open_basedir') ? true : false,
			CURLOPT_FILE           => $fp,
			CURLOPT_SSL_VERIFYPEER => false
		);

		// Merge option
		foreach ($option as $key => $opt)
		{
			if (isset($option[$key]))
			{
				$options[$key] = $option[$key];
			}
		}

		curl_setopt_array($ch, $options);
		curl_exec($ch);

		$errno  = curl_errno($ch);
		$errmsg = curl_error($ch);

		curl_close($ch);
		fclose($fp);

		if ($errno)
		{
			self::$errors[] = $errno . ' - ' . $errmsg;

			return false;
		}
		else
		{
			return true;
		}

	}

	/**
	 * Get last error message, if give first param, will return target error message.
	 *
	 * @param    integer $i Errors key.
	 *
	 * @return   string  Error message "code - message";
	 */
	public static function getError($i = null)
	{
		if ($i)
		{
			return JArrayHelper::getValue(self::$errors, $i);
		}
		else
		{
			return end(self::$errors);
		}
	}

	/**
	 * Get all error message.
	 *
	 * @return   array Error messages array.
	 */
	public static function getErrors()
	{
		return self::$errors;
	}
}