<?php 
/**
 * @package    FrameworkOnFramework
 * @subpackage form
 * @copyright   Copyright (C) 2010 - 2015 Nicholas K. Dionysopoulos / Akeeba Ltd. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// Protect from unauthorized access
defined('F0F_INCLUDED') or die;

JFormHelper::loadFieldClass('checkboxes');

/**
 * Form Field class for F0F
 * Supports a list of checkbox.
 *
 * @package  FrameworkOnFramework
 * @since    2.0
 */
class F0FFormFieldCheckboxes extends JFormFieldCheckboxes implements F0FFormField
{
	protected $static;

	protected $repeatable;

	/** @var   F0FTable  The item being rendered in a repeatable form field */
	public $item;

	/** @var int A monotonically increasing number, denoting the row number in a repeatable view */
	public $rowid;

	/**
	 * Method to get certain otherwise inaccessible properties from the form field object.
	 *
	 * @param   string  $name  The property name for which to the the value.
	 *
	 * @return  mixed  The property value or null.
	 *
	 * @since   2.0
	 */
	public function __get($name)
	{
		switch ($name)
		{
			case 'static':
				if (empty($this->static))
				{
					$this->static = $this->getStatic();
				}

				return $this->static;
				break;

			case 'repeatable':
				if (empty($this->repeatable))
				{
					$this->repeatable = $this->getRepeatable();
				}

				return $this->repeatable;
				break;

			default:
				return parent::__get($name);
		}
	}

	/**
	 * Get the rendering of this field type for static display, e.g. in a single
	 * item view (typically a "read" task).
	 *
	 * @since 2.0
	 *
	 * @return  string  The field HTML
	 */
	public function getStatic()
	{
		return $this->getRepeatable();
	}

	/**
	 * Get the rendering of this field type for a repeatable (grid) display,
	 * e.g. in a view listing many item (typically a "browse" task)
	 *
	 * @since 2.0
	 *
	 * @return  string  The field HTML
	 */
	public function getRepeatable()
	{
		$class     = $this->element['class'] ? (string) $this->element['class'] : $this->id;
		$translate = $this->element['translate'] ? (string) $this->element['translate'] : false;

		$html = '<span class="' . $class . '">';
		foreach ($this->value as $value) {

			$html .= '<span>';

			if ($translate == true)
			{
				$html .= JText::_($value);
			}
			else
			{
				$html .= $value;
			}

			$html .= '</span>';
		}
		$html .= '</span>';
	}

	/**
	 * Get the rendering of this field type for static display, e.g. in a single
	 * item view (typically a "read" task).
	 *
	 * @since 2.0
	 *
	 * @return  string  The field HTML
	 */
	public function getInput()
	{
		// Used for J! 2.5 compatibility
		$this->value = !is_array($this->value) ? explode(',', $this->value) : $this->value;

		return parent::getInput();
	}
}
