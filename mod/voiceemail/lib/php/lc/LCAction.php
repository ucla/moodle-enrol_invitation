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
 * Date: January 2007                                                         *
 *                                                                            *
 ******************************************************************************/

require_once ("LCUser.php");
require_once("PrefixUtil.php");

class LCAction {
    var $session = null;
    var $api = null;
    var $prefixUtil  =null;
    var $server = "";
    var $adminName = "";
    var $adminPass = "";
    var $courseId = "";
    var $login = "";
    var $prefix = "";
    var $errormsg = "";
    
    function LCAction($session, $server, $login, $password, $path, $courseId = "")
    {
        global $PAGE;
        $this->session = $session;
       
        $this->prefixUtil = new PrefixUtil();
        $this->prefix = $this->prefixUtil->getPrefix($login);
        
        if (isset ($this->session))
        {
            $this->courseId = $this->session->getCourseId();
        } else if (isset ($courseId) && $courseId != "")
        {
            $this->courseId = $courseId;
        }
        else
        {
            $this->courseId = $PAGE->course->id;
        }

        if ($this->courseId == "" || $this->courseId === null) {
            wimba_add_log(WIMBA_WARN,WC,__FUNCTION__ . ": Empty or Null courseId found in LCAction constructor.");
        }

        $this->api = LCApi::getInstance($server, $login, $password,$this->prefix, $this->courseId, $path);
        $this->server = $server;

        //create the user of the course
        if ($this->createFirstTime() === false) {
            $this->errormsg = $this->api->lcapi_get_errormsg();
        }
    } 

    function getServer()
    {
        return $this->server;
    } 

    function getPrefix()
    {
        return $this->prefix;
    } 

    /*
     * Create 4 room for the first use
     */
    function createFirstTime()
    {
        if ($this->api->lcapi_get_users($this->getStudentUserid()) === false) 
        {
            // if the above failed we should not continue
            if ($this->api->lcapi_get_error() != null) {
                return false;
            }
            // create the two users
            if ($this->api->lcapi_create_user($this->getStudentUserid()) === false) {
                return false;
            }
        } 
        
        if ($this->api->lcapi_get_users($this->getTeacherUserid()) === false) 
        {
            // if the above failed we should not continue
            if ($this->api->lcapi_get_error() != null) {
                return false;
            }
            // create the two users
            if ($this->api->lcapi_create_user($this->getTeacherUserid()) === false) {
                return false;
            } 
        }
        
        return true; 
    } 

