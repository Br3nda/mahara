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
 * @subpackage grouptype-course
 * @author     Catalyst IT Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2006-2008 Catalyst IT Ltd http://catalyst.net.nz
 *
 */

defined('INTERNAL') || die();

class PluginGrouptypeCourse extends PluginGrouptype {

    public static function postinst($prevversion) {
        if ($prevversion == 0) {
            parent::installgrouptype('GroupTypeCourse');
        }
    }

}

class GroupTypeCourse extends GroupType {

    public static function allowed_join_types() {
        return array('controlled', 'request');
    }

    public static function can_be_created_by_user() {
        global $USER;
        return $USER->get('admin') || $USER->get('staff') || $USER->is_institutional_admin()
            || $USER->is_institutional_staff();
    }

    public static function get_roles() {
        return array('member', 'tutor', 'admin');
    }

    public static function get_view_editing_roles() {
        return array('tutor', 'admin');
    }

    public static function get_view_assessing_roles() {
        return array('tutor', 'admin');
    }

    public static function default_role() {
        return 'member';
    }

    public static function default_artefact_rolepermissions() {
        return array(
            'member' => (object) array('view' => true, 'edit' => false, 'republish' => false),
            'tutor'  => (object) array('view' => true, 'edit' => true, 'republish' => true),
            'admin'  => (object) array('view' => true, 'edit' => true, 'republish' => true),
        );
    }
}

?>
