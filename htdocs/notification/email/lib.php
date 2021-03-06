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
 * @subpackage notification-email
 * @author     Catalyst IT Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2006-2008 Catalyst IT Ltd http://catalyst.net.nz
 *
 */

defined('INTERNAL') || die();

require_once(get_config('docroot') . 'notification/lib.php');

class PluginNotificationEmail extends PluginNotification {

    public static function notify_user($user, $data) {

        $lang = (empty($user->lang) || $user->lang == 'default') ? get_config('lang') : $user->lang;
        $separator = str_repeat('-', 72);

        $sitename = get_config('sitename');
        $subject = get_string_from_language($lang, 'emailsubject', 'notification.email', $sitename);
        if (!empty($data->subject)) {
            $subject .= ': ' . $data->subject;
        }

        $messagebody = get_string_from_language($lang, 'emailheader', 'notification.email', $sitename) . "\n";
        $messagebody .= $separator . "\n\n";

        $messagebody .= get_string_from_language($lang, 'subject') . ': ' . $data->subject . "\n\n";
        if ($data->activityname == 'usermessage') {
            // Do not include the message body in user messages when they are sent by email
            // because it encourages people to reply to the email.
            $messagebody .= get_string_from_language($lang, 'newusermessageemailbody', 'group', display_name($data->userfrom), $data->url);
        }
        else {
            $messagebody .= $data->message;
            if (!empty($data->url)) {
                $messagebody .= "\n\n" . get_string_from_language($lang, 'referurl', 'notification.email', $data->url);
            }
        }

        if (isset($data->unsubscribeurl) && isset($data->unsubscribename)) {
            $messagebody .= "\n\n" . get_string_from_language($lang, 'unsubscribemessage', 'notification.email', $data->unsubscribename, $data->unsubscribeurl);
        }

        $messagebody .= "\n\n$separator";

        $prefurl = get_config('wwwroot') . 'account/activity/preferences/';
        $messagebody .=  "\n\n" . get_string_from_language($lang, 'emailfooter', 'notification.email', $sitename, $prefurl);
        email_user($user, null, $subject, $messagebody, null, !empty($data->customheaders) ? $data->customheaders : null);
    }
}

?>