    function createRoom($roomId, $update)
    {
        $user_Student = new LCUser($this->api->lcapi_get_users($this->getStudentUserid()), $this->prefix);
        $user_Instructor = new LCUser($this->api->lcapi_get_users($this->getTeacherUserid()), $this->prefix);
        $room = new LCRoom();

        if ($update == "false") 
        {
            $room->setArguments($roomId, $this->session->request["description"], $this->session->request["longname"], null, false, false);
        }
        else 
        {
            $room = $this->api->lcapi_get_room_info($roomId);

            $room->setDescription($this->session->request["description"]);
            $room->setLongname($this->session->request["longname"]);
        } 
        // Access settings
        if ( $this->session->request["accessAvailable"] == "1") 
        {
            $room->setPreview("0");
        }
        else 
        {
            $room->setPreview("1");
        } 

        if ($this->session->request["action"] == "create" || $room->isArchive() == false) 
        {
            if ($this->session->request["led"] == "student") 
            { // discussion room
                // Archives
                $room->setArchiveEnabled($this->session->request["archiveEnabled"]);

                // Default media
                $room->setHmsTwoWayEnabled("1");
                $room->setStudentVideoOnStartupEnabled("1");
                $room->setHmsSimulcastRestricted("0");
                $room->setVideoBandwidth($this->session->request["video_bandwidth"]); 
                // Default chat
                $room->setChatEnabled("1");
                $room->setPrivateChatEnabled("1"); 
                // Default features
                // eBoard
                $room->setStudentWhiteboardEnabled("0");
                $room->setBOREnabled("0");
                $room->setBORCarouselsPublic("0");
                $room->setBORShowRoomCarousels("0");

                $room->setArchiveEnabled($this->session->request["archiveEnabled"]);
                $room->setLiveShareEnabled($this->session->request["appshareEnabled"]);
                $room->setPptImportEnabled($this->session->request["pptEnabled"]);
                $room->setGuestAccess($this->session->request["guestAcess_value"]);
            } 
            else 
            {
                // archives
                $room->setArchiveEnabled("1");

                // media
                $room->setHmsTwoWayEnabled($this->session->request["hms_two_way_enabled"]);
                $room->setStudentVideoOnStartupEnabled($this->session->request["enable_student_video_on_startup"]);
                if ($this->session->request["hms_simulcast_restricted"] == "0") 
                {
                    $room->setHmsSimulcastRestricted("1");
                } 
                else 
                {
                    $room->setHmsSimulcastRestricted("0");
                } 
                $room->setVideoBandwidth($this->session->request["video_bandwidth"]);
                if ($this->session->request["video_bandwidth"] == "custom")
                {
                    $room->setVideoWindowSizeOnStartup($this->session->request["video_window_size_on_startup"]);
                    $room->setVideoWindowEncodingSize($this->session->request["video_window_encoding_size"]);
                    $room->setVideoDefaultBitRate($this->session->request["video_default_bit_rate"]);
                }
                // Chat
                $room->setChatEnabled($this->session->request["chatEnabled"]);
                $room->setPrivateChatEnabled($this->session->request["privateChatEnabled"]);

                $room->setStudentWhiteboardEnabled($this->session->request["enabled_student_eboard"]);

                $room->setBOREnabled($this->session->request["enabled_breakoutrooms"]);
                $room->setBORCarouselsPublic($this->session->request["enabled_students_breakoutrooms"]);
                $room->setBORShowRoomCarousels($this->session->request["enabled_students_mainrooms"]);

                $room->setLiveShareEnabled("1");
                $room->setPptImportEnabled("1");
            } 
            // common features
            $room->setUserstatusEnabled($this->session->request["enabled_status"]);
            $room->setSendUserstatusUpdates($this->session->request["status_appear"]); 
            // Maximum Users
            if ($this->session->request["userlimit"] == true) 
            {
                $room->setUserLimit((string) $this->session->request["userlimitValue"]);
            } 
            else 
            {
                $room->setUserLimit("-1");
            } 
            // no limit
        } 
        
        if(!$room->isArchive() || $room->getArchiveVersion() == VALUE_50_ARCHIVE){
          //mp3/mp4 room settings
           $allowMp3Download = $this->session->request["can_download_mp3"];
           $allowMp4Download = $this->session->request["can_download_mp4"];
           $mp4EncodingType = $this->session->request["mp4_encoding_type"];
           $mp4MediaPriority = $this->session->request["mp4_media_priority"];
           $mp4NotIncludeVideo = $this->session->request["mp4_media_priority_content_include_video"];
    
           $room->setDownloadMP3Enabled($allowMp3Download);
           $room->setDownloadMP4Enabled($allowMp4Download);
           $room->setMp4EncodingType($mp4EncodingType);
    
           if ($mp4MediaPriority == VALUE_MP4_MEDIA_PRIORITY_CONTENT_FOCUS_WITH_VIDEO && $mp4NotIncludeVideo) {
             $room->setMp4MediaPriority(VALUE_MP4_MEDIA_PRIORITY_CONTENT_FOCUS);
           }
           else {
             $room->setMp4MediaPriority($mp4MediaPriority);
           }
        }

        if(!$room->isArchive())
        {
          $room->setAutoOpenArchive($this->session->request["auto_open_archive"]);
          $room->setArchiveReminderEnabled($this->session->request["display_archive_reminder"]);
          $room->setArchiveEnabled($this->session->request["enable_archives"]);
        }
        
        
        if ($update == "true") 
        { // modify the room
            $this->api->lcapi_modify_room($roomId, $room->getAttributes()); 
            // before : Students and Instructors have the same rights
            // now : Instructors lead the presentation
            if ($room->isArchive() == false) 
            {
                if ($this->session->request["led"] == "instructor" && $this->isStudentAdmin($roomId, $this->session->getCourseId() . "_S") == "true") 
                {
                    $this->api->lcapi_remove_user_role($roomId, $user_Student->getUserId(), "Instructor");
                    $this->api->lcapi_add_user_role($roomId, $user_Student->getUserId(), "Student");
                } else 
                {
                    if ($this->session->request["led"] == "student" 
                        && $this->isStudentAdmin($roomId, $this->session->getCourseId() . "_S") == "false") 
                    { // student need instructor rigths
                        $this->api->lcapi_add_user_role($roomId, $user_Student->getUserId(), "Instructor");
                        $this->api->lcapi_remove_user_role($roomId, $user_Student->getUserId(), "Student");
                    } 
                } 
            } 
        } 
        else 
        {
            $this->api->lcapi_create_class($roomId, $room->getLongname(), $room->getAttributes());

            if ($this->session->request["led"] == "student") 
            {// student have same rights than teacher
                    $this->api->lcapi_add_user_role($roomId, $user_Student->getUserId(), "Instructor");
            } 
            else 
            {
                $this->api->lcapi_add_user_role($roomId, $user_Student->getUserId(), "Student");
            } 
            $this->api->lcapi_add_user_role($roomId, $user_Instructor->getUserId(), "ClassAdmin");
        } 
        // guest access
        if ($this->session->request["guests"] == "1") 
        {
            $this->api->lcapi_add_user_role($roomId, "Guest", "Student");
        }
        else
        {
            $this->api->lcapi_remove_user_role($roomId, "Guest", "Student");
        }

        $error = $this->api->lcapi_get_error();
        if( ! empty($error) )
        {
            return "error";
        }
        return $roomId;
    } 

