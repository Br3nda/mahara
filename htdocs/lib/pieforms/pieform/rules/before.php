<?php
/**
 * This program is part of Pieforms
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301 USA
 *
 * @package    pieform
 * @subpackage rule
 * @author     Nigel McNie <nigel@catalyst.net.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2006 Catalyst IT Ltd http://catalyst.net.nz
 *
 */

/**
 * Checks whether the given element's value is less than another element.
 *
 * Typically useful for dates.
 *
 * @param Pieform $form      The form the rule is being applied to
 * @param string  $value     The value to check
 * @param array   $element   The element to check
 * @param string  $otherelement The other element to check for
 * @return string            The error message, if the value is invalid.
 */
function pieform_rule_before(Pieform $form, $value, $element, $otherelement) {
    $otherelement = $form->get_element($otherelement);
    $othervalue   = $form->get_value($otherelement);
    if ($value != '' && $othervalue != '' && intval($value) > intval($othervalue)) {
        return sprintf($form->i18n('rule', 'before', 'before', $element), $otherelement['title']);
    }
}

function pieform_rule_before_i18n() {
    return array(
        'en.utf8' => array(
            'before' => 'This can not be after the field "%s"'
        ),
        'de.utf8' => array(
            'before' => 'Dies kann nicht nach dem Feld "%s" sein'
        ),
        'fr.utf8' => array(
            'before' => 'Ceci ne peut pas être placé après le champ "%s"'
        ),
    );
}

?>