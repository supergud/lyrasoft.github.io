<?php 

/**
 * @package   	JCE
 * @copyright 	Copyright (c) 2009-2015 Ryan Demmer. All rights reserved.
 * @license   	GNU/GPL 2 or later - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * JCE is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */
class WFFontsizeselectPluginConfig {

    public static function getConfig(&$settings) {
        $wf = WFEditor::getInstance();

        $settings['fontsizeselect_font_sizes'] = $wf->getParam('fontsizeselect.font_sizes', '8pt,10pt,12pt,14pt,18pt,24pt,36pt', '8pt,10pt,12pt,14pt,18pt,24pt,36pt');
    }
}

?>
