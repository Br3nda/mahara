<?php
/**
 * Mahara: Electronic portfolio, weblog, resume builder and social networking
 * Copyright (C) 2006-2008 Catalyst IT Ltd (http://www.catalyst.net.nz)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package    mahara
 * @subpackage form
 * @author     Catalyst IT Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2006-2008 Catalyst IT Ltd http://catalyst.net.nz
 *
 */

require_once 'form/elements/wysiwyg.php';

/**
 * Renders a textarea, but with extra javascript to turn it into a wysiwyg
 * textarea.
 *
 * This version has far less controls - though that is configured in 
 * lib/web.php
 *
 * @param array   $element The element to render
 * @param Pieform $form    The form to render the element for
 * @return string          The HTML for the element
 */
function pieform_element_tinywysiwyg(Pieform $form, $element) {
    return pieform_element_wysiwyg($form, $element);
}

function pieform_element_tinywysiwyg_rule_required(Pieform $form, $value, $element, $check) {
    return pieform_element_wysiwyg_rule_required($form, $value, $element, $check);
}

function pieform_element_tinywysiwyg_get_headdata() {
    global $USER;
    if ($USER->get_account_preference('wysiwyg') || defined('PUBLIC')) {
        return array('tinytinymce');
    }
    return array();
}

function pieform_element_tinywysiwyg_get_value(Pieform $form, $element) {
    return pieform_element_wysiwyg_get_value($form, $element);
}

/**
 * Extension by Mahara. This api function returns the javascript required to 
 * set up the element, assuming the element has been placed in the page using 
 * javascript. This feature is used in the views interface.
 *
 * In theory, this could go upstream to pieforms itself
 *
 * @param Pieform $form     The form
 * @param array   $element  The element
 */
function pieform_element_tinywysiwyg_views_js(Pieform $form, $element) {
    global $USER;
    if ($USER->get_account_preference('wysiwyg') || defined('PUBLIC')) {
        $formname = json_encode($form->get_name());
        $editor = json_encode($form->get_name() . '_' . $element['name']);
        return "\ntinyMCE.idCounter=0;tinyMCE.execCommand('mceAddControl', true, $editor);PieformManager.connect('onsubmit', $formname, tinyMCE.triggerSave);";
    }
    return '';
}

?>
