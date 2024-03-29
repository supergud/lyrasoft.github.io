<?php 
/**
 * @package     Windwalker.Framework
 * @subpackage  Layouts
 *
 * @author      Simon Asika <asika32764@gmail.com>
 * @copyright   Copyright (C) 2013 Asikart. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

if(JVERSION >= 3){
    $list     = JHtmlSidebar::getEntries();
}else{
    include_once JPATH_ADMINISTRATOR.'/modules/mod_submenu/helper.php' ;
    $list    = modSubMenuHelper::getItems();
}

$displayMenu    = count($list);

$hide = JFactory::getApplication()->input->getBool('hidemainmenu');
if($hide){
    $displayMenu = false ;
}
?>

<?php if( JVERSION >= 3 ): ?>
<div id="ak-submenu">
    <?php if ($displayMenu) : ?>
    <ul id="submenu" class="nav nav-tabs">
        <?php foreach ($list as $item) : ?>
        <?php if (isset ($item[2]) && $item[2] == 1) :
            ?><li class="active"><?php
        else :
            ?><li><?php
        endif;
        ?>
        <?php
        if ($hide) :
                ?><a class="nolink"><?php echo $item[0]; ?><?php
        else :
            if(strlen($item[1])) :
                ?><a href="<?php echo JFilterOutput::ampReplace($item[1]); ?>"><?php echo $item[0]; ?></a><?php
            else :
                ?><?php echo $item[0]; ?><?php
            endif;
        endif;
        ?>
        </li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>
</div>
<?php endif; ?>


<div id="ak-panel-wrap">
    <?php if( JVERSION >= 3 ): ?>

    <div class="subhead-collapse">
        <div class="subhead">
            <div class="container-fluid">
                <div id="container-collapse" class="container-collapse"></div>
                <div class="row-fluid">
                    <div class="span12">
                        <?php echo JToolBar::getInstance('toolbar')->render('toolbar') ; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php else: ?>

    <div id="toolbar-box" class="ak-toolbar m">
        <div id="ref-title" class="fltlft">
            <?php echo JFactory::getApplication()->JComponentTitle; ?>
        </div>

        <?php echo JToolBar::getInstance('toolbar')->render('toolbar') ; ?>

        <div class="clr"></div>
    </div>

    <?php endif; ?>



<?php if( JVERSION < 3 && $displayMenu): ?>
<div id="submenu-box">
    <div class="m">
        <ul id="submenu">
            <?php foreach ($list as $item) : ?>
            <li>
            <?php
            if ($hide) :
                if (isset ($item[2]) && $item[2] == 1) :
                    ?><span class="nolink active"><?php echo $item[0]; ?></span><?php
                else :
                    ?><span class="nolink"><?php echo $item[0]; ?></span><?php
                endif;
            else :
                if(strlen($item[1])) :
                    if (isset ($item[2]) && $item[2] == 1) :
                        ?><a class="active" href="<?php echo JFilterOutput::ampReplace($item[1]); ?>"><?php echo $item[0]; ?></a><?php
                    else :
                        ?><a href="<?php echo JFilterOutput::ampReplace($item[1]); ?>"><?php echo $item[0]; ?></a><?php
                    endif;
                else :
                    ?><?php echo $item[0]; ?><?php
                endif;
            endif;
            ?>
            </li>
            <?php endforeach; ?>
        </ul>
        <div class="clr"></div>
    </div>
</div>
<?php endif; ?>


    <div id="element-box" class="m">
            <?php echo $this->loadInnerTemplate();?>
    </div>
</div>



