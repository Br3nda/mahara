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
 * @author     Martyn Smith <martyn@catalyst.net.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2006,2007 Catalyst IT Ltd http://catalyst.net.nz
 *
 */

define('INTERNAL', 1);
define('MENUITEM', 'groups/groupsiown');
require(dirname(dirname(dirname(__FILE__))) . '/init.php');
require_once('pieforms/pieform.php');
define('TITLE', get_string('editgroup'));

$id = param_integer('id');

$group_data = get_record('group', 'id', $id, 'owner', $USER->get('id'));

if (!$group_data) {
    $SESSION->add_error_msg(get_string('canteditdontown'));
    redirect('/contacts/groups/owned.php');
}

$joinoptions = array(
    'invite'     => get_string('membershiptype.invite'),
    'request'    => get_string('membershiptype.request'),
    'open'       => get_string('membershiptype.open'),
);
if ($USER->get('admin') || $USER->get('staff')) {
    $joinoptions['controlled'] = get_string('membershiptype.controlled');
}

$editgroup = pieform(array(
    'name'     => 'editgroup',
    'method'   => 'post',
    'plugintype' => 'core',
    'pluginname' => 'groups',
    'elements' => array(
        'name' => array(
            'type'         => 'text',
            'title'        => get_string('groupname'),
            'rules'        => array( 'required' => true, 'maxlength' => 128 ),
            'defaultvalue' => $group_data->name,
        ),
        'description' => array(
            'type'         => 'wysiwyg',
            'title'        => get_string('groupdescription'),
            'rows'         => 10,
            'cols'         => 70,
            'defaultvalue' => $group_data->description,
        ),
        'membershiptype' => array(
            'type'         => 'select',
            'title'        => get_string('membershiptype'),
            'options'      => $joinoptions,
            'defaultvalue' => $group_data->jointype,
            'help'         => true,
        ),
        'id'          => array(
            'type'         => 'hidden',
            'value'        => $id,
        ),
        'submit'   => array(
            'type'  => 'submitcancel',
            'value' => array(get_string('savegroup'), get_string('cancel')),
        ),
    ),
));

function editgroup_validate(Pieform $form, $values) {
    global $USER;
    global $SESSION;

    $cid = get_field('group', 'id', 'owner', $USER->get('id'), 'name', $values['name']);

    if ($cid && $cid != $values['id']) {
        $form->set_error('name', get_string('groupalreadyexists'));
    }
}

function editgroup_cancel_submit() {
    redirect('/contacts/groups/owned.php');
}

function editgroup_submit(Pieform $form, $values) {
    global $USER;
    global $SESSION;

    db_begin();

    $now = db_format_timestamp(time());

    update_record(
        'group',
        (object) array(
            'id'             => $values['id'],
            'name'           => $values['name'],
            'description'    => $values['description'],
            'jointype'       => $values['membershiptype'],
            'mtime'          => $now,
        ),
        'id'
    );

    $SESSION->add_ok_msg(get_string('groupsaved'));

    db_commit();

    redirect('/contacts/groups/owned.php');
}

$smarty = smarty();

$smarty->assign('editgroup', $editgroup);

$smarty->display('contacts/groups/edit.tpl');

?>