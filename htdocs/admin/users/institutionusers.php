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
 * @subpackage admin
 * @author     Nigel McNie <nigel@catalyst.net.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2006,2007 Catalyst IT Ltd http://catalyst.net.nz
 *
 */

// NOTE: This script is VERY SIMILAR to the adminusers.php script, a bug fixed
// here might need to be fixed there too.
define('INTERNAL', 1);
define('INSTITUTIONALADMIN', 1);
require(dirname(dirname(dirname(__FILE__))) . '/init.php');
define('TITLE', get_string('adminusers', 'admin'));
define('SECTION_PLUGINTYPE', 'core');
define('SECTION_PLUGINNAME', 'admin');
define('SECTION_PAGE', 'institutionusers');
define('MENUITEM', 'manageinstitutions/institutionusers');
require_once('pieforms/pieform.php');
require_once('institution.php');
$institutionelement = get_institution_selector(false);
$smarty = smarty();
if (empty($institutionelement)) {
    $smarty->display('admin/users/noinstitutions.tpl');
    exit;
}

$institution = param_alphanum('institution', false);
if (!$institution || !$USER->can_edit_institution($institution)) {
    $institution = empty($institutionelement['value']) ? $institutionelement['defaultvalue'] : $institutionelement['value'];
}
else if (!empty($institution)) {
    $institutionelement['defaultvalue'] = $institution;
}

// Show either requesters, members, or nonmembers on the left hand side
$usertype = param_alpha('usertype', 'requesters');

$usertypeselector = pieform(array(
    'name' => 'usertypeselect',
    'elements' => array(
        'usertype' => array(
            'type' => 'select',
            'title' => get_string('userstodisplay', 'admin'),
            'options' => array(
                'requesters' => get_string('institutionusersrequesters', 'admin'),
                'nonmembers' => get_string('institutionusersnonmembers', 'admin'),
                'members' => get_string('institutionusersmembers', 'admin'),
             ),
            'defaultvalue' => $usertype
        ),
        'institution' => $institutionelement,
    )
));

if ($usertype == 'requesters') {
    // LHS shows users who have requested membership, RHS shows users to be added
    $userlistelement = array(
        'title' => get_string('addnewmembers', 'admin'),
        'lefttitle' => get_string('usersrequested', 'admin'),
        'righttitle' => get_string('userstobeadded', 'admin'),
        'searchparams' => array('requested' => 1),
    );
    $submittext = get_string('addmembers', 'admin');
} else if ($usertype == 'members') {
    // LHS shows institution members, RHS shows users to be removed
    $userlistelement = array(
        'title' => get_string('removeusersfrominstitution', 'admin'),
        'lefttitle' => get_string('currentmembers', 'admin'),
        'righttitle' => get_string('userstoberemoved', 'admin'),
        'searchparams' => array('member' => 1),
    );
    $submittext = get_string('removeusers', 'admin');
} else { // $usertype == nonmembers
    // Behaviour depends on whether we allow users to have > 1 institution
    // LHS either shows all nonmembers or just users with no institution
    // RHS shows users to be invited
    $userlistelement = array(
        'title' => get_string('inviteuserstojoin', 'admin'),
        'lefttitle' => get_string('Non-members', 'admin'),
        'righttitle' => get_string('userstobeinvited', 'admin'),
        'searchparams' => array('member' => 0, 'invitedby' => 0, 'requested' => 0)
    );
    $submittext = get_string('inviteusers', 'admin');
}

$userlistelement['type'] = 'userlist';
$userlistelement['filter'] = false;
$userlistelement['searchscript'] = 'admin/users/userinstitutionsearch.json.php';
$userlistelement['defaultvalue'] = array();
$userlistelement['searchparams']['limit'] = 100;
$userlistelement['searchparams']['query'] = '';
$userlistelement['searchparams']['institution'] = $institution;

$userlistform = pieform(array(
    'name' => 'institutionusers',
    'elements' => array(
        'users' => $userlistelement,
        'usertype' => array(
            'type' => 'hidden',
            'value' => $usertype,
            'rules' => array('regex' => '/^[a-z]+$/')
        ),
        'institution' => array(
            'type' => 'hidden',
            'value' => $institution,
            'rules' => array('regex' => '/^[a-zA-Z0-9]+$/')
        ),
        'submit' => array(
            'type' => 'submit',
            'value' => $submittext
        )
    )
));

function institutionusers_submit(Pieform $form, $values) {
    global $SESSION, $USER;

    $inst = $values['institution'];
    $url = '/admin/users/institutionusers.php?usertype=' . $values['usertype'] . '&institution=' . $inst;
    if (empty($inst) || !$USER->can_edit_institution($inst)) {
        $SESSION->add_error_msg(get_string('notadminforinstitution', 'admin'));
        redirect($url);
    }

    $dataerror = false;
    if (!in_array($values['usertype'], array('requesters', 'members', 'nonmembers'))
        || !is_array($values['users'])) {
        $dataerror = true;
    } else {
        foreach ($values['users'] as $id) {
            if (!is_numeric($id)) {
                $dataerror = true;
                break;
            }
        }
    }
    if ($dataerror) {
        $SESSION->add_error_msg(get_string('errorupdatinginstitutionusers', 'admin'));
        redirect($url);
    }

    $institution = new Institution($values['institution']);
    $maxusers = $institution->maxuseraccounts;
    if (!empty($maxusers)) {
        $members = $institution->countMembers();
        if ($values['usertype'] == 'requesters' && $members + count($values['users']) >= $maxusers) {
            $SESSION->add_error_msg(get_string('institutionuserserrortoomanyusers', 'admin'));
            redirect($url);
        }
        if ($values['usertype'] == 'nonmembers' 
            && $members + $institution->countInvites() + count($values['users']) >= $maxusers) {
            $SESSION->add_error_msg(get_string('institutionuserserrortoomanyinvites', 'admin'));
            redirect($url);
        }
    }
    db_begin();
    if ($values['usertype'] == 'members') {
        $institution->removeMembers($values['users']);
    } else {
        $update = $values['usertype'] == 'requesters' ? 'addUserAsMember' : 'inviteUser';
        foreach ($values['users'] as $id) {
            $institution->{$update}($id);
        }
    }
    db_commit();
    $SESSION->add_ok_msg(get_string('institutionusersupdated'.$values['usertype'], 'admin'));
    if (!$USER->get('admin') && !$USER->is_institutional_admin()) {
        redirect(get_config('wwwroot'));
    }
    redirect($url);
}

$wwwroot = get_config('wwwroot');
$js = <<< EOF
function reloadUsers() {
    var inst = '';
    if ($('usertypeselect_institution')) {
        inst = '&institution=' + $('usertypeselect_institution').value;
    }
    window.location.href = '{$wwwroot}admin/users/institutionusers.php?usertype='+$('usertypeselect_usertype').value+inst;
}
addLoadEvent(function() {
    connect($('usertypeselect_usertype'), 'onchange', reloadUsers);
    if ($('usertypeselect_institution')) {
        connect($('usertypeselect_institution'), 'onchange', reloadUsers);
    }
});
EOF;

$smarty->assign('INLINEJAVASCRIPT', $js);
$smarty->assign('usertypeselector', $usertypeselector);
$smarty->assign('institutionusersform', $userlistform);
$smarty->display('admin/users/institutionusers.tpl');

?>