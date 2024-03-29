<?php 
/**
 * @package     FrameworkOnFramework
 * @subpackage  toolbar
 * @copyright   Copyright (C) 2010 - 2015 Nicholas K. Dionysopoulos / Akeeba Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
// Protect from unauthorized access
defined('F0F_INCLUDED') or die;

/**
 * The Toolbar class renders the back-end component title area and the back-
 * and front-end toolbars.
 *
 * @package  FrameworkOnFramework
 * @since    1.0
 */
class F0FToolbar
{
	/** @var array Configuration parameters */
	protected $config = array();

	/** @var array Input (e.g. request) variables */
	protected $input = array();

	/** @var array Permissions map, see the __construct method for more information */
	public $perms = array();

	/** @var array The links to be rendered in the toolbar */
	protected $linkbar = array();

	/** @var bool Should I render the submenu in the front-end? */
	protected $renderFrontendSubmenu = false;

	/** @var bool Should I render buttons in the front-end? */
	protected $renderFrontendButtons = false;

	/**
	 * Gets an instance of a component's toolbar
	 *
	 * @param   string  $option  The name of the component
	 * @param   array   $config  The configuration array for the component
	 *
	 * @return  F0FToolbar  The toolbar instance for the component
	 */
	public static function &getAnInstance($option = null, $config = array())
	{
		static $instances = array();

		// Make sure $config is an array
		if (is_object($config))
		{
			$config = (array) $config;
		}
		elseif (!is_array($config))
		{
			$config = array();
		}

		$hash = $option;

		if (!array_key_exists($hash, $instances))
		{
			if (array_key_exists('input', $config))
			{
				if ($config['input'] instanceof F0FInput)
				{
					$input = $config['input'];
				}
				else
				{
					$input = new F0FInput($config['input']);
				}
			}
			else
			{
				$input = new F0FInput;
			}

			$config['option'] = !is_null($option) ? $option : $input->getCmd('option', 'com_foobar');
			$input->set('option', $config['option']);
			$config['input'] = $input;

			$className = ucfirst(str_replace('com_', '', $config['option'])) . 'Toolbar';

			if (!class_exists($className))
			{
				$componentPaths = F0FPlatform::getInstance()->getComponentBaseDirs($config['option']);

				$searchPaths = array(
					$componentPaths['main'],
					$componentPaths['main'] . '/toolbars',
					$componentPaths['alt'],
					$componentPaths['alt'] . '/toolbars'
				);

				if (array_key_exists('searchpath', $config))
				{
					array_unshift($searchPaths, $config['searchpath']);
				}

                $filesystem = F0FPlatform::getInstance()->getIntegrationObject('filesystem');

				$path = $filesystem->pathFind(
						$searchPaths, 'toolbar.php'
				);

				if ($path)
				{
					require_once $path;
				}
			}

			if (!class_exists($className))
			{
				$className = 'F0FToolbar';
			}

			$instance = new $className($config);

			$instances[$hash] = $instance;
		}

		return $instances[$hash];
	}

