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
 * @subpackage blocktype-textbox
 * @author     Nigel McNie <nigel@catalyst.net.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2006,2007 Catalyst IT Ltd http://catalyst.net.nz
 *
 */

defined('INTERNAL') || die();

class PluginBlocktypeTextbox extends PluginBlocktype {

    public static function get_title() {
        return get_string('title', 'blocktype.textbox');
    }

    public static function get_description() {
        return get_string('description', 'blocktype.textbox');
    }

    public static function get_categories() {
        return array('file');
    }

    public static function render_instance(BlockInstance $instance) {
        $configdata = $instance->get('configdata');
        // TODO if wysiwyg off, then format_whitespace, else cleaning needs to be done
        return (isset($configdata['text'])) ? format_whitespace($configdata['text']) : '';
    }

    public static function has_instance_config() {
        return true;
    }

    public static function instance_config_form($instance) {
        log_debug('getting instance config form for ' . $instance->get('id'));
        $configdata = $instance->get('configdata');
        return array(
            'text' => array(
                'type' => 'wysiwyg',
                'title' => 'Text',
                'width' => '100%',
                'height' => '50px',
                'defaultvalue' => $configdata['text'],
            ),
        );
    }

}

?>