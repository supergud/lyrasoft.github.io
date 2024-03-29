<?php 
/**
 * @package     Windwalker.Framework
 * @subpackage  Form
 * @author      Simon Asika <asika32764@gmail.com>
 * @copyright   Copyright (C) 2013 Asikart. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

/**
 * Supports a Modal picker for target items.
 *
 * @package     Windwalker.Framework
 * @subpackage  Form
 */
class JFormFieldModal extends JFormField
{
	/**
	 * The form field type.
	 *
	 * @var        string
	 */
	protected $type = 'Modal';

	/**
	 * List name.
	 *
	 * @var string
	 */
	protected $view_list;

	/**
	 * Item name.
	 *
	 * @var string
	 */
	protected $view_item;

	/**
	 * Extension name, eg: com_content.
	 *
	 * @var string
	 */
	protected $extension;

	/**
	 * Component name without ext type, eg: content.
	 *
	 * @var string
	 */
	protected $component;

	/**
	 * Method to get the field input markup.
	 *
	 * @return    string    The field input markup.
	 */
	public function getInput()
	{
		// Load the modal behavior script.
		JHtml::_('behavior.modal', 'a.modal');
		$this->setElement();
		$this->setScript();

		// Setup variables for display.
		$readonly = $this->getElement('readonly', false);
		$disabled = $this->getElement('disabled', false);
		$html     = array();
		$link     = $this->getLink();
		$title    = $this->getTitle();

		if (empty($title))
		{
			$title = $this->element['select_label']
				? (string) JText::_($this->element['select_label'])
				: JText::_('COM_' . strtoupper($this->component) . '_SELECT_ITEM');
		}

		$title = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');

		if (JVERSION >= 3)
		{
			// The current user display field.
			$html[] = '<span class="' . (!$disabled && !$readonly ? 'input-append' : '') . '">';
			$html[] = '<input type="text" class="' . (!$disabled && !$readonly ? 'input-medium ' . $this->element['class'] : $this->element['class']) . '" id="' . $this->id . '_name" value="' . $title . '" disabled="disabled" size="35" />';

			if (!$disabled && !$readonly)
			{
				$html[] = '<a class="modal btn" title="' . JText::_('COM_' . strtoupper($this->component) . '_CHANGE_ITEM_BUTTON') . '"  href="' . $link . '&amp;' . JSession::getFormToken() . '=1" rel="{handler: \'iframe\', size: {x: 800, y: 450}}"><i class="icon-file"></i> ' . JText::_('JSELECT') . '</a>';
			}

			$html[] = '</span>';
		}
		else
		{
			AKHelper::_('include.addCSS', 'buttons/delicious-buttons/delicious-buttons.css', 'ww');

			// The current user display field.
			$html[] = '<div class="fltlft">';
			$html[] = '  <input type="text" id="' . $this->id . '_name" value="' . $title . '" disabled="disabled" size="35" />';
			$html[] = '</div>';

			// The user select button.
			if (!$disabled && !$readonly):
				$html[] = '<div class="fltlft">';
				$html[] = '  <div class="">';
				$html[] = '    <a class="modal delicious light blue" title="' . JText::_('COM_' . strtoupper($this->component) . '_CHANGE_ITEM') . '"  href="' . $link . '&amp;' . JSession::getFormToken() . '=1" rel="{handler: \'iframe\', size: {x: 800, y: 450}}">' . JText::_('COM_' . strtoupper($this->component) . '_CHANGE_ITEM_BUTTON') . '</a>';
				$html[] = '  </div>';
				$html[] = '</div>';
			endif;
		}

		// The active article id field.
		if (0 == (int) $this->value)
		{
			$value = '';
		}
		else
		{
			$value = (int) $this->value;
		}

		// class='required' for client side validation
		$class = '';

		if ($this->required)
		{
			$class = ' class="required modal-value"';
		}

		$html[] = '<input type="hidden" id="' . $this->id . '_id"' . $class . ' name="' . $this->name . '" value="' . $value . '" />';

		return implode("\n", $html) . $this->quickadd();
	}

	/**
	 * setScript
	 */
	public function setScript()
	{
		// Build the script.
		$script   = array();
		$script[] = '    function jSelect' . ucfirst($this->component) . '_' . $this->id . '(id, title) {';
		$script[] = '        document.id("' . $this->id . '_id").value = id;';
		$script[] = '        document.id("' . $this->id . '_name").value = title;';
		$script[] = '        SqueezeBox.close();';
		$script[] = '    }';

		// Add the script to the document head.
		JFactory::getDocument()->addScriptDeclaration(implode("\n", $script));
	}

	/**
	 * Set some element attributes to class variable.
	 */
	public function setElement()
	{
		$view_item = (string) $this->element['view_item'];
		$view_list = (string) $this->element['view_list'];
		$extension = (string) $this->element['extension'];

		if (!empty($view_item))
		{
			$this->view_item = $view_item;
		}

		if (!empty($view_list))
		{
			$this->view_list = $view_list;
		}

		if (!empty($extension))
		{
			$this->extension = $extension;
		}

		$this->component = str_replace('com_', '', $this->extension);
	}

