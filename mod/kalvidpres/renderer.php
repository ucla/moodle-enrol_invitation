<?php

// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Kaltura video presentation renderer class
 *
 * @package    mod
 * @subpackage kalvidpres
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once(dirname(dirname(dirname(__FILE__))) . '/local/kaltura/locallib.php');

class mod_kalvidpres_renderer extends plugin_renderer_base {

    function display_mod_info($title) {

        $output = '';

        $attr = array('for' => 'video_name');
//        $output .= html_writer::tag('label', get_string('vid_prop_name', 'kalvidres'), $attr);
//        $output .= '&nbsp;';

        $output .= html_writer::start_tag('b');
        $output .= html_writer::tag('div', $title);
        $output .= html_writer::end_tag('b');
        $output .= html_writer::empty_tag('br');


        return $output;
    }

    function video_notification_bar() {
        $output = '';

        $attr = array('id' => 'notification',
                      'class' => 'notification');
        $output .= html_writer::tag('div', '', $attr);

        $attr = array('id' => 'video_presentation_tag');
        $output .= html_writer::tag('div', '', $attr);


        return $output;

    }

    function video_presentation_button() {

        $output = html_writer::empty_tag('br');
        $output .= html_writer::start_tag('center');

        $attr = array('id' => 'id_pres_btn',
                      'type' => 'button',
                      'value' => get_string('view_vid_pres_btn', 'kalvidpres'));

        $output .= html_writer::empty_tag('input', $attr);

        $output .= html_writer::end_tag('center');

        return $output;

    }
    function player_markup($kalvidpres, $admin_mode) {

        $output = '';

        if (!empty($kalvidpres->entry_id)) {

            $entry_obj = local_kaltura_get_ready_entry_object($kalvidpres->entry_id);

            //$markup = local_kaltura_get_swfdoc_code($entry_obj->id);
            //$attr   = array('type' => 'text/javascript');
            //$output .= html_writer::start_tag('script', $attr);
            //$output .= $markup;
            //$output .= html_writer::end_tag('script');
            $player_markup = local_kaltura_get_kdp_presentation_player($entry_obj, $admin_mode);
            $output = html_writer::tag('div', $player_markup);
        }

        return $output;
    }

    function panel_markup() {
        $output = '';

        // Panel markup to load the KCW
        $attr = array('id' => 'video_pres_panel');
        $output .=  html_writer::start_tag('div', $attr);

        $attr = array('class' => 'hd');
        $output .= html_writer::tag('div', '', $attr);

        $attr = array('class' => 'bd');
        $output .= html_writer::tag('div', '', $attr);

        $output .= html_writer::end_tag('div');

        return $output;

    }

    function connection_failure() {
        return html_writer::tag('p', get_string('conn_failed_alt', 'local_kaltura'));
    }

}