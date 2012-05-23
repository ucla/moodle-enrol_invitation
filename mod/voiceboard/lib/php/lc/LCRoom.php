<?php
/*
 * Created on Jun 5, 2007
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */

require_once("PrefixUtil.php"); 
// Constants
define("ATTRIB_ROOM_DESCRIPTION", "description");
define("ATTRIB_ROOM_LONG_NAME", "longname");
define("ATTRIB_ROOM_IS_ARCHIVE", "archive");
define("ATTRIB_ROOM_ID", "class_id");
define("ATTRIB_ROOM_CONTACT_EMAIL", "contact_email");
define("ATTRIB_ROOM_ARCHIVE", "archive");
define("ATTRIB_ROOM_PREVIEW", "preview");
define("ATTRIB_ROOM_ADD_CAROUSEL", "add_carousel");
define("ATTRIB_ROOM_DELETE_CAROUSEL", "delete_carousel");
define("ATTRIB_ROOM_PARTICIPANT_PIN", "participant_pin");
define("ATTRIB_ROOM_PRESENTER_PIN", "presenter_pin"); 
// Media
define("ATTRIB_ROOM_HMS_SIMULCAST", "hms_simulcast");
define("ATTRIB_ROOM_HMS_SIMULCAST_RESTRICTED", "hms_simulcast_restricted");
define("ATTRIB_ROOM_HMS_TWO_WAY_ENABLED", "hms_two_way_enabled");
define("ATTRIB_ROOM_MEDIA_FORMAT", "media_format");
define("ATTRIB_ROOM_MEDIA_TYPE", "media_type"); 
// Room
define("ATTRIB_ROOM_ARCHIVE_REMINDER_ENABLED", "display_archive_reminder");
define("ATTRIB_ROOM_USERSTATUS_ENABLED", "userstatus_enabled");
define("ATTRIB_ROOM_SEND_USERSTATUS_UPDATES", "send_userstatus_updates"); 
// Lecture room
define("ATTRIB_ROOM_CAN_EBOARD", "can_eboard");
define("ATTRIB_BOR_ENABLED", "bor_enabled");
define("ATTRIB_BOR_CAROUSELS_PUBLIC", "bor_carousels_public");
define("ATTRIB_BOR_SHOW_ROOM_CAROUSELS", "bor_show_room_carousels"); 
// Discussion room
define("ATTRIB_ROOM_CAN_ARCHIVE", "can_archive");
define("ATTRIB_ROOM_CAN_LIVESHARE", "can_liveshare");
define("ATTRIB_ROOM_CAN_PPT_IMPORT", "can_ppt_import"); 
// Chat
define("ATTRIB_ROOM_CHATENABLE", "chatenable");
define("ATTRIB_ROOM_PRIVATECHATENABLE", "privatechatenable"); 
// Access
define("ATTRIB_ROOM_USERLIMIT", "userlimit");
define("ATTRIB_ROOM_ENABLE_GUEST_ACCESS", "enable_guest_access");

define("ATTRIB_ROOM_CAN_LOGCHAT", "can_logchat");
define("ATTRIB_ROOM_CAN_MOVE_STUDENT", "can_move_student");
define("ATTRIB_ROOM_CAN_SHOW_WEB", "can_show_web");

define("ATTRIB_ROOM_VF_WIDTH", "vf_width");
define("ATTRIB_ROOM_VF_HEIGHT", "vf_height");
define("ATTRIB_ROOM_VF_LOCATION", "vf_location");
define("ATTRIB_ROOM_VIDEOFRAMESET", "videoframeset");

define("ATTRIB_ROOM_STUDENT_WB_ENABLED", "student_wb_enabled");
define("ATTRIB_ROOM_STUDENT_WB_LIVEAPP", "student_wb_liveapp");

define("ATTRIB_ROOM_ENABLE_STUDENT_VIDEO_ON_STARTUP", "enable_student_video_on_startup");
define("ATTRIB_ROOM_GUEST_URL", "guest_url");
define("ATTRIB_ROOM_HMS_VIDEO_WINDOW_SIZE_ON_STARTUP", "video_window_size_on_startup");
define("ATTRIB_ROOM_HMS_VIDEO_WINDOW_ENCODING_SIZE", "video_window_encoding_size");
define("ATTRIB_ROOM_HMS_VIDEO_DEFAULT_BIT_RATE", "video_default_bit_rate");
define("ATTRIB_ROOM_HMS_VIDEO_BIT_RATE_CEILING", "video_bit_rate_ceiling");
define("ATTRIB_ROOM_HMS_VIDEO_BANDWIDTH", "video_bandwidth");

define("VALUE_HMS_SIMULCAST_NONE", "none");
define("VALUE_HMS_SIMULCAST_BRIDGE", "bridge");
define("VALUE_HMS_SIMULCAST_DOTELL", "dotell");
define("VALUE_HMS_SIMULCAST_PUBLIC", "public");

