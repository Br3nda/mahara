<?php
/**
 * This program is part of Mahara
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
 * @package    mahara
 * @subpackage core
 * @author     Penny Leach <penny@catalyst.net.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2006,2007 Catalyst IT Ltd http://catalyst.net.nz
 *
 */

defined('INTERNAL') || die();


/**
 * is a user allowed to leave a group? 
 * checks if they're the owner and the membership type
 *
 * @param object $group (corresponds to db record). if an id is given, record will be fetched.
 * @param int $userid (optional, will default to logged in user)
 */
function group_user_can_leave($group, $userid=null) {

    $userid = optional_userid($userid);
    
    if (is_numeric($group)) {
        if (!$group = get_record('group', 'id', $group)) {
            return false;
        }
    }
    
    if ($group->owner == $userid) {
        return false;
    }
    
    if ($group->jointype == 'controlled') {
        return false;
    }
    return true;
}

/**
 * removes a user from a group
 *
 * @param int $group id of group
 * @param int $user id of user to remove
 */
function group_remove_user($group, $userid) {    
    db_begin();
    delete_records('group_member', 'group', $group, 'member', $userid);
    delete_records('usr_watchlist_group', 'group', $group, 'usr', $userid);
    db_commit();
}

/**
 * all groups the user is a member of
 * 
 * @param int userid (optional, defaults to $USER id) 
 * @return array of group db rows
 */
