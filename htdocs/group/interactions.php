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
 * @subpackage group-interactions
 * @author     Penny Leach <penny@catalyst.net.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2006,2007 Catalyst IT Ltd http://catalyst.net.nz
 *
 */

define('INTERNAL', 1);
define('MENUITEM', 'groups');

require(dirname(dirname(__FILE__)) . '/init.php');
require_once(get_config('docroot') . 'interaction/lib.php');

$id = param_integer('id');

if (!$group = get_record('group', 'id', $id)) {
    throw new GroupNotFoundException('groupnotfound', 'group', $id);
}

if (!$group->owner == $USER->get('id')) {
    throw new AccessDeniedException(get_string('notallowedtoeditinteraction', 'group'));
}

define('TITLE', get_string('groupinteractions', 'group'));

$interactiontypes = array_flip(
    array_map(
        create_function('$a', 'return $a->name;'),
        plugins_installed('interaction')
    )
);

if (!$interactions = get_records_select_array('interaction_instance', 
    '"group" = ? AND deleted = ?', array($id, 0), 
    'plugin, ctime', 'id, plugin, title')) {
    $interactions = array();
}
$names = array();
foreach (array_keys($interactiontypes) as $plugin) {
    $names[$plugin] = array(
        'single' => get_string('name', 'interaction.' . $plugin),
        'plural' => get_string('nameplural', 'interaction.' . $plugin)
    );
}

foreach ($interactions as $i) {
    if (!is_array($interactiontypes[$i->plugin])) {
        $interactiontypes[$i->plugin] = array();
    }
    $interactiontypes[$i->plugin][] = $i;
}
$smarty = smarty();
$smarty->assign('group', $group);
$smarty->assign('data', $interactiontypes);
$smarty->assign('pluginnames', $names);
$smarty->assign('heading', TITLE);
$smarty->display('group/interactions.tpl');

?>