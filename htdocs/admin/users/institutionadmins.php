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
define('SECTION_PAGE', 'adminusers');
require_once('pieforms/pieform.php');
define('MENUITEM', 'manageinstitutions/institutionadmins');
$smarty = smarty();

require_once('institution.php');
$institution = add_institution_selector_to_page(&$smarty, param_alphanum('institution', false), 
                                                get_config('wwwroot') . 'admin/users/institutionadmins.php');

if ($institution === false) {
    $smarty->display('admin/users/noinstitutions.tpl');
    exit;
}

// Get users who are currently admins
$adminusers = get_column('usr_institution', 'usr', 'admin', 1, 'institution', $institution);

$form = array(
    'name' => 'adminusers',
    'elements' => array(
        'users' => array(
            'type' => 'userlist',
            'title' => get_string('adminusers', 'admin'),
            'defaultvalue' => $adminusers,
            'filter' => false,
            'lefttitle' => get_string('institutionmembers', 'admin'),
            'righttitle' => get_string('currentadmins', 'admin'),
            'searchparams' => array('limit' => 100, 'query' => '', 'member' => 1,
                                    'institution' => $institution),
            'searchscript' => 'admin/users/userinstitutionsearch.json.php',
        ),
        'institution' => array(
            'type' => 'hidden',
            'value' => $institution,
        ),
        'submit' => array(
            'type' => 'submit',
            'value' => get_string('submit')
        )
    )
);

function adminusers_submit(Pieform $form, $values) {
    global $SESSION, $USER;

    $inst = $values['institution'];
    if (empty($inst) || !$USER->can_edit_institution($inst)) {
        $SESSION->add_error_msg(get_string('notadminforinstitution', 'admin'));
        redirect('/admin/users/institutionadmins.php');
    }

    db_begin();
    execute_sql('UPDATE {usr_institution}
        SET admin = 0
        WHERE admin = 1 AND institution = ' . db_quote($inst));
    if ($values['users']) {
        execute_sql('UPDATE {usr_institution}
            SET admin = 1
            WHERE usr IN (' . join(',', $values['users']) . ') AND institution = ' . db_quote($inst));
    }
    activity_add_admin_defaults($values['users']);
    db_commit();
    $SESSION->add_ok_msg(get_string('adminusersupdated', 'admin'));
    redirect('/admin/users/institutionadmins.php?institution=' . $inst);
}

$smarty->assign('adminusersform', pieform($form));
$smarty->display('admin/users/institutionadmins.tpl');

?>