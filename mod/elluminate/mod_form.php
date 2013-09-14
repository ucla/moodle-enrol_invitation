<?php
global $PAGE;

require_once $CFG->dirroot . '/course/moodleform_mod.php';
require_once dirname(__FILE__) . '/lib.php';
$PAGE->requires->js('/mod/elluminate/web/js/mod_form.js');

class mod_elluminate_mod_form extends moodleform_mod {

   private $ELLUMINATE_BOUNDARY_TIMES = array(
      0 => '0',
      15 => '15',
      30 => '30',
      45 => '45',
      60 => '60'
   );

   private $ELLUMINATE_MAX_TALKERS = array(
      1 => '1',
      2 => '2',
      3 => '3',
      4 => '4',
      5 => '5',
      6 => '6'
   );

   function definition() {
      global $CFG, $COURSE, $USER, $DB, $ELLUMINATE_CONTAINER;

      $id = optional_param('update', '', PARAM_INT);

      //-------------------------------------------------------------------------------
      //Set flag to true if group mode force is ON
      if ($COURSE->groupmodeforce == 1 && $COURSE->groupmode != 0) {
         $forceGroupModeOn = true;
      } else {
         $forceGroupModeOn = false;
      }
      $mform =& $this->_form;

      if (!empty($id)) {
         if (!$cm = get_coursemodule_from_id('elluminate', $id)) {
            print_error(get_string('cmidincorrect', 'elluminate'));
         }

         //Load Session and Trap Errors
         try {
            $loader = $ELLUMINATE_CONTAINER['sessionLoader'];
            $modSession = $loader->getSessionById($cm->instance);

            //Process Potential Force Group Mode Switch
            $switcher = $ELLUMINATE_CONTAINER['groupSwitcher'];
            $modSession = $switcher->checkForRequiredGroupModeChange($modSession, $cm->course);
         } catch (Elluminate_Exception $e) {
            print_error(get_string('sessionloaderror', 'elluminate') . get_string($e->getUserMessage(), 'elluminate'));
         } catch (Exception $e) {
            print_error(get_string('sessionloaderror', 'elluminate') . get_string('user_error_processing', 'elluminate'));
         }

         //flag for edit mode disables
         $mform->addElement('hidden', 'isedit', 'true'); //This is a placeholder to do disables on.
         $mform->setType('isedit', PARAM_TEXT);
      }

      //-------------------------------------------------------------------------------
      $mform->addElement('header', 'general', get_string('basicsession', 'elluminate'));

      //Title
      $mform->addElement('text', 'name', get_string('title', 'elluminate'), array('size' => '64', 'maxlength' => '64'));
      $mform->setType('name', PARAM_NOTAGS);
      $mform->addRule('name', null, 'required', null, 'client');
      $mform->addHelpButton('name', 'title', 'elluminate');

      //Session Name
      $mform->addElement('text', 'sessionname', get_string('customsessionname', 'elluminate'), array('size' => '64', 'maxlength' => '64'));
      $mform->setType('sessionname', PARAM_NOTAGS);
      $mform->addHelpButton('sessionname', 'customsessionname', 'elluminate');

      //Description Rich Text Editor
      $mform->addElement('editor', 'description', get_string('description', 'elluminate'),
         array('rows' => 3), array('collapsed' => true));
      $mform->setType('description', PARAM_RAW);
      if (isset($modSession)) {
         $mform->setDefault('description', array('text' => $modSession->description, 'format' => FORMAT_HTML));
      }

      //Start and End Date
      $mform->addElement('date_time_selector', 'timestart', get_string('meetingbegins', 'elluminate'), array('optional' => false, 'step' => 15));
      $mform->setDefault('timestart', time() + 900);
      $mform->addElement('date_time_selector', 'timeend', get_string('meetingends', 'elluminate'), array('optional' => false, 'step' => 15));
      $mform->setDefault('timeend', time() + 4500);

      //-------------------------------------------------------------------------------

      $this->standard_coursemodule_elements();

      //-------------------------------------------------------------------------------    
      $mform->addElement('header', 'schedulingdetailshdr', get_string('scheduling', 'elluminate'));

      //Boundary Time
      //Default Setting of anything >1 means no per-session setting
      if ($CFG->elluminate_boundary_default != '-1') {
         $attributes = array('disabled' => 'true');
      } else {
         $attributes = '';
      }

      $boundaryselect = $mform->addElement('select', 'boundarytime', get_string('boundarytime', 'elluminate'),
         $this->ELLUMINATE_BOUNDARY_TIMES, $attributes);

      //Don't override existing setting if present
      if (!isset($modSession)) {
         if ($CFG->elluminate_boundary_default == '-1') {
            $mform->setDefault('boundarytime', Elluminate_Session::BOUNDARY_TIME_DEFAULT);
         } else {
            $mform->setConstant('boundarytime', $CFG->elluminate_boundary_default);
         }
      }

      //Boundary Time Display
      $mform->addHelpButton('boundarytime', 'boundarytime', 'elluminate');
      $mform->addElement('checkbox', 'boundarytimedisplay', get_string('boundarytimedisplay', 'elluminate'));
      $mform->disabledIf('boundarytimedisplay', 'boundarytime', 'eq', 0);

      //-------------------------------------------------------------------------------
      $mform->addElement('header', 'settingshdr', get_string('settings', 'elluminate'));

      //Recording Mode
      $mform->addElement('select', 'recordingmode', get_string('recordingmode', 'elluminate'), $this->getRecordingOptions());
      $mform->setDefault('recordingmode', Elluminate_Recordings_Constants::RECORDING_MANUAL);
      $mform->addHelpButton('recordingmode', 'recordingmode', 'elluminate');

      //Max Talkers
      $mform->addElement('select', 'maxtalkers', get_string('maxtalkers', 'elluminate'), $this->ELLUMINATE_MAX_TALKERS);
      $mform->setDefault('maxtalkers', $this->getDefaultMaxTalkers());
      if (!empty($modSession->maxtalkers)) {
         $mform->setDefault('maxtalkers', $modSession->maxtalkers);
      }
      $mform->addHelpButton('maxtalkers', 'maxtalkers', 'elluminate');

      //Restrict Participants - only in non group mode
      if ($forceGroupModeOn != true) {
         $mform->addElement('checkbox', 'restrictparticipants', get_string('restrictparticipants', 'elluminate'));
         $mform->addHelpButton('restrictparticipants', 'restrictparticipants', 'elluminate');
         $mform->disabledIf('restrictparticipants', 'groupmode', 'neq', '0');

         if (isset($modSession)) {
            if ($modSession->restrictparticipants) {
               $mform->setDefault('restrictparticipants', 'checked');
            }
         }
      }

      //Telephony
      $telephonySettings = $ELLUMINATE_CONTAINER['telephonySettingsView'];
      if (isset($modSession)) {
         $telephonySettings->addTelephonyConfigurationOption($mform, $modSession);
      } else {
         $telephonySettings->addTelephonyConfigurationOption($mform);
      }

      //-------------------------------------------------------------------------------      
      $mform->addElement('header', 'gradinghdr', get_string('grading', 'elluminate'));
      //MOOD-322
      $javascript_action = "";
      if (isset($modSession)) {
         if ($modSession->gradesession) {
            $javascript_action = 'onclick="elluminate_warn_grade_session(\'' . get_string('gradesessiondeletewarn', 'elluminate') . '\');"';
         }
      }
      $mform->addElement('checkbox', 'gradesession', get_string('gradesession', 'elluminate'), '', $javascript_action);
      $mform->setDefault('gradesession', '0');
      $mform->disabledIf('grade', 'gradesession', 'notchecked');
      $mform->addHelpButton('gradesession', 'gradesession', 'elluminate');

      $mform->addElement('modgrade', 'grade', get_string('gradeattendance', 'elluminate'));
      $mform->setDefault('grade', 0);

      //-------------------------------------------------------------------------------
      $mform->addElement('header', 'grouphdr', get_string('group', 'elluminate'));

      if ($COURSE->groupmodeforce == 1 && $COURSE->groupmode == 0) {
         $mform->addElement('html', '<p>' . get_string('groupsettingsdisabled', 'elluminate') . '</p>');
      } else {
         $mform->addElement('select', 'customname', get_string('appendgroupname', 'elluminate'), $this->getCustomNameOptions());
         $mform->addHelpButton('customname', 'customname', 'elluminate');
         $mform->addElement('checkbox', 'customdescription', get_string('customdescription', 'elluminate'));
         $mform->disabledIf('customname', 'groupmode', 'eq', 0);
         $mform->disabledIf('customdescription', 'groupmode', 'eq', 0);
      }

      if ($forceGroupModeOn == true) {
         $mform->addElement('hidden', 'forcegroupmode', $COURSE->groupmode);
         $mform->setType('forcegroupmode', PARAM_TEXT);
      }

      //Save, Cancel Buttons
      $this->add_action_buttons();


      //-- Edit Mode Disables --
      $mform->disabledIf('groupmode', 'isedit', 'eq', 'true');
   }

   private function getCustomNameOptions() {
      $customNameOptions = array();
      $customNameOptions[0] = get_string('customnamenone', 'elluminate');
      $customNameOptions[1] = get_string('customnamegrouponly', 'elluminate');
      $customNameOptions[2] = get_string('customnameappend', 'elluminate');
      return $customNameOptions;
   }

   private function getRecordingOptions() {
      $recording_options = array(
         Elluminate_Recordings_Constants::RECORDING_NONE => get_string('disabled', 'elluminate'),
         Elluminate_Recordings_Constants::RECORDING_MANUAL => get_string('manual', 'elluminate'),
         Elluminate_Recordings_Constants::RECORDING_AUTOMATIC => get_string('automatic', 'elluminate')
      );
      return $recording_options;
   }

   private function getDefaultMaxTalkers() {
      global $CFG;
      $maxTalkersDefault = $CFG->elluminate_max_talkers;
      //If configuration value is not set, default to 1
      if ($maxTalkersDefault == '-1') {
         $maxTalkersDefault = Elluminate_Session::MAX_TALKERS_DEFAULT;
      }
      return $maxTalkersDefault;
   }
}


