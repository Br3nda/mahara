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
 * @subpackage artefact-file
 * @author     Catalyst IT Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2006-2008 Catalyst IT Ltd http://catalyst.net.nz
 *
 */

define('INTERNAL', 1);
define('MENUITEM', 'groups/files');

require(dirname(dirname(dirname(__FILE__))) . '/init.php');
require_once(get_config('libroot') . 'group.php');
safe_require('artefact', 'file');

$javascript = ArtefactTypeFileBase::get_my_files_js(param_integer('folder', null));

define('GROUP', param_integer('group'));
$group = group_current_group();

if (!$role = group_user_access($group->id)) {
    throw new AccessDeniedException();
}
define('TITLE', $group->name . ' - ' . get_string('groupfiles', 'artefact.file'));

require_once(get_config('docroot') . 'interaction/lib.php');

$groupdata = json_encode($group);
$grouproles = json_encode(array_values(group_get_role_info($group->id)));
$defaultperms = group_get_default_artefact_permissions($group->id);
// By default, users can edit files they upload themselves
$defaultperms[$role] = (object) array('view' => true, 'edit' => true, 'republish' => true);
$grouprolepermissions = json_encode($defaultperms);

$javascript .= <<<GROUPJS
var group = {$groupdata};
group.roles = {$grouproles};
group.rolepermissions = {$grouprolepermissions};
browser.setgroup({$group->id});
uploader.setgroup({$group->id});
GROUPJS;

$smarty = smarty(array('tablerenderer', 'artefact/file/js/file.js'));
$smarty->assign('heading', $group->name);
$smarty->assign('INLINEJAVASCRIPT', $javascript);
$smarty->display('artefact:file:index.tpl');

?>