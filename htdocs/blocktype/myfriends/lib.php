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
 * @subpackage blocktype-myfriends
 * @author     Catalyst IT Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2006-2008 Catalyst IT Ltd http://catalyst.net.nz
 *
 */

defined('INTERNAL') || die();

class PluginBlocktypeMyfriends extends SystemBlocktype {

    public static function get_title() {
        return get_string('title', 'blocktype.myfriends');
    }

    public static function get_description() {
        return get_string('description', 'blocktype.myfriends');
    }

    public static function single_only() {
        return true;
    }

    public static function get_categories() {
        return array('internal');
    }

    public static function get_viewtypes() {
        return array('profile');
    }

    public static function render_instance(BlockInstance $instance, $editing=false) {
        $userid = $instance->get_view()->get('owner');
        $smarty = smarty_core();
        $records = get_records_sql_array('SELECT usr1, usr2 FROM {usr_friend}
            JOIN {usr} u1 ON (u1.id = usr1 AND u1.deleted = 0)
            JOIN {usr} u2 ON (u2.id = usr2 AND u2.deleted = 0)
            WHERE usr1 = ? OR usr2 = ?
            ORDER BY ' . db_random() . '
            LIMIT ?',
            array($userid, $userid, 16)
        );
        $numberoffriends = count_records_sql('SELECT COUNT(usr1) FROM {usr_friend}
            JOIN {usr} u1 ON (u1.id = usr1 AND u1.deleted = 0)
            JOIN {usr} u2 ON (u2.id = usr2 AND u2.deleted = 0)
            WHERE usr1 = ? OR usr2 = ?',
            array($userid, $userid)
        );
        if ($numberoffriends > 16) {
            $friendsmessage = get_string('numberoffriends', 'group', $records ? count($records) : 0, $numberoffriends);
        }
        else {
            $friendsmessage = get_string('Friends', 'group');
        }
        // get the friends into a 4x4 array
        if ($records) {
            $friends = array();
            for ($i = 0; $i < 4; $i++) {
                $friends[$i] = array();
                for($j = 4 * $i; $j < ($i + 1 ) * 4; $j++) {
                    if (isset($records[$j])) {
                        if ($records[$j]->usr1 == $userid) {
                            $friends[$i][] = $records[$j]->usr2;
                        }
                        else {
                            $friends[$i][] = $records[$j]->usr1;
                        }
                    }
                }
            }
        }
        else {
            $friends = false;
        }
        $smarty->assign('friends', $friends);
        $smarty->assign('friendsmessage', $friendsmessage);
        return $smarty->fetch('blocktype:myfriends:myfriends.tpl');
    }

    public static function has_instance_config() {
        return false;
    }
}

?>