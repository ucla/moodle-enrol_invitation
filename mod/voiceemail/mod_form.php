<?php    
/******************************************************************************
 *                                                                            *
 * Copyright (c) 1999-2008  Wimba, All Rights Reserved.                       *
 *                                                                            *
 * COPYRIGHT:                                                                 *
 *      This software is the property of Wimba.                               *
 *      You can redistribute it and/or modify it under the terms of           *
 *      the GNU General Public License as published by the                    *
 *      Free Software Foundation.                                             *
 *                                                                            *
 * WARRANTIES:                                                                *
 *      This software is distributed in the hope that it will be useful,      *
 *      but WITHOUT ANY WARRANTY; without even the implied warranty of        *
 *      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         *
 *      GNU General Public License for more details.                          *
 *                                                                            *
 *      You should have received a copy of the GNU General Public License     *
 *      along with the Wimba Moodle Integration;                              *
 *      if not, write to the Free Software Foundation, Inc.,                  *
 *      51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA                *
 *                                                                            *
 * Author: Thomas Rollinger                                                   *
 *                                                                            *
 * Date: March 2007                                                           *
 *                                                                            *
 ******************************************************************************/

defined('MOODLE_INTERNAL') || die;

require_once ($CFG->dirroot.'/course/moodleform_mod.php');

if (!function_exists('getKeysOfGeneralParameters')) {
    require_once('lib/php/common/WimbaLib.php');
}
if(!function_exists('voicetools_api_create_resource')) {
    require_once('lib/php/vt/WimbaVoicetoolsAPI.php');
    require_once('lib/php/vt/WimbaVoicetools.php');
}
require_once($CFG->dirroot.'/calendar/lib.php');
require_once($CFG->dirroot.'/mod/voiceemail/lib.php');
require_once($CFG->dirroot.'/version.php');

$PAGE->requires->js('/mod/voiceemail/js/mod.js');

class mod_voiceemail_mod_form extends moodleform_mod {