define("VALUE_MEDIA_FORMAT_NONE", "none");
define("VALUE_MEDIA_FORMAT_QUICKTIME", "quicktime");
define("VALUE_MEDIA_FORMAT_REALMEDIA", "realmedia");
define("VALUE_MEDIA_FORMAT_HMS", "hms");

define("VALUE_MEDIA_TYPE_NONE", "none");
define("VALUE_MEDIA_TYPE_ONE_WAY_AUDIO", "one-way-audio");

define("VALUE_MEDIA_TYPE_TWO_WAY_VIDEO", "two-way-video");
define("VALUE_MEDIA_TYPE_ONE_WAY_VIDEO", "one-way-video");
define("VALUE_MEDIA_TYPE_SIMULCAST_ONLY", "simulcast-only");

define("VALUE_VF_LOCATION_TOP_LEFT", "Top Left");
define("VALUE_VF_LOCATION_TOP_RIGHT", "Top Right");
define("VALUE_VF_LOCATION_BOTTOM_LEFT", "Bottom Left");
define("VALUE_VF_LOCATION_BOTTOM_RIGHT", "Bottom Right");

define("ATTRIB_BOR_AUTO_MOVE_INSTRUCTORS", "bor_auto_move_instructors");
define("ATTRIB_BOR_AUTO_MOVE_SELF", "bor_auto_move_self");
define("ATTRIB_BOR_INITIAL_NUMBER", "bor_initial_number");

define(" VALUE_VF_WIDTH_160", 160);
define(" VALUE_VF_WIDTH_270", 270);
define(" VALUE_VF_WIDTH_320", 320);
define(" VALUE_VF_HEIGHT_120", 120);
define(" VALUE_VF_HEIGHT_210", 210);
define(" VALUE_VF_HEIGHT_240", 240);

define("VALUE_HMS_VIDEO_WINDOW_SIZE_SLOW", "80x60");
define("VALUE_HMS_VIDEO_WINDOW_SIZE_MEDIUM", "160x120");
define("VALUE_HMS_VIDEO_WINDOW_SIZE_FAST", "320x240");

define("VALUE_HMS_VIDEO_ENCODING_SIZE_SLOW", "80x60");
define("VALUE_HMS_VIDEO_ENCODING_SIZE_MEDIUM", "160x120");
define("VALUE_HMS_VIDEO_ENCODING_SIZE_FAST", "320x240");

define("VALUE_HMS_VIDEO_DEFAULT_BIT_RATE_SLOW", "32kb");
define("VALUE_HMS_VIDEO_DEFAULT_BIT_RATE_MEDIUM", "128kb");
define("VALUE_HMS_VIDEO_DEFAULT_BIT_RATE_FAST", "256kb");

define("VALUE_HMS_VIDEO_BIT_RATE_CEILING_SLOW", "128kb");
define("VALUE_HMS_VIDEO_BIT_RATE_CEILING_MEDIUM", "256kb");
define("VALUE_HMS_VIDEO_BIT_RATE_CEILING_FAST", "256kb");

define("VALUE_HMS_VIDEO_VIDEO_BANDWIDTH_SMALL", "small");
define("VALUE_HMS_VIDEO_VIDEO_BANDWIDTH_MEDIUM", "medium");
define("VALUE_HMS_VIDEO_VIDEO_BANDWIDTH_LARGE", "large");
define("VALUE_HMS_VIDEO_VIDEO_BANDWIDTH_CUSTOM", "custom");

define("ROOM_SELECTION_LOBBY_LINK", "--LOBBY--");
define("ROOM_SELECTION_SECTION_DEFAULT", "--SECTION--");
define("ROOM_SELECTION_LIST", "--LIST--");

define("ACCEPTABLE_ROOM_ID_LENGTH", "54");
define("ACCEPTABLE_ROOM_ID_REGEX", "/[a-zA-Z0-9_]{1,54}|\\-\\-LOBBY\\-\\-|\\-\\-SECTION\\-\\-/");

define("VALUE_OLD_ARCHIVE","pre5");//a version prior to WC 5.0.0
define("VALUE_50_ARCHIVE","5+");//5.0.0 and above
//$test=array("pre5","5+");
//define("VALUE_ARCHIVE_LIST",$test);
define("ATTRIB_ARCHIVE_VERSION","archive_version");

// MP4/MP3 settings
define("ATTRIB_ROOM_CAN_DOWNLOAD_MP4", "can_download_mp4");
define("ATTRIB_ROOM_CAN_DOWNLOAD_MP3", "can_download_mp3");
define("ATTRIB_ROOM_AUTO_OPEN_NEW_ARCHIVES", "auto_open_new_archives");
define("ATTRIB_ROOM_MP4_ENCODING_TYPE", "mp4_encoding_type");
define("ATTRIB_ROOM_MP4_MEDIA_PRIORITY", "mp4_media_priority");


/**
   * Mp4 Settings
   */