function get_member_groups($userid=0, $offset=0, $limit=0) {

    $userid = optional_userid($userid);

    return get_records_sql_array('SELECT g.id, g.name, g.description, g.jointype, g.owner, g.ctime, g.mtime, gm.ctime, gm.tutor, COUNT(v.view) AS hasviews
              FROM {group} g 
              JOIN {group_member} gm ON gm.group = g.id
              LEFT JOIN {view_access_group} v ON v.group = g.id
              WHERE g.owner != ? AND gm.member = ?
              GROUP BY 1, 2, 3, 4, 5, 6, 7, 8, 9', array($userid, $userid), $offset, $limit);
}


/**
 * all groups the user owns
 * 
 * @param int userid (optional, defaults to $USER id) 
 * @param string $jointype (optional), will filter by jointype.
 * @return array of group db rows
 */
function get_owned_groups($userid=0, $jointype=null) {

    $userid = optional_userid($userid);

    $sql = 'SELECT g.* FROM {group} g 
             WHERE g.owner = ?';
    $values = array($userid);

    if (!empty($jointype)) {
        $sql .= ' AND jointype = ?';
        $values[] = $jointype;
    }
       
    return get_records_sql_array($sql, $values);
}

/**
 * all groups the user has pending invites to
 * 
 * @param int userid (optional, defaults to $USER id)
 * @return array of group db rows
 */
function get_invited_groups($userid=0) {

    $userid = optional_userid($userid);

    return get_records_sql_array('SELECT g.*, gmi.ctime, gmi.reason
             FROM {group} g 
             JOIN {group_member_invite} gmi ON gmi.group = g.id
             WHERE gmi.member = ?)', array($userid));
}

/**
 * all groups the user has pending requests for 
 * 
 * @param int $userid (optional, defaults to $USER id)
 * @return array of group db rows
 */

function get_requested_group($userid=0) {

    $userid = optional_userid($userid);

    return get_records_sql_array('SELECT g.*, gmr.ctime, gmr.reason 
              FROM {group} g 
              JOIN {group_member_request} gmr ON gmr.group = g.id
              WHERE gmr.member = ?', array($userid));
}

/**
 * all groups this user is associated with at all
 * either member, invited or requested.
 * 
 * @param int $userid (optional, defaults to $USER id)
 * @return array of group db rows (with type=member|invite|request)
 */

function get_associated_groups($userid=0) {

    $userid = optional_userid($userid);
    
    $sql = "SELECT g.*, a.type FROM {group} g JOIN (
    SELECT gm.group, 'invite' AS type
        FROM {group_member_invite} gm WHERE gm.member = ?
    UNION 
    SELECT gm.group, 'request' AS type
        FROM {group_member_request} gm WHERE gm.member = ?
    UNION 	
    SELECT gm.group, 'member' AS type
        FROM {group_member} gm WHERE gm.member = ?
    ) AS a ON a.group = g.id";
    
    return get_records_sql_assoc($sql, array($userid, $userid, $userid));
}


/**
 * gets groups the user is a tutor in, or the user owns
 * 
 * @param int $userid (optional, defaults to $USER id)
 * @param string $jointype (optional, will filter by jointype
 */
function get_tutor_groups($userid=0, $jointype=null) {

    $userid = optional_userid($userid);

    $sql = 'SELECT DISTINCT g.*, gm.ctime
              FROM {group} g 
              LEFT JOIN {group_member} gm ON gm.group = g.id
              WHERE (g.owner = ? OR (gm.member = ? AND gm.tutor = ?))';
    $values = array($userid, $userid, 1);
    
    if (!empty($jointype)) {
        $sql .= ' AND g.jointype = ? ';
        $values[] = $jointype;
    }
    return get_records_sql_array($sql, $values);
}


// constants for group membership type
define('GROUP_MEMBERSHIP_ADMIN', 1);
define('GROUP_MEMBERSHIP_STAFF', 2);
define('GROUP_MEMBERSHIP_OWNER', 4);
define('GROUP_MEMBERSHIP_TUTOR', 8);
define('GROUP_MEMBERSHIP_MEMBER', 16);


/**
 * Can a user access a given group?
 * 
 * @param mixed $group id of group or db record (object)
 * @param mixed $user optional (object or id), defaults to logged in user
 *
 * @returns constant access level or FALSE
 */
function user_can_access_group($group, $user=null) {

    if (empty($userid)) {
        global $USER;
        $user = $USER;
    }
    else if (is_int($user)) {
        $user = get_user($user);
    }
    else if (is_object($user) && !$user instanceof User) {
        $user = get_user($user->get('id'));
    }

    if (!$user instanceof User) {
        throw new InvalidArgumentException("not useful user arg given to user_can_access_group: $user");
    }

    if (is_int($group)) {
        $group = get_record('group', 'id', $group);
    }

    if (!is_object($group)) {
        throw new InvalidArgumentException("not useful group arg given to user_can_access_group: $group");
    }

    $membertypes = 0;

    if ($user->get('admin')) {
        $membertypes = GROUP_MEMBERSHIP_ADMIN;
    }
    if ($user->get('staff')) {
        $membertypes = $membertypes | GROUP_MEMBERSHIP_STAFF;
    }
    if ($group->owner == $user->get('id')) {
        $membertypes = $membertypes | GROUP_MEMBERSHIP_OWNER;
    }

    if (!$membership = get_record('group_member', 'group', $group->id, 'member', $user->get('id'))) {
        return $membertypes;
    }

    if ($membership->tutor) {
        $membertypes = $membertypes | GROUP_MEMBERSHIP_TUTOR;
    }
    
    return ($membertypes | GROUP_MEMBERSHIP_MEMBER);
}


/**
 * helper function to remove a user from a group
 * also deletes the group from their watchlist, 
 * and deletes any views they can only access through this group
 * from their watchlist. 
 *
 * @param int $groupid
 * @param int $userid
 */
function group_remove_member($groupid, $userid) {

    delete_records('usr_watchlist_group', 'usr', $userid, 'group', $groupid);
    // get all the views in this user's watchlist associated with this group.
    $views = get_column_sql('SELECT v.id 
                             FROM {view} v JOIN {usr_watchlist_view} va on va.view = v.id
                             JOIN {view_access_group} g ON g.view = v.id');
    // @todo this is probably a retarded way to do it and should be changed later.
    foreach ($views as $view) {
        db_begin();
        delete_records('usr_watchlist_view', 'view', $view, 'usr', $userid);
        if (can_view_view($view, $userid)) {
            db_rollback();
        }
        else {
            db_commit();
        }
    }
    delete_records('group_member', 'member', $userid, 'group', $groupid);
    $user = optional_userobj($userid);
    activity_occurred('watchlist', 
                      array('group' => $groupid, 
                            'subject' => get_string('removedgroupmembersubj', 'activity', display_default_name($user))));
}

/**
 * function to add a member to a group
 * doesn't do any jointype checking, that should be handled by the caller
 *
 * @param int $groupid
 * @param int $userid
 */
function group_add_member($groupid, $userid) {
    $cm = new StdClass;
    $cm->member = $userid;
    $cm->group = $groupid;
    $cm->ctime =  db_format_timestamp(time());
    $cm->tutor = 0;
    insert_record('group_member', $cm);
    $user = optional_userobj($userid);
    activity_occurred('watchlist', 
                      array('group' => $groupid, 
                            'subject' => get_string('newgroupmembersubj', 'activity', display_default_name($user))));
}

?>