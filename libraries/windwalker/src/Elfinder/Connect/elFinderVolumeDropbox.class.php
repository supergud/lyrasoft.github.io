<?php 
/**
 * @package     Windwalker.Framework
 * @subpackage  elFinder
 *
 * @author      Simon Asika <asika32764@gmail.com>
 * @copyright   Copyright (C) 2013 Asikart. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

elFinder::$netDrivers['dropbox'] = 'Dropbox';

/**
 * Simple elFinder driver for FTP
 *
 * @package     Windwalker.Framework
 * @subpackage  elFinder
 * @author Dmitry (dio) Levashov
 * @author Cem (discofever)
 **/
class elFinderVolumeDropbox extends elFinderVolumeDriver {

	/**
	 * Driver id
	 * Must be started from letter and contains [a-z0-9]
	 * Used as part of volume id
	 *
	 * @var string
	 **/
	protected $driverId = 'd';

	/**
	 * OAuth object
	 *
	 * @var oauth
	 **/
	protected $oauth = null;

	/**
	 * Dropbox object
	 *
	 * @var dropbox
	 **/
	protected $dropbox = null;

	/**
	 * Directory for meta data caches
	 * If not set driver not cache meta data
	 *
	 * @var string
	 **/
	protected $metaCache = '';

	/**
	* Meta Data Cache file
	*
	* @var array
	**/
	protected $metaCacheFile = '';

	/**
	 * Meta Data Cache file
	 *
	 * @var array
	 **/
	protected $metaCacheArr = '';

	/**
	 * Last API error message
	 *
	 * @var string
	 **/
	protected $apiError = '';

	/**
	 * Directory for tmp files
	 * If not set driver will try to use tmbDir as tmpDir
	 *
	 * @var string
	 **/
	protected $tmp = '';
	
	/**
	 * Net mount key
	 *
	 * @var string
	 **/
	public $netMountKey = '';
	
	/**
	 * Dropbox.com uid
	 *
	 * @var string
	 **/
	protected $dropboxUid = '';
	
	/**
	* Meta Data Cache
	*
	* @var array
	**/
	protected $metaDataCache = array();

	private $dropbox_phpFound = false;
	
	/**
	 * Constructor
	 * Extend options with required fields
	 *
	 * @return void
	 * @author Dmitry (dio) Levashov
	 * @author Cem (DiscoFever)
	 **/
	public function __construct() {
		
		@ include 'Dropbox/autoload.php';
		$this->dropbox_phpFound = in_array('Dropbox_autoload', spl_autoload_functions());
		
		$opts = array(
			'consumerKey'       => '',
			'consumerSecret'    => '',
			'accessToken'       => '',
			'accessTokenSecret' => '',
			'dropboxUid'        => '',
			'root'              => 'dropbox',
			'path'              => '/',
			'treeDeep'          => 0,
			'tmbPath'           => '../files/.tmb',
			'tmbURL'            => 'files/.tmb',
			'tmpPath'           => '',
			'getTmbSize'        => 'medium', // small: 32x32, medium or s: 64x64, large or m: 128x128, l: 640x480, xl: 1024x768
			'metaCachePath'     => '',
			'metaCacheTime'     => '600', // 10m
			'acceptedName'      => '#^[^/\\?*:|"<>]*[^./\\?*:|"<>]$#',
			'icon'              => (defined('ELFINDER_IMG_PARENT_URL')? (rtrim(ELFINDER_IMG_PARENT_URL, '/').'/') : '').'img/volume_icon_dropbox.png'
		);
		$this->options = array_merge($this->options, $opts);
		$this->options['mimeDetect'] = 'internal';
	}