	/**
	 * Get item title.
	 */
	public function getTitle()
	{
		$ctrl        = $this->view_list;
		$title_field = $this->element['title_field'] ? (string) $this->element['title_field'] : 'title';

		$db = JFactory::getDbo();
		$q  = $db->getQuery(true);

		$q->select($title_field)
			->from('#__' . $this->component . '_' . $ctrl)
			->where("id = '{$this->value}'");

		$db->setQuery($q);
		$title = $db->loadResult();

		if ($error = $db->getErrorMsg())
		{
			JError::raiseWarning(500, $error);
		}

		return $title;
	}

	/**
	 * Get item link.
	 */
	public function getLink()
	{
		// Avoid self
		$id     = JRequest::getVar('id');
		$option = JRequest::getVar('option');
		$view   = JRequest::getVar('view');
		$layout = JRequest::getVar('layout');
		$params = '';

		if (isset($this->element['show_root']))
		{
			$params .= '&show_root=1';
		}

		if ($view == $this->view_item && $option == $this->extension && $layout == 'edit' && $id)
		{
			$params .= '&avoid=' . $id;
		}

		return 'index.php?option=' . $this->extension . '&view=' . $this->view_list . $params . '&layout=modal&tmpl=component&function=jSelect' . ucfirst($this->component) . '_' . $this->id;
	}

	/**
	 * Add an quick add button & modal
	 */
	public function quickadd()
	{
		// Prepare Element
		$readonly = $this->getElement('readonly', false);
		$disabled = $this->getElement('disabled', false);

		if ($readonly || $disabled)
		{
			return;
		}

		$quickadd         = $this->getElement('quickadd', false);
		$table_name       = $this->getElement('table', '#__' . $this->component . '_' . $this->view_list);
		$key_field        = $this->getElement('key_field', 'id');
		$value_field      = $this->getElement('value_field', 'title');
		$formpath         = $this->getElement('quickadd_formpath', "administrator/components/{$this->extension}/models/forms/{$this->view_item}.xml");
		$quickadd_handler = $this->getElement('quickadd_handler', $this->extension);
		$title            = $this->getElement('quickadd_label', 'LIB_WINDWALKER_QUICKADD_TITLE');

		$qid = $this->id . '_quickadd';

		if (!$quickadd)
		{
			return '';
		}

		// Prepare Script & Styles
		$doc = JFactory::getDocument();
		AKHelper::_('include.sortedStyle', 'includes/css', $quickadd_handler);
		AKHelper::_('include.addJS', 'quickadd.js', 'ww');

		if (JVERSION < 3)
		{
			AKHelper::_('include.addCSS', 'buttons/delicious-buttons/delicious-buttons.css', 'ww');
			AKHelper::_('include.addCSS', 'ui/modal-j25.css', 'ww');
		}

		// Set AKQuickAddOption
		$config['quickadd_handler'] = $quickadd_handler;
		$config['extension']        = $this->extension;
		$config['component']        = $this->component;
		$config['table']            = $table_name;
		$config['model_name']       = $this->view_item;
		$config['key_field']        = $key_field;
		$config['value_field']      = $value_field;
		$config['joomla3']          = (JVERSION >= 3);

		$config = AKHelper::_('html.getJSObject', $config);

		$script = <<<QA
        window.addEvent('domready', function(){
            var AKQuickAddOption = {$config} ;
            AKQuickAdd.init('{$qid}', AKQuickAddOption);
        });
QA;

		$doc->addScriptDeclaration($script);

		// Load Language & Form
		AKHelper::_('lang.loadLanguage', $this->extension, null);
		$content = AKHelper::_('ui.getQuickaddForm', $qid, $formpath);

		// Prepare HTML
		$html         = '';
		$button_title = $title;
		$modal_title  = $button_title;
		$button_class = 'btn btn-small btn-success delicious green light fltlft quickadd_buttons';

		$footer = "<button class=\"btn delicious\" type=\"button\" onclick=\"$$('#{$qid} input', '#{$qid} select').set('value', '');AKQuickAdd.closeModal('{$qid}');\" data-dismiss=\"modal\">" . JText::_('JCANCEL') . "</button>";
		$footer .= "<button class=\"btn btn-primary delicious blue\" type=\"submit\" onclick=\"AKQuickAdd.submit('{$qid}', event);\">" . JText::_('JSUBMIT') . "</button>";

		$html .= AKHelper::_('ui.modalLink', JText::_($button_title), $qid, array('class' => $button_class, 'icon' => 'icon-new icon-white'));
		$html .= AKHelper::_('ui.renderModal', $qid, $content, array('title' => JText::_($modal_title), 'footer' => $footer));

		return $html;
	}

	/**
	 * Get Element Value.
	 */
	public function getElement($key, $default = null)
	{
		if ($this->element[$key])
		{
			return (string) $this->element[$key];
		}
		else
		{
			return $default;
		}
	}
}