    /*
     * Create a default room
     */
    function createSimpleRoom($longname, $lecture, $courseId)
    { 
        // room
        $id = $courseId . rand();

        $room = new LCRoom();
        $room->setArguments($id, null, $longname, null, "0", "0");
        $room->setBORCarouselsPublic("0");
        $room->setAutoOpenArchive("1");

        $this->api->lcapi_create_class($id, $longname, $room->getAttributes());

        if ($lecture == "true")
        { // instructor lead the presentation
            $this->api->lcapi_add_user_role($id, $this->getStudentUserid(), "Student");
            $this->api->lcapi_add_user_role($id, $this->getTeacherUserid(), "ClassAdmin");
        } 
        else 
        {
            $this->api->lcapi_add_user_role($id, $this->getStudentUserid(), "Instructor");
            $this->api->lcapi_add_user_role($id, $this->getTeacherUserid(), "ClassAdmin");
        } 
        return $id;
    } 

    function getRooms($userid, $roomid = '', $archive = '0')
    {
        $rooms = $this->api->lcapi_get_rooms($userid, $roomid, $archive);

        if(!is_array($rooms)) {
            $this->errormsg = $this->api->lcapi_get_errormsg();
            return false;
        }
        return $rooms;
    } 
    function getRoom($roomid)
    {
        $this->prefixUtil->trimPrefix($roomid,$this->prefix);
        if(!$room = $this->api->lcapi_get_room_info($roomid)) {
            $this->errormsg = $this->api->lcapi_get_errormsg();
            return false;
        }
        return $room;
    } 

