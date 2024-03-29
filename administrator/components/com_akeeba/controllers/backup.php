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

use Akeeba\Engine\Platform;
use Akeeba\Engine\Factory;

/**
 * The Backup controller class
 *
 */
class AkeebaControllerBackup extends AkeebaControllerDefault
{
	public function execute($task)
	{
		if ($task != 'ajax')
		{
			$task = 'add';
		}
		parent::execute($task);
	}

	public function add()
	{
		$this->display(false);
	}

	/**
	 * Default task; shows the initial page where the user selects a profile
	 * and enters description and comment
	 *
	 */
	public function onBeforeAdd()
	{
		$result = parent::onBeforeEdit();

		if ($result)
		{
			// Push models to view
			$model = $this->getThisModel();
			$view = $this->getThisView();
			$view->setModel($model, true);

			$newProfile = $this->input->get('profileid', -10, 'int');

			if (is_numeric($newProfile) && ($newProfile > 0))
			{
				$this->_csrfProtection();

				$session = JFactory::getSession();
				$session->set('profile', $newProfile, 'akeeba');

				Platform::getInstance()->load_configuration($newProfile);
			}

			// Deactivate the menus
			JRequest::setVar('hidemainmenu', 1);

			// Push data to the model
			$model->setState('profile', $this->input->get('profileid', -10, 'int'));
			$model->setState('ajax', $this->input->get('ajax', '', 'cmd'));
			$model->setState('autostart', $this->input->get('autostart', 0, 'int'));

			$description = $this->input->get('description', null, 'string', 2);

			if (!empty($description))
			{
				$model->setState('description', $description);
			}

			$comment = $this->input->get('comment', null, 'html', 2);

			if (!empty($comment))
			{
				$model->setState('comment', $comment);
			}

			$model->setState('jpskey', $this->input->get('jpskey', '', 'raw', 2));
			$model->setState('angiekey', $this->input->get('angiekey', '', 'raw', 2));
			$model->setState('returnurl', $this->input->get('returnurl', '', 'raw', 2));
			$model->setState('backupid', $this->input->get('backupid', null, 'string', 2));
		}

		return $result;
	}

	public function ajax()
	{
		/** @var AkeebaModelBackups $model */
		$model = $this->getThisModel();

		$model->setState('profile', $this->input->get('profileid', -10, 'int'));
		$model->setState('ajax', $this->input->get('ajax', '', 'cmd'));
		$model->setState('description', $this->input->get('description', '', 'string'));
		$model->setState('comment', $this->input->get('comment', '', 'html', 2));
		$model->setState('jpskey', $this->input->get('jpskey', '', 'raw', 2));
		$model->setState('angiekey', $this->input->get('angiekey', '', 'raw', 2));
		$model->setState('backupid', $this->input->get('backupid', null, 'string', 2));
		$model->setState('tag', $this->input->get('tag', 'backend', 'cmd'));
		$model->setState('errorMessage', $this->input->getString('errorMessage', ''));

		// System Restore Point backup state variables
		$model->setState('type', strtolower($this->input->get('type', '', 'cmd')));
		$model->setState('name', strtolower($this->input->get('name', '', 'cmd')));
		$model->setState('group', strtolower($this->input->get('group', '', 'cmd')));

		$model->setState('customdirs', $this->input->get('customdirs', array(), 'array', 2));
		$model->setState('customfiles', $this->input->get('customfiles', array(), 'array', 2));
		$model->setState('extraprefixes', $this->input->get('extraprefixes', array(), 'array', 2));
		$model->setState('customtables', $this->input->get('customtables', array(), 'array', 2));
		$model->setState('skiptables', $this->input->get('skiptables', array(), 'array', 2));
		$model->setState('langfiles', $this->input->get('langfiles', array(), 'array', 2));
		$model->setState('xmlname', $this->input->getString('xmlname', ''));

		define('AKEEBA_BACKUP_ORIGIN', $this->input->get('tag', 'backend', 'cmd'));

		$ret_array = $model->runBackup();

		@ob_end_clean();
		header('Content-type: text/plain');
		header('Connection: close');
		echo '###' . json_encode($ret_array) . '###';
		flush();
		JFactory::getApplication()->close();
	}
}