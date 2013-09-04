<?php
class Elluminate_HTML_Session_View{
   const PAGE_URL = '/mod/elluminate/view.php';
   const CM_ID_PARAM = "?id=";
   
   const TITLE_DELIMITER = ":";
   const TITLE_COLSPAN = 1;
   const SESSIONINFO_COLUMNS = 2;
   
   const SINGLE_USER_PREFIX = "single";
   const NO_USER_PREFIX = "no";
   const MULTI_USER_PREFIX = "multi";
   
   const MODERATOR_KEY = "moderator";
   const PARTICIPANT_KEY = "participant";
   const ATTENDEE_KEY = "attendance";
   
   private $logger;
  
   private $pageSession;
   private $permissions;
   private $sessionTable;
   private $groupSwitchTable;
   private $output;
   private $courseModuleId;
   private $sessionKey;
   private $cacheManager;
   private $licenseManager;
   private $groupAPI;
   
   public function __set($property, $value)
   {
      if (property_exists($this, $property)) {
         $this->$property = $value;
      }
      return $this;
   }
   
   public function __construct(){
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_HTML_SessionView");
   }
   
   public function getBaseUrl(){
      return self::PAGE_URL;
   }
   
   public static function getPageUrl($courseModuleId){
      return self::PAGE_URL . self::CM_ID_PARAM . $courseModuleId;
   }
   
   public function getSessionInfoTable(){
      $this->sessionTable->init(array('center','left','center'));
      $this->addSessionHeaderRow();
      $this->sessionTable->addNameValueRow(get_string('customsessionname','elluminate'),self::TITLE_COLSPAN,
            $this->pageSession->sessionname);

      //Description
      if ($this->pageSession->description != ''){
         $this->sessionTable->addNameValueRow(get_string('description','elluminate'),self::TITLE_COLSPAN,
                  $this->pageSession->description);
      }
      
      if (!$this->pageSession->hasSessionEnded() &&
               $this->pageSession->telephony &&
               $this->licenseManager->isTelephonyLicensed()){
         if ($this->pageSession->meetingid != ''){         
            try{
               $this->addTelephonyRows();
            }catch(Elluminate_Exception $e){
               $this->logger->error("Telephony Information could not be retrieved [" . $e->getMessage() . "]");
               $this->sessionTable->addNameValueRow(get_string('participantphone','elluminate'),0,
                        get_string('telephonysaserror','elluminate'));               
            }
         }else{
            if ($this->pageSession->isGroupSession()){
               $this->addTelephonyNoDataRow();
            }
         }
      }
      
      //Start, End, Boundary
      $this->sessionTable->addNameValueRow(get_string('meetingbegins','elluminate'),self::TITLE_COLSPAN,
               userdate($this->pageSession->timestart));
      
      $this->sessionTable->addNameValueRow(get_string('meetingends','elluminate'),self::TITLE_COLSPAN,
               userdate($this->pageSession->timeend));
      
      if ($this->pageSession->boundarytimedisplay){
         $this->sessionTable->addNameValueRow(get_string('boundarytime','elluminate'),self::TITLE_COLSPAN,
                  userdate($this->pageSession->getBoundaryTimeRealTime()));
      }
      
      //Preloads
      if ( !$this->pageSession->hasSessionEnded() && 
            $this->permissions->doesUserHaveManagePreloadPermissionsForSession()){
         $this->addPreloadRow();
      }
      
      //Recording Mode
      $this->sessionTable->addNameValueRow(get_string('recordingmode','elluminate'),self::TITLE_COLSPAN,
               Elluminate_Recordings_Utils::getRecordingModeString($this->pageSession->recordingmode));

      //Guest Link
      if (! $this->pageSession->hasSessionEnded()){
         if ($this->permissions->doesUserHaveViewGuestLinkPermissionsForSession()){
            $this->getGuestLinkRow();
         }
      }
      
      //Moderator and Participant Management
      if ( !$this->pageSession->hasSessionEnded()){
         $this->addUserManagementRows();
      }
      
      //Attendance
      if ($this->permissions->doesUserHaveViewAttendancePermissionsForSession() && $this->pageSession->hasSessionEnded()){
         $this->addAttendanceRows();
      }

      return $this->sessionTable->getTableOutput();
   }
   