define("VALUE_MP4_ENCODING_TYPE_STANDARD", "standard");
define("VALUE_MP4_ENCODING_TYPE_STREAMING", "streaming");
define("VALUE_MP4_ENCODING_TYPE_HIGH_QUALITY", "high_quality");
//define("mp4EncodingTypeList", array(
  //      VALUE_MP4_ENCODING_TYPE_STANDARD,
    ///    VALUE_MP4_ENCODING_TYPE_STREAMING,
      //  VALUE_MP4_ENCODING_TYPE_HIGH_QUALITY));

define("VALUE_MP4_MEDIA_PRIORITY_CONTENT_FOCUS", "content_focus_no_video");
define("VALUE_MP4_MEDIA_PRIORITY_CONTENT_FOCUS_WITH_VIDEO", "content_focus_with_video");
define("VALUE_MP4_MEDIA_PRIORITY_VIDEO_FOCUS", "video_focus");
define("VALUE_MP4_MEDIA_PRIORITY_EBOARD_ONLY", "eboard_only");
define("VALUE_MP4_MEDIA_PRIORITY_APPSHARE_ONLY", "appshare_only");
define("VALUE_MP4_MEDIA_PRIORITY_VIDEO_ONLY", "video_only");
//define("mp4MediaPriorityList", array(
 //       VALUE_MP4_MEDIA_PRIORITY_CONTENT_FOCUS,
 //       VALUE_MP4_MEDIA_PRIORITY_CONTENT_FOCUS_WITH_VIDEO,
   //     VALUE_MP4_MEDIA_PRIORITY_VIDEO_FOCUS,
     //   VALUE_MP4_MEDIA_PRIORITY_EBOARD_ONLY,
       // VALUE_MP4_MEDIA_PRIORITY_APPSHARE_ONLY,
       // VALUE_MP4_MEDIA_PRIORITY_VIDEO_ONLY));



class LCRoom {

    var $attributes = array();
    /**
     * Constructor
     * 
     * @param currentRecord $ 
     * @param prefix $ 
     */
    function LCRoom()
    {
    } 