	/**
	 * Prepare
	 * Call from elFinder::netmout() before volume->mount()
	 *
	 * @return Array
	 * @author Naoki Sawada
	 **/
	public function netmountPrepare($options) {
		if (empty($options['consumerKey']) && defined('ELFINDER_DROPBOX_CONSUMERKEY')) $options['consumerKey'] = ELFINDER_DROPBOX_CONSUMERKEY;
		if (empty($options['consumerSecret']) && defined('ELFINDER_DROPBOX_CONSUMERSECRET')) $options['consumerSecret'] = ELFINDER_DROPBOX_CONSUMERSECRET;
		
		if ($options['user'] === 'init') {

			if (! $this->dropbox_phpFound || empty($options['consumerKey']) || empty($options['consumerSecret'])) {
				return array('exit' => true, 'body' => '{msg:errNetMountNoDriver}');
			}
			
			if (class_exists('OAuth')) {
				$this->oauth = new Dropbox_OAuth_PHP($options['consumerKey'], $options['consumerSecret']);
			} else {
				if (! class_exists('HTTP_OAuth_Consumer')) {
					// We're going to try to load in manually
					include 'HTTP/OAuth/Consumer.php';
				}
				if (class_exists('HTTP_OAuth_Consumer')) {
					$this->oauth = new Dropbox_OAuth_PEAR($options['consumerKey'], $options['consumerSecret']);
				}
			}
			
			if (! $this->oauth) {
				return array('exit' => true, 'body' => '{msg:errNetMountNoDriver}');
			}

			if ($options['pass'] === 'init') {
				$html = '';
				if (isset($_SESSION['elFinderDropboxTokens'])) {
					// token check
					try {
						list(, $accessToken, $accessTokenSecret) = $_SESSION['elFinderDropboxTokens'];
						$this->oauth->setToken($accessToken, $accessTokenSecret);
						$this->dropbox = new Dropbox_API($this->oauth, $this->options['root']);
						$this->dropbox->getAccountInfo();
						$script = '<script>
							$("#elfinder-cmd-netmout-dropbox-host").html("Dropbox.com");
							$("#elfinder-cmd-netmout-dropbox-user").val("done");
							$("#elfinder-cmd-netmout-dropbox-pass").val("done");
						</script>';
						$html = 'Dropbox.com'.$script;
					} catch (Dropbox_Exception $e) {
						unset($_SESSION['elFinderDropboxTokens']);
					}
				}
				if (! $html) {
					// get customdata
					$cdata = '';
					$innerKeys = array('cmd', 'host', 'options', 'pass', 'protocol', 'user');
					$post = (strtolower($_SERVER['REQUEST_METHOD']) === 'post')? $_POST : $_GET;
					foreach($post as $k => $v) {
						if (! in_array($k, $innerKeys)) {
							$cdata .= '&' . $k . '=' . rawurlencode($v);
						}
					}
					if (strpos($options['url'], 'http') !== 0 ) {
						$options['url'] = $this->getConnectorUrl();
					}
					$callback  = $options['url']
					           . '?cmd=netmount&protocol=dropbox&host=dropbox.com&user=init&pass=return'.$cdata;
					
					try {
						$tokens = $this->oauth->getRequestToken();
						$url= $this->oauth->getAuthorizeUrl(rawurlencode($callback));
					} catch (Dropbox_Exception $e) {
						return array('exit' => true, 'body' => '{msg:errAccess}');
					}
					
					$_SESSION['elFinderDropboxAuthTokens'] = $tokens;
					$html = '<input class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only" value="{msg:btnApprove}" type="button" onclick="window.open(\''.$url.'\')">';
				}
				return array('exit' => true, 'body' => $html);
			} else {
				$this->oauth->setToken($_SESSION['elFinderDropboxAuthTokens']);
				unset($_SESSION['elFinderDropboxAuthTokens']);
				$tokens = $this->oauth->getAccessToken();
				$_SESSION['elFinderDropboxTokens'] = array($_GET['uid'], $tokens['token'], $tokens['token_secret']);
				$script = '
					var p = window.opener;
					p.$("#elfinder-cmd-netmout-dropbox-host").html("Dropbox.com");
					p.$("#elfinder-cmd-netmout-dropbox-user").val("done");
					p.$("#elfinder-cmd-netmout-dropbox-pass").val("done");
					window.close();';
				
				$out = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><script>'.$script.'</script></head><body><a href="#" onlick="window.close();return false;">Close this window</a></body></html>';
				 
				while( ob_get_level() ) {
					if (! ob_end_clean()) {
						break;
					}
				}
				 
				header('Content-Type: text/html; charset=utf-8');
				header('Content-Length: '.strlen($out));
				header('Cache-Control: private');
				header('Pragma: no-cache');
				echo $out;
				 
				exit();
			}
		}
		if (isset($_SESSION['elFinderDropboxTokens'])) {
			list($options['dropboxUid'], $options['accessToken'], $options['accessTokenSecret']) = $_SESSION['elFinderDropboxTokens'];
		}
		unset($options['user'], $options['pass']);
		return $options;
	}
	