   public function getParentSessionLink(){
      $parentSessionLink = '';
      $parentSessionLink .= '<div class="elluminateconvertedgroupsessions">';
      $parentSessionLink .= '<hr /><p>' . get_string('convertedgroupsession1','elluminate') . '</p>';

      $url = $this->output->getMoodleUrl(self::getPageUrl($this->courseModuleId));
   
      $parentSessionLink .= '<a href="' . $url . '">'  . get_string('convertedgroupsessionreturn','elluminate') . '</a>';
   
      return $parentSessionLink;
   }
   
   public function getSupportLinkHTML(){
      $sessionSupportLink = '';
      $sessionSupportLink .= '<p class="elluminatesupportlink">';
      $sessionSupportLink .= get_string('supportlinktext', 'elluminate');
      $sessionSupportLink .= '<a href="http://support.blackboardcollaborate.com/ics/support/default.asp?deptID=8336" target="_blank"> here </a></p>';
      return $sessionSupportLink;
   }
    
   public function getSessionUpdateButtonHTML()
   {
      $buttonTextHTML = "";
   
      if($this->permissions->doesUserHaveManagePermissionsForSession()){
         $buttonLabel = get_string('updatethis', '', get_string("modulenameinstance", "elluminate"));
         $buttonTextHTML = "<form method=\"get\" action=\"" . $this->getEditSessionUrl() ."\">" .
                  "<div>" .
                  "<input type=\"hidden\" name=\"update\" value=\"" . $this->courseModuleId . "\" />" .
                  "<input type=\"hidden\" name=\"return\" value=\"true\" />" .
                  "<input type=\"hidden\" name=\"sesskey\" value=\"".$this->sessionKey."\" />" .
                  "<input type=\"submit\" value=\"$buttonLabel\" /></div></form>";
      }
      return $buttonTextHTML;
   }
   
   /**
    * If a group session has been force switched to a non-group session, display
    * child sessions in a table.  
    * 
    * If the parent group for a child session has been removed, that session is ignored.
    * 
    * @param unknown_type $courseModule
    */
   public function getChildSessionLinks($courseModule){
      $convertString = get_string('convertedgroupsession1','elluminate') . '<br/>' .
            get_string('convertedgroupsession2','elluminate');
      
      $this->groupSwitchTable->init(array('center','center'));
      $this->groupSwitchTable->addHeaderRow(get_string('groupsessions','elluminate'),self::SESSIONINFO_COLUMNS);
      $this->groupSwitchTable->addSpanRow($convertString,self::SESSIONINFO_COLUMNS);
      $this->groupSwitchTable->addColumnHeaders(array('groupnamelabel','sessionnamedisplay'));
      
      foreach($this->pageSession->childSessions as $childSession){
         if ($this->groupAPI->doesGroupExist($childSession->groupid)){
            $cellArray = array();
            $cellArray[] = $childSession->getParentGroupName();
            $cellArray[] = $this->getChildSessionLink($childSession, $courseModule);
            $this->groupSwitchTable->addRow($cellArray);
         }
      }
      return $this->groupSwitchTable->getTableOutput();
   }
    
