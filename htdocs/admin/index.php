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
 * @subpackage admin
 * @author     Catalyst IT Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2006-2008 Catalyst IT Ltd http://catalyst.net.nz
 *
 */

define('INTERNAL', 1);
define('ADMIN', 1);
define('MENUITEM', 'adminhome');
define('SECTION_PLUGINTYPE', 'core');
define('SECTION_PLUGINNAME', 'admin');
define('SECTION_PAGE', 'index');

require(dirname(dirname(__FILE__)).'/init.php');
if (get_config('installed')) {
    define('TITLE', get_string('administration', 'admin'));
}
else {
    define('TITLE', get_string('installation', 'admin'));
}
require(get_config('libroot') . 'upgrade.php');
require(get_config('libroot') . 'registration.php');

$smarty = smarty();

$upgrades = check_upgrades();

if (isset($upgrades['core']) && !empty($upgrades['core']->install)) {
    $smarty->assign('installing', true);
    $smarty->assign('releaseargs', array($upgrades['core']->torelease, $upgrades['core']->to));
    $smarty->display('admin/installgpl.tpl');
    exit;
}

// normal admin page starts here
$smarty->assign('upgrades', $upgrades);

if (!get_config('registration_lastsent')) {
    $register = register_site();
    $smarty->assign('register', $register);
}

$smarty->display('admin/index.tpl');

?>
