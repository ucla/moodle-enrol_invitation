<?php
/**
 * Grades for the module consist of the following:
 *
 * Module Attendance Entries:  Entries are logged in the mdl_elluminate_attendance table
 * when a student attends a session, regardless of if Moodle Gradebook grading is enabled for the
 * session or not
 *
 * Moodle GradeBook Entries:
 *   If a session is flagged as graded on creation or update, then the following happens:
 *      On create, an entry is created in the gradebook (mdl_grade_items) table.  This
 *      causes a column to be added to the gradebook for the session
 *
 *      When a student loads the meeting, a grade is logged for the session in the
 *      mdl_grade_grades table.
 *
 * When a session is deleted, all attendance and gradebook entries are deleted.
 *
 * Updating Sessions is a bit complex, since a session can be changed in the following ways:
 *    -update a currently graded session to no longer be graded
 *    -update a currently not graded session to be graded
 *    -update the maximum grade available for the session, which will update all existing grading entries.
 *
 * @author Danny Wieser Dec 2012
 */
class Elluminate_Session_Grading {
   private $logger;

   const DEFAULT_GRADE = 0;

   private $gradesFactory;

   public function __construct() {
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_Session_Grading");
   }

   public function __set($property, $value) {
      if (property_exists($this, $property)) {
         $this->$property = $value;
      }
      return $this;
   }

   public function addSessionGradeBook($sessionid, $sessionname, $courseid, $grade) {
      $this->logger->debug("addSessionGradeBook - session : " . $sessionid . " courseid = " . $courseid);
      $gradeBook = $this->gradesFactory->newGradeBook();
      $gradeBook->init($sessionid, $courseid, $sessionname, $grade);
      $gradeBook->save();
   }

   public function deleteGradeBook($sessionid, $courseid) {
      $this->logger->debug("deleteGradeBook - session : " . $sessionid . " courseid = " . $courseid);
      $gradeBook = $this->gradesFactory->newGradeBook();
      $gradeBook->init($sessionid, $courseid, '', '');
      return $gradeBook->delete();
   }

   public function logSessionAttendance($sessionid, $userid, $grade) {
      $this->logger->debug("logSessionAttendance - session : " . $sessionid . " userid = " . $userid);

      $attendance = $this->gradesFactory->newAttendance();
      $attendance->loadForSessionAndUser($sessionid, $userid);
      $attendance->setAttendanceGrade($grade);
      $attendance->save();
   }

   public function logToGradeBook($sessionid, $courseid, $sessionname, $sessiongrade, $userid) {
      $this->logger->debug("logToGradeBook - session : " . $sessionid . " userid = " . $userid);
      $gradeBook = $this->gradesFactory->newGradeBook();

      $gradeBook->init($sessionid, $courseid, $sessionname, $sessiongrade);
      $gradeBook->addMaxValueEntry($userid);
      $gradeBook->save();
   }

   /**
    * Find all records in the mdl_elluminate_attendance table and set them back to a default grade
    * (grading has been disabled)
    */
   public function resetAttendanceToDefault($sessionid) {
      $this->logger->debug("resetAttendanceToDefault - session : " . $sessionid);
      $attendanceStub = $this->gradesFactory->newAttendance();
      $attendanceList = $attendanceStub->loadForSession($sessionid);
      foreach ($attendanceList as $attendance) {
         $attendance->resetDefaultGrade();
      }
   }

   /**
    * Find all records in the mdl_elluminate_attendance table and update them with the
    * new max grade
    */
   private function updateExistingAttendanceAndGrades($sessionid, $courseid, $sessionname, $updatedmaxgrade) {
      $this->logger->debug("updateExistingAttendanceAndGrades - session : " . $sessionid . " grade = " . $updatedmaxgrade);
      $attendanceStub = $this->gradesFactory->newAttendance();
      $attendanceList = $attendanceStub->loadForSession($sessionid);

      $gradeBook = $this->gradesFactory->newGradeBook();
      $gradeBook->init($sessionid, $courseid, $sessionname, $updatedmaxgrade);

      foreach ($attendanceList as $attendance) {
         $this->logger->debug("Updating Attendance for Session User: " . $attendance->userid);

         //We only update grade if it's already been set to a value
         if ($attendance->hasExistingGrade()) {
            $attendance->setAttendanceGrade($updatedmaxgrade);
            $attendance->save();

            $gradeBook->addMaxValueEntry($attendance->userid);
            $gradeBook->save();
         }
      }
   }

   /**
    * This function will synchronize the moodle gradebook with the collaborate
    * attendance table for a particular user.
    * @param $sessionid
    * @param $courseid
    * @param $userid
    */
   public function synchGradeBookForUser($sessionid, $courseid, $sessionname, $maxgrade, $userid) {
      $attendance = $this->gradesFactory->newAttendance();
      $attendance->loadForSessionAndUser($sessionid, $userid);
      $gradeBook = $this->gradesFactory->newGradeBook();
      $gradeBook->init($sessionid, $courseid, $sessionname, $maxgrade);

      $gradeBook->addEntry($userid, $attendance->grade);
      $gradeBook->save();
   }