   private function addUserManagementRows(){
      $moderatorText = $this->getUserCountString($this->pageSession->getModeratorCount(),self::MODERATOR_KEY);
      if ($this->permissions->doesUserHaveManageUserPermissionsForSession(Elluminate_HTML_UserEditor::MODERATOR_EDIT_MODE)) {      
         $moderatorText .= '<div id="elluminatelink"><a href="'. $this->getEditModeratorUrl() . '">' . 
            get_string('editmoderatorsforthissession','elluminate') . '</a></div>';
      }
      
      $participantText = $this->getParticipantText();
      if ($this->permissions->doesUserHaveManageUserPermissionsForSession(Elluminate_HTML_UserEditor::PARTICIPANT_EDIT_MODE)) {         
         $participantText .= '<div id="elluminatelink"><a href="'. $this->getEditParticipantUrl() . '">' .
                  get_string('editparticipantsforthissession','elluminate') . '</a></div>';
      }
      
      $this->sessionTable->addHeaderRow("Session Moderators and Participants", self::SESSIONINFO_COLUMNS);
      $this->sessionTable->addSpanRow($moderatorText,self::SESSIONINFO_COLUMNS);
      if ($participantText != ''){
         $this->sessionTable->addSpanRow($participantText,self::SESSIONINFO_COLUMNS);
      }
   }
   
   /**
    * Participant Text has the following combinations:
    *   COURSE Session Type: text indicating all users may join
    *   PRIVATE Session Type: text showing the number of users invited and a link to edit if user has permission
    *   GROUP - VISIBLE: text indicating all users may join
    *   GROUP - PRIVATE: text indicating all members of group may join
    *   
    *   This method will err on the side of caution with the returned value - all checks
    *   are specific and if somehow a session does not meet these criteria the string
    *   returned will be blank.
    */
   private function getParticipantText(){
      $participantText = '';
      
      if ($this->pageSession->sessiontype == Elluminate_Session::COURSE_SESSION_TYPE){
         $participantText = get_string('allparticipant','elluminate');
      }
      
      //Private Session
      if ($this->pageSession->isPrivateSession()){
         $participantText = $this->getUserCountString($this->pageSession->getParticipantCount(),self::PARTICIPANT_KEY);
      }
      
      if ($this->pageSession->isGroupSession()){
         if ($this->pageSession->groupmode == Elluminate_Group_Session::GROUP_MODE_VISIBLE){
            $participantText = get_string('allparticipant','elluminate');
         }
         
         if ($this->pageSession->groupmode == Elluminate_Group_Session::GROUP_MODE_PRIVATE){
            if ($this->pageSession->groupid == 0){
               $participantText = get_string('allparticipant','elluminate');
            }else{
               $participantText = get_string('groupparticipant','elluminate',$this->pageSession->getParentGroupName());
            }
         }           
      }
      return $participantText;
   }
  
   private function addAttendanceRows(){
      $this->sessionTable->addHeaderRow(get_string('sessionattendance','elluminate'), self::SESSIONINFO_COLUMNS);    
      $attendanceText = $this->getUserCountString($this->pageSession->getAttendeeCount(),self::ATTENDEE_KEY);
      $attendanceText .='<div id="elluminatelink"><a href="' . $this->getSessionAttendanceUrl() . '">' .
               get_string('editattendance','elluminate') .
               '</a></div>';
      $this->sessionTable->addSpanRow($attendanceText,self::SESSIONINFO_COLUMNS);
   }
   
   private function addPreloadRow(){
      if ($this->pageSession->checkForPreloads()){
         $actionIcon = $this->getActionIcon('delete', 'deletepreloadfile', $this->getPreloadDeleteUrl());
         $preload = $this->pageSession->getPreload();
         
         $this->sessionTable->addNameValueRow(get_string('preloadfile','elluminate'),self::TITLE_COLSPAN,
                  $preload->description,0,
                  $actionIcon);
      }else{
         $actionIcon = $this->getActionIcon('add', 'addpreload', $this->getAddPreloadUrl());
         $this->sessionTable->addNameValueRow(get_string('preloadfile','elluminate'),self::TITLE_COLSPAN,
                  get_string('nopreloadfile','elluminate'),0,
                  $actionIcon);
      }
   }
   
