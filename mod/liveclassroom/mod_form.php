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
 * Date: October 2006                                                         *
 *                                                                            *
 ******************************************************************************/

defined('MOODLE_INTERNAL') || die;

require_once("../config.php");
require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/calendar/lib.php');
if (!function_exists('getKeysOfGeneralParameters')) {
    require_once($CFG->dirroot.'/mod/liveclassroom/lib/php/common/WimbaLib.php');
}
require_once($CFG->dirroot.'/mod/liveclassroom/lib.php');
require_once($CFG->dirroot.'/mod/liveclassroom/lib/php/lc/LCAction.php');

$PAGE->requires->js('/mod/liveclassroom/lib/web/js/lib/prototype/prototype.js');
$PAGE->requires->js('/mod/liveclassroom/lib/web/js/wimba_ajax.js');
$PAGE->requires->js('/mod/liveclassroom/js/mod.js');

class mod_liveclassroom_mod_form extends moodleform_mod {

    function definition() {
        global $COURSE, $CFG, $DB, $USER;

        $mform = $this->_form;

        $section = $this->current->section;
        $event = false;
        $cal_visibility="hidden";
        $disabledCheckbox = false;
        $calStart = $eventDate=mktime(0,0,0,1,date('z',$COURSE->startdate)+($section-1)*7+1,date('y',$COURSE->startdate));
        $course_id = $COURSE->id;
        $error = false;
        $description = '';
        $name = '';

        $api=new LCAction(null,$CFG->liveclassroom_servername, $CFG->liveclassroom_adminusername,
                $CFG->liveclassroom_adminpassword, $CFG->dataroot, $COURSE->id);

        if(isset($this->current->update))
        {
            $disabled="";
            //get the information of the activity
            if (! $cmLiveClass = $DB->get_record("course_modules", array("id"=>$this->current->update)))
            {
                return false;
            }
            $course_id=$cmLiveClass->course;

            $activity = $DB->get_record("liveclassroom", array("id" => $cmLiveClass->instance ));
            $sectionId = $activity->section;

            $event = liveclassroom_get_event_calendar($activity->id);
            if($event)
            {
                $cal_visibility="visible";
                $checked ="checked"; 
                list($description,$link)=explode("<br>",$event->description);
                $eventDate=$event->timestart;
            }
            else
            {
                $eventDate=mktime(0,0,0,1,date('z',$COURSE->startdate)+($sectionId-1)*7+1,date('y',$COURSE->startdate))  ;
            }

            $name = $activity->name;
        }

        if(($COURSE->format == "weeks" || $COURSE->format == "weekscss") && $section == 0)
        {
            $disabledCheckbox = true;
        }

        $rooms = $api->getRooms($COURSE->id."_T");
        if ($rooms === false)
            print_error(get_string('error_connection_lc','liveclassroom'));

        $url_params = liveclassroom_get_url_params($course_id);

        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('hidden', 'course_startdate', $COURSE->startdate, array('id' => 'course_startdate'));
        $mform->addElement('hidden', 'calendar_start', $calStart, array('id' => 'calendar_start'));
        $mform->addElement('hidden', 'course_format', $COURSE->format, array('id' => 'course_format'));
        $mform->addElement('hidden', 'url_params', "$url_params&default=true&time=".time());

        //$mform->addElement('html', '<div id="content" class="content" style="width:700px;background-color:white;margin:0 auto;border: solid 1px #D9DEE5;" align="center">');

        /*$mform->addElement('html', '<div class="headerBar">
                     <div class="headerBarLeft" >
                         <span>Wimba</span>
                     </div>
        </div>');*/

        //name
        $mform->addElement('text', 'name', get_string('activity_name', 'liveclassroom'), array('maxlength' =>255));
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->setDefault('name', $name);

        //associated room
        $resopts = array('empty' => 'Select...');
        foreach ($rooms as $room)
        {
            if ($room->isArchive() == false) {
                $resopts[$api->getPrefix().$room->getRoomId()] = (strlen($room->getLongname()) > 25) 
                                                            ? substr($room->getLongname(), 0, 25)."..." 
                                                            : $room->getLongname();
            }
        }
        $resopts['new'] = 'New room....';
        $resselc = isset($this->current->type) ? $this->current->type : 'empty';
        $mform->addElement('select', 'resource', get_string('liveclassroomtype', 'liveclassroom'), $resopts, array('onchange' => 'LoadNewFeaturePopup(this.options[this.selectedIndex].value);isValidate();'));
        $mform->addRule('resource', null, 'required', null, 'client');
        $mform->setDefault('resource', $resselc);


        //calendar
        $mform->addElement('checkbox', 'calendar_event', get_string('add_calendar', 'liveclassroom'), '', array('onclick' => 'hideCalendarEvent("check");', 'id' => 'id_calendar_event'));
        if ($cal_visibility == 'visible')
            $mform->setDefault('calendar_event', 'checked');
        if ($disabledCheckbox)
            $mform->freeze('calendar_event');
        $mform->addElement('html', '<span id="calendar" style="visibility:'.$cal_visibility.';">');
        $defaultdate = ($event !== false) ? $event->timestart : date('Y-m-d 8:00:00');
        if ($COURSE->format == 'weeks' || $COURSE->format == 'weekscss') {
            $options = array('format' => 'l hA i', 'minYear' => date('Y'), 'maxYear' => date('Y')+10);
            $mform->addElement('date', 'calendar', get_string('start_date', 'liveclassroom'), $options);
        } else {
            $options = array('format' => 'FdY hA i', 'minYear' => date('Y'), 'maxYear' => date('Y')+10);
            $mform->addElement('date', 'calendar', get_string('start_date', 'liveclassroom'), $options, array('id'=>'calendar_topics'));
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
        $mform->addGroup($durarr, null, get_string('duration_calendar', 'liveclassroom'), array(' '), false);
        $mform->setDefault('duration_hrs', intval($defaultduration / 3600));
        $mform->setDefault('duration_min', ($defaultduration % 3600) / 60);

        //description
        $descopts = array('rows' => 4, 'cols' => 30);
        $mform->addElement('textarea', 'description', get_string('description_calendar', 'liveclassroom'), $descopts);
        $mform->setDefault('description', $description);

        $mform->addElement('html', '</div>'); //close calendar_extra
        //$mform->addElement('html', '</div>');

        //ajax related divs
        $mform->addElement('html', '<div id="hiddenDiv" style="display:none" class="opac"></div>');
        //new popup box
        $mform->addElement('html', '<div class="wimba_box" id="newPopup" style="width:350px;z-index:150;display:none;position:absolute;left: 38%; top: 25%;">
    <div class="wimba_boxTop">
	    <div class="wimbaBoxTopDiv">
	            <span class="wimba_boxTopTitle"  style="width:300px;">'."Creation of a new room :".'
	            </span>    
	            <span title="close" class="wimba_close_box" onclick="onCancelButtonPopup()">Close</span>     
	            <p style="text-align:left;"> 
	               <span style="padding-left:15px">Please enter a title for the new room:</span>
	            </p>
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

        //loading box
        $mform->addElement('html', '<div class="wimba_box" id="loading" style="width:100px;z-index:100;display:none;position:absolute;left: 42%; top: 30%;"">
   <div class="wimba_boxTop">
      <div class="wimbaBoxTopDiv">  
        <img src="'.$CFG->wwwroot.'/mod/liveclassroom/lib/web/pictures/items/wheel-24.gif"><br>
        <span style="font-color:#666666">Loading...</span>
      </div>
    </div>
    <div class="wimba_boxBottom">
        <div>
        </div> 
    </div>
</div>');

        //error box
        $mform->addElement('html', '<div class="wimba_box" id="error" style="width:350px;z-index:150;display:none;position:absolute;left: 38%; top: 25%;">
     <div class="wimba_boxTop">
        <div class="wimbaBoxTopDiv">
                <span class="wimba_boxTopTitle"  style="width:300px;">Error
                </span>
                <span title="close" class="wimba_close_box" onclick="$(\'error\').hide();$(\'hiddenDiv\').hide();return false;">Close</span>
                <p class="wimba_boxText" style="padding:20px">'.get_string("error_connection_lc",'liveclassroom').'</p>
                <p style="height:20px;padding-top:10px;padding-left:20px">
                     <a href="<?php echo $CFG->wwwroot;?>/course/view.php?id=<?php p($course_id)?>" style="margin-left:70px;"  class="regular_btn"><span style="width:110px">Ok</span></a>
                </p>
                <div style="clear: both; display:block; height:0px;"><h:outputText value="&#160;"/></div>
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

        if (!isset($data['resource']))
            $errors['resource'] = get_string('error_connection_lc', 'liveclassroom');

        return $errors;
    }
}