    function deleteRoom($roomid)
    {
        return $this->api->lcapi_delete_room($roomid);
    } 
    function getAuthoken()
    {
        $screenName = $this->session->getFirstname() . "_" . $this->session->getLastname();
        return $this->api->lcapi_get_session($this->session->getLcCurrentUser(), $screenName);
    } 
    function getAuthokenNormal($userID, $firstName, $lastName)
    {
        return $this->api->lcapi_get_session($userID, $firstName . "_" . $lastName);
    } 
    function isStudentAdmin($room_id, $user_id)
    {
        $role = $this->api->lcapi_get_user_role($room_id, $user_id);

        if ($role == "Instructor")
        {
            return "true";
        }
        return "false";
    } 
    function isGuestAuthorized($room_id)
    {
        $role = $this->api->lcapi_get_user_role($room_id, "Guest");
        if ($role == "Student")
        {
            return true;
        }
        return false;
    } 
    function getPhoneNumbers()
    {
        return $this->api->lcapi_get_simulcast();
    } 

    /**
     * Returns the LC userID of the teacher profile in the for this course
     * 
     * @return string - the LC user id of the student profile
     */
    function getStudentUserid()
    {
        return $this->courseId . "_S";
    } 

    /**
     * Returns the LC userID of the teacher profile in the for this course
     * 
     * @return string - the LC user id of the teacher profile
     */
    function getTeacherUserid()
    {
        return $this->courseId . "_T";
    } 
    
    function getRoomPreview($roomId)
    {
        $preview=$this->api->lcapi_get_room_preview($roomId);
        if(empty($preview))//the lc return an empty value when the room is open
        {
                return "0";
        }
        return $preview;
    }
    
    function setRoomPreview($roomId,$preview){
        $room=new LCRoom();
        
        $room->setPreview($preview);
        
        $this->api->lcapi_modify_room($roomId, $room->getAttributes());
    }
    
    function removeRole($roomId,$userId,$typeRole)
    {
         $roomId = $this->prefixUtil->trimPrefix($roomId,$this->prefix);
         $this->api->lcapi_remove_user_role($roomId,$userId,$typeRole);      
    }
    function cloneRoom($course_id,$roomId, $userData = "0", $isStudentAdmin, $preview )
    {
        $newId = "c".rand();
        $oldIdWithoutPrefix = $this->prefixUtil->trimPrefix($roomId,$this->prefix);
        
        if( $userData=="1" )
        {   
            $this->api->lcapi_clone_class($oldIdWithoutPrefix, $newId);
        }
        else
        {
            $this->api->lcapi_clone_class($oldIdWithoutPrefix, $newId,"1");
        }
        
        if(empty($preview))//preview is empty for the available room
        {
            $preview = "0";
        }
        
        $this->setRoomPreview($newId,$preview);
            
        if ($isStudentAdmin == "true")
        { // instructor lead the presentation
            $this->api->lcapi_add_user_role($newId, $course_id."_S", "Student");
            $this->api->lcapi_add_user_role($newId, $course_id."_T", "ClassAdmin");
        } 
        else 
        {
            $this->api->lcapi_add_user_role($newId, $course_id."_S", "Instructor");
            $this->api->lcapi_add_user_role($newId, $course_id."_T", "ClassAdmin");
        } 
        return   $newId;
       
    }
    function getVersion()
    {
        $config=$this->api->lcapi_get_status();
        $version=$config["horizon_version"];
        if(empty($version))
        {
            $version="Unknown";
        }
        return $version;
    }
    
    function getRoomName($roomId)
    {
        return $this->api->lcapi_get_room_name($roomId);
    }
    
    function getMp3Status($roomId)
    {
        return $this->api->lcapi_getMP3Status($roomId,1,$this->session->getLcCurrentUser());
    }
    function getMp4Status($roomId)
    {
        return $this->api->lcapi_getMP4Status($roomId,1,$this->session->getLcCurrentUser());
    }
    function getSystemConfig() {
        return $this->api->lcapi_get_system_config();
    }
     
} 



?>