    function setByRecord($currentRecord, $prefix)
    { 
       
        // room attributes
        $prefixUtil = new PrefixUtil();
        $rid = $prefixUtil->trimPrefix($this->getKeyValue($currentRecord,ATTRIB_ROOM_ID), $prefix);

        $this->setRoomId($rid);
        $this->setDescription($this->getKeyValue($currentRecord,ATTRIB_ROOM_DESCRIPTION));
        $this->setLongname($this->getKeyValue($currentRecord,ATTRIB_ROOM_LONG_NAME));
        $this->setContactEmail($this->getKeyValue($currentRecord,ATTRIB_ROOM_CONTACT_EMAIL));
        $this->setArchive($this->getKeyValue($currentRecord,ATTRIB_ROOM_ARCHIVE));
        $this->setPreview($this->getKeyValue($currentRecord,ATTRIB_ROOM_PREVIEW));
        $this->setBORAutoMoveInstructors($this->getKeyValue($currentRecord,ATTRIB_BOR_AUTO_MOVE_INSTRUCTORS));
        $this->setBORAutoMoveSelf($this->getKeyValue($currentRecord,ATTRIB_BOR_AUTO_MOVE_SELF));
        $this->setBORCarouselsPublic($this->getKeyValue($currentRecord,ATTRIB_BOR_CAROUSELS_PUBLIC));
        $this->setBOREnabled($this->getKeyValue($currentRecord,ATTRIB_BOR_ENABLED));
        $this->setBORInitialNumber($this->getKeyValue($currentRecord,ATTRIB_BOR_INITIAL_NUMBER));
        $this->setBORShowRoomCarousels($this->getKeyValue($currentRecord,ATTRIB_BOR_SHOW_ROOM_CAROUSELS));
        $this->setUserstatusEnabled($this->getKeyValue($currentRecord,ATTRIB_ROOM_USERSTATUS_ENABLED));
        $this->setSendUserstatusUpdates($this->getKeyValue($currentRecord,ATTRIB_ROOM_SEND_USERSTATUS_UPDATES)); 
        $this->setArchiveReminderEnabled($this->getKeyValue($currentRecord,ATTRIB_ROOM_ARCHIVE_REMINDER_ENABLED));
        // Media Settings
        // Compatible with LC 5.X
        $this->setHmsSimulcast($this->getKeyValue($currentRecord,ATTRIB_ROOM_HMS_SIMULCAST));
        $this->setHmsSimulcastRestricted($this->getKeyValue($currentRecord,ATTRIB_ROOM_HMS_SIMULCAST_RESTRICTED));
        $this->setHmsTwoWayEnabled($this->getKeyValue($currentRecord,ATTRIB_ROOM_HMS_TWO_WAY_ENABLED));
        $this->setMediaFormat($this->getKeyValue($currentRecord,ATTRIB_ROOM_MEDIA_FORMAT));
        $this->setMediaType($this->getKeyValue($currentRecord,ATTRIB_ROOM_MEDIA_TYPE));
        $this->setVideoFrameWidth($this->getKeyValue($currentRecord,ATTRIB_ROOM_VF_WIDTH));
        $this->setVideoFrameHeight($this->getKeyValue($currentRecord,ATTRIB_ROOM_VF_HEIGHT));
        $this->setVideoFrameLocation($this->getKeyValue($currentRecord,ATTRIB_ROOM_VF_LOCATION));
        $this->setVideoFrameSet($this->getKeyValue($currentRecord,ATTRIB_ROOM_VIDEOFRAMESET));
        $this->setStudentVideoOnStartupEnabled($this->getKeyValue($currentRecord,ATTRIB_ROOM_ENABLE_STUDENT_VIDEO_ON_STARTUP)); 
        $this->setVideoWindowSizeOnStartup($this->getKeyValue($currentRecord,ATTRIB_ROOM_HMS_VIDEO_WINDOW_SIZE_ON_STARTUP));
        $this->setVideoWindowEncodingSize($this->getKeyValue($currentRecord,ATTRIB_ROOM_HMS_VIDEO_WINDOW_ENCODING_SIZE));
        $this->setVideoDefaultBitRate($this->getKeyValue($currentRecord,ATTRIB_ROOM_HMS_VIDEO_DEFAULT_BIT_RATE));
        // setVideoBitRateCeiling((String)currentRecord[ATTRIB_ROOM_HMS_VIDEO_BIT_RATE_CEILING));
        $this->setVideoBandwidth($this->getKeyValue($currentRecord,ATTRIB_ROOM_HMS_VIDEO_BANDWIDTH)); 
        // Advanced settings
        $this->setArchiveEnabled($this->getKeyValue($currentRecord,ATTRIB_ROOM_CAN_ARCHIVE));
        $this->setEboardEnabled($this->getKeyValue($currentRecord,ATTRIB_ROOM_CAN_EBOARD));
        $this->setLiveShareEnabled($this->getKeyValue($currentRecord,ATTRIB_ROOM_CAN_LIVESHARE));
        $this->setLogChatEnabled($this->getKeyValue($currentRecord,ATTRIB_ROOM_CAN_LOGCHAT));
        $this->setMoveStudentEnabled($this->getKeyValue($currentRecord,ATTRIB_ROOM_CAN_MOVE_STUDENT));
        $this->setPptImportEnabled($this->getKeyValue($currentRecord,ATTRIB_ROOM_CAN_PPT_IMPORT));
        $this->setShowWebEnabled($this->getKeyValue($currentRecord,ATTRIB_ROOM_CAN_SHOW_WEB));
        $this->setChatEnabled($this->getKeyValue($currentRecord,ATTRIB_ROOM_CHATENABLE));
        $this->setPrivateChatEnabled($this->getKeyValue($currentRecord,ATTRIB_ROOM_PRIVATECHATENABLE));
        $this->setStudentWhiteboardEnabled($this->getKeyValue($currentRecord,ATTRIB_ROOM_STUDENT_WB_ENABLED));
        $this->setStudentLiveAppEnabled($this->getKeyValue($currentRecord,ATTRIB_ROOM_STUDENT_WB_LIVEAPP)); 
        // Access
        $this->setUserLimit($this->getKeyValue($currentRecord,ATTRIB_ROOM_USERLIMIT));
        $this->setGuestURL($this->getKeyValue($currentRecord,ATTRIB_ROOM_GUEST_URL));
        $this->setGuestAccess($this->getKeyValue($currentRecord,ATTRIB_ROOM_ENABLE_GUEST_ACCESS));
        // PINs are compatible with LC 4.3.0+
        $this->setParticipantPin($this->getKeyValue($currentRecord,ATTRIB_ROOM_PARTICIPANT_PIN));
        $this->setPresenterPin($this->getKeyValue($currentRecord,ATTRIB_ROOM_PRESENTER_PIN));
        $this->setArchiveVersion($this->getKeyValue($currentRecord,ATTRIB_ARCHIVE_VERSION));
        
        //Mp4
        $this->setMp4EncodingType($this->getKeyValue($currentRecord,ATTRIB_ROOM_MP4_ENCODING_TYPE));
        $this->setMp4MediaPriority($this->getKeyValue($currentRecord,ATTRIB_ROOM_MP4_MEDIA_PRIORITY));
        $this->setDownloadMP3Enabled($this->getKeyValue($currentRecord, ATTRIB_ROOM_CAN_DOWNLOAD_MP3));
        $this->setDownloadMP4Enabled($this->getKeyValue($currentRecord, ATTRIB_ROOM_CAN_DOWNLOAD_MP4));
        $this->setAutoOpenArchive($this->getKeyValue($currentRecord, ATTRIB_ROOM_AUTO_OPEN_NEW_ARCHIVES));
        
    } 

