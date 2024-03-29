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

JFormHelper::loadFieldClass('category');

/**
 * Form Field class for Category & quickadd.
 *
 * @package     Windwalker.Framework
 * @subpackage  Form
 */
class JFormFieldCategoryadd extends JFormFieldCategory
{
	/**
	 * The form field type.
	 *
	 * @var        string
	 */
	public $type = 'Categoryadd';

	/**
	 * List name.
	 *
	 * @var string
	 */
	protected $view_list = 'categories';

	/**
	 * Item name.
	 *
	 * @var string
	 */
	protected $view_item = 'category';

	/**
	 * Component name without ext type, eg: content.
	 *
	 * @var string
	 */
	protected $component = 'categories';

	/**
	 * Method to get the field input markup for a generic list.
	 * Use the multiple attribute to enable multiselect.
	 *
	 * @return  string  The field input markup.
	 * @since   11.1
	 */
	protected function getInput()
	{
		return parent::getInput() . $this->quickadd();
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
		$formpath         = AKPATH_FORM . "/forms/quickadd/category.xml";
		$quickadd_handler = $this->getElement('quickadd_handler', JRequest::getVar('option'));
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
		$config['cat_extension']    = (string) $this->element['extension'];
		$config['extension']        = 'com_' . $this->component;
		$config['component']        = $this->component;
		$config['table']            = $table_name;
		$config['model_name']       = 'category';
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
		AKHelper::_('lang.loadLanguage', 'com_' . $this->component, null);

		$formpath = str_replace(JPATH_ROOT, '', $formpath);
		$content  = AKHelper::_('ui.getQuickaddForm', $qid, $formpath, (string) $this->element['extension']);

		// Prepare HTML
		$html         = '';
		$button_title = $title;
		$modal_title  = $button_title;
		$button_class = 'btn btn-small btn-success delicious green light fltlft quickadd_button';

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