	/**
	 * Public constructor
	 *
	 * @param   array  $config  The configuration array of the component
	 */
	public function __construct($config = array())
	{
		// Make sure $config is an array
		if (is_object($config))
		{
			$config = (array) $config;
		}
		elseif (!is_array($config))
		{
			$config = array();
		}

		// Cache the config
		$this->config = $config;

		// Get the input for this MVC triad
		if (array_key_exists('input', $config))
		{
			$this->input = $config['input'];
		}
		else
		{
			$this->input = new F0FInput;
		}

		// Get the default values for the component and view names
		$this->component = $this->input->getCmd('option', 'com_foobar');

		// Overrides from the config

		if (array_key_exists('option', $config))
		{
			$this->component = $config['option'];
		}

		$this->input->set('option', $this->component);

		// Get default permissions (can be overriden by the view)
		$platform = F0FPlatform::getInstance();
		$perms = (object) array(
				'manage'	 => $platform->authorise('core.manage', $this->input->getCmd('option', 'com_foobar')),
				'create'	 => $platform->authorise('core.create', $this->input->getCmd('option', 'com_foobar')),
				'edit'		 => $platform->authorise('core.edit', $this->input->getCmd('option', 'com_foobar')),
				'editstate'	 => $platform->authorise('core.edit.state', $this->input->getCmd('option', 'com_foobar')),
				'delete'	 => $platform->authorise('core.delete', $this->input->getCmd('option', 'com_foobar')),
		);

		// Save front-end toolbar and submenu rendering flags if present in the config
		if (array_key_exists('renderFrontendButtons', $config))
		{
			$this->renderFrontendButtons = $config['renderFrontendButtons'];
		}

		if (array_key_exists('renderFrontendSubmenu', $config))
		{
			$this->renderFrontendSubmenu = $config['renderFrontendSubmenu'];
		}

		// If not in the administrative area, load the JToolbarHelper
		if (!F0FPlatform::getInstance()->isBackend())
		{
            // Needed for tests, so we can inject our "special" helper class
            if(!class_exists('JToolbarHelper'))
            {
                $platformDirs = F0FPlatform::getInstance()->getPlatformBaseDirs();
                require_once $platformDirs['root'] . '/administrator/includes/toolbar.php';
            }

			// Things to do if we have to render a front-end toolbar
			if ($this->renderFrontendButtons)
			{
				// Load back-end toolbar language files in front-end
				F0FPlatform::getInstance()->loadTranslations('');

                // Needed for tests (we can fake we're not in the backend, but we are still in CLI!)
                if(!F0FPlatform::getInstance()->isCli())
                {
                    // Load the core Javascript
	                if (version_compare(JVERSION, '3.0', 'ge'))
	                {
		                JHtml::_('jquery.framework');

						if (version_compare(JVERSION, '3.3.0', 'ge'))
						{
							JHtml::_('behavior.core');
						}
						else
						{
							JHtml::_('behavior.framework', true);
						}
	                }
	                else
	                {
		                JHtml::_('behavior.framework');
	                }
                }
			}
		}

		// Store permissions in the local toolbar object
		$this->perms = $perms;
	}

	/**
	 * Renders the toolbar for the current view and task
	 *
	 * @param   string    $view   The view of the component
	 * @param   string    $task   The exact task of the view
	 * @param   F0FInput  $input  An optional input object used to determine the defaults
	 *
	 * @return  void
	 */
	public function renderToolbar($view = null, $task = null, $input = null)
	{
		if (!empty($input))
		{
			$saveInput = $this->input;
			$this->input = $input;
		}

		// If tmpl=component the default behaviour is to not render the toolbar
		if ($this->input->getCmd('tmpl', '') == 'component')
		{
			$render_toolbar = false;
		}
		else
		{
			$render_toolbar = true;
		}

		// If there is a render_toolbar=0 in the URL, do not render a toolbar

		$render_toolbar = $this->input->getBool('render_toolbar', $render_toolbar);

		if (!$render_toolbar)
		{
			return;
		}

		// Get the view and task

		if (empty($view))
		{
			$view = $this->input->getCmd('view', 'cpanel');
		}

		if (empty($task))
		{
			$task = $this->input->getCmd('task', 'default');
		}

		$this->view = $view;
		$this->task = $task;
		$view = F0FInflector::pluralize($view);
		$component = $this->input->get('option', 'com_foobar', 'cmd');

		$configProvider = new F0FConfigProvider;
		$toolbar = $configProvider->get(
			$component . '.views.' . $view . '.toolbar.' . $task
		);

		// If we have a toolbar config specified
		if (!empty($toolbar))
		{
			return $this->renderFromConfig($toolbar);
		}

		// Check for an onViewTask method
		$methodName = 'on' . ucfirst($view) . ucfirst($task);

		if (method_exists($this, $methodName))
		{
			return $this->$methodName();
		}

		// Check for an onView method
		$methodName = 'on' . ucfirst($view);

		if (method_exists($this, $methodName))
		{
			return $this->$methodName();
		}

		// Check for an onTask method
		$methodName = 'on' . ucfirst($task);

		if (method_exists($this, $methodName))
		{
			return $this->$methodName();
		}

		if (!empty($input))
		{
			$this->input = $saveInput;
		}
	}

	/**
	 * Renders the toolbar for the component's Control Panel page
	 *
	 * @return  void
	 */
	public function onCpanelsBrowse()
	{
		if (F0FPlatform::getInstance()->isBackend() || $this->renderFrontendSubmenu)
		{
			$this->renderSubmenu();
		}

		if (!F0FPlatform::getInstance()->isBackend() && !$this->renderFrontendButtons)
		{
			return;
		}

		$option = $this->input->getCmd('option', 'com_foobar');

		JToolBarHelper::title(JText::_(strtoupper($option)), str_replace('com_', '', $option));
		JToolBarHelper::preferences($option, 550, 875);
	}

