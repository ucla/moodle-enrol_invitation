<?php
class Elluminate_HTML_AttendView {

   private $logger;
   private $viewSession;

   private $yesno;
   private $stryes;
   private $strno;

   private $canManageAttendance;
   private $context;
   private $wwwroot;

   //External Dependencies
   private $gradesAPI;
   private $gradesFactory;
   private $sessionUsers;

   const GRADE_NOT_ATTENDED_YET = 0;
   const GRADE_ATTENDED = 1;

   const NO_ATTENDANCE_DATA = null;

   public function __set($property, $value) {
      if (property_exists($this, $property)) {
         $this->$property = $value;
      }
      return $this;
   }

   public function __construct() {
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_HTML_AttendView");

      $this->stryes = get_string('yes');
      $this->strno = get_string('no');
      $this->yesno = array(0 => $this->strno, 1 => $this->stryes);
   }

   public function init($session, $wwwRoot, $canUserManageAttendance, $context) {
      $this->canManageAttendance = $canUserManageAttendance;
      $this->viewSession = $session;
      $this->wwwroot = $wwwRoot;
      $this->context = $context;
      $this->sessionUsers->init($session, $context);
   }

   /**
    * Handle a form submit to update attendance data from the from.
    *
    * @param unknown_type $data
    * @return boolean
    */
   public function processUpdateAction($data) {
      $attendance = clean_param_array($data->attendance, PARAM_INT);
      $userList = clean_param_array($data->userids, PARAM_INT);

      if (!$this->canManageAttendance) {
         return false;
      }

      foreach ($userList as $idx => $userid) {
         $updatedgrade = $attendance[$idx];
         $this->updateUserGrade($userid, $updatedgrade);
      }
   }

   /**
    * Update a single grade record for a userid
    *
    * @param string $userid
    * @param string $updatedgrade
    */
   private function updateUserGrade($userid, $updatedgrade) {
      $attendance = $this->gradesFactory->newAttendance();
      $attendance->loadForSessionAndUser($this->viewSession->id, $userid);
      $realGrade = $this->getRealGrade($updatedgrade);

      if ($attendance->id) {
         if ($updatedgrade != $attendance->grade) {
            $this->updateExistingAttendance($attendance, $realGrade);
            if ($this->viewSession->gradesession) {
               $this->updateGradeBookEntry($userid, $realGrade);
            }
         }
      } else {
         if ($updatedgrade != self::GRADE_NOT_ATTENDED_YET) {
            $this->addNewAttendanceEntry($userid, $realGrade);
            if ($this->viewSession->gradesession) {
               $this->addNewGradeBookEntry($userid, $realGrade);
            }
         }
      }
   }

   /**
    *
    * @param unknown_type $attendance
    * @param unknown_type $updatedgrade
    */
   private function updateExistingAttendance($attendance, $updatedgrade) {
      $this->logger->debug("updateExistingAttendance start: " . $updatedgrade);
      $attendance->grade = $updatedgrade;
      $attendance->save();
   }

   private function addNewAttendanceEntry($userid, $updatedgrade) {
      $this->logger->debug("addNewAttendanceEntry start: " . $updatedgrade);
      $attendance = $this->gradesFactory->newAttendance();
      $attendance->elluminateid = $this->viewSession->id;
      $attendance->userid = $userid;
      $attendance->grade = $updatedgrade;
      $attendance->save();
   }

   private function addNewGradeBookEntry($userid, $updatedgrade) {
      $gradebook = $this->gradesFactory->newGradeBook();
      $gradebook->init($this->viewSession->id,
         $this->viewSession->course,
         $this->viewSession->sessionname,
         $this->viewSession->grade);


      $gradebook->addEntry($userid, $updatedgrade);
      $gradebook->save();
   }

   private function updateGradeBookEntry($userid, $updatedgrade) {
      $gradebook = $this->gradesFactory->newGradeBook();
      $gradebook->init($this->viewSession->id,
         $this->viewSession->course,
         $this->viewSession->sessionname,
         $this->viewSession->grade);

      //For scaled grades, reset entry instead of setting to 0
      if ($updatedgrade == 0 && $this->viewSession->isSessionGradeScaled()) {
         $gradebook->resetEntry($userid);
      } else {
         $gradebook->addEntry($userid, $updatedgrade);
      }

      $gradebook->save();
   }

   private function buildCourseUserListArray() {
      $userTableRows = array();
      $availableCourseUsers = $this->sessionUsers->getSessionParticipantList();
      if (empty($availableCourseUsers)) {
         return $userTableRows;
      }

      return $this->getParticipantGradingRows($availableCourseUsers);
   }

