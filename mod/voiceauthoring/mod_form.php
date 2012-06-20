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
require_once($CFG->dirroot.'/mod/voiceauthoring/lib.php');
require_once($CFG->dirroot.'/version.php');

//$PAGE->requires->js('/mod/voiceauthoring/lib/web/js/lib/prototype/prototype.js');
$PAGE->requires->js('/mod/voiceauthoring/js/mod.js');
$PAGE->requires->js(new moodle_url($CFG->voicetools_servername.'/ve/record.js'), true);

class mod_voiceauthoring_mod_form extends moodleform_mod {

    function definition() {
        global $COURSE, $CFG, $DB, $USER;

        $mform = $this->_form;

        $section = $this->current->section;
        $event = false;
        $cal_visibility="hidden";
        $disabledCheckbox = false;
        $calDate = $eventDate=mktime(0,0,0,1,date('z',$COURSE->startdate)+($section-1)*7+1,date('y',$COURSE->startdate));
        $course_id = $COURSE->id;
        $error = false;
        $description = '';
        $name = '';

        if(isset($this->current->update))
        {
            $disabled="";
            //get the information of the activity
            if (! $cmVT = $DB->get_record("course_modules", array("id"=>$this->current->update)))
            {
                return false;
            }
            $course_id=$cmVT->course;

            $activity = $DB->get_record("voiceauthoring", array("id" => $cmVT->instance ));
            $sectionId = $activity->section;

            $rid_voiceAuthoring = $activity->rid;
            $event = voiceauthoring_get_event_calendar($activity->id);
            if($event)
            {
                $cal_visibility="visible";
                $checked ="checked"; 
                list($description,$link)=explode("<br>",$event->description);
                $eventDate=$event->timestart;
            }
            else
            {
                $tweekSelected=($sectionId-1)*604800+$COURSE->startdate+3600;//500 to make sure that we are in the day after
                $eventDate=mktime(0,0,0,1,date('z',$COURSE->startdate)+($sectionId-1)*7+1,date('y',$COURSE->startdate))  ;
            }

            $name = $activity->activityname;
            $this->current->name = $name;
            $voiceauthoring = $DB->get_record("voiceauthoring_resources", array("course" => $COURSE->id));
            $mid=$activity->mid;
        } else {
            //update the mid to not overide the last message recorded
            $voiceauthoring = $DB->get_record("voiceauthoring_resources", array("course" => $COURSE->id ));

            if ( !empty($voiceauthoring) )
            {
                $mid = ++$voiceauthoring->mid;
            }
        }

        //manage the voice authoring
        /*
        * We use only one voice authoring resource per course
        * Only the id of the message is different for the activities of a course  
        */
         //Create the voice authoring
        $vtAction = new vtAction($USER->email);
        $vtUser = new VtUser();
        $vtUserRigths = new VtRights();
        $vtUserRigths->setProfile ('moodle.recorder.instructor');
        $message = new vtMessage();

        if(!empty($voiceauthoring))
        {
            $rid_voiceAuthoring=$voiceauthoring->rid;
            $resource = $vtAction->getResource($rid_voiceAuthoring) ;
            if($resource==null)
            {
                $error=true;
            }
            if(isset($this->current->update))
            {
                $message->setMid($mid);
            }
            else
            {
                $message->setMid("va-".$mid);
            }
        }
        else
        { //creation of the voice authoring linked to this course

            $mid=0;
            $resource = $vtAction->createRecorder("Voice Authoring associated to the course ".$course_id) ;
            if ($resource === false)
                print_error(get_string('error_voicetools','voiceauthoring'));
            if($resource->error === false)
            {
                wimba_add_log(WIMBA_ERROR,voiceauthoring_LOGS,"Problem to create the voice authoring associated to the course: ".$COURSE->id);
                $error=true;
            }
            else
            {
                $rid_voiceAuthoring = $resource->getRid();
                storeRecorderResource($rid_voiceAuthoring,$COURSE->id,$mid);//default availability
                $message->setMid("va-".$mid);     
            }
        }

        if($error === false  && $resource->error != "error")
        {
            $result=$vtAction->getVtSession($resource,$vtUser,$vtUserRigths,$message);
            if($resource->error == "error")
            {
                $error=true;
            }
        }

        if(($COURSE->format == "weeks" || $COURSE->format == "weekscss") && $section == 0)
        {
            $disabledCheckbox = true;
        }

        $url_params = voiceauthoring_get_url_params($course_id);      

        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('hidden', 'course_startdate', $COURSE->startdate, array('id' => 'course_startdate'));
        $mform->addElement('hidden', 'calendar_start', $calDate, array('id' => 'calendar_start'));
        $mform->addElement('hidden', 'course_format', $COURSE->format, array('id' => 'course_format'));
        $mform->addElement('hidden', 'url_params', "$url_params&default=true&time=".time());
        $mform->addElement('hidden', 'rid', $rid_voiceAuthoring);
        $mform->addElement('hidden', 'mid', $mid);

        if (isset($resource_id))
            $mform->addElement('hidden', 'r_id', $resource_id);

        //$mform->addElement('html', '<div id="content" class="content" style="width:700px;background-color:white;margin:0 auto;border: solid 1px #D9DEE5;" align="center">');

        /*$mform->addElement('html', '<div class="headerBar">
                     <div class="headerBarLeft" >
                         <span>Wimba</span>
                     </div>
        </div>');*/

        //name
        $mform->addElement('text', 'name', get_string('activity_name', 'voiceauthoring'), array('maxlength' =>255));
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->setDefault('name', $name);

        //voice authoring applet
        $applet = '<span><script type="text/javascript">this.focus();</script>
                <script type="text/javascript">
                    var w_p = new Object();
                    w_p.nid="'.$result->getNid().'";
                    w_p.width="250px";
                    w_p.bg="white";
                    w_p.border="0";';
        if (!isset($this->current->update))
            $applet .= 'w_p.play_last="false";';
        $applet .= 'if (window.w_ve_record_tag) w_ve_record_tag(w_p);
            else document.write("Applet should be there, but the Voice Tools server is down");</script></span>';
        $mform->addElement('static', 'applet', get_string('voiceauthoring', 'voiceauthoring'), $applet);

        //calendar
        $mform->addElement('checkbox', 'calendar_event', get_string('add_calendar', 'voiceauthoring'), '', array('onclick' => 'hideCalendarEvent("check");', 'id' => 'id_calendar_event'));
        if ($cal_visibility == 'visible')
            $mform->setDefault('calendar_event', 'checked');
        if ($disabledCheckbox)
            $mform->freeze('calendar_event');
        $mform->addElement('html', '<span id="calendar" style="visibility:'.$cal_visibility.';">');
        $defaultdate = ($event !== false) ? $event->timestart : date('Y-m-d 8:00:00');
        if ($COURSE->format == 'weeks' || $COURSE->format == 'weekscss') {
            $options = array('format' => 'l hA i', 'minYear' => date('Y'), 'maxYear' => date('Y')+10);
            $mform->addElement('date', 'calendar', get_string('start_date', 'voiceauthoring'), $options);
        } else {
            $options = array('format' => 'FdY hA i', 'minYear' => date('Y'), 'maxYear' => date('Y')+10);
            $mform->addElement('date', 'calendar', get_string('start_date', 'voiceauthoring'), $options);
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
        $mform->addGroup($durarr, null, get_string('duration_calendar', 'voiceauthoring'), array(' '), false);
        $mform->setDefault('duration_hrs', intval($defaultduration / 3600));
        $mform->setDefault('duration_min', ($defaultduration % 3600) / 60);

        //description
        $descopts = array('rows' => 4, 'cols' => 30);
        $mform->addElement('textarea', 'description', get_string('description_calendar', 'voiceauthoring'), $descopts);
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

        return $errors;
    }
}
