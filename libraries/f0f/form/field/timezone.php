<?php 
/**
 * @package    FrameworkOnFramework
 * @subpackage form
 * @copyright   Copyright (C) 2010 - 2015 Nicholas K. Dionysopoulos / Akeeba Ltd. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// Protect from unauthorized access
defined('F0F_INCLUDED') or die;

JFormHelper::loadFieldClass('timezone');

/**
 * Form Field class for F0F
 * Supports a generic list of options.
 *
 * @package  FrameworkOnFramework
 * @since    2.0
 */
class F0FFormFieldTimezone extends JFormFieldTimezone implements F0FFormField
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
		$class = $this->element['class'] ? (string) $this->element['class'] : '';

		$selected = F0FFormFieldGroupedlist::getOptionName($this->getOptions(), $this->value);

		if (is_null($selected))
		{
			$selected = array(
				'group'	 => '',
				'item'	 => ''
			);
		}

		return '<span id="' . $this->id . '-group" class="fof-groupedlist-group ' . $class . '>' .
			htmlspecialchars($selected['group'], ENT_COMPAT, 'UTF-8') .
			'</span>' .
			'<span id="' . $this->id . '-item" class="fof-groupedlist-item ' . $class . '>' .
			htmlspecialchars($selected['item'], ENT_COMPAT, 'UTF-8') .
			'</span>';
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
		return $this->getStatic();
	}
}
