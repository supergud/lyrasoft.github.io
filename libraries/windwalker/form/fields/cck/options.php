<?php 
/**
 * @package     Windwalker.Framework
 * @subpackage  Form.CCK
 * @author      Simon Asika <asika32764@gmail.com>
 * @copyright   Copyright (C) 2013 Asikart. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

JForm::addFieldPath(AKPATH_FORM . '/fields');

/**
 * Supports an HTML grid for list option.
 *
 * @package     Windwalker.Framework
 * @subpackage  Form.CCK
 */
class JFormFieldOptions extends JFormField
{
	/**
	 * The form field type.
	 *
	 * @var        string
	 */
	public $type = 'Options';

	public $value;

	public $name;

	/**
	 * Method to get the field input markup.
	 *
	 * @return    string    The field input markup.
	 */
	public function getInput()
	{
		$element = $this->element;
		$doc     = JFactory::getDocument();
		$default = JRequest::getVar('field_default');

		// Is checkbox?
		$checkbox = (string) $this->element['checkbox'];

		if ($checkbox == 'true' || $checkbox == '1')
		{
			$checkbox = true;
			$default  = explode(',', $default);
		}
		else
		{
			$checkbox = false;
		}

		// Set Default Vars
		$vars   = $this->value ? $this->value : array();
		$vars[] = array('text' => '', 'value' => '');

		// Prepare Grid
		include_once AKPATH_HTML . '/grid.php';

		$grid = new AKGrid;

		$grid->setTableOptions(array('class' => 'adminlist table table-striped', 'id' => 'ak-attr-table'));
		$grid->setColumns(array('default', 'value', 'text', 'operate'));

		// Set TH
		$grid->addRow(array('class' => 'row1'));
		$grid->setRowCell('default', JText::_('LIB_WINDWALKER_ATTR_DEFAULT'));
		$grid->setRowCell('value', JText::_('LIB_WINDWALKER_ATTR_VALUE'));
		$grid->setRowCell('text', JText::_('LIB_WINDWALKER_ATTR_TEXT'));
		$grid->setRowCell('operate', JText::_('LIB_WINDWALKER_ATTR_OPERATE'));

		foreach ($vars as $key => $var):
			$checked = '';

			if ($checkbox)
			{
				if (in_array($var['value'], $default))
				{
					$checked = 'checked';
				}
			}
			else
			{
				if ($var['value'] === $default)
				{
					$checked = 'checked';
				}
			}

			//Set Operate buttons
			$add_btn = (JVERSION >= 3)
				? '<a class="ak-delete-option btn" onclick="addOption(this);"><i class="icon-save-new"></i></a>'
				: '<a class="ak-delete-option btn" onclick="addOption(this);"><img src="templates/bluestork/images/admin/icon-16-add.png" alt="delete" /></a>';

			$del_btn = (JVERSION >= 3)
				? '<a class="ak-delete-option btn" onclick="deleteOption(this);"><i class="icon-delete"></i></a>'
				: '<a class="ak-delete-option btn" onclick="deleteOption(this);"><img src="templates/bluestork/images/admin/publish_r.png" alt="delete" /></a>';

			// Set TR
			$grid->addRow(array('class' => 'row' . $key % 2));

			// Set TDs
			if ($checkbox)
			{
				$grid->setRowCell('default', '<input type="checkbox" class="attr-default" id="option-' . $key . '" name="attrs[default][]" value="' . $var['value'] . '" ' . $checked . '/>');
			}
			else
			{
				$grid->setRowCell('default', '<input type="radio" class="attr-default" id="option-' . $key . '" name="attrs[default]" value="' . $var['value'] . '" ' . $checked . '/>');
			}

			$grid->setRowCell('value', '<input type="text" class="attr-value input-medium" name="attrs[options][value][]" value="' . $var['value'] . '" onfocus="addAttrRow(this);" onblur="setDefault(this)" />');
			$grid->setRowCell('text', '<input type="text" class="attr-text input-medium" name="attrs[options][text][]" value="' . $var['text'] . '" onfocus="addAttrRow(this);" />');
			$grid->setRowCell('operate', $add_btn . $del_btn);

			//$html .=  ;
			//$html .= '<input type="text" name="attrs[options]['.$key.'][text]" value="'.$var['text'].'" />' ;
			//$html .= '<input type="text" name="attrs[options]['.$key.'][value]" value="'.$var['text'].'" />' ;
			//$html .= '<div class="clr clearfix"> </div></div>' ;
		endforeach;

		// Set Javascript
		$doc->addScriptDeclaration("\n\n var akfields_num = " . (count($vars) - 1) . ' ;');
		$this->addScript(count($vars) - 1);

		return (string) $grid;
	}

	/**
	 * Add JS to head.
	 */
	public function addScript($num = 0)
	{
		$script = <<<SCRIPT
    var addAttrRow ;
    
    var setAttr = function(tr, i){
        // Set New Element attrs
        tr.getElements('input.attr-default')[0].set( {'id': 'option-'+i , 'value': i, 'num': i, checked: false} );
        tr.getElements('input.attr-value')[0].set( {'name': 'attrs[options][value][]' , 'value': '', 'num': i} );
        tr.getElements('input.attr-text')[0].set( {'name': 'attrs[options][text][]' , 'value': '', 'num': i} );
        tr.set({ 'class': 'row'+(i%2), 'num': i });
        
        return tr ;
    }
    
    window.addEvent('domready', function(){
        var inputs   = $$('#ak-attr-table input');
        var trs      = $$('#ak-attr-table tr');
        var table    = $$('#ak-attr-table tbody')[0] ;
        var num      = trs.length - 2;
        var i        = -1 ;
        
        // Set Number to detect add new or not.
        trs.each( function(e){
            e.set('num', i);
            e.getElements('input').set('num', i);
            i++;
        });
        
        addAttrRow = function(e){
            var n = e.get('num').toInt() ;
            if(( n + 1 ) != i) return ;
            
            var trs    = $$('#ak-attr-table tr');
            var tr     = trs[trs.length - 1].clone();
            
            tr = setAttr(tr, i);
            
            tr.inject(table, 'bottom');
            i++;
        }
        
    });
    
    var addOption = function(e){
        var tr1 = e.getParent('tr') ;
        
        var tr = tr1.clone();
        n = tr.get('num').toInt() ;
        tr = setAttr(tr, n);    
        tr.inject(tr1, 'after');
    }
    
    var deleteOption = function(e){
        e.getParent('tr').destroy();
    }
    
    var setDefault = function(e){
        var v = e.value;
        e.getParent('tr').getElement('input.attr-default').set('value', v);
    }
SCRIPT;

		$doc = JFactory::getDocument();
		$doc->addScriptDeclaration($script);
	}
}
