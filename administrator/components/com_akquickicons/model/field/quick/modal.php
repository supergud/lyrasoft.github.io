<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_akquickicons
 *
 * @copyright   Copyright (C) 2012 Asikart. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Generated by AKHelper - http://asikart.com
 */

// no direct access
defined('_JEXEC') or die;

JFormHelper::loadFieldClass('Modal');

/**
 * Supports a modal article picker.
 */
class JFormFieldQuick_Modal extends JFormFieldModal
{
	/**
	 * The form field type.
	 *
	 * @var        string
	 * @since    1.6
	 */
	protected $type = 'Quick_Modal';

	/**
	 * Property view_list.
	 *
	 * @var  string
	 */
	protected $view_list = 'icons';

	/**
	 * Property view_item.
	 *
	 * @var  string
	 */
	protected $view_item = 'icon';

	/**
	 * Property extension.
	 *
	 * @var  string
	 */
	protected $extension = 'com_akquickicons';

	/**
	 * Method to get the field input markup.
	 *
	 * @return    string    The field input markup.
	 */
	public function getInput()
	{
		// Load the modal behavior script.
		JHtml::_('behavior.modal', 'a.modal');
		JHtmlBehavior::framework(true);
		$this->setElement();

		// Build the script.
		$script   = array();
		$script[] = '	function jSelect' . ucfirst($this->component) . '_' . $this->id . '(id, title) {';
		$script[] = '		document.id("jform_link").value = id;';
		$script[] = '		document.id("jform_link").highlight();';
		$script[] = '		SqueezeBox.close();';
		$script[] = '	}';

		// Add the script to the document head.
		JFactory::getDocument()->addScriptDeclaration(implode("\n", $script));

		// Setup variables for display.
		$html = array();
		$link = $this->getLink();

		$title = $this->getTitle();

		if (empty($title))
		{
			$title = JText::_('COM_' . strtoupper($this->component) . '_SELECT_ITEM');
		}

		$title = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');

		if (JVERSION >= 3)
		{
			// The current user display field.
			$html[] = '<span class="">';
			$html[] = '<a class="modal btn" title="' . JText::_('COM_' . strtoupper($this->component) . '_CHANGE_ITEM_BUTTON') . '"  href="' . $link . '&amp;' . JSession::getFormToken() . '=1" rel="{handler: \'iframe\', size: {x: 800, y: 450}}"><i class="icon-file"></i> ' . JText::_('JSELECT') . '</a>';
			$html[] = '</span>';
		}
		else
		{

			// The user select button.
			$html[] = '<div class="button2-left">';
			$html[] = '  <div class="blank">';
			$html[] = '	<a class="modal" title="' . JText::_('COM_' . strtoupper($this->component) . '_CHANGE_ITEM') . '"  href="' . $link . '&amp;' . JSession::getFormToken() . '=1" rel="{handler: \'iframe\', size: {x: 800, y: 450}}">' . JText::_('JSELECT') . '</a>';
			$html[] = '  </div>';
			$html[] = '</div>';
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

		return implode("\n", $html);
	}

	/**
	 * getLink
	 *
	 * @return  string
	 */
	public function getLink()
	{
		$input = JFactory::getApplication()->input;

		// Avoid self
		$id     = $input->get('id');
		$option = $input->get('option');
		$view   = $input->get('view');
		$layout = $input->get('layout');

		$select = \Windwalker\Helper\XmlHelper::get($this->element, 'select');

		return 'index.php?option=' . $this->extension . '&view=' . $select . '&layout=modal&tmpl=component&function=jSelect' . ucfirst($this->component) . '_' . $this->id;
	}
}