    function definition() {
        global $COURSE, $CFG, $DB, $USER;

        $mform = $this->_form;

        $section = $this->current->section;
        $event = false;
        $cal_visibility="hidden";
        $disabledCheckbox = false;
        $maxLength = '180';
        $audioQuality = 'spx_16_q4';
        $description = '';
        $calDate = $eventDate=mktime(0,0,0,1,date('z',$COURSE->startdate)+($section-1)*7+1,date('y',$COURSE->startdate));

        if(isset($this->current->update))
        {
            $disabled="";
            $isButtonDisabled = "";
            //get the information of the activity
            if (! $cmVT = $DB->get_record("course_modules", array("id"=>$this->current->update)))
            {
                return false;
            }
            $course_id=$cmVT->course;

            $activity = $DB->get_record("voiceemail", array("id" => $cmVT->instance ));
            $sectionId=$activity->section;
            $resourceBd = $DB->get_record("voiceemail_resources", array("id" => $activity->rid));
            $resource_id = $resourceBd->rid;

            $event=voiceemail_get_event_calendar($activity->id);
            if( $event )
            {
                $cal_visibility="visible";
                $checked = "checked";
                list($description,$link)=explode("<br>",$event->description);
                $eventDate=$event->timestart;
                $stringDate=date('m',$eventDate)."/".date('d',$eventDate)."/".date('Y',$eventDate); 
            }
            else
            {
                $tweekSelected=($sectionId-1)*604800+$COURSE->startdate+3600;//500 to make sure that we are in the day after
                $stringDate=date('m',$tweekSelected)."/".date('d',$tweekSelected)."/".date('Y',$tweekSelected);    
                $eventDate=mktime(0,0,0,1,date('z',$COURSE->startdate)+($sectionId-1)*7+1,date('y',$COURSE->startdate))  ; 
            }

            $name=$activity->name;
            $recipients=$activity->recipients_email;

            //get the Vmail resouce
            $vtAction = new vtAction($USER->email);
            $resource = $vtAction->getResource($resource_id) ;
            if($resource->error != "error"){
                $resourceOptions = $resource->getOptions() ;
                $maxLength = $resourceOptions->getMaxLength();
                $resourceAudio = $resourceOptions->getAudioFormat();
                $audioQuality = $resourceAudio->getName();
                $replyLink = $resourceOptions->getReplyLink();
                $prefilledSubject = $resourceOptions->getSubject();
            }
            else
            {
                $error=true;
            }
        } else {
            $audioQuality = 'spx_16_q4';
            $maxLength = '180';
            $replyLink = "false";
            $prefilledSubject = "";
            $recipients = "all";
        }

        if(($COURSE->format == "weeks" || $COURSE->format == "weekscss") && $section == 0)
        {
            $disabledCheckbox = true;
        }

        $url_params = voiceemail_get_url_params($COURSE->id);

        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('hidden', 'url_params', "$url_params&default=true&time=".time());
        $mform->addElement('hidden', 'course_startdate', $COURSE->startdate, array('id' => 'course_startdate'));
        $mform->addElement('hidden', 'calendar_start', $calDate, array('id' => 'calendar_start'));
        $mform->addElement('hidden', 'course_format', $COURSE->format, array('id' => 'course_format'));

        if (isset($resource_id))
            $mform->addElement('hidden', 'r_id', $resource_id);

        //name
        $mform->addElement('text', 'name', get_string('activity_name', 'voiceemail'), array('maxlength' =>255));
        $mform->addRule('name', null, 'required', null, 'client');

        //recipients
        $radioarray=array();
        $radioarray[] = &MoodleQuickForm::createElement('radio', 'recipients_email', '', get_string('instructors', 'voiceemail'), 'instructors');
        $radioarray[] = &MoodleQuickForm::createElement('radio', 'recipients_email', '', get_string('students', 'voiceemail'), 'students');
        $radioarray[] = &MoodleQuickForm::createElement('radio', 'recipients_email', '', get_string('all', 'voiceemail'), 'all');
        $mform->addGroup($radioarray, null, get_string('recipients_email', 'voiceemail'), array(' '), false);
        $mform->setDefault('recipients_email', $recipients);

        //pre filled subject field
        $prefilledarr = array();
        $prefilledarr[] = &MoodleQuickForm::createElement('radio', 'pre_filled_subject', '', get_string('no', 'voiceemail'), 'false');
        $prefilledarr[] = &MoodleQuickForm::createElement('radio', 'pre_filled_subject', '', get_string('yes', 'voiceemail'), 'true');
        $prefilledarr[] = &MoodleQuickForm::createElement('text', 'subject', '', null, $prefilledSubject);
        $mform->addGroup($prefilledarr, 'pre_filled_group', get_string('pre_filled_subject', 'voiceemail'), array(' '), false);
        $predefault = ($prefilledSubject == '') ? 'false' : 'true';
        $mform->setDefault('pre_filled_subject', $predefault);
        $mform->disabledIf('subject', 'pre_filled_subject', 'eq', 'false');
        $mform->setDefault('subject', $prefilledSubject);

        //include reply links
        $replyarr = array();
        $replyarr[] = &MoodleQuickForm::createElement('radio', 'reply_link', '', get_string('no', 'voiceemail'), 'false');
        $replyarr[] = &MoodleQuickForm::createElement('radio', 'reply_link', '', get_string('yes', 'voiceemail'), 'true');
        $mform->addGroup($replyarr, null, get_string('reply_link', 'voiceemail'), array(' '), false);
        $replydefault = ($prefilledSubject == '') ? 'false' : 'true';
        $mform->setDefault('reply_link', $replydefault);

        //audio quality
        $audioopts = array();
        $audioopts['spx_8_q3'] = get_string('basicquality', 'voiceemail');
        $audioopts['spx_16_q4'] = get_string('standardquality', 'voiceemail');
        $audioopts['spx_16_q6'] = get_string('goodquality', 'voiceemail');
        $audioopts['spx_32_q8'] = get_string('superiorquality', 'voiceemail');
        $mform->addElement('select', 'audio_format', get_string('audio_quality', 'voiceemail'), $audioopts);
        $mform->setDefault('audio_format', $audioQuality);

        //max message length
        $maxlenopts = array('15' => '15 s', '30' => '30 s', '60' => '1 min', '180' => '3 min', '300' => '5 min',
                            '600' => '10 min', '1200' => '20 min');
        $mform->addElement('select', 'max_length', get_string('max_length', 'voiceemail'), $maxlenopts);
        $mform->setDefault('max_length', $maxLength);

        //calendar
        $mform->addElement('checkbox', 'calendar_event', get_string('add_calendar', 'voiceemail'), '', array('onclick' => 'hideCalendarEvent("check");', 'id' => 'id_calendar_event'));
        if ($cal_visibility == 'visible')
            $mform->setDefault('calendar_event', 'checked');
        if ($disabledCheckbox)
            $mform->freeze('calendar_event');
        $mform->addElement('html', '<span id="calendar" style="visibility:'.$cal_visibility.';">');
        $defaultdate = ($event !== false) ? $event->timestart : date('Y-m-d 8:00:00');
        if ($COURSE->format == 'weeks' || $COURSE->format == 'weekscss') {
            $options = array('format' => 'l hA i', 'minYear' => date('Y'), 'maxYear' => date('Y')+10);
            $mform->addElement('date', 'calendar', get_string('start_date', 'voiceemail'), $options);
        } else {
            $options = array('format' => 'FdY hA i', 'minYear' => date('Y'), 'maxYear' => date('Y')+10);
            $mform->addElement('date', 'calendar', get_string('start_date', 'voiceemail'), $options);
        }
        $mform->setDefault('calendar', $defaultdate);
        $mform->addElement('html', '</span>');

        //calendar_extra
        $mform->addElement('html', '<div id="calendar_extra"  style="visibility:'.$cal_visibility.'">');

        //duration
        $defaultduration = ($event !== false) ? $event->timeduration : 0;
        $minarr = array();
        foreach (range(0,60,10) as $min)
            $minarr[$min] = $min;
        $durarr = array();
        $durarr[] = &MoodleQuickForm::createElement('select', 'duration_hrs', '', range(0, 23));
        $durarr[] = &MoodleQuickForm::createElement('select', 'duration_min', '', $minarr);
        $mform->addGroup($durarr, null, get_string('duration_calendar', 'voiceemail'), array(' '), false);
        $mform->setDefault('duration_hrs', intval($defaultduration / 3600));
        $mform->setDefault('duration_min', ($defaultduration % 3600) / 60);

        //description
        $descopts = array('rows' => 4, 'cols' => 30);
        $mform->addElement('textarea', 'description', get_string('description_calendar', 'voiceemail'), $descopts);
        $mform->setDefault('description', $description);

        $mform->addElement('html', '</div>'); //close calendar_extra
        //$mform->addElement('html', '</div>');

        $this->standard_coursemodule_elements();

//-------------------------------------------------------------------------------
// buttons
        $this->add_action_buttons();
    }

    function validation($data) {
        $errors = array();

        if ($data['pre_filled_subject'] == 'true' && empty($data['subject'])) {
            $errors['pre_filled_group'] = get_string('error_subject', 'voiceemail');
        }

        return $errors;
    }
}