	/**
	 * Renders the toolbar for the component's Browse pages (the plural views)
	 *
	 * @return  void
	 */
	public function onBrowse()
	{
		// On frontend, buttons must be added specifically
		if (F0FPlatform::getInstance()->isBackend() || $this->renderFrontendSubmenu)
		{
			$this->renderSubmenu();
		}

		if (!F0FPlatform::getInstance()->isBackend() && !$this->renderFrontendButtons)
		{
			return;
		}

		// Set toolbar title
		$option = $this->input->getCmd('option', 'com_foobar');
		$subtitle_key = strtoupper($option . '_TITLE_' . $this->input->getCmd('view', 'cpanel'));
		JToolBarHelper::title(JText::_(strtoupper($option)) . ': ' . JText::_($subtitle_key), str_replace('com_', '', $option));

		// Add toolbar buttons
		if ($this->perms->create)
		{
			if (version_compare(JVERSION, '3.0', 'ge'))
			{
				JToolBarHelper::addNew();
			}
			else
			{
				JToolBarHelper::addNewX();
			}
		}

		if ($this->perms->edit)
		{
			if (version_compare(JVERSION, '3.0', 'ge'))
			{
				JToolBarHelper::editList();
			}
			else
			{
				JToolBarHelper::editListX();
			}
		}

		if ($this->perms->create || $this->perms->edit)
		{
			JToolBarHelper::divider();
		}

		if ($this->perms->editstate)
		{
			JToolBarHelper::publishList();
			JToolBarHelper::unpublishList();
			JToolBarHelper::divider();
		}

		if ($this->perms->delete)
		{
			$msg = JText::_($this->input->getCmd('option', 'com_foobar') . '_CONFIRM_DELETE');
			JToolBarHelper::deleteList(strtoupper($msg));
		}
	}

	/**
	 * Renders the toolbar for the component's Read pages
	 *
	 * @return  void
	 */
	public function onRead()
	{
		// On frontend, buttons must be added specifically
		if (F0FPlatform::getInstance()->isBackend() || $this->renderFrontendSubmenu)
		{
			$this->renderSubmenu();
		}

		if (!F0FPlatform::getInstance()->isBackend() && !$this->renderFrontendButtons)
		{
			return;
		}

		$option = $this->input->getCmd('option', 'com_foobar');
		$componentName = str_replace('com_', '', $option);

		// Set toolbar title
		$subtitle_key = strtoupper($option . '_TITLE_' . $this->input->getCmd('view', 'cpanel') . '_READ');
		JToolBarHelper::title(JText::_(strtoupper($option)) . ': ' . JText::_($subtitle_key), $componentName);

		// Set toolbar icons
		JToolBarHelper::back();
	}

