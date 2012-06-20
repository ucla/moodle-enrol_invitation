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

/*
$course_id = optional_param('course', 0, PARAM_INT);
$resource_id = optional_param('rid',null, PARAM_RAW);
$update = optional_param('update', null, PARAM_INT);
$sectionId = optional_param('section', null, PARAM_INT);

$getstr = "?id=$course_id";
if (isset($resource_id))
    $getstr .= "&rid=$resource_id";
if (isset($update))
    $getstr .= "&update=$update";
if (isset($section))
    $getstr .= "&section=$sectionId";

redirect($CFG->wwwroot."/mod/voiceboard/mod.php$getstr");*/

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
require_once($CFG->dirroot.'/mod/voiceboard/lib.php');
require_once($CFG->dirroot.'/version.php');

$PAGE->requires->js('/mod/voiceboard/lib/web/js/lib/prototype/prototype.js');
$PAGE->requires->js('/mod/voiceboard/lib/web/js/wimba_ajax.js');
$PAGE->requires->js('/mod/voiceboard/js/mod.js');

class mod_voiceboard_mod_form extends moodleform_mod {

    function definition() {
        global $COURSE, $CFG, $DB;

        $mform = $this->_form;

        $section = $this->current->section;
        $event = false;
        $cal_visibility="hidden";
        $disabledCheckbox = false;
        $calDate = $eventDate=mktime(0,0,0,1,date('z',$COURSE->startdate)+($section-1)*7+1,date('y',$COURSE->startdate));
        $description = '';

        if(isset($this->current->update))
        {
            $disabled="";
            $textSaveButton=get_string('validationElement_saveAllActivity', 'voiceboard');
            $textSaveAndBackButton=get_string('validationElement_saveAllAndBack', 'voiceboard');
            $isButtonDisabled = "";
            //get the information of the activity
            if (! $cmVT = $DB->get_record("course_modules", array("id"=>$this->current->update)))
            {
                return false;
            }
            $course_id=$cmVT->course;

            $activity = $DB->get_record("voiceboard", array("id" => $cmVT->instance ));
            $sectionId=$activity->section;
            $resource_id = $activity->rid;

            $event=voiceboard_get_event_calendar($activity->id);
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
            $activity_context=get_string('updateActivity', 'voiceboard');
        }

        if(($COURSE->format == "weeks" || $COURSE->format == "weekscss") && $section == 0)
        {
            $disabledCheckbox = true;
        }

        //get the list of resource available
        $resourcesRid= voiceboard_get_voicetools_list($COURSE->id);
        $vtResources=voicetools_api_get_resources($resourcesRid["rid"]);

        $url_params = voiceboard_get_url_params($COURSE->id);

        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('hidden', 'url_params', "$url_params&default=true&time=".time());
        $mform->addElement('hidden', 'course_startdate', $COURSE->startdate);
        $mform->addElement('hidden', 'calendar_start', $calDate, array('id' => 'calendar_start'));
        $mform->addElement('hidden', 'course_format', $COURSE->format, array('id' => 'course_format'));

        //name
        $mform->addElement('text', 'name', get_string('activity_name', 'voiceboard'), array('maxlength' =>255));
        $mform->addRule('name', null, 'required', null, 'client');

        $resopts = array('empty' => 'Select...');
        for($i=0;$vtResources!=null && $i<count($vtResources->getResources());$i++)
        {
            $resource = $vtResources->getResource($i);
            if (isset($resource_id) && $resource_id == $resource->getRid())
                $resselected = $resource->getRid();
            $resopts[$resource->getRid()] = ((strlen($resource->getTitle()) > 25)
                                            ? substr($resource->getTitle(),0,25)."..."
                                            : $resource->getTitle());
        }
        $resopts['new'] = 'New Voice Board....';
        $resselc = isset($this->current->rid) ? $this->current->rid : 'empty';
        $mform->addElement('select', 'resource', get_string('voiceboardtype', 'voiceboard'), $resopts, array('onchange' => 'LoadNewFeaturePopup(this.options[this.selectedIndex].value);isValidate();'));
        $mform->addRule('resource', null, 'required', null, 'client');
        $mform->setDefault('resource', $resselc);

        //calendar
        $mform->addElement('checkbox', 'calendar_event', get_string('add_calendar', 'voiceboard'), '', array('onclick' => 'hideCalendarEvent("check");', 'id' => 'id_calendar_event'));
        if ($cal_visibility == 'visible')
            $mform->setDefault('calendar_event', 'checked');
        if ($disabledCheckbox)
            $mform->freeze('calendar_event');
        $mform->addElement('html', '<span id="calendar" style="visibility:'.$cal_visibility.';">');
        $defaultdate = ($event !== false) ? $event->timestart : date('Y-m-d 8:00:00');
        if ($COURSE->format == 'weeks' || $COURSE->format == 'weekscss') {
            $options = array('format' => 'l hA i', 'minYear' => date('Y'), 'maxYear' => date('Y')+10);
            $mform->addElement('date', 'calendar', get_string('start_date', 'voiceboard'), $options);
        } else {
            $options = array('format' => 'FdY hA i', 'minYear' => date('Y'), 'maxYear' => date('Y')+10);
            $mform->addElement('date', 'calendar', get_string('start_date', 'voiceboard'), $options);
        }
        $mform->setDefault('calendar', $defaultdate);
        $mform->addElement('html', '</span>');

        $mform->addElement('html', '<div id="calendar_extra"  style="visibility:'.$cal_visibility.'">');

        //duration
        $defaultduration = ($event !== false) ? $event->timeduration : 0;
        $minarr = array();
        foreach (range(0,60,10) as $min)
            $minarr[$min] = $min;
        $durarr = array();
        $durarr[] = &MoodleQuickForm::createElement('select', 'duration_hrs', '', range(0, 23));
        $durarr[] = &MoodleQuickForm::createElement('select', 'duration_min', '', $minarr);
        $mform->addGroup($durarr, null, get_string('duration_calendar', 'voiceboard'), array(' '), false);
        $mform->setDefault('duration_hrs', intval($defaultduration / 3600));
        $mform->setDefault('duration_min', ($defaultduration % 3600) / 60);

        //description
        $descopts = array('rows' => 4, 'cols' => 30);
        $mform->addElement('textarea', 'description', get_string('description_calendar', 'voiceboard'), $descopts);
        $mform->setDefault('description', $description);

        $mform->addElement('html', '</div>');

        //ajax related divs
        $mform->addElement('html', '<div id="hiddenDiv" style="display:none" class="opac"></div>');
        $mform->addElement('html', '<div class="wimba_box" id="newPopup" style="width:350px;z-index:150;display:none;position:absolute;left: 38%; top: 25%;">
    <div class="wimba_boxTop">
	    <div class="wimbaBoxTopDiv">
	            <span class="wimba_boxTopTitle"  style="width:300px;">'."Please enter a title for the new Voice Board :".'
	            </span>
	            <p>
	                   <input type="text" id="nameNewResource" style="width:250px" maxlength="50" onkeyup="isOk()">
	            </p>
	            <p style="height:20px;padding-top:10px;padding-left:20px">
	                <a class="regular_btn" href="#" onclick="onCancelButtonPopup()"><span style="width:110px">Cancel</span></a>
	                <input class="regular_btn-submit-disabled" disabled id="advancedOk" style="margin-left:10px;" type="button" id="advancedOk" onclick="javascript:create($(\'nameNewResource\'));return false;"  Value="Ok"/>
	            </p>
	            <div style="clear: both; display:block; height:0px;"><h:outputText value="&#160;"/></div>
	    </div>
	</div>
	<div class="wimba_boxBottom">
	    <div>
	    </div> 
	</div>
</div>');

        $mform->addElement('html', '<div class="wimba_box" id="loading" style="width:100px;z-index:100;display:none;position:absolute;left: 42%; top: 30%;"">
   <div class="wimba_boxTop">
      <div class="wimbaBoxTopDiv">  
        <img src="'.$CFG->wwwroot.'/mod/voiceboard/lib/web/pictures/items/wheel-24.gif"><br>
        <span style="font-color:#666666">Loading...</span>
      </div>
    </div>
    <div class="wimba_boxBottom">
        <div>
        </div> 
    </div>
</div>');

        $this->standard_coursemodule_elements();

//-------------------------------------------------------------------------------
// buttons
        $this->add_action_buttons();
    }

    function validation($data) {
        $errors = array();
        # http://devtools.bbbb.net:8080/browse/CVMI-18
        # if resource is "new" nothing has been entered, reset form and deisplay proper error
        if ( ($data['resource'] == 'empty') || ($data['resource'] == 'new') )
            $errors['resource'] = get_string('resourcereq', 'voiceboard');

        return $errors;
    }
}
