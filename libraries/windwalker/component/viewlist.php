<?php 
/**
 * @package     Windwalker.Framework
 * @subpackage  Component
 * @author      Simon Asika <asika32764@gmail.com>
 * @copyright   Copyright (C) 2013 Asikart. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

include_once dirname(__FILE__) . '/view.php';

/**
 * View class for list.
 *
 * @package     Windwalker.Framework
 * @subpackage  Component
 */
class AKViewList extends AKView
{
	/**
	 * Items cache.
	 *
	 * @var array
	 */
	protected $items = null;

	/**
	 * Pagination cache.
	 *
	 * @var object
	 */
	protected $pagination = null;

	/**
	 * Model state.
	 *
	 * @var JRegistry
	 */
	protected $state = null;

	/**
	 * Component option name.
	 *
	 * @var string
	 */
	protected $option = '';

	/**
	 * List name.
	 *
	 * @var string
	 */
	protected $list_name = '';

	/**
	 * Item name.
	 *
	 * @var string
	 */
	protected $item_name = '';

	/**
	 * Display this view, if in front-end, will show toolbar and submenus.
	 *
	 * @param   string $tpl  View layout name.
	 * @param   type   $path The panel layout from?
	 *
	 * @return  string    Render result.
	 */
	public function displayWithPanel($tpl = null, $path = null)
	{
		$app = JFactory::getApplication();

		// We don't need toolbar in the modal window.
		if ($this->getLayout() !== 'modal')
		{
			$this->addToolbar();
			$this->sidebarFilter();

			if (JVERSION >= 3)
			{
				$this->sidebar = JHtmlSidebar::render();
			}
		}

		// Nested ordering
		if ($this->state->get('items.nested'))
		{
			foreach ($this->items as &$item)
			{
				$this->ordering[$item->a_parent_id][] = $item->a_id;
			}
		}

		// If is frontend, show toolbar
		if ($app->isAdmin())
		{
			parent::display($tpl);
		}
		else
		{
			parent::displayWithPanel($tpl, $path);
		}
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return void
	 */
	protected function addToolbar()
	{
		$app          = JFactory::getApplication();
		$state        = $this->get('State');
		$canDo        = AKHelper::getActions($this->option);
		$user         = JFactory::getUser();
		$filter_state = $this->state->get('filter');

		// Get the toolbar object instance
		$bar = JToolBar::getInstance('toolbar');

		// Toolbar Buttons
		// ========================================================================
		if ($canDo->get('core.create'))
		{
			JToolBarHelper::addNew($this->item_name . '.add');
		}

		if ($canDo->get('core.edit'))
		{
			JToolBarHelper::editList($this->item_name . '.edit');
		}

		if ($canDo->get('core.create'))
		{
			JToolBarHelper::custom($this->list_name . '.duplicate', 'copy.png', 'copy_f2.png', 'JTOOLBAR_DUPLICATE', true);
		}

		if ($canDo->get('core.edit.state'))
		{
			JToolBarHelper::divider();
			JToolBarHelper::publish($this->list_name . '.publish', 'JTOOLBAR_PUBLISH', true);
			JToolBarHelper::unpublish($this->list_name . '.unpublish', 'JTOOLBAR_UNPUBLISH', true);
			JToolbarHelper::checkin($this->list_name . '.checkin');

			if ($this->state->get('items.nested'))
			{
				JToolBarHelper::custom($this->list_name . '.rebuild', 'refresh.png', 'refresh_f2.png', 'JTOOLBAR_REBUILD', false);
			}

			JToolBarHelper::divider();
		}

		if ((JArrayHelper::getValue($filter_state, 'a.published') == -2 && $canDo->get('core.delete')) || $this->get('no_trash') || AKDEBUG)
		{
			JToolbarHelper::deleteList(JText::_('LIB_WINDWALKER_TOOLBAR_CONFIRM_DELETE'), $this->list_name . '.delete');
		}
		elseif ($canDo->get('core.edit.state'))
		{
			JToolbarHelper::trash($this->list_name . '.trash');
		}

		// Add a batch modal button
		$batch = AKHelper::_('path.get', null, $this->option) . '/views/' . $this->list_name . '/tmpl/default_batch.php';

		if ($canDo->get('core.edit') && JVERSION >= 3 && JFile::exists($batch))
		{
			AKToolbarHelper::modal('JTOOLBAR_BATCH', 'batchModal');
		}

		if ($canDo->get('core.admin') && $app->isAdmin())
		{
			AKToolBarHelper::preferences($this->option);
		}

	}

	/**
	 * Render filter at sidebar.
	 *
	 * @return void
	 */
	public function sidebarFilter()
	{
		// Sidebar Filters
		// ========================================================================
		if (JVERSION >= 3 && $this->state->get('core_sidebar'))
		{
			JHtmlSidebar::setAction('index.php?option=' . $this->option . '&view=' . $this->getName());

			$filters = $this->filter['filter_sidebar'];

			foreach ($filters as $filter)
			{
				$options = array();
				$i       = 0;

				foreach ($filter->option as $option)
				{
					$options[$i]['value'] = (string) $option['value'];
					$options[$i]['text']  = (string) $option;
					$i++;
				}

				JHtmlSidebar::addFilter(
					(string) $filter['title'],
					(string) 'filter[' . $filter['name'] . ']',
					JHtml::_('select.options', $options, 'value', 'text', $filter_state[(string) $filter['name']], true)
				);
			}
		}
	}

	/**
	 * Render table tree array to grid view.
	 *
	 * @param   array $table  A table tree array.
	 * @param   array $option The option for this table.
	 *
	 * @return  string  Grid HTML.
	 */
	public function renderGrid($table, $option = array())
	{
		// Set Grid
		// =================================================================================
		$grid = new AKGrid;

		$grid->setTableOptions($option);
		$grid->setColumns(array_keys($table['thead']['tr'][0]['th']));

		// Thead
		// =================================================================================
		$grid->addRow($table['thead']['tr'][0]['option'], 1);

		foreach ($table['thead']['tr'][0]['th'] as $key => $th):
			$grid->setRowCell($key, $th['content'], $th['option']);
		endforeach;

		// Tbody
		// =================================================================================
		foreach ($table['tbody']['tr'] as $tr)
		{
			$grid->addRow($tr['option']);

			foreach ($tr['td'] as $key2 => $td)
			{
				$grid->setRowCell($key2, $td['content'], $td['option']);
			}
		}

		return $grid;
	}

	/**
	 * A proxy for JHtnlGrid::sort();
	 *
	 * @param   string $text Sort button title.
	 * @param   string $col  Table column name.
	 *
	 * @return  string    Sort button HTML.
	 */
	public function sort($text, $col)
	{
		$listOrder = $this->state->get('list.ordering');
		$listDirn  = $this->state->get('list.direction');

		return JHtml::_('grid.sort', $text, $col, $listDirn, $listOrder);
	}
}