   /**
    * This function will synchronize the moodle gradebook with the collaborate
    * attendance table for ALL users.
    *
    * This is required in the scenario where moodle has locked a gradebook entry.
    *
    * When locked, updates to attendance won't be reflected in the gradebook.
    *
    * If that grade is then unlocked, we need to set the gradebook entry to match the attendance entry
    *
    * @param $sessionid
    * @param $courseid
    * @param $userid
    */
   public function synchGradeBookForAllUsers($sessionid, $courseid, $sessionname, $grade) {
      $attendanceStub = $this->gradesFactory->newAttendance();
      $attendanceList = $attendanceStub->loadForSession($sessionid);
      $gradeBook = $this->gradesFactory->newGradeBook();
      $gradeBook->init($sessionid, $courseid, $sessionname, $grade);

      foreach ($attendanceList as $attendance) {
         $this->logger->debug("attendance for user: " . $attendance->userid);

         //If an existing attendance grade 
         if ($attendance->hasExistingGrade()) {
            $gradeBook->addEntry($attendance->userid, $attendance->grade);
            $gradeBook->save();
         }
      }
   }

   /**
    * This function is called when a session is updated and applies the business rules
    * that occur when grading is enabled/disabled or a max grade value is changed.  This
    * is used to apply updates to the session itself.
    *
    * @param $originalGrade
    * @param $originalGradeSession
    * @param $updateSession
    * @return StdClass
    */
   private function processGradeChanges($originalGrade, $originalGradeSession, $updateSession) {
      $gradeChanges = new StdClass;
      $gradeChanges->hasMaxGradeBeenChanged = false;
      $gradeChanges->hasGradingBeenDisabled = false;
      $gradeChanges->hasGradingBeenEnabled = false;

      //If session is still graded after update, and grade value has changed, set max grade values
      if ($originalGradeSession && $originalGrade != $updateSession->grade) {
         $gradeChanges->hasMaxGradeBeenChanged = true;
         $gradeChanges->maxGradeNewValue = $updateSession->grade;
      }

      //Session has changed from graded to not graded
      if ($originalGradeSession && !$updateSession->gradesession) {
         $gradeChanges->hasGradingBeenDisabled = true;
      }

      //Session has changed from not graded to graded
      if (!$originalGradeSession && $updateSession->gradesession) {
         $gradeChanges->hasGradingBeenEnabled = true;
         $gradeChanges->maxGradeNewValue = $updateSession->grade;
      }
      return $gradeChanges;
   }

   public function getAttendeeCount($sessionId) {
      $attendance = $this->gradesFactory->newAttendance();
      return $attendance->getAttendeeCountForSession($sessionId);
   }

   /**
    * This function is invoked during the session update process to handle any updates that may have happened to grading:
    *   -Max Grade Changed
    *   -Grading Enabled
    *   -Grading Disabled
    *
    * @see Elluminate_Session_Controller_Update
    */
   public function updateSessionGrades($existingSession, $updatedSession) {

      $gradeChanges = $this->processGradeChanges(
         $existingSession->grade,
         $existingSession->gradesession,
         $updatedSession);

      $this->logger->debug(
         "updateSessionGrades id = " . $existingSession->id .
         ", hasMaxGradeBeenChanged = " . $gradeChanges->hasMaxGradeBeenChanged .
         ", hasGradingBeenEnabled = " . $gradeChanges->hasGradingBeenEnabled .
         ", hasGradingBeenDisabled = " . $gradeChanges->hasGradingBeenDisabled);

      if ($gradeChanges->hasMaxGradeBeenChanged) {
         $this->changeMaxGradeForExistingSession($gradeChanges, $existingSession);
      }

      if ($gradeChanges->hasGradingBeenEnabled) {
         $this->enableGradingForExistingSession($gradeChanges, $existingSession);
      }

      if ($gradeChanges->hasGradingBeenDisabled) {
         $this->disableGradingForExistingSession($existingSession);
      }

      if ($existingSession->isGroupSession()) {
         $this->updateGroupSessionGrades($existingSession);
      }
   }

   /**
    * For Group Sessions, when grading is updated we need to
    * do all the same logic as in Elluminate_Session::updateSessionGrades(),
    * and then once the current session (parent) has been updated, cascade
    * those changes down to the children.
    *
    * We don't call updateSessionGrades on the child sessions, because
    * that would cause the gradebook operations to happen multiple times when
    * they're only needed once.
    *
    * @see Elluminate_Session::updateSessionGrades()
    */
   public function updateGroupSessionGrades($existingSession) {
      foreach ($existingSession->childSessions as $childSession) {
         $childSession->grade = $existingSession->grade;
         $childSession->gradesession = $existingSession->gradesession;
      }
   }

   private function changeMaxGradeForExistingSession($gradeChanges, $existingSession) {
      $existingSession->grade = $gradeChanges->maxGradeNewValue;
      $existingSession->gradesession = Elluminate_Session::GRADING_ENABLED;

      $this->updateExistingAttendanceAndGrades(
         $existingSession->id,
         $existingSession->course,
         $existingSession->sessionname,
         $existingSession->grade);
   }

   private function enableGradingForExistingSession($gradeChanges, $existingSession) {
      $existingSession->grade = $gradeChanges->maxGradeNewValue;
      $existingSession->gradesession = Elluminate_Session::GRADING_ENABLED;

      $this->addSessionGradeBook(
         $existingSession->id,
         $existingSession->sessionname,
         $existingSession->course,
         $existingSession->grade);
   }

   private function disableGradingForExistingSession($existingSession) {
      $existingSession->grade = Elluminate_Session_Grading::DEFAULT_GRADE;
      $existingSession->gradesession = Elluminate_Session::GRADING_DISABLED;

      $this->resetAttendanceToDefault($existingSession->id);
      $this->deleteGradeBook($existingSession->id, $existingSession->course);
   }
}