    /**
     * Constructor
     * 
     * @param classId $ 
     * @param description $ 
     * @param longname $ 
     * @param contactEmail $ 
     * @param isArchive $ 
     */
    function setArguments($classId, $description,
        $longname, $contactEmail,
        $isArchive, $isPreview)
    {
        $this->setRoomId($classId);
        $this->setDescription($description);
        $this->setLongname($longname);
        $this->setArchive($isArchive);
        $this->setContactEmail($contactEmail);
        $this->setPreview($isPreview);
    } 

    /**
     * 
     * @return Returns the roomId.
     */
    function getRoomId()
    {
        return $this->roomId;
    } 

    /**
     * 
     * @param classId $ The roomId to set.
     */
    function setRoomId($classId)
    {
        if ($this->isValidRoomId($classId)) 
        {
            $this->roomId = $classId;
        } 
        else 
        {
            LOG . Debug("isValidRoomId: setRoomID: " + $classId); 
            // throw new ArgumentException("Room IDs must match " + ACCEPTABLE_ROOM_ID_REGEX + ".  Given room ID: " + classId);
        } 
    } 

    /**
     * 
     * @return Returns the description.
     */
    function getDescription()
    {
        return $this->attributes[ATTRIB_ROOM_DESCRIPTION];
    } 

    /**
     * 
     * @param description $ The description to set.
     */
    function setDescription($description)
    {
        $this->attributes[ATTRIB_ROOM_DESCRIPTION] = $description;
    } 

    /**
     * 
     * @return Returns the longname.
     */
    function getLongname()
    {
        return $this->attributes[ATTRIB_ROOM_LONG_NAME];
    } 

    /**
     * 
     * @param longname $ The longname to set.
     */
    function setLongname($longname)
    {
        $this->attributes[ATTRIB_ROOM_LONG_NAME] = $longname;
    } 

    /**
     * 
     * @return Returns the archive.
     */
    function isArchive()
    {
        return $this->attributes[ATTRIB_ROOM_ARCHIVE];
    } 

    /**
     * 
     * @param isArchive $ The archive to set.
     */
    function setArchive($isArchive)
    {
        $this->attributes[ATTRIB_ROOM_ARCHIVE] = $isArchive;
    } 

    /**
     * 
     * @return Returns the preview.
     */
    function isPreview()
    {
        return $this->attributes[ATTRIB_ROOM_PREVIEW];
    } 

    /**
     * 
     * @param isPreview $ The archive to set.
     */
    function setPreview($isPreview)
    {
        $this->attributes[ATTRIB_ROOM_PREVIEW] = $isPreview;
    } 

    /**
     * 
     * @return Returns the contactEmail.
     */
    function getContactEmail()
    {
        return $this->attributes[ATTRIB_ROOM_CONTACT_EMAIL];
    } 

    /**
     * 
     * @param contactEmail $ The contactEmail to set.
     */
    function setContactEmail($contactEmail)
    {
        $this->attributes[ATTRIB_ROOM_CONTACT_EMAIL] = $contactEmail;
    } 

    /**
     * Compares the given $to the regular expression ACCEPTABLE_ROOM_ID_REGEX
     * to determine whether it fits the LC requirements for that field.
     * 
     * @param id $ 
     * @return 
     */
    function isValidRoomId($id)
    {
        if ($id != null && preg_match(ACCEPTABLE_ROOM_ID_REGEX, $id) > 0) {
            return true;
        } else {
            return true;
        } 
    } 

    function intValue($Hashtable, $key)
    {
        $s = $Hashtable[key];
        return Int32 . Parse(s);
    } 

    function setHmsSimulcast($hmsSimulcast)
    {
        $this->attributes[ATTRIB_ROOM_HMS_SIMULCAST] = $hmsSimulcast;
    } 

    function setHmsSimulcastRestricted($hmsSimulcastRestricted)
    {
        $this->attributes[ATTRIB_ROOM_HMS_SIMULCAST_RESTRICTED] = $hmsSimulcastRestricted;
    } 

    function setHmsTwoWayEnabled($hmsTwoWayEnabled)
    {
        $this->attributes[ATTRIB_ROOM_HMS_TWO_WAY_ENABLED] = $hmsTwoWayEnabled;
    } 

    function setMediaFormat($mediaFormat)
    {
        $this->attributes[ATTRIB_ROOM_MEDIA_FORMAT] = $mediaFormat;
    } 

    function setMediaType($mediaType)
    {
        $this->attributes[ATTRIB_ROOM_MEDIA_TYPE] = $mediaType;
    } 

    function setArchiveEnabled($archiveEnabled)
    {
        $this->attributes[ATTRIB_ROOM_CAN_ARCHIVE] = $archiveEnabled;
    } 

    function setEboardEnabled($eboardEnabled)
    {
        $this->attributes[ATTRIB_ROOM_CAN_EBOARD] = $eboardEnabled;
    } 

