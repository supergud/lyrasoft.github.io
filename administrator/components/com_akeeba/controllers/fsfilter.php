<?php 
/**
 * @package AkeebaBackup
 * @copyright Copyright (c)2009-2014 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 *
 * @since 3.0
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

/**
 * The Filesystem Filters controller class
 *
 */
class AkeebaControllerFsfilter extends AkeebaControllerDefault
{
	public function execute($task)
	{
		if($task != 'ajax') {
			$task = 'browse';
		}
		parent::execute($task);
	}

	public function browse($cachable = false, $urlparams = false)
	{
		$task = $this->input->get('task', 'normal', 'cmd');
		$this->getThisModel()->setState('browse_task', $task);
		parent::display($cachable, $urlparams);
	}

	/**
	 * AJAX proxy.
	 */
	public function ajax($cachable = false, $urlparams = false)
	{
		// Parse the JSON data and reset the action query param to the resulting array
		$action_json = $this->input->get('action', '', 'none', 2);
		$action = json_decode($action_json);

        /** @var AkeebaModelFsfilters $model */
		$model = $this->getThisModel();
		$model->setState('action', $action);

		$ret = $model->doAjax();

		@ob_end_clean();
		echo '###' . json_encode($ret) . '###';
		flush();
		JFactory::getApplication()->close();
	}

}