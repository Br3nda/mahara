<?php
/**
 * Mahara: Electronic portfolio, weblog, resume builder and social networking
 * Copyright (C) 2006-2007 Catalyst IT Ltd (http://www.catalyst.net.nz)
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
 * @subpackage core
 * @author     Clare Lenihan <clare@catalyst.net.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2006,2007 Catalyst IT Ltd http://catalyst.net.nz
 *
 */

define('INTERNAL', 1);
define('MENUITEM', 'groups/findfriends');
require(dirname(dirname(__FILE__)) . '/init.php');
require_once('pieforms/pieform.php');

$id = param_integer('id');

if (is_friend($id, $USER->get('id'))
    || get_friend_request($id, $USER->get('id'))
    || get_account_preference($id, 'friendscontrol') != 'auth'
    || $id == $USER->get('id')) {
    throw new AccessDeniedException(get_string('cantrequestfriendship', 'group'));
}

define('TITLE', get_string('sendfriendshiprequest', 'group', display_name($id)));

$form = pieform(array(
    'name' => 'requestfriendship',
    'autofocus' => false,
    'elements' => array(
        'reason' => array(
            'type'  => 'textarea',
            'title' => get_string('reason'),
            'cols'  => 50,
            'rows'  => 4,       
        ),
        'submit' => array(
            'type' => 'submitcancel',
            'value' => array(get_string('yes'), get_string('no'))
        )
    )
));

$smarty = smarty();
$smarty->assign('heading', TITLE);
$smarty->assign('form', $form);
$smarty->display('user/requestfriendship.tpl');

function requestfriendship_submit(Pieform $form, $values) {
    global $USER, $SESSION, $id;
    
    $loggedinid = $USER->get('id');
    $user = get_record('usr', 'id', $id);

    // friend db record
    $f = new StdClass;
    $f->ctime = db_format_timestamp(time());
    
    // notification info
    $n = new StdClass;
    $n->url = get_config('wwwroot') . 'user/view.php?id=' . $loggedinid;
    $n->users = array($user->id);
    $lang = get_user_language($user->id);
    $displayname = display_name($USER, $user);

    $f->owner     = $id;
    $f->requester = $loggedinid;
    $f->reason    = $values['reason'];
    insert_record('usr_friend_request', $f);
    $n->subject = get_string_from_language($lang, 'requestedfriendlistsubject', 'group');
    if (isset($values['reason']) && !empty($values['reason'])) {
        $n->message = get_string_from_language($lang, 'requestedfriendlistmessagereason', 'group', $displayname) . $values['reason'];
    }
    else {
        $n->message = get_string_from_language($lang, 'requestedfriendlistmessage', 'group', $displayname);
    }
    activity_occurred('maharamessage', $n);

    $SESSION->add_ok_msg(get_string('friendformrequestsuccess', 'group', display_name($id)));
    switch (param_alpha('returnto', 'myfriends')) {
        case 'find':
            redirect('/user/find.php');
            break;
        case 'view':
            redirect('/user/view.php?id=' . $id);
            break;
        default:
            redirect('/user/myfriends.php');
            break;
    }
}

?>