    function setLiveShareEnabled($liveShareEnabled)
    {
        $this->attributes[ATTRIB_ROOM_CAN_LIVESHARE] = $liveShareEnabled;
    } 

    function setLogChatEnabled($logChatEnabled)
    {
        $this->attributes[ATTRIB_ROOM_CAN_LOGCHAT] = $logChatEnabled;
    } 

    function setMoveStudentEnabled($moveStudentEnabled)
    {
        $this->attributes[ATTRIB_ROOM_CAN_MOVE_STUDENT] = $moveStudentEnabled;
    } 

    function setPptImportEnabled($pptImportEnabled)
    {
        $this->attributes[ATTRIB_ROOM_CAN_PPT_IMPORT] = $pptImportEnabled;
    } 

    function setShowWebEnabled($showWebEnabled)
    {
        $this->attributes[ATTRIB_ROOM_CAN_SHOW_WEB] = $showWebEnabled;
    } 

    function setChatEnabled($chatEnabled)
    {
        $this->attributes[ATTRIB_ROOM_CHATENABLE] = $chatEnabled;
    } 

    function setPrivateChatEnabled($privateChatEnabled)
    {
        $this->attributes[ATTRIB_ROOM_PRIVATECHATENABLE] = $privateChatEnabled;
    } 

    function setStudentWhiteboardEnabled($studentWhiteboardEnabled)
    {
        $this->attributes[ATTRIB_ROOM_STUDENT_WB_ENABLED] = $studentWhiteboardEnabled;
    } 

    function setStudentLiveAppEnabled($studentLiveAppEnabled)
    {
        $this->attributes[ATTRIB_ROOM_STUDENT_WB_LIVEAPP] = $studentLiveAppEnabled;
    } 

    function setUserLimit($userLimit)
    {
        $this->attributes[ATTRIB_ROOM_USERLIMIT] = $userLimit;
    } 
    
    function setGuestURL($guestURL)
    {
        $this->attributes[ATTRIB_ROOM_GUEST_URL] = $guestURL;
    }
    
    function setGuestAccess($guestAccess)
    {
        $this->attributes[ATTRIB_ROOM_ENABLE_GUEST_ACCESS] = $guestAccess;
    }

    function setArchiveReminderEnabled($enabled)
    {
        $this->attributes[ATTRIB_ROOM_ARCHIVE_REMINDER_ENABLED] = $enabled;
    }

    function getHmsSimulcast()
    {
        return $this->attributes[ATTRIB_ROOM_HMS_SIMULCAST];
    } 

    function isHmsSimulcastRestricted()
    {
        return $this->attributes[ATTRIB_ROOM_HMS_SIMULCAST_RESTRICTED];
    } 

    function isHmsTwoWayEnabled()
    {
        return $this->attributes[ATTRIB_ROOM_HMS_TWO_WAY_ENABLED];
    } 

    function getMediaFormat()
    {
        return $this->attributes[ATTRIB_ROOM_MEDIA_FORMAT];
    } 

    function getMediaType()
    {
        return $this->attributes[ATTRIB_ROOM_MEDIA_TYPE];
    } 

    function isArchiveEnabled()
    {
        return $this->attributes[ATTRIB_ROOM_CAN_ARCHIVE];
    }

    function isArchiveReminderEnabled()
    {
        return $this->attributes[ATTRIB_ROOM_ARCHIVE_REMINDER_ENABLED];
    } 

    function isEboardEnabled()
    {
        return $this->attributes[ATTRIB_ROOM_CAN_EBOARD];
    } 

    function isLiveShareEnabled()
    {
        return $this->attributes[ATTRIB_ROOM_CAN_LIVESHARE];
    } 

    function isLogChatEnabled()
    {
        return $this->attributes[ATTRIB_ROOM_CAN_LOGCHAT ];
    } 

    function isMoveStudentEnabled()
    {
        return $this->attributes[ATTRIB_ROOM_CAN_MOVE_STUDENT];
    } 

    function isPptImportEnabled()
    {
        return $this->attributes[ATTRIB_ROOM_CAN_PPT_IMPORT];
    } 

    function isShowWebEnabled()
    {
        return $this->attributes[ATTRIB_ROOM_CAN_SHOW_WEB];
    } 

    function isChatEnabled()
    {
        return $this->attributes[ATTRIB_ROOM_CHATENABLE];
    } 

    function isPrivateChatEnabled()
    {
        return $this->attributes[ATTRIB_ROOM_PRIVATECHATENABLE];
    } 

    function isStudentWhiteboardEnabled()
    {
        return $this->attributes[ATTRIB_ROOM_STUDENT_WB_ENABLED] ;
    } 

    function isStudentLiveAppEnabled()
    {
        return $this->attributes[ATTRIB_ROOM_STUDENT_WB_LIVEAPP];
    } 

    function getUserLimit()
    {
        return $this->attributes[ATTRIB_ROOM_USERLIMIT];
    } 
    
