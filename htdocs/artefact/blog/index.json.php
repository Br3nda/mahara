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
 * @subpackage artefact-blog
 * @author     Catalyst IT Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2006-2008 Catalyst IT Ltd http://catalyst.net.nz
 *
 */

define('INTERNAL', 1);
define('JSON', 1);

require(dirname(dirname(dirname(__FILE__))) . '/init.php');
safe_require('artefact', 'blog');

$action = param_variable('action', 'list');
$id = param_variable('id', null);

json_headers();

if ($action == 'list') {
    $limit = param_integer('limit', ArtefactTypeBlog::pagination);
    $offset = param_integer('offset', 0);

    list($count, $data) = ArtefactTypeBlog::get_blog_list($USER, $limit, $offset);

    echo json_encode(array(
        'count' => $count,
        'limit' => $limit,
        'offset' => $offset,
        'data' => $data
    ));
}
else if ($action == 'delete') {
    $blog = artefact_instance_from_id($id);
    if ($blog instanceof ArtefactTypeBlog) {
        $blog->check_permission();
        $blog->delete();
        json_reply(false, get_string('blogdeleted', 'artefact.blog'));
    }

    throw new ArtefactNotFoundException(get_string('blogdoesnotexist', 'artefact.blog'));
}

?>