   private function addSessionHeaderRow(){
      $headerHTML =  $this->pageSession->name;
      if ($this->permissions->doesUserHaveLoadPermissionsForSession()){
         $headerHTML .= $this->getSessionJoinLinkHTML();
      }
      $this->sessionTable->addHeaderRow($headerHTML,self::SESSIONINFO_COLUMNS);
   }
   
   private function getActionIcon($image, $altKey, $url){
      return $this->output->getActionIcon($url, $image, $altKey);
   }
   
   /**
    * Build the HTML string for launching the session
    * @return string
    */
   private function getSessionJoinLinkHTML()
   {
      $sessionJoinLinkHTML = '';
      
      $joinUrl = $this->output->getMoodleUrl('/mod/elluminate/loadmeeting.php?id=' . $this->pageSession->id);
      if ($this->pageSession->isSessionInProgress()) {
         $link = '<a href="' . $joinUrl . 
         '" target="_blank">' . get_string('joinsession', 'elluminate') . '</a>';
         $sessionJoinLinkHTML .= '<div class="elluminatejoinmeeting">' . $link . '</div>';
      }
      return $sessionJoinLinkHTML;
   }
   
   /**
    * If telephony is enabled, add a row with telephony information to the session detail table.
    * 
    * For the purpose of telephony, a user is considered a moderator if they have the ability to 
    * manage moderators for the session.  If the user has this permission, we output the 
    * moderator telephone # as well.
    *  
    */
   private function addTelephonyRows(){
      $telephonyString = '';
      
      $participantPhone = $this->cacheManager->getCacheContent(Elluminate_Cache_Constants::TELEPHONY_CACHE,
               Elluminate_Cache_Constants::TELEPHONY_PARTICIPANT_URI,
               $this->pageSession->meetingid);
      
      $participantPIN = $this->cacheManager->getCacheContent(Elluminate_Cache_Constants::TELEPHONY_CACHE,
               Elluminate_Cache_Constants::TELEPHONY_PARTICIPANT_PIN,
               $this->pageSession->meetingid);
     
      $telephonyString .=  $participantPhone . 
         "<div class='elluminatetelephonypin'>" . get_string('telephonypin','elluminate') . "</div>" .
            $participantPIN;
         
      $this->sessionTable->addNameValueRow(get_string('participantphone','elluminate'),0,$telephonyString);
      
      //Moderator Row if permissions allow
      if ($this->permissions->doesUserHaveModeratePermissionsForSession()) {
         $this->moderatorTelephonyRow();
      }
   }
   
   private function moderatorTelephonyRow(){
      $moderatorPhone = $this->cacheManager->getCacheContent(Elluminate_Cache_Constants::TELEPHONY_CACHE,
               Elluminate_Cache_Constants::TELEPHONY_MODERATOR_URI,
               $this->pageSession->meetingid);
      
      $moderatorPin = $this->cacheManager->getCacheContent(Elluminate_Cache_Constants::TELEPHONY_CACHE,
               Elluminate_Cache_Constants::TELEPHONY_MODERATOR_PIN,
               $this->pageSession->meetingid);
      
      $telephonyString =  $moderatorPhone .
         "<div class='elluminatetelephonypin'>" . get_string('telephonypin','elluminate') . "</div>" .
         $moderatorPin;
      
      $this->sessionTable->addNameValueRow(get_string('moderatorphone','elluminate'),0,$telephonyString);
   }
   
   private function addTelephonyNoDataRow(){
      $this->sessionTable->addNameValueRow(get_string('participantphone','elluminate'),
               0,
               get_string('telephonygrouperror','elluminate'));
   }
   
