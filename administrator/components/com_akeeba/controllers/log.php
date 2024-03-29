<?php 
/**
 * @package   AkeebaBackup
 * @copyright Copyright (c)2009-2014 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 *
 * @since     1.3
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

use Akeeba\Engine\Factory;
use Akeeba\Engine\Platform;

/**
 * Log view controller class
 *
 */
class AkeebaControllerLog extends AkeebaControllerDefault
{
	public function execute($task)
	{
		$profile_id = $this->input->getInt('profileid', null);

		if (!empty($profile_id) && is_numeric($profile_id) && ($profile_id > 0))
		{
			$session = JFactory::getSession();
			$session->set('profile', $profile_id, 'akeeba');
		}

		if (!in_array($task, array('iframe', 'download')))
		{
			$task = 'browse';
		}

		parent::execute($task);
	}

	/**
	 * Display the log page
	 *
	 */
	public function browse($cachable = false, $urlparams = false)
	{
		$tag = $this->input->get('tag', null, 'cmd');
		if (empty($tag))
		{
			$tag = null;
		}
		$model = $this->getThisModel();
		$model->setState('tag', $tag);

		Platform::getInstance()->load_configuration(Platform::getInstance()->get_active_profile());

		parent::display($cachable, $urlparams);
	}

	// Renders the contents of the log's iframe
	public function iframe($cachable = false, $urlparams = false)
	{
		$tag = $this->input->get('tag', null, 'cmd');
		if (empty($tag))
		{
			$tag = null;
		}
		$model = $this->getThisModel();
		$model->setState('tag', $tag);

		Platform::getInstance()->load_configuration(Platform::getInstance()->get_active_profile());

		parent::display();

		flush();
		JFactory::getApplication()->close();
	}

	public function download($cachable = false, $urlparams = false)
	{
		Platform::getInstance()->load_configuration(Platform::getInstance()->get_active_profile());

		$tag = $this->input->get('tag', null, 'cmd');
		if (empty($tag))
		{
			$tag = null;
		}

		@ob_end_clean(); // In case some braindead plugin spits its own HTML
		header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
		header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
		header("Content-Description: File Transfer");
		header('Content-Type: text/plain');
		header('Content-Disposition: attachment; filename="Akeeba Backup Debug Log.txt"');

		$model = $this->getThisModel();
		$model->setState('tag', $tag);
		$model->echoRawLog();

		flush();
		JFactory::getApplication()->close();
	}
}