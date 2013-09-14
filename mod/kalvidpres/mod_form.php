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
 * Kaltura video presentation settings page
 *
 * @package    mod
 * @subpackage kalvidpres
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once(dirname(dirname(dirname(__FILE__))) . '/course/moodleform_mod.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/local/kaltura/locallib.php');

class mod_kalvidpres_mod_form extends moodleform_mod {

    function definition() {
        global $CFG, $COURSE, $PAGE;

        if (empty($this->current->entry_id)) {
            $kaltura = new kaltura_connection();
            $connection = $kaltura->get_connection(true, KALTURA_SESSION_LENGTH);

            $login_session = '';

            if (!empty($connection)) {
                $login_session = $connection->getKs();
            }

            $PAGE->requires->css('/mod/kalvidpres/styles.css');

            $partner_id    = local_kaltura_get_partner_id();
            $sr_unconf_id  = local_kaltura_get_player_uiconf('mymedia_screen_recorder');
            $host = local_kaltura_get_host();
            $url = new moodle_url("{$host}/p/{$partner_id}/sp/{$partner_id}/ksr/uiconfId/{$sr_unconf_id}");
            
            // Check if connection to Kaltura can be established
            if ($connection) {
                $PAGE->requires->js($url, true);
                $PAGE->requires->js('/local/kaltura/js/screenrecorder.js', true);
    
                $PAGE->requires->js('/local/kaltura/js/jquery.js', true);
                $PAGE->requires->js('/local/kaltura/js/swfobject.js', true);
                $PAGE->requires->js('/local/kaltura/js/kcwcallback.js', true);
    
    
                $jsmodule = array(
                    'name'     => 'local_kaltura',
                    'fullpath' => '/local/kaltura/js/kaltura.js',
                    'requires' => array('yui2-yahoo-dom-event',
                                        'yui2-container',
                                        'yui2-dragdrop',
                                        'yui2-animation',
                                        'base',
                                        'dom',
                                        'node'
                                        ),
                    'strings' => array(
                            array('upload_successful', 'kalvidpres'),
                            array('video_converting', 'kalvidpres'),
                            array('document_converting', 'kalvidpres'),
                            array('previewvideo', 'kalvidpres'),
                            array('javanotenabled', 'kalvidpres')
                            )
                    );
    
                $courseid = get_courseid_from_context($PAGE->context);
                $conversion_script  = "../local/kaltura/check_conversion.php?courseid={$courseid}&entry_id=";
    
                $kcw                = local_kaltura_get_kcw('pres_uploader', true);
                $panel_markup       = $this->get_popup_markup();
    
                $ksu_ui_conf        = local_kaltura_get_player_uiconf('simple_uploader');
                $uploader_url       = local_kaltura_get_host() . '/kupload/ui_conf_id/' . $ksu_ui_conf;
                $flashvars          = local_kaltura_get_uploader_flashvars(true);
    
                $progress_bar_markup = $this->draw_progress_bar();
    
                $PAGE->requires->js_init_call('M.local_kaltura.video_presentation',
                                              array($conversion_script, $panel_markup,
                                                    $uploader_url, $flashvars,
                                                    $kcw, $login_session, $partner_id, $progress_bar_markup),
                                              true, $jsmodule);
            }
        }

        $mform =& $this->_form;

        /* Hidden fields */

        // Video presentation entry id
        $attr = array('id' => 'entry_id');
        $mform->addElement('hidden', 'entry_id', '', $attr);
        $mform->setType('entry_id', PARAM_TEXT);

        // Video entry id
        $attr = array('id' => 'video_entry_id');
        $mform->addElement('hidden', 'video_entry_id', '', $attr);
        $mform->setType('entry_id', PARAM_TEXT);

        // Document entry id
        $attr = array('id' => 'doc_entry_id');
        $mform->addElement('hidden', 'doc_entry_id', '', $attr);
        $mform->setDefault('doc_entry_id', "0");
        $mform->setType('doc_entry_id', PARAM_TEXT);

        // Video added flag
        $attr = array('id' => 'id_video_added');
        $mform->addElement('hidden', 'video_added', '', $attr);
        $mform->setDefault('id_video_added', '0');
        $mform->setType('id_video_added', PARAM_INT);

        // Video title
        $attr = array('id' => 'video_title');
        $mform->addElement('hidden', 'video_title', '', $attr);
        $mform->setType('video_title', PARAM_TEXT);

        // Id of player to use
        $attr = array('id' => 'uiconf_id');
        $mform->addElement('hidden', 'uiconf_id', '', $attr);
        $mform->setDefault('uiconf_id', KALTURA_PLAYER_PLAYERREGULARDARK);
        $mform->setType('uiconf_id', PARAM_INT);

        $attr = array('id' => 'widescreen');
        $mform->addElement('hidden', 'widescreen', '', $attr);
        $mform->setDefault('widescreen', 0);
        $mform->setType('widescreen', PARAM_INT);

        $attr = array('id' => 'height');
        $mform->addElement('hidden', 'height', '', $attr);
        $mform->setDefault('height', '365');
        $mform->setType('height', PARAM_TEXT);

        $attr = array('id' => 'width');
        $mform->addElement('hidden', 'width', '', $attr);
        $mform->setDefault('width', '400');
        $mform->setType('width', PARAM_TEXT);

        $attr = array('id' => 'wwwroot');
        $mform->addElement('hidden', 'wwwroot', '', $attr);
        $mform->setDefault('wwwroot', $CFG->wwwroot);
        $mform->setType('wwwroot', PARAM_URL);

        // URL returned by convertPptToSwf
        $attr = array('id' => 'id_ppt_dnld_url');
        $mform->addElement('hidden', 'ppt_dnld_url', '', $attr);
        $mform->setDefault('ppt_dnld_url', '');
        $mform->setType('ppt_dnld_url', PARAM_URL);

        // URL returned by KalturaDocumentsService's serve action
        $attr = array('id' => 'id_ppt_dnld_url2');
        $mform->addElement('hidden', 'ppt_dnld_url2', '', $attr);
        $mform->setDefault('ppt_dnld_url2', '');
        $mform->setType('ppt_dnld_url2', PARAM_URL);

        // DEBUGGING ELEMENT - TODO comment this when not debugging - remove references in javascript files as well
//        $attr = array('id' => 'id_debug');
//        $mform->addElement('hidden', 'id_debug', '', $attr);
//        $mform->setDefault('id_debug', '');
//        $mform->setType('id_debug', PARAM_TEXT);

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('name', 'kalvidpres'), array('size'=>'64'));

        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }

        $mform->addRule('name', null, 'required', null, 'client');

        $this->add_intro_editor(false);

        if (empty($this->current->entry_id)) {

            if (local_kaltura_login(true, '')) {

                $mform->addElement('header', 'video', get_string('video_hdr', 'kalvidpres'));

                $mform->addElement('static', 'pres_info', '&nbsp;', get_string('pres_info', 'kalvidpres'));

                $this->add_video_definition($mform);

                $this->add_document_definition($mform);
            } else {
                $mform->addElement('static', 'connection_fail', get_string('conn_failed_alt', 'local_kaltura'));
            }
        }

        $this->standard_coursemodule_elements();

        $this->add_action_buttons();
    }

    private function add_video_definition($mform) {
        global $COURSE;

        $thumbnail = $this->get_thumbnail_markup();

        $mform->addElement('static', 'add_video_thumb', '&nbsp;', $thumbnail);

        $radioarray = array();
        $attributes = array();
        $enable_ksr = get_config(KALTURA_PLUGIN_NAME, 'enable_screen_recorder');
        $context    = null;

        // Check of KSR is enabled via config or capability
        if (!empty($this->_cm)) {
            $context       = get_context_instance(CONTEXT_MODULE, $this->_cm->id);
        } else {

            $context       = get_context_instance(CONTEXT_COURSE, $COURSE->id);
        }

        if ($enable_ksr && has_capability('mod/kalvidpres:screenrecorder', $context)) {
            $radioarray[] =& $mform->createElement('radio', 'media_method', '', get_string('use_screen_recorder', 'kalvidpres'), 1, $attributes);
        }

        $radioarray[] =& $mform->createElement('radio', 'media_method', '', get_string('use_kcw', 'kalvidpres'), 0, $attributes);
        $mform->addGroup($radioarray, 'radioar', get_string('media_method', 'kalvidpres'), array('<br />'), false);
        $mform->addHelpButton('radioar', 'media_creation', 'kalvidpres');

        $videogroup = array();
        $videogroup[] =& $mform->createElement('button', 'add_video', get_string('add_video', 'kalvidpres'));
        $videogroup[] =& $mform->createElement('button', 'video_preview', get_string('vide_preview', 'kalvidpres'));

        $mform->addGroup($videogroup, 'video_group', '&nbsp;', '&nbsp;', false);

    }

    private function draw_progress_bar() {
        $attr         = array('id' => 'progress_bar');
        $progress_bar = html_writer::tag('span', '', $attr);

        $attr          = array('id' => 'slider_border');
        $slider_border = html_writer::tag('div', $progress_bar, $attr);

        $attr          = array('id' => 'loading_text');
        $loading_text  = html_writer::tag('div', get_string('scr_loading', 'mod_kalvidpres'), $attr);

        $attr   = array('id' => 'progress_bar_container',
                        'style' => 'width:100px; padding-left:10px; padding-right:10px; visibility: hidden');
        $output = html_writer::tag('span', $slider_border . $loading_text, $attr);

        return $output;

    }

    private function add_document_definition($mform) {

        global $CFG;

        $thumbnail = $this->get_document_thumbnail_markup();
        $ksu_code = local_kaltura_get_ksu_code();

        $mform->addElement('html', $ksu_code);
        $mform->addElement('static', 'add_document_thumb', '&nbsp;', $thumbnail);

        $mform->addElement('static', 'loading_gif', '', '<p id="progress_gif" style="display:none;visibility:hidden;"><img src="'.
                           $CFG->wwwroot.'/local/kaltura/pix/loading.gif" id="progress_image"></p>');

        $name = get_string('add_document', 'kalvidpres');
        $status = get_string('check_status', 'kalvidpres');
        $style = 'style="z-index: 99"';
        $mform->addElement('static', 'ksu', '&nbsp;', "<span id=\"ksu_tag\" {$style}></span>".
                           "<input type='button' id=\"id_add_document\" disabled=\"disabled\" value=\"{$name}\" />&nbsp;".
                           "<input type='button' id=\"id_check_doc_status\" disabled=\"disabled\" value=\"{$status}\"");

        $attr = array();

    }

    private function get_popup_markup() {


        $output = '';

        // Panel markup to load the KCW
        $attr = array('id' => 'video_panel');
        $output .=  html_writer::start_tag('div', $attr);

        $attr = array('class' => 'hd');
        $output .= html_writer::tag('div', '', $attr);

        $attr = array('class' => 'bd');

        $output .= html_writer::tag('div', '', $attr);

        $output .= html_writer::end_tag('div');

        // Panel markup to preview video
        $attr = array('id' => 'video_preview_panel');
        $output .=  html_writer::start_tag('div', $attr);

        $attr = array('class' => 'hd');
        $output .= html_writer::tag('div', get_string('video_preview_header', 'kalvidpres'), $attr);

        $attr = array('class' => 'bd',
                      'id' => 'video_preview_body');

        $output .= html_writer::tag('div', '', $attr);

        // Panel wait markup
        $output .= html_writer::end_tag('div');

        $attr = array('id' => 'wait');
        $output .=  html_writer::start_tag('div', $attr);

        $attr = array('class' => 'hd');
        $output .= html_writer::tag('div', '', $attr);

        $attr = array('class' => 'bd');

        $output .= html_writer::tag('div', '', $attr);

        $output .= html_writer::end_tag('div');

        return $output;
    }

    private function get_document_thumbnail_markup() {
        global $CFG;

        $source = '';

        // tabindex -1 is required in order for the focus event to be capture
        // amongst all browsers
        $attr = array('id' => 'doc_notification',
                      'class' => 'notification',
                      'tabindex' => '-1',
                      //'style' => 'width:400px;height:200px'
                     );
        $output = html_writer::tag('div', '', $attr);

        $output = html_writer::tag('div', /*get_string('flashminimum', 'local_kaltura')*/'', $attr);

        $source = $CFG->wwwroot . '/local/kaltura/pix/kavatar.png';

        $attr = array('id' => 'document_thumbnail',
                      'src' => $source,
                      'alt' => get_string('add_document', 'kalvidpres'),
                      'title' => get_string('add_document', 'kalvidpres'),
                      );
        $image_tag = html_writer::empty_tag('img', $attr);

        $attr = array('id' => 'document_thumbnail_container');
        $output .= html_writer::tag('span', $image_tag, $attr);


        return $output;

    }

    private function get_thumbnail_markup() {
        global $CFG;

        $source = '';
        $output = '';

        // tabindex -1 is required in order for the focus event to be capture
        // amongst all browsers
        $attr = array('id' => 'notification',
                      'class' => 'notification',
                      'tabindex' => '-1');
        $output .= html_writer::tag('div', '', $attr);

        $attr = array('id' => 'video_thumbnail');

        $source = $CFG->wwwroot . '/local/kaltura/pix/vidThumb.png';

        $attr['src']    = $source;
        $attr['alt']    = get_string('add_video', 'kalvidpres');
        $attr['title']  = get_string('add_video', 'kalvidpres');


        $output .= html_writer::empty_tag('img', $attr);

        return $output;

    }

    function validation($data, $files) {
        $errors = array();

        $errors = parent::validation($data, $files);

        if (!empty($data['video_added'])) {

            if (empty($data['ppt_dnld_url2'])) {
                $errors['video_group'] = get_string('vid_pres_incomplete', 'kalvidpres');
            }
        }

        return $errors;
    }

    function definition_after_data() {
        $mform = $this->_form;

        if (!empty($mform->_defaultValues['entry_id'])) {
            foreach ($mform->_elements as $key => $data) {

                if ($data instanceof MoodleQuickForm_group) {

                    foreach ($data->_elements as $key2 => $data2) {
                        if (0 == strcmp('add_video', $data2->_attributes['name'])) {
                            $mform->_elements[$key]->_elements[$key2]->setValue(get_string('replace_video', 'kalvidpres'));
                            break;
                        }

                    }
                }

//                if ($data instanceof MoodleQuickForm_hidden) {
//
//                    if (array_key_exists('id', $data->_attributes) &&
//                        (0 == strcmp('entry_id', $data->_attributes['id'])) &&
//                        !empty($data->_attributes['id']) )  {
//
//                        $set_thumb = true;
//                        print_object($data->getValue());
//                    }
//                }
//
//                if ($data instanceof MoodleQuickForm_static) {
//                    if (array_key_exists('name', $data->_attributes) &&
//                        (0 == strcmp('add_video_thumb', $data->_attributes['name'])) ) {
//
//                            $data->setAttributes(array('src' => 'did not work'));
//                        }
//                }

            }
        }


    }
}