    function getGuestURL()
    {
        return $this->attributes[ATTRIB_ROOM_GUEST_URL];
    }

    function setVideoFrameWidth($videoFrameWidth)
    {
        $this->attributes[ATTRIB_ROOM_VF_HEIGHT] = $videoFrameWidth;
    } 

    function getVideoFrameWidth()
    {
        return $this->attributes[ATTRIB_ROOM_VF_HEIGHT];
    } 

    function setVideoFrameHeight($videoFrameHeight)
    {
        $this->attributes[ATTRIB_ROOM_VF_HEIGHT] = $videoFrameHeight;
    } 

    function getVideoFrameHeight()
    {
        return $this->attributes[ATTRIB_ROOM_VF_HEIGHT];
    } 

    function setVideoFrameLocation($videoFrameLocation)
    {
        $this->attributes[ATTRIB_ROOM_VF_LOCATION] = $videoFrameLocation;
    } 

    function getVideoFrameLocation()
    {
        return $this->attributes[ATTRIB_ROOM_VF_LOCATION];
    } 

    function setVideoFrameSet($videoFrameSet)
    {
        $this->attributes[ATTRIB_ROOM_VIDEOFRAMESET] = $videoFrameSet;
    } 

    function isVideoFrameSet()
    {
        return $this->attributes[ATTRIB_ROOM_VIDEOFRAMESET];
    } 

    function getParticipantPin()
    {
        return $this->attributes[ATTRIB_ROOM_PARTICIPANT_PIN];
    } 

    function setParticipantPin($participantPin)
    {
        $this->attributes[ATTRIB_ROOM_PARTICIPANT_PIN] = $participantPin;
    } 

    function getPresenterPin()
    {
        return $this->attributes[ATTRIB_ROOM_PRESENTER_PIN];
    } 

    function setPresenterPin($presenterPin)
    {
        $this->attributes[ATTRIB_ROOM_PRESENTER_PIN] = $presenterPin;
    } 

    function isBORAutoMoveInstructors()
    {
        return $this->attributes[ATTRIB_BOR_AUTO_MOVE_INSTRUCTORS];
    } 

    function setBORAutoMoveInstructors($isAutoMoveInstructors)
    {
        $this->attributes[ATTRIB_BOR_AUTO_MOVE_INSTRUCTORS] = $isAutoMoveInstructors;
    } 

    function isBORAutoMoveSelf()
    {
        return $this->attributes[ATTRIB_BOR_AUTO_MOVE_SELF];
    } 

    function setBORAutoMoveSelf($isAutoMoveSelf)
    {
        $this->attributes[ATTRIB_BOR_AUTO_MOVE_SELF] = $isAutoMoveSelf;
    } 

    function isBORCarouselsPublic()
    {
        return $this->attributes[ATTRIB_BOR_CAROUSELS_PUBLIC];
    } 

    function setBORCarouselsPublic($isCarouselsPublic)
    {
        $this->attributes[ATTRIB_BOR_CAROUSELS_PUBLIC] = $isCarouselsPublic;
    } 

    function isBOREnabled()
    {
        return $this->attributes[ATTRIB_BOR_ENABLED];
    } 

    function setBOREnabled($isEnabled)
    {
        $this->attributes[ATTRIB_BOR_ENABLED] = $isEnabled;
    } 

    function isBORShowRoomCarousels()
    {
        return $this->attributes[ATTRIB_BOR_SHOW_ROOM_CAROUSELS];
    } 

    function setBORShowRoomCarousels($isShowRoomCarousels)
    {
        $this->attributes[ATTRIB_BOR_SHOW_ROOM_CAROUSELS] = $isShowRoomCarousels;
    } 

    function getBORInitialNumber()
    {
        return $this->attributes[ATTRIB_BOR_INITIAL_NUMBER];
    } 

    function setBORInitialNumber($initialNumber)
    {
        $this->attributes[ATTRIB_BOR_INITIAL_NUMBER] = $initialNumber;
    } 

    function isStudentVideoOnStartupEnabled()
    {
        return $this->attributes[ATTRIB_ROOM_ENABLE_STUDENT_VIDEO_ON_STARTUP];
    } 

    function setStudentVideoOnStartupEnabled($isStudentVideoOnStartupEnabled)
    {
        $this->attributes[ATTRIB_ROOM_ENABLE_STUDENT_VIDEO_ON_STARTUP] = $isStudentVideoOnStartupEnabled;
    } 

    function getVideoWindowSizeOnStartup()
    {
        return $this->attributes[ATTRIB_ROOM_HMS_VIDEO_WINDOW_SIZE_ON_STARTUP];
    } 

    function setVideoWindowSizeOnStartup($videoWindowSizeOnStartup)
    {
        $this->attributes[ATTRIB_ROOM_HMS_VIDEO_WINDOW_SIZE_ON_STARTUP] = $videoWindowSizeOnStartup;
    } 

