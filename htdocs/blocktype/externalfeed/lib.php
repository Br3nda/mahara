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
 * @subpackage blocktype-externalfeed
 * @author     Catalyst IT Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2006-2008 Catalyst IT Ltd http://catalyst.net.nz
 *
 */

defined('INTERNAL') || die();

class PluginBlocktypeExternalfeed extends SystemBlocktype {

    public static function get_title() {
        return get_string('title', 'blocktype.externalfeed');
    }

    public static function get_description() {
        return get_string('description', 'blocktype.externalfeed');
    }

    public static function get_categories() {
        return array('feeds');
    }

    public static function get_viewtypes() {
        return array('portfolio', 'profile');
    }

    public static function render_instance(BlockInstance $instance, $editing=false) {
        $configdata = $instance->get('configdata');
        if ($configdata['feedid']) {
            $data = get_record('blocktype_externalfeed_data', 'id', $configdata['feedid']);

            $data->content = unserialize($data->content);
            $data->image   = unserialize($data->image);

            // Attempt to fix relative URLs in the feeds
            if (!empty($data->image['link'])) {
                $data->description = preg_replace(
                    '/src="(\/[^"]+)"/',
                    'src="' . $data->image['link'] . '$1"',
                    $data->description
                );
                foreach ($data->content as &$entry) {
                    $entry->description = preg_replace(
                        '/src="(\/[^"]+)"/',
                        'src="' . $data->image['link'] . '$1"',
                        $entry->description
                    );
                }
            }

            $smarty = smarty_core();
            $smarty->assign('title', $data->title);
            $smarty->assign('description', $data->description);
            $smarty->assign('url', $data->url);
            // 'full' won't be set for feeds created before 'full' support was added
            $smarty->assign('full', isset($configdata['full']) ? $configdata['full'] : false); 
            $smarty->assign('link', $data->link);
            $smarty->assign('entries', $data->content);
            $smarty->assign('feedimage', self::make_feed_image_tag($data->image));
            $smarty->assign('lastupdated', get_string('lastupdatedon', 'blocktype.externalfeed', format_date(time($data->lastupdate))));
            return $smarty->fetch('blocktype:externalfeed:feed.tpl');
        }
        return '';
    }

    public static function has_instance_config() {
        return true;
    }

    public static function instance_config_form($instance) {
        $configdata = $instance->get('configdata');

        if (isset($configdata['feedid'])) {
            $url = get_field('blocktype_externalfeed_data', 'url', 'id', $configdata['feedid']);
        }
        else {
            $url = '';
        }

        if (isset($configdata['full'])) {
            $full = $configdata['full'];
        }
        else {
            $full = false;
        }

        return array(
            'url' => array(
                'type'  => 'text',
                'title' => get_string('feedlocation', 'blocktype.externalfeed'),
                'description' => get_string('feedlocationdesc', 'blocktype.externalfeed'),
                'width' => '90%',
                'defaultvalue' => $url,
                'rules' => array(
                    'required' => true,
                    'maxlength' => 255, // mysql hack, see install.xml for this plugin
                ),
            ),
            'full' => array(
                'type'         => 'checkbox',
                'title'        => get_string('showfeeditemsinfull', 'blocktype.externalfeed'),
                'description'  => get_string('showfeeditemsinfulldesc', 'blocktype.externalfeed'),
                'defaultvalue' => (bool)$full,
            ),
        );
    }

    /**
     * Optional method. If exists, allows this class to decide the title for 
     * all blockinstances of this type
     */
    public static function get_instance_title(BlockInstance $bi) {
        $configdata = $bi->get('configdata');

        if (!empty($configdata['feedid'])) {
            if ($title = get_field('blocktype_externalfeed_data', 'title', 'id', $configdata['feedid'])) {
                return $title;
            }
        }
        return '';
    }

    public static function instance_config_validate(Pieform $form, $values) {
        if (strpos($values['url'], '://') == false) {
            // try add on http://
            $values['url'] = 'http://' . $values['url'];
        }
        else {
            $proto = substr($values['url'], 0, strpos($values['url'], '://'));
            if (!in_array($proto, array('http', 'https'))) {
                $form->set_error('url', get_string('invalidurl', 'blocktype.externalfeed'));
            }
        }
        if (!$form->get_error('url') && !record_exists('blocktype_externalfeed_data', 'url', $values['url'])) {
            try {
                self::parse_feed($values['url']);
                return;
            }
            catch (XML_Feed_Parser_Exception $e) {
                $form->set_error('url', get_string('invalidfeed', 'blocktype.externalfeed',  $e->getMessage()));
            }
        }
    }

    public static function instance_config_save($values) {
        // we need to turn the feed url into an id in the feed_data table..
        if (strpos($values['url'], '://') == false) {
            // try add on http://
            $values['url'] = 'http://' . $values['url'];
        }
        if ($exists = get_record('blocktype_externalfeed_data', 'url', $values['url'])) {
            $values['feedid'] = $exists->id;
            unset($values['url']);
            return $values;
        }
        // We know this is safe because self::parse_feed caches its result and 
        // the validate method would have failed if the feed was invalid
        $data = self::parse_feed($values['url']);
        $data->content  = serialize($data->content);
        $data->image    = serialize($data->image);
        $data->lastupdate = db_format_timestamp(time());
        $id = ensure_record_exists('blocktype_externalfeed_data', array('url' => $values['url']), $data, 'id', true);
        $values['feedid'] = $id;
        unset($values['url']);
        return $values;

    }