	/**
	 * Get script url
	 * 
	 * @return string full URL
	 * @author Naoki Sawada
	 */
	private function getConnectorUrl() {
		$url  = ((isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')? 'https://' : 'http://')
		       . $_SERVER['SERVER_NAME']                                              // host
		      . ($_SERVER['SERVER_PORT'] == 80 ? '' : ':' . $_SERVER['SERVER_PORT'])  // port
		       . $_SERVER['REQUEST_URI'];                                             // path & query
		list($url) = explode('?', $url);
		return $url;
	}
	
	/**
	 * Clear meta data cache
	 * 
	 * @param string $path 
	 * @author Naoki Sawada
	 */
	private function metaCacheClear($path) {
		$parent = $this->_dirname($path);
		unset($this->metaDataCache[$parent], $this->metaDataCache[$path]);
	}
	
	/**
	 * Get meta cache and set to $this->metaCacheArr
	 * 
	 * @param bool $refresh need refresh
	 * @author Naoki Sawada
	 */
	private function metaCacheGet($refresh = false) {
		$data = false;
		if ($data = @file_get_contents($this->metaCacheFile)) {
			if (function_exists('gzcompress') && function_exists('gzuncompress')) {
				$data = @unserialize(gzuncompress($data));
			} else {
				$data = @unserialize(base64_decode($data));
			}
			if (!$data) {
				@unlink($this->metaCacheFile);
			}
		}
		if (! $data || !isset($data['data'])) {
			$this->metaCacheArr = array();
			$this->deltaCheck();
		} else {
			$this->metaCacheArr = $data;
			if ($refresh || ($data['mtime'] + $this->options['metaCacheTime']) < $_SERVER['REQUEST_TIME']) {
				$this->deltaCheck();
			}
		}
		
	}
	
	/**
	 * Save meta data to file cache
	 * 
	 * @author Naoki Sawada
	 */
	private function mataCacheSave() {
		if (function_exists('gzcompress') && function_exists('gzuncompress')) {
			$data = gzcompress(serialize($this->metaCacheArr));
		} else {
			$data = base64_encode(serialize($this->metaCacheArr));
		}
		file_put_contents($this->metaCacheFile, $data, LOCK_EX);
	}
	
	/*********************************************************************/
	/*                        INIT AND CONFIGURE                         */
	/*********************************************************************/

	/**
	 * Prepare FTP connection
	 * Connect to remote server and check if credentials are correct, if so, store the connection id in $ftp_conn
	 *
	 * @return bool
	 * @author Dmitry (dio) Levashov
	 * @author Cem (DiscoFever)
	 **/
	protected function init() {
		if (!$this->options['consumerKey']
		||  !$this->options['consumerSecret']
		||  !$this->options['accessToken']
		||  !$this->options['accessTokenSecret']) {
			return $this->setError('Required options undefined.');
		}
		
		// make net mount key
		$this->netMountKey = md5(join('-', array('dropbox', $this->options['path'])));

		if (! $this->oauth) {
			if (class_exists('OAuth')) {
				$this->oauth = new Dropbox_OAuth_PHP($this->options['consumerKey'], $this->options['consumerSecret']);
			} else {
				if (! class_exists('HTTP_OAuth_Consumer')) {
					// We're going to try to load in manually
					include 'HTTP/OAuth/Consumer.php';
				}
				if (class_exists('HTTP_OAuth_Consumer')) {
					$this->oauth = new Dropbox_OAuth_PEAR($this->options['consumerKey'], $this->options['consumerSecret']);
				}
			}
		}
		
		if (! $this->oauth) {
			return $this->setError('OAuth extension not loaded.');
		}

		// normalize root path
		$this->root = $this->options['path'] = $this->_normpath($this->options['path']);

		if (empty($this->options['alias'])) {
			$this->options['alias'] = ($this->options['path'] === '/')? 'Dropbox.com'  : 'Dropbox'.$this->options['path'];
		}

		$this->rootName = $this->options['alias'];
		$this->options['separator'] = '/';

		try {
			$this->oauth->setToken($this->options['accessToken'], $this->options['accessTokenSecret']);
			$this->dropbox = new Dropbox_API($this->oauth, $this->options['root']);
		} catch (Dropbox_Exception $e) {
			unset($_SESSION['elFinderDropboxTokens']);
			return $this->setError('Dropbox error: '.$e->getMessage());
		}
		
		// user
		if (empty($this->options['dropboxUid'])) {
			try {
				$res = $this->dropbox->getAccountInfo();
				$this->options['dropboxUid'] = $res['uid'];
			} catch (Dropbox_Exception $e) {
				unset($_SESSION['elFinderDropboxTokens']);
				return $this->setError('Dropbox error: '.$e->getMessage());
			}
		}
		$this->dropboxUid = $this->options['dropboxUid'];

		if (!empty($this->options['tmpPath'])) {
			if ((is_dir($this->options['tmpPath']) || @mkdir($this->options['tmpPath'])) && is_writable($this->options['tmpPath'])) {
				$this->tmp = $this->options['tmpPath'];
			}
		}
		if (!$this->tmp && is_writable($this->options['tmbPath'])) {
			$this->tmp = $this->options['tmbPath'];
		}
		
		if (!empty($this->options['metaCachePath'])) {
			if ((is_dir($this->options['metaCachePath']) || @mkdir($this->options['metaCachePath'])) && is_writable($this->options['metaCachePath'])) {
				$this->metaCache = $this->options['metaCachePath'];
			}
		}
		if (!$this->metaCache && $this->tmp) {
			$this->metaCache = $this->tmp;
		}
		
		if (!$this->tmp) {
			$this->disabled[] = 'archive';
			$this->disabled[] = 'extract';
		}
		
		if (!$this->metaCache) {
			return $this->setError('Cache dirctory (metaCachePath or tmp) is require.');
		}
		
		$this->metaCacheFile = $this->metaCache.DIRECTORY_SEPARATOR.'.elFinder_dropbox_metaCache_'.md5($this->dropboxUid.$this->options['consumerSecret']);
		
		$this->metaCacheGet(!empty($_REQUEST['init']));
		
		return true;
	}


	/**
	 * Configure after successfull mount.
	 *
	 * @return void
	 * @author Dmitry (dio) Levashov
	 **/
	protected function configure() {
		parent::configure();
		
		if (!$this->tmp) {
			$this->disabled[] = 'archive';
			$this->disabled[] = 'extract';
		}
	}

	/*********************************************************************/
	/*                               FS API                              */
	/*********************************************************************/

	/**
	 * Close opened connection
	 *
	 * @return void
	 * @author Dmitry (dio) Levashov
	 **/
	public function umount() {

	}
	
	/**
	 * Get local temp filename
	 *
	 * @return string | false
	 * @author Naoki Sawada
	 **/
	protected function getLocalName($path) {
		if ($this->tmp) {
			return $this->tmp.DIRECTORY_SEPARATOR.md5($this->dropboxUid.$path);
		}
		return false;
	}
	
	protected function deltaCheck() {

		$cache = $this->metaCacheArr;
		
		$cursor = '';
		if (!$cache) {
			$cache = array('cursor' => '', 'data' => array());
			$cache['data']['/'] = array(
				'path'      => '/',
				'is_dir'    => 1,
				'mime_type' => '',
				'bytes'     => 0
			);
		} else if (isset($cache['cursor'])) {
			$cursor = $cache['cursor'];
		}
		
		try {
			$more = true;
			$info = array('cursor' => $cursor, 'entries' => array());
			do {
				$_info = $this->dropbox->delta($cursor);
				$info['entries'] += $_info['entries'];
				$cursor = $_info['cursor'];
			} while(! empty($_info['has_more']));
		} catch(Dropbox_Exception $e) {
			$info = $e->getMessage();
		}
		$info['cursor'] = $cursor;
		
		$entries = $info['entries'];
		
		$cache['cursor'] = $info['cursor'];
		foreach($entries as $entry) {
			$key = strtolower($entry[0]);
			$pkey = strtolower(dirname($key));
			if (empty($entry[1])) {
				if (isset($cache['data'][$key]) && !empty($cache['data'][$key]['is_dir'])) {
					$cache['data'][$pkey]['dirs']--;
				}
				unset($cache['data'][$pkey]['contents'][$key], $cache['data'][$key]);
				continue;
			}
			if (!isset($cache['data'][$pkey])) {
				$cache['data'][$pkey] = array();
			} else {
				unset($cache['data'][$key]['width'], $cache['data'][$key]['height']);
			}
			if (!empty($entry[1]['is_dir'])) {
				if (!isset($cache['data'][$pkey]['dirs'])) {
					$cache['data'][$pkey]['dirs'] = 1;
				} else {
					$cache['data'][$pkey]['dirs']++;
				}
			}
			$cache['data'][$pkey]['contents'][$key] = true;
			$cache['data'][$key] = $entry[1];
		}
		$cache['mtime'] = $_SERVER['REQUEST_TIME'];
		
		$this->metaCacheArr = $cache;
		$this->mataCacheSave();
	}
	
	/**
	 * Parse line from dropbox metadata output and return file stat (array)
	 *
	 * @param  string  $raw  line from ftp_rawlist() output
	 * @return array
	 * @author Dmitry Levashov
	 **/
	protected function parseRaw($raw) {
		$stat = array();

		$stat['rev']   = isset($raw['rev'])? $raw['rev'] : 'root';
		$stat['name']  = basename($raw['path']);
		$stat['mime']  = $raw['is_dir']? 'directory' : $raw['mime_type'];
		$stat['size']  = $stat['mime'] == 'directory' ? 0 : $raw['bytes'];
		$stat['ts']    = isset($raw['client_mtime'])? strtotime($raw['client_mtime']) :
		                (isset($raw['modified'])? strtotime($raw['modified']) : $_SERVER['REQUEST_TIME']);
		$stat['dirs']  = ($raw['is_dir'] && !empty($raw['dirs']))? 1 : 0;
		if (isset($raw['url'])) {
			$stat['url'] = $raw['url'];
		} else {
			$stat['url'] = '1';
		}
		if (isset($raw['width'])) $stat['width'] = $raw['width'];
		if (isset($raw['height'])) $stat['height'] = $raw['height'];
		
		return $stat;
	}

	/**
	 * Cache dir contents
	 *
	 * @param  string  $path  dir path
	 * @return void
	 * @author Dmitry Levashov
	 **/
	protected function cacheDir($path) {
		$this->dirsCache[$path] = array();
		$res = $this->metaCacheArr['data'][strtolower($path)];

		if (! empty($res['contents'])) {
			foreach(array_keys($res['contents']) as $_path) {
				$raw = $this->metaCacheArr['data'][strtolower($_path)];
				if ($stat = $this->parseRaw($raw)) {
					$stat = $this->updateCache($_path, $stat);
					if (empty($stat['hidden'])) {
						$this->dirsCache[$path][] = $raw['path'];
					}
				}
			}
		}
		return $this->dirsCache[$path];
	}

	/**
	* Clean cache
	*
	* @return void
	* @author Dmitry (dio) Levashov
	**/
	protected function clearcache() {
		parent::clearcache();
		$this->metaDataCache = array();
	}

	/**
	* Recursive files search
	*
	* @param  string  $path   dir path
	* @param  string  $q      search string
	* @param  array   $mimes
	* @return array
	* @author Naoki Sawada
	**/
	protected function doSearch($path, $q, $mimes) {
	
		$result = array();

		try {
			if ($path === '/') $path = '';
			$res = $this->dropbox->search($q, null, $path);
		} catch (Dropbox_Exception $e) {
			return $this->setError('Dropbox error: '.$e->getMessage());
		}
		
		if ($res) {
			foreach($res as $raw) {
				if ($stat = $this->parseRaw($raw)) {
					if ($stat['mime'] === 'directory' || !$this->mimeAccepted($stat['mime'], $mimes)) {
						continue;
					}
					$result[] = $this->stat($raw['path']);
				}
			}
		}
		
		return $result;
	}
	
	/**
	* Copy file/recursive copy dir only in current volume.
	* Return new file path or false.
	*
	* @param  string  $src   source path
	* @param  string  $dst   destination dir path
	* @param  string  $name  new file name (optionaly)
	* @return string|false
	* @author Dmitry (dio) Levashov
	* @author Naoki Sawada
	**/
	protected function copy($src, $dst, $name) {

		$this->clearcache();

		return $this->_copy($src, $dst, $name)
		? $this->_joinPath($dst, $name)
		: $this->setError(elFinder::ERROR_COPY, $this->_path($src));
	}
	
	/**
	* Remove file/ recursive remove dir
	*
	* @param  string  $path   file path
	* @param  bool    $force  try to remove even if file locked
	* @return bool
	* @author Dmitry (dio) Levashov
	* @author Naoki Sawada
	**/
	protected function remove($path, $force = false, $recursive = false) {
		$stat = $this->stat($path);
		$stat['realpath'] = $path;
		$this->rmTmb($stat);
		$this->clearcache();
	
		if (empty($stat)) {
			return $this->setError(elFinder::ERROR_RM, $this->_path($path), elFinder::ERROR_FILE_NOT_FOUND);
		}
	
		if (!$force && !empty($stat['locked'])) {
			return $this->setError(elFinder::ERROR_LOCKED, $this->_path($path));
		}
	
		if ($stat['mime'] == 'directory') {
			foreach ($this->_scandir($path) as $p) {
				$name = $this->_basename($p);
				if ($name != '.' && $name != '..' && !$this->remove($p, false, true)) {
					return false;
				}
			}
			if (!$recursive && !$this->_rmdir($path)) {
				return $this->setError(elFinder::ERROR_RM, $this->_path($path));
			}
				
		} else {
			if (!$recursive && !$this->_unlink($path)) {
				return $this->setError(elFinder::ERROR_RM, $this->_path($path));
			}
		}
	
		$this->removed[] = $stat;
		return true;
	}
	
	/**
	 * Resize image
	 *
	 * @param  string   $hash    image file
	 * @param  int      $width   new width
	 * @param  int      $height  new height
	 * @param  int      $x       X start poistion for crop
	 * @param  int      $y       Y start poistion for crop
	 * @param  string   $mode    action how to mainpulate image
	 * @return array|false
	 * @author Dmitry (dio) Levashov
	 * @author Alexey Sukhotin
	 * @author nao-pon
	 * @author Troex Nevelin
	 **/
	public function resize($hash, $width, $height, $x, $y, $mode = 'resize', $bg = '', $degree = 0) {
		if ($this->commandDisabled('resize')) {
			return $this->setError(elFinder::ERROR_PERM_DENIED);
		}
	
		if (($file = $this->file($hash)) == false) {
			return $this->setError(elFinder::ERROR_FILE_NOT_FOUND);
		}
	
		if (!$file['write'] || !$file['read']) {
			return $this->setError(elFinder::ERROR_PERM_DENIED);
		}
	
		$path = $this->decode($hash);
	
		if (!$this->canResize($path, $file)) {
			return $this->setError(elFinder::ERROR_UNSUPPORT_TYPE);
		}
		
		$path4stat = $path;
		if (! $path = $this->getLocalName($path)) {
			return false;
		}
		
		//if (! $contents = $this->getThumbnail($path4stat, $this->options['getTmbSize'])) {
		if (! $contents = $this->_getContents($path4stat)) {
			return false;
		}
		
		if (! @ file_put_contents($path, $contents, LOCK_EX)) {
			return false;
		}
		
		switch($mode) {
				
			case 'propresize':
				$result = $this->imgResize($path, $width, $height, true, true);
				break;
	
			case 'crop':
				$result = $this->imgCrop($path, $width, $height, $x, $y);
				break;
	
			case 'fitsquare':
				$result = $this->imgSquareFit($path, $width, $height, 'center', 'middle', ($bg ? $bg : $this->options['tmbBgColor']));
				break;
	
			case 'rotate':
				$result = $this->imgRotate($path, $degree, ($bg ? $bg : $this->options['tmbBgColor']));
				break;
	
			default:
				$result = $this->imgResize($path, $width, $height, false, true);
				break;
		}
	
		if ($result && $fp = @fopen($path, 'rb')) {
			
			clearstatcache();
			$res = $this->_save($fp, $path4stat, array());
			@fclose($fp);

			file_exists($path) && @unlink($path);
			
			$this->rmTmb($file);
			$this->clearcache();
			return $this->stat($path4stat);
		}
		
		is_file($path) && @unlink($path);
		
		return false;
	}
	
	/**
	* Create thumnbnail and return it's URL on success
	*
	* @param  string  $path  file path
	* @param  string  $mime  file mime type
	* @return string|false
	* @author Dmitry (dio) Levashov
	* @author Naoki Sawada
	**/
	protected function createTmb($path, $stat) {
		if (!$stat || !$this->canCreateTmb($path, $stat)) {
			return false;
		}
	
		$name = $this->tmbname($stat);
		$tmb  = $this->tmbPath.DIRECTORY_SEPARATOR.$name;
	
		// copy image into tmbPath so some drivers does not store files on local fs
		if (! $data = $this->getThumbnail($path, $this->options['getTmbSize'])) {
			return false;
		}
		if (! file_put_contents($tmb, $data)) {
			return false;
		}
	
		$result = false;
	
		$tmbSize = $this->tmbSize;
	
		if (($s = getimagesize($tmb)) == false) {
			return false;
		}
	
		/* If image smaller or equal thumbnail size - just fitting to thumbnail square */
		if ($s[0] <= $tmbSize && $s[1]  <= $tmbSize) {
			$result = $this->imgSquareFit($tmb, $tmbSize, $tmbSize, 'center', 'middle', $this->options['tmbBgColor'], 'png' );
	
		} else {
	
			if ($this->options['tmbCrop']) {
	
				/* Resize and crop if image bigger than thumbnail */
				if (!(($s[0] > $tmbSize && $s[1] <= $tmbSize) || ($s[0] <= $tmbSize && $s[1] > $tmbSize) ) || ($s[0] > $tmbSize && $s[1] > $tmbSize)) {
					$result = $this->imgResize($tmb, $tmbSize, $tmbSize, true, false, 'png');
				}
	
				if (($s = getimagesize($tmb)) != false) {
					$x = $s[0] > $tmbSize ? intval(($s[0] - $tmbSize)/2) : 0;
					$y = $s[1] > $tmbSize ? intval(($s[1] - $tmbSize)/2) : 0;
					$result = $this->imgCrop($tmb, $tmbSize, $tmbSize, $x, $y, 'png');
				}
	
			} else {
				$result = $this->imgResize($tmb, $tmbSize, $tmbSize, true, true, $this->imgLib, 'png');
				$result = $this->imgSquareFit($tmb, $tmbSize, $tmbSize, 'center', 'middle', $this->options['tmbBgColor'], 'png' );
			}
	
		}
		if (!$result) {
			unlink($tmb);
			return false;
		}
	
		return $name;
	}
	
	/**
	 * Return thumbnail file name for required file
	 *
	 * @param  array  $stat  file stat
	 * @return string
	 * @author Dmitry (dio) Levashov
	 **/
	protected function tmbname($stat) {
		return 'dropbox_'.$stat['rev'].'.png';
	}
	
	/**
	 * Get thumbnail from dropbox.com
	 * @param string $path
	 * @param string $size
	 * @return string | boolean
	 */
	protected function getThumbnail($path, $size = 'small') {
		try {
			return $this->dropbox->getThumbnail($path, $size);
		} catch (Dropbox_Exception $e) {
			return false;
		}
	}
	
	/**
	* Return content URL
	*
	* @param string  $hash  file hash
	* @param array $options options
	* @return array
	* @author Naoki Sawada
	**/
	public function getContentUrl($hash, $options = array()) {
		if (($file = $this->file($hash)) == false || !$file['url'] || $file['url'] == 1) {
			$path = $this->decode($hash);
			$cache =& $this->metaCacheArr['data'][strtolower($path)];
			$url = '';
			if (isset($cache['share'])) {
				$res = $this->getHttpResponseHeader($cache['share']);
				if (preg_match("/^HTTP\/[01\.]+ ([0-9]{3})/", $res, $match)) {
					if (preg_match('/^location:\s*(http[^\s]+)/im', $res, $match)) {
						$url = $match[1];
					} else if ($match[1] >= 400) {
						$url = '';
					}
				} else {
					$url = '';
				}
			}
			if (! $url) {
				try {
					$res = $this->dropbox->share($path);
					$res = $this->getHttpResponseHeader($res['url']);
					if (preg_match('/^location:\s*(http[^\s]+)/im', $res, $match)) {
						$url = $match[1] . '?dl=1';
					}
					if ($url) {
						$cache['share'] = $url;
						$this->mataCacheSave();
						$res = $this->getHttpResponseHeader($url);
						if (preg_match('/^location:\s*(http[^\s]+)/im', $res, $match)) {
							$url = $match[1];
						}
					}
				} catch (Dropbox_Exception $e) {
					return false;
				}
			}
			return $url;
		}
		return $file['url'];
	}
	
	/**
	 * Get HTTP request response header string
	 * 
	 * @param string $url target URL
	 * @return string
	 * @author Naoki Sawada
	 */
	private function getHttpResponseHeader($url) {
		if (function_exists('curl_exec')) {

			$c = curl_init();
			curl_setopt( $c, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $c, CURLOPT_CUSTOMREQUEST, 'HEAD' );
			curl_setopt( $c, CURLOPT_HEADER, 1 );
			curl_setopt( $c, CURLOPT_NOBODY, true );
			curl_setopt( $c, CURLOPT_URL, $url );
			$res = curl_exec( $c );
			
		} else {
			
			require_once 'HTTP/Request2.php';
			try {
				$request2 = new HTTP_Request2();
				$request2->setConfig(array(
                    'ssl_verify_peer' => false,
                    'ssl_verify_host' => false
                ));
				$request2->setUrl($url);
				$request2->setMethod(HTTP_Request2::METHOD_HEAD);
				$result = $request2->send();
				$res = array();
				$res[] = 'HTTP/'.$result->getVersion().' '.$result->getStatus().' '.$result->getReasonPhrase();
				foreach($result->getHeader() as $key => $val) {
					$res[] = $key . ': ' . $val;
				}
				$res = join("\r\n", $res);
			} catch( HTTP_Request2_Exception $e ){
				$res = '';
			} catch (Exception $e){
				$res = '';
			}
		
		}
		return $res;
	}
	
	/*********************** paths/urls *************************/

	/**
	 * Return parent directory path
	 *
	 * @param  string  $path  file path
	 * @return string
	 * @author Dmitry (dio) Levashov
	 **/
	protected function _dirname($path) {
		return dirname($path);
	}

	/**
	 * Return file name
	 *
	 * @param  string  $path  file path
	 * @return string
	 * @author Dmitry (dio) Levashov
	 **/
	protected function _basename($path) {
		return basename($path);
	}

	/**
	 * Join dir name and file name and retur full path
	 *
	 * @param  string  $dir
	 * @param  string  $name
	 * @return string
	 * @author Dmitry (dio) Levashov
	 **/
	protected function _joinPath($dir, $name) {
		return $this->_normpath($dir.'/'.$name);
	}

	/**
	 * Return normalized path, this works the same as os.path.normpath() in Python
	 *
	 * @param  string  $path  path
	 * @return string
	 * @author Troex Nevelin
	 **/
	protected function _normpath($path) {
		$path = str_replace(DIRECTORY_SEPARATOR, '/', $path);
		$path = '/' . ltrim($path, '/');
		return $path;
	}

	/**
	 * Return file path related to root dir
	 *
	 * @param  string  $path  file path
	 * @return string
	 * @author Dmitry (dio) Levashov
	 **/
	protected function _relpath($path) {
		return $path;
	}

	/**
	 * Convert path related to root dir into real path
	 *
	 * @param  string  $path  file path
	 * @return string
	 * @author Dmitry (dio) Levashov
	 **/
	protected function _abspath($path) {
		return $path;
	}

	/**
	 * Return fake path started from root dir
	 *
	 * @param  string  $path  file path
	 * @return string
	 * @author Dmitry (dio) Levashov
	 **/
	protected function _path($path) {
		return $path;
	}

	/**
	 * Return true if $path is children of $parent
	 *
	 * @param  string  $path    path to check
	 * @param  string  $parent  parent path
	 * @return bool
	 * @author Dmitry (dio) Levashov
	 **/
	protected function _inpath($path, $parent) {
		return $path == $parent || strpos($path, $parent.'/') === 0;
	}

	/***************** file stat ********************/
	/**
	 * Return stat for given path.
	 * Stat contains following fields:
	 * - (int)    size    file size in b. required
	 * - (int)    ts      file modification time in unix time. required
	 * - (string) mime    mimetype. required for folders, others - optionally
	 * - (bool)   read    read permissions. required
	 * - (bool)   write   write permissions. required
	 * - (bool)   locked  is object locked. optionally
	 * - (bool)   hidden  is object hidden. optionally
	 * - (string) alias   for symlinks - link target path relative to root path. optionally
	 * - (string) target  for symlinks - link target path. optionally
	 *
	 * If file does not exists - returns empty array or false.
	 *
	 * @param  string  $path    file path
	 * @return array|false
	 * @author Dmitry (dio) Levashov
	 **/
	protected function _stat($path) {
		if (isset($this->metaCacheArr['data'][strtolower($path)])) {
			return $this->parseRaw($this->metaCacheArr['data'][strtolower($path)]);
		}
		return false;
	}

	/**
	 * Return true if path is dir and has at least one childs directory
	 *
	 * @param  string  $path  dir path
	 * @return bool
	 * @author Dmitry (dio) Levashov
	 **/
	protected function _subdirs($path) {
		return ($stat = $this->stat($path)) && isset($stat['dirs']) ? $stat['dirs'] : false;
	}

	/**
	 * Return object width and height
	 * Ususaly used for images, but can be realize for video etc...
	 *
	 * @param  string  $path  file path
	 * @param  string  $mime  file mime type
	 * @return string
	 * @author Dmitry (dio) Levashov
	 **/
	protected function _dimensions($path, $mime) {
		if (strpos($mime, 'image') !== 0) return '';
		$cache =& $this->metaCacheArr['data'][strtolower($path)];
		if (isset($cache['width']) && isset($cache['height'])) {
			return $cache['width'].'x'.$cache['height'];
		}
		if ($local = $this->getLocalName($path)) {
			if (file_put_contents($local, $this->dropbox->getFile($path), LOCK_EX)) {
				if ($size = @getimagesize($local)) {
					$cache =& $this->metaCacheArr['data'][strtolower($path)];
					$cache['width'] = $size[0];
					$cache['height'] = $size[1];
					$this->mataCacheSave();
					unlink($local);
					return $size[0].'x'.$size[1];
				}
				unlink($local);
			}
		}
		return '';
	}

	/******************** file/dir content *********************/

	/**
	 * Return files list in directory.
	 *
	 * @param  string  $path  dir path
	 * @return array
	 * @author Dmitry (dio) Levashov
	 * @author Cem (DiscoFever)
	 **/
	protected function _scandir($path) {
		return isset($this->dirsCache[$path])
			? $this->dirsCache[$path]
			: $this->cacheDir($path);
	}

	/**
	 * Open file and return file pointer
	 *
	 * @param  string  $path  file path
	 * @param  bool    $write open file for writing
	 * @return resource|false
	 * @author Dmitry (dio) Levashov
	 **/
	protected function _fopen($path, $mode='rb') {

		if ($this->tmp) {
			$contents = $this->_getContents($path);
			
			if ($contents === false) {
				return false;
			}
			
			if ($local = $this->getLocalName($path)) {
				if (file_put_contents($local, $contents, LOCK_EX) !== false) {
					return @fopen($local, $mode);
				}
			}
		}

		return false;
	}

	/**
	 * Close opened file
	 *
	 * @param  resource  $fp  file pointer
	 * @return bool
	 * @author Dmitry (dio) Levashov
	 **/
	protected function _fclose($fp, $path='') {
		@fclose($fp);
		if ($path) {
			@unlink($this->getLocalName($path));
		}
	}

	/********************  file/dir manipulations *************************/

	/**
	 * Create dir and return created dir path or false on failed
	 *
	 * @param  string  $path  parent dir path
	 * @param string  $name  new directory name
	 * @return string|bool
	 * @author Dmitry (dio) Levashov
	 **/
	protected function _mkdir($path, $name) {
		$path = $this->_normpath($path.'/'.$name);
		try {
			$this->dropbox->createFolder($path);
		} catch (Dropbox_Exception $e) {
			return $this->setError('Dropbox error: '.$e->getMessage());
		}
		$this->metaCacheClear($path);
		$this->deltaCheck();
		return $path;
	}

	/**
	 * Create file and return it's path or false on failed
	 *
	 * @param  string  $path  parent dir path
	 * @param string  $name  new file name
	 * @return string|bool
	 * @author Dmitry (dio) Levashov
	 **/
	protected function _mkfile($path, $name) {
		return $this->_filePutContents($path.'/'.$name, '');
	}

	/**
	 * Create symlink. FTP driver does not support symlinks.
	 *
	 * @param  string  $target  link target
	 * @param  string  $path    symlink path
	 * @return bool
	 * @author Dmitry (dio) Levashov
	 **/
	protected function _symlink($target, $path, $name) {
		return false;
	}

	/**
	 * Copy file into another file
	 *
	 * @param  string  $source     source file path
	 * @param  string  $targetDir  target directory path
	 * @param  string  $name       new file name
	 * @return bool
	 * @author Dmitry (dio) Levashov
	 **/
	protected function _copy($source, $targetDir, $name) {
		$path = $this->_normpath($targetDir.'/'.$name);
		try {
			$this->dropbox->copy($source, $path);
		} catch (Dropbox_Exception $e) {
			return $this->setError('Dropbox error: '.$e->getMessage());
		}
		$this->metaCacheClear($path);
		$this->deltaCheck();
		return true;
	}

	/**
	 * Move file into another parent dir.
	 * Return new file path or false.
	 *
	 * @param  string  $source  source file path
	 * @param  string  $target  target dir path
	 * @param  string  $name    file name
	 * @return string|bool
	 * @author Dmitry (dio) Levashov
	 **/
	protected function _move($source, $targetDir, $name) {
		$target = $this->_normpath($targetDir.'/'.$name);
		try {
			$this->dropbox->move($source, $target);
		} catch (Dropbox_Exception $e) {
			return $this->setError('Dropbox error: '.$e->getMessage());
		}
		unset($this->metaDataCache[$source], $this->metaDataCache[$target]);
		$this->metaCacheClear($source);
		$this->metaCacheClear($target);
		$this->deltaCheck();
		return $target;
	}

	/**
	 * Remove file
	 *
	 * @param  string  $path  file path
	 * @return bool
	 * @author Dmitry (dio) Levashov
	 **/
	protected function _unlink($path) {
		try {
			$this->dropbox->delete($path);
		} catch (Dropbox_Exception $e) {
			return $this->setError('Dropbox error: '.$e->getMessage());
		}
		$this->metaCacheClear($path);
		$this->deltaCheck();
		return true;
	}

	/**
	 * Remove dir
	 *
	 * @param  string  $path  dir path
	 * @return bool
	 * @author Dmitry (dio) Levashov
	 **/
	protected function _rmdir($path) {
		return $this->_unlink($path);
	}

	/**
	 * Create new file and write into it from file pointer.
	 * Return new file path or false on error.
	 *
	 * @param  resource  $fp   file pointer
	 * @param  string    $dir  target dir path
	 * @param  string    $name file name
	 * @param  array     $stat file stat (required by some virtual fs)
	 * @return bool|string
	 * @author Dmitry (dio) Levashov
	 **/
	protected function _save($fp, $path, $name, $stat) {
		if ($name) $path .= '/'.$name;
		$path = $this->_normpath($path);
		try {
			$this->dropbox->putFile($path, $fp);
		} catch (Dropbox_Exception $e) {
			return $this->setError('Dropbox error: '.$e->getMessage());
		}
		$this->metaCacheClear($path);
		$this->deltaCheck();
		return $path;
	}

	/**
	 * Get file contents
	 *
	 * @param  string  $path  file path
	 * @return string|false
	 * @author Dmitry (dio) Levashov
	 **/
	protected function _getContents($path) {
		$contents = '';
		try {
			$contents = $this->dropbox->getFile($path);
		} catch (Dropbox_Exception $e) {
			return $this->setError('Dropbox error: '.$e->getMessage());
		}
		return $contents;
	}

	/**
	 * Write a string to a file
	 *
	 * @param  string  $path     file path
	 * @param  string  $content  new file content
	 * @return bool
	 * @author Dmitry (dio) Levashov
	 **/
	protected function _filePutContents($path, $content) {
		$res = false;

		if ($local = $this->getLocalName($path)) {
			$local .= '.txt';

			if (@file_put_contents($local, $content, LOCK_EX) !== false
			&& ($fp = @fopen($local, 'rb'))) {
				clearstatcache();
				$res = $this->_save($fp, $path, array());
				@fclose($fp);
			}
			file_exists($local) && @unlink($local);
		}

		return $res;
	}

	/**
	 * Detect available archivers
	 *
	 * @return void
	 **/
	protected function _checkArchivers() {
		// die('Not yet implemented. (_checkArchivers)');
		return array();
	}

	/**
	 * Unpack archive
	 *
	 * @param  string  $path  archive path
	 * @param  array   $arc   archiver command and arguments (same as in $this->archivers)
	 * @return true
	 * @return void
	 * @author Dmitry (dio) Levashov
	 * @author Alexey Sukhotin
	 **/
	protected function _unpack($path, $arc) {
		die('Not yet implemented. (_unpack)');
		return false;
	}

	/**
	 * Recursive symlinks search
	 *
	 * @param  string  $path  file/dir path
	 * @return bool
	 * @author Dmitry (dio) Levashov
	 **/
	protected function _findSymlinks($path) {
		die('Not yet implemented. (_findSymlinks)');
		if (is_link($path)) {
			return true;
		}
		if (is_dir($path)) {
			foreach (scandir($path) as $name) {
				if ($name != '.' && $name != '..') {
					$p = $path.DIRECTORY_SEPARATOR.$name;
					if (is_link($p)) {
						return true;
					}
					if (is_dir($p) && $this->_findSymlinks($p)) {
						return true;
					} elseif (is_file($p)) {
						$this->archiveSize += filesize($p);
					}
				}
			}
		} else {
			$this->archiveSize += filesize($path);
		}

		return false;
	}

	/**
	 * Extract files from archive
	 *
	 * @param  string  $path  archive path
	 * @param  array   $arc   archiver command and arguments (same as in $this->archivers)
	 * @return true
	 * @author Dmitry (dio) Levashov,
	 * @author Alexey Sukhotin
	 **/
	protected function _extract($path, $arc) {
		die('Not yet implemented. (_extract)');

	}

	/**
	 * Create archive and return its path
	 *
	 * @param  string  $dir    target dir
	 * @param  array   $files  files names list
	 * @param  string  $name   archive name
	 * @param  array   $arc    archiver options
	 * @return string|bool
	 * @author Dmitry (dio) Levashov,
	 * @author Alexey Sukhotin
	 **/
	protected function _archive($dir, $files, $name, $arc) {
		die('Not yet implemented. (_archive)');
		return false;
	}

} // END class