    function getVideoWindowEncodingSize()
    {
        return $this->attributes[ATTRIB_ROOM_HMS_VIDEO_WINDOW_ENCODING_SIZE];
    } 

    function setVideoWindowEncodingSize($videoWindowEncodingSize)
    {
        $this->attributes[ATTRIB_ROOM_HMS_VIDEO_WINDOW_ENCODING_SIZE] = $videoWindowEncodingSize;
    } 

    function getVideoDefaultBitRate()
    {
        return $this->attributes[ATTRIB_ROOM_HMS_VIDEO_DEFAULT_BIT_RATE];
    } 

    function setVideoDefaultBitRate($videoDefaultBitRate)
    {
        $this->attributes[ATTRIB_ROOM_HMS_VIDEO_DEFAULT_BIT_RATE] = $videoDefaultBitRate;
    } 

    function getVideoBitRateCeiling()
    {
        return $this->attributes[ATTRIB_ROOM_HMS_VIDEO_BIT_RATE_CEILING];
    } 

    function setVideoBitRateCeiling($videoBitRateCeiling)
    {
        $this->attributes[ATTRIB_ROOM_HMS_VIDEO_BIT_RATE_CEILING] = $videoBitRateCeiling;
    } 

    function isUserstatusEnabled()
    {
        return $this->attributes[ATTRIB_ROOM_USERSTATUS_ENABLED];
    } 

    function setUserstatusEnabled($isEnabled)
    {
        $this->attributes[ATTRIB_ROOM_USERSTATUS_ENABLED] = $isEnabled;
    } 

    function isSendUserstatusUpdates()
    {
        return $this->attributes[ATTRIB_ROOM_SEND_USERSTATUS_UPDATES];
    } 

    function setSendUserstatusUpdates($sendUserstatusUpdates)
    {
        $this->attributes[ATTRIB_ROOM_SEND_USERSTATUS_UPDATES] = $sendUserstatusUpdates;
    } 

    function getVideoBandwidth()
    {
        return $this->attributes[ATTRIB_ROOM_HMS_VIDEO_BANDWIDTH];
    } 

    function setVideoBandwidth($bandwidth)
    {
        $this->attributes[ATTRIB_ROOM_HMS_VIDEO_BANDWIDTH] = $bandwidth;
    } 

    function getAttributes()
    {
        return $this->attributes;
    } 
    
    function getKeyValue($tab,$key){
        if(array_key_exists($key,$tab)){
            return $tab[$key];
        }
        return "";
    }
    function getArchiveVersion() 
    { 
       return $this->attributes[ATTRIB_ARCHIVE_VERSION];
    }
	  	 
    function setArchiveVersion($archiveVersion)
    {
     // if(!in_array(VALUE_ARCHIVE_LIST,$archiveVersion))
     // {
     //   return false;
     // }
      $this->attributes[ATTRIB_ARCHIVE_VERSION] = $archiveVersion;
    }
    
    function isAutoOpenArchive()
    {
      return $this->attributes[ATTRIB_ROOM_AUTO_OPEN_NEW_ARCHIVES];
    }
  
    function setAutoOpenArchive($autoOpenArchive)
    {
      $this->attributes[ATTRIB_ROOM_AUTO_OPEN_NEW_ARCHIVES] = $autoOpenArchive;
    }
  
    function getMp4EncodingType()
    {
      return $this->attributes[ATTRIB_ROOM_MP4_ENCODING_TYPE];
    }
  
    function setMp4EncodingType($mp4EncodingType)
    {
      //if (!in_array(mp4EncodingTypeList,$mp4EncodingType)) {
     //   return false;
      //}
      $this->attributes[ATTRIB_ROOM_MP4_ENCODING_TYPE] = $mp4EncodingType;
    }
  
    function getMp4MediaPriority()
    {
      return $this->attributes[ATTRIB_ROOM_MP4_MEDIA_PRIORITY];
    }
  
    function setMp4MediaPriority($mp4MediaPriority)
    {
     // if (!in_array(mp4MediaPriorityList,$mp4MediaPriority)) {
       // return false;
    //  }
      $this->attributes[ATTRIB_ROOM_MP4_MEDIA_PRIORITY] = $mp4MediaPriority;
    }
  
    function isDownloadMP3Enabled()
    {
      return $this->attributes[ATTRIB_ROOM_CAN_DOWNLOAD_MP3];
    }
  
    function setDownloadMP3Enabled($downloadMP3Enabled)
    {
      $this->attributes[ATTRIB_ROOM_CAN_DOWNLOAD_MP3] = $downloadMP3Enabled;
    }
  
    function isDownloadMP4Enabled()
    {
      return $this->attributes[ATTRIB_ROOM_CAN_DOWNLOAD_MP4];
    }
  
    function setDownloadMP4Enabled($downloadMP4Enabled)
    {
      $this->attributes[ATTRIB_ROOM_CAN_DOWNLOAD_MP4] = $downloadMP4Enabled;
    }
} 

?>