    public static function get_cron() {
        $refresh = new StdClass;
        $refresh->callfunction = 'refresh_feeds';
        $refresh->hour = '*';
        $refresh->minute = '0';

        $cleanup = new StdClass;
        $cleanup->callfunction = 'cleanup_feeds';
        $cleanup->hour = '3';
        $cleanup->minute = '0';

        return array($refresh, $cleanup);

    }

    public static function refresh_feeds() {
        if (!$feeds = get_records_select_array('blocktype_externalfeed_data', 
            'lastupdate < ?', array(db_format_timestamp(strtotime('-30 minutes'))))) {
            return;
        }
        foreach ($feeds as $feed) {
            try {
                $data = self::parse_feed($feed->url);
                $data->id = $feed->id;
                $data->lastupdate = db_format_timestamp(time());
                $data->content = serialize($data->content);
                $data->image   = serialize($data->image);
                update_record('blocktype_externalfeed_data', $data);
            }
            catch (XML_Feed_Parser_Exception $e) {
                // The feed must have changed in such a way as to become 
                // invalid since it was added. We ignore this case in the hope 
                // the feed will become valid some time later
            }
        }
    }

    public static function cleanup_feeds() {
        $ids = array();
        if ($instances = get_records_array('block_instance', 'blocktype', 'externalfeed')) {
            foreach ($instances as $block) {
                $data = unserialize($block->configdata);
                if ($data['feedid']) {
                    $ids[$data['feedid']] = true;
                }
            }
        }
        if (count($ids) == 0) {
            delete_records('blocktype_externalfeed_data'); // just delete it all 
            return;
        }
        $usedids = implode(', ', array_map('db_quote', array_keys($ids)));
        delete_records_select('blocktype_externalfeed_data', 'id NOT IN ( ' . $usedids . ' )');
    }

    /**
     * Parses the RSS feed given by $source. Throws an exception if the feed 
     * isn't able to be parsed
     *
     * @param string $source The URI for the feed
     * @throws XML_Feed_Parser_Exception
     */
    public static function parse_feed($source) {

        static $cache;
        if (empty($cache)) {
            $cache = array();
        }
        if (array_key_exists($source, $cache)) {
            return $cache[$source];
        }

        require_once('XML/Feed/Parser.php');

        $config = array(CURLOPT_URL => $source);

        $result = mahara_http_request($config);

        if($result->data) {
            if ($result->error) {
                $cache[$source] = $result->error;
                throw $cache[$source];
            }
        }

        try {
            $feed = new XML_Feed_Parser($result->data, false, true, false);
        }
        catch (XML_Feed_Parser_Exception $e) {
            $cache[$source] = $e;
            throw $e;
            // Don't catch other exceptions, they're an indication something 
            // really bad happened
        }

        $data = new StdClass;
        $data->title = $feed->title;
        $data->url = $source;
        $data->link = $feed->link;
        $data->description = $feed->description;

        // Work out the icon for the feed depending on whether it's RSS or ATOM
        $data->image = $feed->image;
        if (!$data->image) {
            // ATOM feed. These are simple strings
            $data->image = $feed->logo ? $feed->logo : null;
        }

        $data->content = array();
        foreach ($feed as $count => $item) {
            if ($count == 10) {
                break;
            }
            $description = $item->description;
            if (!$description && ($item->content || $item->summary)) {
                // ATOM feed
                $description = $item->content ? $item->content : ($item->summary ? $item->summary : null);
            }
            $data->content[] = (object)array('title' => $item->title, 'link' => $item->link, 'description' => $description);
        }
        $cache[$source] = $data;
        return $data;
    }

    /**
     * Returns the HTML for the feed icon (not the little RSS one, but the 
     * actual logo associated with the feed)
     */
    private static function make_feed_image_tag($image) {
        $result = '';

        if (!$image['url']) {
            return '';
        }

        if (is_string($image)) {
            // Easy!
            return '<img src="' . hsc($image) . '">';
        }

        if (!empty($image['link'])) {
            $result .= '<a href="' . hsc($image['link']) . '">';
        }

        $url = $image['url'];
        // Try and fix URLs that aren't absolute. The standards all say URLs 
        // are supposed to be absolute in RSS feeds, yet still some people 
        // can't even get the basics right...
        if (substr($url, 0, 1) == '/' && !empty($image['link'])) {
            $url = $image['link'] . $image['url'];
        }

        $result .= '<img src="' . hsc($url) . '"';
        // Required by the specification, but we can't count on it...
        if (!empty($image['title'])) {
            $result .= ' alt="' . hsc($image['title']) . '"';
        }

        if (!empty($image['width']) || !empty($image['height'])) {
            $result .= ' style="';
            if (!empty($image['width'])) {
                $result .= 'width: ' . hsc($image['width']) . 'px;"';
            }
            if (!empty($image['height'])) {
                $result .= 'height: ' . hsc($image['height']) . 'px;"';
            }
            $result .= '"';
        }

        $result .= '>';

        if (!empty($image['link'])) {
            $result .= '</a>';
        }

        return $result;
    }

    public static function default_copy_type() {
        return 'full';
    }

}

?>
