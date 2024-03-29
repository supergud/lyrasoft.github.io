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
 * A URI helper to handle some common issue.
 *
 * @package     Windwalker.Framework
 * @subpackage  Helpers
 */
class AKHelperUri
{

	/**
	 * Give a relative path, return path with host.
	 *
	 * @param   string $path A system path.
	 *
	 * @return  string  Path with host added.
	 */
	public static function pathAddHost($path)
	{

		if (!$path)
		{
			return;
		}

		// build path
		$uri = new JURI($path);
		if ($uri->getHost())
		{
			return $path;
		}

		$uri->parse(JURI::root());
		$root_path = $uri->getPath();

		if (strpos($path, $root_path) === 0)
		{
			$num  = JString::strlen($root_path);
			$path = JString::substr($path, $num);
		}

		$uri->setPath($uri->getPath() . $path);
		$uri->setScheme('http');
		$uri->setQuery(null);

		return $uri->toString();
	}

	/**
	 * Give a relative path, return path with subdir.
	 * If your site install in htp://site.com/subdir/,
	 *   and the $path = 'images/image.jpg', will return 'subdir/images/image.jpg'.
	 *
	 * @param   string $path A system path.
	 *
	 * @return  string    Path with subdir added.
	 */
	public static function pathAddSubfolder($path)
	{
		$uri     = JFactory::getURI();
		$host    = $uri->getScheme() . '://' . $uri->getHost();
		$current = JURI::root();

		$subfolder = str_replace($host, '', $current);
		$subfolder = trim($subfolder, '/');

		return $subfolder . '/' . trim($path, '/');
	}

	/**
	 * A base encode & decode function, will auto convert white space to plus to avoid errors.
	 *
	 * @param   string $action 'encode' OR 'decode'
	 * @param   string $url    A url or a base64 string to convert.
	 *
	 * @return  string    URL or base64 decode string.
	 */
	public static function base64($action, $url)
	{

		switch ($action)
		{
			case 'encode' :
				$url = base64_encode($url);
				break;

			case 'decode' :
				$url = str_replace(' ', '+', $url);
				$url = base64_decode($url);
				break;
		}
		return $url;
	}

	/**
	 * A download function to hide real file path.
	 *  When call this function, will start download instantly.
	 * This function should call when view has not executed yet, if header sended,
	 *  the file which downloaded will error, because download by stream will
	 *  contain header in this file.
	 *
	 * @param   string  $path     The file system path with filename & type.
	 * @param   boolean $absolute Absolute URL or not.
	 * @param   boolean $stream   Use stream or redirect to download.
	 * @param   array   $option   Some download options.
	 */
	public static function download($path, $absolute = false, $stream = false, $option = array())
	{
		if ($stream)
		{
			if (!$absolute)
			{
				$path = JPATH_ROOT . '/' . $path;
			}

			if (!JFile::exists($path))
			{
				die();
			}

			$file = pathinfo($path);

			$filesize = filesize($path) + JArrayHelper::getValue($option, 'size_offset', 0);
			ini_set('memory_limit', JArrayHelper::getValue($option, 'memory_limit', '1540M'));

			// Set Header
			header('Content-Type: application/octet-stream');
			header('Cache-Control: no-store, no-cache, must-revalidate');
			header('Cache-Control: pre-check=0, post-check=0, max-age=0');
			header('Content-Transfer-Encoding: binary');
			header('Content-Encoding: none');
			header('Content-type: application/force-download');
			header('Content-length: ' . $filesize);
			header('Content-Disposition: attachment; filename="' . $file['basename'] . '"');

			$handle    = fopen($path, 'rb');
			$buffer    = '';
			$chunksize = 1 * (1024 * 1024);

			// Start Download File by Stream
			while (!feof($handle))
			{
				$buffer = fread($handle, $chunksize);
				echo $buffer;
				ob_flush();
				flush();
			}

			fclose($handle);

			jexit();
		}
		else
		{

			if (!$absolute)
			{
				$path = JURI::root() . $path;
			}

			// Redirect it.
			$app = JFactory::getApplication();
			$app->redirect($path);
		}
	}

	/**
	 * Current URL, same as JURI::current() but add one params.
	 *
	 * @param   boolean $hasQuery Is return URL contain query strings?
	 *
	 * @return  string  Current URL.
	 */
	public static function current($hasQuery = false)
	{
		if ($hasQuery)
		{
			return JFactory::getURI()->toString();
		}
		else
		{
			return JURI::current();
		}
	}

	/**
	 * Get component URL.
	 *
	 * @param   string  $client   Client name, 'site', 'admin', 'administrator'.
	 * @param   array   $option   Component option name.
	 * @param   boolean $absoulte To get absolute URL or not.
	 *
	 * @return  string  Component URL.
	 */
	public static function component($client = 'site', $option = null, $absoulte = false)
	{
		$root = $absoulte ? JURI::base() : '';
		if (!$option)
		{
			$option = JRequest::getVar('option');
		}

		if ($client == 'site')
		{
			return $root . 'components/' . $option;
		}
		else
		{
			return $root . 'components/' . $option;
		}
	}

	/**
	 * Get WindWalker URL.
	 *
	 * @param   boolean $absoulte To get absolute URL or not.
	 *
	 * @return  string  WindWalker URL.
	 */
	public static function windwalker($absoulte = false)
	{
		$root   = $absoulte ? JURI::base() : '';
		$option = JRequest::getVar('option');

		return $root . 'libraries/windwalker';
	}

	/**
	 * Make a URL safe.
	 * - Replace white space to '%20'.
	 *
	 * @param   string $url The URL you want to make safe.
	 *
	 * @return  string    Replaced URL.
	 */
	public static function safe($uri)
	{
		$uri = (string) $uri;
		$uri = str_replace(' ', '%20', $uri);

		return $uri;
	}
}