	/**
	 * Renders the toolbar for the component's Add pages
	 *
	 * @return  void
	 */
	public function onAdd()
	{
		// On frontend, buttons must be added specifically
		if (!F0FPlatform::getInstance()->isBackend() && !$this->renderFrontendButtons)
		{
			return;
		}

		$option = $this->input->getCmd('option', 'com_foobar');
		$componentName = str_replace('com_', '', $option);

		// Set toolbar title
		$subtitle_key = strtoupper($option . '_TITLE_' . F0FInflector::pluralize($this->input->getCmd('view', 'cpanel'))) . '_EDIT';
		JToolBarHelper::title(JText::_(strtoupper($option)) . ': ' . JText::_($subtitle_key), $componentName);

		// Set toolbar icons
        if ($this->perms->edit || $this->perms->editown)
        {
            // Show the apply button only if I can edit the record, otherwise I'll return to the edit form and get a
            // 403 error since I can't do that
            JToolBarHelper::apply();
        }

		JToolBarHelper::save();

		if ($this->perms->create)
		{
			JToolBarHelper::custom('savenew', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
		}

		JToolBarHelper::cancel();
	}

	/**
	 * Renders the toolbar for the component's Edit pages
	 *
	 * @return  void
	 */
	public function onEdit()
	{
		// On frontend, buttons must be added specifically
		if (!F0FPlatform::getInstance()->isBackend() && !$this->renderFrontendButtons)
		{
			return;
		}

		$this->onAdd();
	}

	/**
	 * Removes all links from the link bar
	 *
	 * @return  void
	 */
	public function clearLinks()
	{
		$this->linkbar = array();
	}

	/**
	 * Get the link bar's link definitions
	 *
	 * @return  array
	 */
	public function &getLinks()
	{
		return $this->linkbar;
	}

	/**
	 * Append a link to the link bar
	 *
	 * @param   string       $name    The text of the link
	 * @param   string|null  $link    The link to render; set to null to render a separator
	 * @param   boolean      $active  True if it's an active link
	 * @param   string|null  $icon    Icon class (used by some renderers, like the Bootstrap renderer)
	 * @param   string|null  $parent  The parent element (referenced by name)) Thsi will create a dropdown list
	 *
	 * @return  void
	 */
	public function appendLink($name, $link = null, $active = false, $icon = null, $parent = '')
	{
		$linkDefinition = array(
			'name'	 => $name,
			'link'	 => $link,
			'active' => $active,
			'icon'	 => $icon
		);

		if (empty($parent))
		{
            if(array_key_exists($name, $this->linkbar))
            {
                $this->linkbar[$name] = array_merge($this->linkbar[$name], $linkDefinition);

                // If there already are some children, I have to put this view link in the "items" array in the first place
                if(array_key_exists('items', $this->linkbar[$name]))
                {
                    array_unshift($this->linkbar[$name]['items'], $linkDefinition);
                }
            }
            else
            {
                $this->linkbar[$name] = $linkDefinition;
            }
		}
		else
		{
			if (!array_key_exists($parent, $this->linkbar))
			{
				$parentElement = $linkDefinition;
                $parentElement['name'] = $parent;
				$parentElement['link'] = null;
				$this->linkbar[$parent] = $parentElement;
				$parentElement['items'] = array();
			}
			else
			{
				$parentElement = $this->linkbar[$parent];

				if (!array_key_exists('dropdown', $parentElement) && !empty($parentElement['link']))
				{
					$newSubElement = $parentElement;
					$parentElement['items'] = array($newSubElement);
				}
			}

			$parentElement['items'][] = $linkDefinition;
			$parentElement['dropdown'] = true;

			if($active)
			{
				$parentElement['active'] = true;
			}

			$this->linkbar[$parent] = $parentElement;
		}
	}

	/**
	 * Prefixes (some people erroneously call this "prepend" – there is no such word) a link to the link bar
	 *
	 * @param   string       $name    The text of the link
	 * @param   string|null  $link    The link to render; set to null to render a separator
	 * @param   boolean      $active  True if it's an active link
	 * @param   string|null  $icon    Icon class (used by some renderers, like the Bootstrap renderer)
	 *
	 * @return  void
	 */
	public function prefixLink($name, $link = null, $active = false, $icon = null)
	{
		$linkDefinition = array(
			'name'	 => $name,
			'link'	 => $link,
			'active' => $active,
			'icon'	 => $icon
		);
		array_unshift($this->linkbar, $linkDefinition);
	}

	/**
	 * Renders the submenu (toolbar links) for all detected views of this component
	 *
	 * @return  void
	 */
	public function renderSubmenu()
	{
		$views = $this->getMyViews();

		if (empty($views))
		{
			return;
		}

		$activeView = $this->input->getCmd('view', 'cpanel');

		foreach ($views as $view)
		{
			// Get the view name
			$key = strtoupper($this->component) . '_TITLE_' . strtoupper($view);

            //Do we have a translation for this key?
			if (strtoupper(JText::_($key)) == $key)
			{
				$altview = F0FInflector::isPlural($view) ? F0FInflector::singularize($view) : F0FInflector::pluralize($view);
				$key2 = strtoupper($this->component) . '_TITLE_' . strtoupper($altview);

                // Maybe we have for the alternative view?
				if (strtoupper(JText::_($key2)) == $key2)
				{
                    // Nope, let's use the raw name
					$name = ucfirst($view);
				}
				else
				{
					$name = JText::_($key2);
				}
			}
			else
			{
				$name = JText::_($key);
			}

			$link = 'index.php?option=' . $this->component . '&view=' . $view;

			$active = $view == $activeView;

			$this->appendLink($name, $link, $active);
		}
	}

	/**
	 * Automatically detects all views of the component
	 *
	 * @return  array  A list of all views, in the order to be displayed in the toolbar submenu
	 */
	protected function getMyViews()
	{
		$views      = array();
		$t_views    = array();
		$using_meta = false;

		$componentPaths = F0FPlatform::getInstance()->getComponentBaseDirs($this->component);
		$searchPath     = $componentPaths['main'] . '/views';
        $filesystem     = F0FPlatform::getInstance()->getIntegrationObject('filesystem');

		$allFolders = $filesystem->folderFolders($searchPath);

		if (!empty($allFolders))
		{
			foreach ($allFolders as $folder)
			{
				$view = $folder;

				// View already added
				if (in_array(F0FInflector::pluralize($view), $t_views))
				{
					continue;
				}

				// Do we have a 'skip.xml' file in there?
				$files = $filesystem->folderFiles($searchPath . '/' . $view, '^skip\.xml$');

				if (!empty($files))
				{
					continue;
				}

				// Do we have extra information about this view? (ie. ordering)
				$meta = $filesystem->folderFiles($searchPath . '/' . $view, '^metadata\.xml$');

				// Not found, do we have it inside the plural one?
				if (!$meta)
				{
					$plural = F0FInflector::pluralize($view);

					if (in_array($plural, $allFolders))
					{
						$view = $plural;
						$meta = $filesystem->folderFiles($searchPath . '/' . $view, '^metadata\.xml$');
					}
				}

				if (!empty($meta))
				{
					$using_meta = true;
					$xml = simplexml_load_file($searchPath . '/' . $view . '/' . $meta[0]);
					$order = (int) $xml->foflib->ordering;
				}
				else
				{
					// Next place. It's ok since the index are 0-based and count is 1-based

					if (!isset($to_order))
					{
						$to_order = array();
					}

					$order = count($to_order);
				}

				$view = F0FInflector::pluralize($view);

				$t_view = new stdClass;
				$t_view->ordering = $order;
				$t_view->view = $view;

				$to_order[] = $t_view;
				$t_views[] = $view;
			}
		}

        F0FUtilsArray::sortObjects($to_order, 'ordering');
		$views = F0FUtilsArray::getColumn($to_order, 'view');

		// If not using the metadata file, let's put the cpanel view on top
		if (!$using_meta)
		{
			$cpanel = array_search('cpanels', $views);

			if ($cpanel !== false)
			{
				unset($views[$cpanel]);
				array_unshift($views, 'cpanels');
			}
		}

		return $views;
	}

	/**
	 * Return the front-end toolbar rendering flag
	 *
	 * @return  boolean
	 */
	public function getRenderFrontendButtons()
	{
		return $this->renderFrontendButtons;
	}

	/**
	 * Return the front-end submenu rendering flag
	 *
	 * @return  boolean
	 */
	public function getRenderFrontendSubmenu()
	{
		return $this->renderFrontendSubmenu;
	}

	/**
	 * Render the toolbar from the configuration.
	 *
	 * @param   array  $toolbar  The toolbar definition
	 *
	 * @return  void
	 */
	private function renderFromConfig(array $toolbar)
	{
		if (F0FPlatform::getInstance()->isBackend() || $this->renderFrontendSubmenu)
		{
			$this->renderSubmenu();
		}

		if (!F0FPlatform::getInstance()->isBackend() && !$this->renderFrontendButtons)
		{
			return;
		}

		// Render each element
		foreach ($toolbar as $elementType => $elementAttributes)
		{
			$value = isset($elementAttributes['value']) ? $elementAttributes['value'] : null;
			$this->renderToolbarElement($elementType, $value, $elementAttributes);
		}

		return;
	}

	/**
	 * Render a toolbar element.
	 *
	 * @param   string  $type        The element type.
	 * @param   mixed   $value       The element value.
	 * @param   array   $attributes  The element attributes.
	 *
	 * @return  void
	 *
     * @codeCoverageIgnore
	 * @throws  InvalidArgumentException
	 */
	private function renderToolbarElement($type, $value = null, array $attributes = array())
	{
		switch ($type)
		{
			case 'title':
				$icon = isset($attributes['icon']) ? $attributes['icon'] : 'generic.png';
				JToolbarHelper::title($value, $icon);
				break;

			case 'divider':
				JToolbarHelper::divider();
				break;

			case 'custom':
				$task = isset($attributes['task']) ? $attributes['task'] : '';
				$icon = isset($attributes['icon']) ? $attributes['icon'] : '';
				$iconOver = isset($attributes['icon_over']) ? $attributes['icon_over'] : '';
				$alt = isset($attributes['alt']) ? $attributes['alt'] : '';
				$listSelect = isset($attributes['list_select']) ?
					F0FStringUtils::toBool($attributes['list_select']) : true;

				JToolbarHelper::custom($task, $icon, $iconOver, $alt, $listSelect);
				break;

			case 'preview':
				$url = isset($attributes['url']) ? $attributes['url'] : '';
				$update_editors = isset($attributes['update_editors']) ?
					F0FStringUtils::toBool($attributes['update_editors']) : false;

				JToolbarHelper::preview($url, $update_editors);
				break;

			case 'help':
				if (!isset($attributes['help']))
				{
					throw new InvalidArgumentException(
						'The help attribute is missing in the help button type.'
					);
				}

				$ref = $attributes['help'];
				$com = isset($attributes['com']) ? F0FStringUtils::toBool($attributes['com']) : false;
				$override = isset($attributes['override']) ? $attributes['override'] : null;
				$component = isset($attributes['component']) ? $attributes['component'] : null;

				JToolbarHelper::help($ref, $com, $override, $component);
				break;

			case 'back':
				$alt = isset($attributes['alt']) ? $attributes['alt'] : 'JTOOLBAR_BACK';
				$href = isset($attributes['href']) ? $attributes['href'] : 'javascript:history.back();';

				JToolbarHelper::back($alt, $href);
				break;

			case 'media_manager':
				$directory = isset($attributes['directory']) ? $attributes['directory'] : '';
				$alt = isset($attributes['alt']) ? $attributes['alt'] : 'JTOOLBAR_UPLOAD';

				JToolbarHelper::media_manager($directory, $alt);
				break;

			case 'assign':
				$task = isset($attributes['task']) ? $attributes['task'] : 'assign';
				$alt = isset($attributes['alt']) ? $attributes['alt'] : 'JTOOLBAR_ASSIGN';

				JToolbarHelper::assign($task, $alt);
				break;

			case 'new':
				if ($this->perms->create)
				{
					$task = isset($attributes['task']) ? $attributes['task'] : 'add';
					$alt = isset($attributes['alt']) ? $attributes['alt'] : 'JTOOLBAR_NEW';
					$check = isset($attributes['check']) ?
						F0FStringUtils::toBool($attributes['check']) : false;

					JToolbarHelper::addNew($task, $alt, $check);
				}

				break;

			case 'publish':
				if ($this->perms->editstate)
				{
					$task = isset($attributes['task']) ? $attributes['task'] : 'publish';
					$alt = isset($attributes['alt']) ? $attributes['alt'] : 'JTOOLBAR_PUBLISH';
					$check = isset($attributes['check']) ?
						F0FStringUtils::toBool($attributes['check']) : false;

					JToolbarHelper::publish($task, $alt, $check);
				}

				break;

			case 'publishList':
				if ($this->perms->editstate)
				{
					$task = isset($attributes['task']) ? $attributes['task'] : 'publish';
					$alt = isset($attributes['alt']) ? $attributes['alt'] : 'JTOOLBAR_PUBLISH';

					JToolbarHelper::publishList($task, $alt);
				}

				break;

			case 'unpublish':
				if ($this->perms->editstate)
				{
					$task = isset($attributes['task']) ? $attributes['task'] : 'unpublish';
					$alt = isset($attributes['alt']) ? $attributes['alt'] : 'JTOOLBAR_UNPUBLISH';
					$check = isset($attributes['check']) ?
						F0FStringUtils::toBool($attributes['check']) : false;

					JToolbarHelper::unpublish($task, $alt, $check);
				}

				break;

			case 'unpublishList':
				if ($this->perms->editstate)
				{
					$task = isset($attributes['task']) ? $attributes['task'] : 'unpublish';
					$alt = isset($attributes['alt']) ? $attributes['alt'] : 'JTOOLBAR_UNPUBLISH';

					JToolbarHelper::unpublishList($task, $alt);
				}

				break;

			case 'archiveList':
				if ($this->perms->editstate)
				{
					$task = isset($attributes['task']) ? $attributes['task'] : 'archive';
					$alt = isset($attributes['alt']) ? $attributes['alt'] : 'JTOOLBAR_ARCHIVE';

					JToolbarHelper::archiveList($task, $alt);
				}

				break;

			case 'unarchiveList':
				if ($this->perms->editstate)
				{
					$task = isset($attributes['task']) ? $attributes['task'] : 'unarchive';
					$alt = isset($attributes['alt']) ? $attributes['alt'] : 'JTOOLBAR_UNARCHIVE';

					JToolbarHelper::unarchiveList($task, $alt);
				}

				break;

			case 'editList':
				if ($this->perms->edit)
				{
					$task = isset($attributes['task']) ? $attributes['task'] : 'edit';
					$alt = isset($attributes['alt']) ? $attributes['alt'] : 'JTOOLBAR_EDIT';

					JToolbarHelper::editList($task, $alt);
				}

				break;

			case 'editHtml':
				$task = isset($attributes['task']) ? $attributes['task'] : 'edit_source';
				$alt = isset($attributes['alt']) ? $attributes['alt'] : 'JTOOLBAR_EDIT_HTML';

				JToolbarHelper::editHtml($task, $alt);
				break;

			case 'editCss':
				$task = isset($attributes['task']) ? $attributes['task'] : 'edit_css';
				$alt = isset($attributes['alt']) ? $attributes['alt'] : 'JTOOLBAR_EDIT_CSS';

				JToolbarHelper::editCss($task, $alt);
				break;

			case 'deleteList':
				if ($this->perms->delete)
				{
					$msg = isset($attributes['msg']) ? $attributes['msg'] : '';
					$task = isset($attributes['task']) ? $attributes['task'] : 'remove';
					$alt = isset($attributes['alt']) ? $attributes['alt'] : 'JTOOLBAR_DELETE';

					JToolbarHelper::deleteList($msg, $task, $alt);
				}

				break;

			case 'trash':
				if ($this->perms->editstate)
				{
					$task = isset($attributes['task']) ? $attributes['task'] : 'remove';
					$alt = isset($attributes['alt']) ? $attributes['alt'] : 'JTOOLBAR_TRASH';
					$check = isset($attributes['check']) ?
						F0FStringUtils::toBool($attributes['check']) : true;

					JToolbarHelper::trash($task, $alt, $check);
				}

				break;

			case 'apply':
				$task = isset($attributes['task']) ? $attributes['task'] : 'apply';
				$alt = isset($attributes['alt']) ? $attributes['alt'] : 'JTOOLBAR_APPLY';

				JToolbarHelper::apply($task, $alt);
				break;

			case 'save':
				$task = isset($attributes['task']) ? $attributes['task'] : 'save';
				$alt = isset($attributes['alt']) ? $attributes['alt'] : 'JTOOLBAR_SAVE';

				JToolbarHelper::save($task, $alt);
				break;

			case 'save2new':
				$task = isset($attributes['task']) ? $attributes['task'] : 'save2new';
				$alt = isset($attributes['alt']) ? $attributes['alt'] : 'JTOOLBAR_SAVE_AND_NEW';

				JToolbarHelper::save2new($task, $alt);
				break;

			case 'save2copy':
				$task = isset($attributes['task']) ? $attributes['task'] : 'save2copy';
				$alt = isset($attributes['alt']) ? $attributes['alt'] : 'JTOOLBAR_SAVE_AS_COPY';
				JToolbarHelper::save2copy($task, $alt);
				break;

			case 'checkin':
				$task = isset($attributes['task']) ? $attributes['task'] : 'checkin';
				$alt = isset($attributes['alt']) ? $attributes['alt'] :'JTOOLBAR_CHECKIN';
				$check = isset($attributes['check']) ?
					F0FStringUtils::toBool($attributes['check']) : true;

				JToolbarHelper::checkin($task, $alt, $check);
				break;

			case 'cancel':
				$task = isset($attributes['task']) ? $attributes['task'] : 'cancel';
				$alt = isset($attributes['alt']) ? $attributes['alt'] : 'JTOOLBAR_CANCEL';

				JToolbarHelper::cancel($task, $alt);
				break;

			case 'preferences':
				if (!isset($attributes['component']))
				{
					throw new InvalidArgumentException(
						'The component attribute is missing in the preferences button type.'
					);
				}

				$component = $attributes['component'];
				$height = isset($attributes['height']) ? $attributes['height'] : '550';
				$width = isset($attributes['width']) ? $attributes['width'] : '875';
				$alt = isset($attributes['alt']) ? $attributes['alt'] : 'JToolbar_Options';
				$path = isset($attributes['path']) ? $attributes['path'] : '';

				JToolbarHelper::preferences($component, $height, $width, $alt, $path);
				break;

			default:
				throw new InvalidArgumentException(sprintf('Unknown button type %s', $type));
		}
	}
}