   private function getParticipantGradingRows($availableCourseUsers) {
      $userTableRows = array();
      foreach ($availableCourseUsers as $courseUser) {
         $userAttendance = $this->gradesFactory->newAttendance();
         $userAttendance->loadForSessionAndUser($this->viewSession->id, $courseUser->id);

         if ($userAttendance->grade > 0) {
            if ($this->canManageAttendance) {
               $rowValue = $this->buildAttendedSelectHTML($userAttendance);
            } else {
               //Can't manage attendance, just return a static value
               $rowValue = $this->getStaticGradeRowValueAttended($userAttendance);
            }
         } else {
            if ($this->canManageAttendance) {
               $rowValue = $this->buildNotAttendedSelectHTML($userAttendance);
            } else {
               //Can't manage attendance, just return a static value
               $rowValue = $this->getStaticGradeRowValueNotAttended($userAttendance);
            }
         }
         $formelem = $this->canManageAttendance ? '<input type="hidden" name="userids[]" value="' . $courseUser->id . '" />' : '';
         array_push($userTableRows, array($formelem . fullname($courseUser), $rowValue));
      }
      return $userTableRows;
   }

   private function buildAttendedSelectHTML($attendance) {
      if ($this->viewSession->isSessionGradeNumeric()) {
         $select = $this->gradesAPI->getGradeSelectMenu($this->yesno, self::GRADE_ATTENDED);
      } else {
         $select = $this->gradesAPI->getGradeSelectMenu($this->getScaledGradeSelect(), $attendance->grade);
      }
      return $select;
   }

   private function buildNotAttendedSelectHTML() {
      if ($this->viewSession->isSessionGradeNumeric()) {
         $select = $this->gradesAPI->getGradeSelectMenu($this->yesno, self::GRADE_NOT_ATTENDED_YET);
      } else {
         $select = $this->gradesAPI->getGradeSelectMenu($this->getScaledGradeSelect(), self::GRADE_NOT_ATTENDED_YET);
      }
      return $select;
   }

   private function getStaticGradeRowValueAttended($userAttendance) {
      if ($this->viewSession->isSessionGradeNumeric()) {
         $rowValue = $this->stryes;
      } else {
         $scaled = $this->getScaledGradeSelect();
         $rowValue = $scaled[$userAttendance->grade];
      }
      return $rowValue;
   }

   private function getStaticGradeRowValueNotAttended($userAttendance) {
      if ($this->viewSession->isSessionGradeNumeric()) {
         $rowValue = $this->strno;
      } else {
         $scaled = $this->getScaledGradeSelect();
         $rowValue = $scaled[$userAttendance->grade];
      }
      return $rowValue;
   }

   private function getScaledGradeSelect() {
      return $this->gradesAPI->getScaledGradeMenu($this->viewSession->grade);
   }

   public function getUserTableData($sesskey) {
      $userRows = $this->buildCourseUserListArray();
      if ($userRows == null) {
         return $this::NO_ATTENDANCE_DATA;
      } else {
         $formData = $this->getTable($userRows, $sesskey);
      }
      return $formData;
   }

   private function getTable($userRows) {
      $tableData = array();
      if ($this->canManageAttendance) {
         foreach ($userRows as $row) {
            $tableData[] = $row;
         }
      } else {
         foreach ($userRows as $row) {
            $tableData[] = $row;
         }
      }
      return $tableData;
   }

   public function getFormStartHTML($sesskey) {
      $formStartHTML = '';
      if ($this->canManageAttendance) {
         $formStartHTML = '';
         $formStartHTML .= '<form input action="' . $this->wwwroot .
            '/mod/elluminate/attend-form.php" method="post">';
         $formStartHTML .= '<input type="hidden" name="id" value="' . $this->viewSession->id . '"/>';
         $formStartHTML .= '<input type="hidden" name="sesskey" value="' . $sesskey . '" />';
      }
      return $formStartHTML;
   }

   public function getFormEndHTML() {
      $formEndHTML = '';
      if ($this->canManageAttendance) {
         $formEndHTML = '';
         $formEndHTML .= '<center><input type="submit" value="';
         $formEndHTML .= get_string('updateattendance', 'elluminate');
         $formEndHTML .= '" /></form>';
      }
      return $formEndHTML;
   }

   public function setupMoodleTable() {
      $table = new html_table('meeting-attendance-', $this->viewSession->id);

      $table->cellspacing = 1;
      $table->cellpadding = 8;
      $table->align = array('center');
      $table->tablealign = 'center';
      return $table;
   }

   public function getHeaderRow() {
      return array(get_string('fullname'), get_string('attended', 'elluminate'));
   }

   /**
    * The attendance form for non-scale graded has two choices - Attended: YES (1) or NO (0)
    *
    * This equates to either full marks or none at all.  This method takes the result from the
    * form and translates into the correct grade value.
    *
    */
   private function getRealGrade($updateGrade) {
      $realGrade = $this::GRADE_NOT_ATTENDED_YET;

      //Scale grades can be set to specific value, so we don't
      //default any values
      if ($this->viewSession->isSessionGradeScaled()) {
         $realGrade = $updateGrade;
      } else {
         if ($updateGrade == $this::GRADE_ATTENDED) {
            $realGrade = $this->viewSession->grade;
         }
      }
      return $realGrade;
   }
}