   /**
    * Get the HTML String for the guest session link
    * 
    * @return string
    */
   private function getGuestLinkRow()
   {      
      $guestLink = '';
      if ($this->pageSession->meetingid){
         $guestLink = $this->cacheManager->getCacheContent(Elluminate_Cache_Constants::GUEST_LINK_CACHE,
               null, $this->pageSession->meetingid);
      }
      if ($guestLink != ''){      
         $guestLinkHTML = '<a href="' . $guestLink .	'" target = "_blank">' . $guestLink . '</a>';
      }else{
        $guestLinkHTML = $this->getNoGuestLinkHTML();
      }
      
      $this->sessionTable->addNameValueRow(get_string('guestlink','elluminate'),self::TITLE_COLSPAN,
               $guestLinkHTML);
   }
   
   /**
    * If there is no valid meeting ID, then a guest link cannot be retrieved - 
    * Return an error message instead.
    * @return string
    */
   private function getNoGuestLinkHTML()
   {
      if ($this->pageSession->isGroupSession()) {
          //This is a group/grouping session. If we cannot retrieve the link
          //it may be because they have to join the session first to have it created.
         $guestLinkHTML = '<p class="elluminateguestlink">'. get_string('guestlinkgrouperror', 'elluminate') . '</p>';
      }else {
         $guestLinkHTML = '<p class="elluminateguestlink">'. get_string('guestlinkerror', 'elluminate') . '</p>';
      } 
      return $guestLinkHTML;
   }
   
   private function getChildSessionLink($childSession, $courseModule){
   	$childSessionLinkHTML = '';

   	$urlString = "/mod/elluminate/view.php?id=" . $courseModule->id . "&group=" . $childSession->groupid;
   	$url = $this->output->getMoodleUrl($urlString);
   	
   	$childSessionLinkHTML .= '<a href="' . $url . '">'  . $childSession->sessionname . '</a>';
   	$childSessionLinkHTML .= '</div>';
   	
   	return $childSessionLinkHTML;
   }
   
   
   private function getEditSessionUrl(){
      return $this->output->getMoodleUrl('/course/mod.php');
   }
   
   private function getEditModeratorUrl()
   {
      return $this->output->getMoodleUrl('/mod/elluminate/user-edit.php?id=' .
               $this->pageSession->id . "&amp;type=" . Elluminate_HTML_UserEditor::MODERATOR_EDIT_MODE);
   }
    
   private function getEditParticipantUrl()
   {
      return $this->output->getMoodleUrl('/mod/elluminate/user-edit.php?id=' .
               $this->pageSession->id . "&amp;type=" . Elluminate_HTML_UserEditor::PARTICIPANT_EDIT_MODE);
   }
   
   private function getAddPreloadUrl()
   {
      return $this->output->getMoodleUrl('/mod/elluminate/preload-form.php?id=' . $this->pageSession->id);
   }
   
   private function getPreloadDeleteUrl(){
      $preload = $this->pageSession->getPreload();
      $deleteLink = '';
      $deleteLink .= '/mod/elluminate/preload-form.php';
      $deleteLink .= '?id=' . $this->pageSession->id;
      $deleteLink .= '&amp;delete=' . $preload->presentationid;
      return $this->output->getMoodleUrl($deleteLink);
   }
   
   private function getSessionEditUrl(){
      return $this->output->getMoodleUrl('course/mod.php');
   }
    
   private function getSessionAttendanceUrl()
   {
      return $this->output->getMoodleUrl('/mod/elluminate/attend-form.php?id=' . $this->pageSession->id);
   }
   
   /**
    * This function handles the different output strings for different number of users that
    * are invited/attended a session
    * @param unknown_type $count
    * @param unknown_type $type
    * @return string
    */
   private function getUserCountString($count,$type){
      $inviteString = '';
      if ($count == 0){
         $key = self::NO_USER_PREFIX . $type;
         $inviteString = get_string($key,'elluminate');
      }
      else if ($count == 1){
         $key = self::SINGLE_USER_PREFIX . $type;
         $inviteString = get_string($key,'elluminate');
      }
      else {
         $key =  self::MULTI_USER_PREFIX . $type;
         $inviteString = get_string($key,'elluminate',$count);
      }
      return $inviteString;
   }
}