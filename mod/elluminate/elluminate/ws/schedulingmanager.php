<?php
interface Elluminate_WS_SchedulingManager{
	/**
	 * The testConnection method is used to send a GetServerConfiguration SOAP call. If the call is successful will return true.
	 * @return boolean
	 */
    public function testConnection();
    
    //Session Related
    /**
     * The getSession method is used to send a ListSession SOAP call. Since we're looking for a specific session it will return a
     * full session from the scheduling server.
     * @param int $sessionId
     * @return Elluminate_Session (with meetingid)
     */
    public function getSession($sessionId);
        
    /**
     * The deleteSession method is used to send a RemoveSession SOAP call.
     * @param int $sessionId
     * @return boolean
     */
    public function deleteSession($sessionId);
    
    /**
     *
     * The createSession method is used to send a SetSession SOAP call. A creatorId, startTime, endTime and sessionName
     * are required to create a session so they must be set at this point on the $Session parameter otherwise a Elluminate_WS_SASException
     * could be potentially thrown 
     * @param Elluminate_Session $Session
     * @return Elluminate_Session (with meetingid set from the response)
     */
    public function createSession($Session);
    
    /**
     *
     * The updateSession method is used to send a UpdateSession SOAP call. A meetingId is required on the $Session object along with all
     * the other attributes that are looking to be updated.  If updating times, the time need to follow the same validation as the create
     * session.
     * @param Elluminate_Session $Session
     * @return Elluminate_Session 
     */
    public function updateSession($Session);
    
    /**
     *
     * The updateSession method is used to send a UpdateSession SOAP call. A meetingId, chairList and nonChairList are required and are the only
     * attributes that will be updated.
     * @param Elluminate_Session $Session
     * @return Elluminate_Session
     */
    public function updateUsers($Session);
    
    /**
     *
     * The getGuestLink method is used to send a buildSessionUrl with a static displayname, from there we actually manipulate the link returned from
     * the scheduling server so that it's a generic link that will prompt you for a display name when used.  
     * @param int $sessionId
     * @return String url
     */
    public function getGuestLink($sessionId);
    
    /**
     *
     * The getGuestLink method is used to send a buildSessionUrl.  If the userId is passed that user must be in either the chairList or nonChairList.
     * @param int $sessionId
     * @param String $displayName
     * @param String $userId
     * @return String url
     */
    public function getSessionUrl($sessionId, $displayName, $userId);
    
    //Recording Related
    /**
     * The getRecordingsForTime method is used to send a ListRecordings SOAP call. We're looking for all recordings within a range of times
     * from start time to end time inclusive.
     * @param int $startTime
     * @param int $endTime
     * @return array of Elluminate_Recording
     */
    public function getRecordingsForTime($startTime, $endTime);
    
    /**
     * The getRecordingsForSession method is used to send a ListRecordings SOAP call. Will return all recordings based on the associated session id.
     * @param int $sessionId
     * @return array of Elluminate_Recording
     */
    public function getRecordingsForSession($sessionId);
    
    /**
     * Get the information required to playback recordings (and convert if applicable)
     * 
     */
    public function getRecordingPlaybackDetails($recordingIdsToCheck);
    
    /**
     * The deleteRecording method is used to send a RemoveRecording SOAP call.
     * @param int $sessionId
     * @return boolean
     */
    public function deleteRecording($recordingId);
 
    
    //Preload Related
    
    /**
     *
     * The uploadPresentationContent method is used to upload presentation file data to the scheduling server.  A $Preload object is required as a parameter
     * with the attributes creatorId,formattedfilename and filecontents being required.
     *
     * @param int $Preload
     * @return boolean
     */
    public function uploadPresentationContent($Preload);    
    
    /** 
     *     
     * The setSessionPresentation method is used to attach a given presentation file to a session.
     * Both the presentation id and session id need to be valid for the attach to work correctly otherwise a Elluminate_WS_SASException could be thrown.
     * Only one presentation can be attached to a given session.
     * 
     * @param int $presentationId
     * @param int $sessionId
     * @return boolean
     */
    public function setSessionPresentation($presentationId, $sessionId);
    
    /**
     *
     * The deleteSessionPresentation method removes the link between the session and the presentation.  It does leave the presentation data intact on the 
     * scheduling server however.
     *
     * @param int $presentationId
     * @param int $sessionId
     * @return boolean
     */
    public function deleteSessionPresentation($presentationId, $sessionId);
    
    
    /**
     * Convert a recording MP3/MP4 formats
     * @param Elluminate_Recording $recording
     * @param string $format - mp3/mp4
     */
    public function convertRecording($recording,$format);
    
    /**
     * Get License Information from SAS
     * 
     */
    public function getLicenses();
    
    /**
     * Enable or disable telephony for a given session
     * 
     * $sessionID 
     * $status:
     *   true - enabled
     *   false - disabled
     * 
     */
    public function setTelephony($sessionId, $status);
    
    /**
     * Returns whether a given implementation of this SchedulingManager is ELM or SAS.
     */
    public function getSchedulingManagerName();
    
}