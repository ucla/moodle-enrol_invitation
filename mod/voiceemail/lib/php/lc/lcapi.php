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
 * Author: Hugues Pisapia                                                     *
 *                                                                            *
 * Date: February 2007                                                        *
 *                                                                            *
 ******************************************************************************/

/* $Id:$ */
/**
 * This is the API to authenticate and perform transactions with the Live cLassroom server.
 * 
 * @author Hugues Pisapia 
 * @version $Revision: 44310 $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package lcapi
 */
require_once ("LCRoom.php");
require_once ("LCUser.php");
require_once ("LCAudioFileStatus.php");
require_once("PrefixUtil.php");
require_once ($CFG->libdir.'/filelib.php');

define("LCAPI_DEBUG", true);
define("LCAPI_COMMAND", "/admin/api/api.pl?");
define("LCAPI_FUNCTION_NOOP", "function=NOOP");
define("LCAPI_FUNCTION_CREATE_USER", "function=createUser");
define("LCAPI_FUNCTION_MODIFY_USER", "function=modifyUser");
define("LCAPI_FUNCTION_LIST_USER", "function=listUser");
define("LCAPI_FUNCTION_GET_TOKEN", "function=getAuthToken");
define("LCAPI_FUNCTION_CREATE_CLASS", "function=createClass");
define("LCAPI_FUNCTION_DELETE_ROOM", "function=deleteClass");
define("LCAPI_FUNCTION_MODIFY_ROOM", "function=modifyClass");
define("LCAPI_FUNCTION_CREATE_ROLE", "function=createRole");
define("LCAPI_FUNCTION_DELETE_ROLE", "function=deleteRole");
define("LCAPI_FUNCTION_GET_ROOM_LIST", "function=listClass");
define("LCAPI_FUNCTION_LIST_ROLE", "function=listRole");
define("LCAPI_FUNCTION_CREATE_GROUP", "function=createGroup");
define("LCAPI_FUNCTION_MODIFY_GROUP", "function=modifyGroup");
define("LCAPI_FUNCTION_STATUS", "function=statusServer");
define("LCAPI_FUNCTION_LIST_SYSCONF", "function=listSysConfig");
define("LCAPI_FUNCTION_LIST_SIMULCAST", "function=listSimulcast");
define("LCAPI_FUNCTION_CLONE_CLASS", "function=cloneClass");
define("LCAPI_FUNCTION_GET_MP4_STATUS", "function=getMP4Status");
define("LCAPI_FUNCTION_GET_MP3_STATUS", "function=getMP3Status");
define("LCAPI_RECORD_SEPARATOR", "=END RECORD\n");
define("LCAPI_COOKIE_FILE", '/lcapi_cookie.txt');
define("LCAPI_COOKIE_TSTAMP", '/lcapi_tstamp.txt');
// Any new error code added here must also have a corresponding error
// message placed into error_msg_map, defined in the constructor 
define("LCAPI_ENOERR", 0); // no err
define("LCAPI_EADDR", -1); // bad address
define("LCAPI_EAUTH", -2); // authentication error
define("LCAPI_ECURL", -3); // error in the CURL layer
define("LCAPI_ECRUD", -4); // CRUD operation failed
define("LCAPI_ENOSESN", -5); // No Session Available
define("LCAPI_EEXIST", -6); // Resource already exist
define("LCAPI_EQRY", -7); // problem to send
define("LCAPI_EHTTP", -8); // Unable to connect via http
define("LCAPI_EHTTPS", -9); // Unable to connect via https
define("LCAPI_ETIME", -10); // Connection timeout
define("LCAPI_EPBCON", -11);  // Platform Bridge node communication error
define("LCAPI_ATTR_PREVIEW", "preview");
define("LCAPI_ATTR_LONGNAME", "longname");
define("LCAPI_ATTR_CLASSID", "class_id");
define("LCAPI_ATTR_ROLEID", "role_id");
define("LCAPI_ATTR_OBJECTID", "object_id");
define("LCAPI_ATTR_MEDIATYPE", "media_type");
define("LCAPI_ATTR_ARCHIVE", "archive");
define("LCAPI_ATTR_CANARCH", "can_archive");
define("LCAPI_ATTR_CANEBRD", "can_eboard");
define("LCAPI_ATTR_CANAPPSHR", "can_liveshare");
define("LCAPI_ATTR_CANPPTIMPRT", "can_ppt_import");
define("LCAPI_ATTR_CHTNBL", "chatenable");
define("LCAPI_ATTR_PRVTCHTNBL", "privatechatenable");
define("LCAPI_ATTR_DESCR", "description");
define("LCAPI_ATTR_HMSSIMCAST", "hms_simulcast");
define("LCAPI_ATTR_HMSSIMCAST_RSTRD", "hms_simulcast_restricted");
define("LCAPI_ATTR_HMS_2WYENBLD", "hms_two_way_enabled");
define("LCAPI_ATTR_STDNT_WBENBLD", "student_wb_enabled");
define("LCAPI_ATTR_STDNT_WBLVAPP", "student_wb_liveapp");
define("LCAPI_ATTR_USRLIMT", "userlimit");
define("LCAPI_ATTR_USERID", "user_id");
define("LCAPI_ATTR_FIRSTNAME", "first_name");
define("LCAPI_ATTR_LASTNAME", "last_name");
define("LCAPI_ATTR_VIDEO_BANDWIDTH", "video_bandwidth");
define("LCAPI_ATTR_ENABLE_STUDENT_VIDEO_ON_STARTUP", "enable_student_video_on_startup");
define("LCAPI_ATTR_GUEST_URL", "guest_url");
// Status
define("LCAPI_ATTR_USERSTATUS_ENABLED", "userstatus_enabled");
define("LCAPI_ATTR_SEND_USERSTATUS_UPDATES", "send_userstatus_updates");
// breakout
define("LCAPI_ATTR_ENABLED", "bor_enabled");
define("LCAPI_ATTR_CAROUSELS_PUBLIC", "bor_carousels_public");
define("LCAPI_ATTR_SHOW_ROOM_CAROUSELS", "bor_show_room_carousels");
define("LCAPI_ATTR_PARTICIPANT_PIN", "participant_pin");
define("LCAPI_ATTR_PRESENTER_PIN", "presenter_pin");
define("LCAPI_DELAY", 1800); // validity time of an auth request in seconds
define("LCAPI_ATTRIB_ROOM_AUTO_OPEN_NEW_ARCHIVES", "auto_open_new_archives");
define("LCAPI_ATTRIB_ROOM_ARCHIVE_REMINDER_ENABLED", "display_archive_reminder");
define("LCAPI_ATTRIB_ROOM_HMS_VIDEO_WINDOW_SIZE_ON_STARTUP", "video_window_size_on_startup");
define("LCAPI_ATTRIB_ROOM_HMS_VIDEO_WINDOW_ENCODING_SIZE", "video_window_encoding_size");
define("LCAPI_ATTRIB_ROOM_HMS_VIDEO_DEFAULT_BIT_RATE", "video_default_bit_rate");
define("LCAPI_ATTRIB_ROOM_MP4_ENCODING_TYPE", "mp4_encoding_type");
define("LCAPI_ATTRIB_ROOM_MP4_MEDIA_PRIORITY", "mp4_media_priority");
define("LCAPI_ATTRIB_ARCHIVE_VERSION", "archive_version");
define("LCAPI_ATTRIB_ROOM_CAN_DOWNLOAD_MP4", "can_download_mp4");
define("LCAPI_ATTRIB_ROOM_CAN_DOWNLOAD_MP3", "can_download_mp3");

class LCApi {

    var $_lcapi_config;
    var $cookie_tstamp=null;
    var $cookie_file = null;
    var $valid = null; 
    // Command
    var $prefix; 
    var $error=null;
    var $cookiePath = null;
    var $tstampPath = null;
    
    function LCApi($server, $login, $password, $prefix_api, $courseId, $tmppath = "")
    {
        global $CFG, $DB;

	$this->error_msg_map[LCAPI_EADDR] = "Invalid Classroom server address.";
        $this->error_msg_map[LCAPI_EAUTH] = "Error authenticating with Classroom server.";
        $this->error_msg_map[LCAPI_ECURL] = "HTTP request failed: Error connecting to Classroom server.";
        $this->error_msg_map[LCAPI_ECRUD] = "CRUD operation failed.";
        $this->error_msg_map[LCAPI_ENOSESN] = "No session available.";
        $this->error_msg_map[LCAPI_EEXIST] = "Resource already exists.";
        $this->error_msg_map[LCAPI_EQRY] = "Error executing request.";
        $this->error_msg_map[LCAPI_EHTTP] = "HTTP Request failed: HTTPS is required for this service.";
        $this->error_msg_map[LCAPI_EHTTPS] = "HTTP Request failed: HTTPS is not supported on this service.";
        $this->error_msg_map[LCAPI_ETIME] = "HTTP request timed out.";
        $this->error_msg_map[LCAPI_EPBCON] = "Platform Bridge configuration error.";


        $this->_lcapi_config->server = $server;
        $this->_lcapi_config->login = $login;
        $this->_lcapi_config->password = $password;
        $this->_lcapi_config->courseId = $courseId;
        $course = $DB->get_record('course',array('id'=>$courseId));
        $this->_lcapi_config->shortName = $course->shortname;
        $this->_lcapi_config->tmppath = $tmppath;
        $this->prefix = $prefix_api;
        
        /****
        * Bug 28933 : Make our classroom integration working on a load balancing sytem
        * Now, we create one authentication cookie per apache instance instead of having just one 
        * shared by all the instances.
        */
        $this->cookiePath = $CFG->dataroot.LCAPI_COOKIE_FILE;
        $this->tstampPath = $CFG->dataroot.LCAPI_COOKIE_TSTAMP;
        //create the directory used to store the classroom authentification cookie
        //@mkdir($this->_lcapi_config->tmppath."/".SERVER_NAME, 0700);

    }

    function getServer()
    {
        return $this->_lcapi_config->server;
    } 

    function getAdminName()
    {
        return $this->_lcapi_config->login;
    }

    function getInstance($server, $login, $password, $prefix, $courseId, $path)
    {
        static $myInstance = null;
        if ($myInstance == null) 
        {
            $myInstance = new LCApi($server, $login, $password, $prefix, $courseId, $path);
        } 
        return $myInstance;
    } 
    /**
     * Set the error code
     * 
     * @param the $ error code to set
     * @return nothing 
     */
    function lcapi_error($error)
    {
        $this->error = $error;
    }
    
    /**
     * Returns the last error code
     * 
     * @return the last error code
     */
    function lcapi_get_error()
    {
        return $this->error;
    }
 
    /**
     * Returns the last error code
     * 
     * @return the last error code
     */
    function lcapi_get_errormsg($error=null)
    {
      if ($error === null) {
        return $this->error_msg_map[$this->lcapi_get_error()];
      } else {
        return $this->error_msg_map[$error];
      }
    } 
    
    /**
     * Returns an Array Platform Bridge header elements
     */
    function get_bridge_header_elements() {
      return array("X-Wimba-CourseId: ".$this->_lcapi_config->shortName,
                   "X-Wimba-IntegrationType: ".LIVECLASSROOM_MODULE_NAME,
                   "X-Wimba-IntegrationVersion: ".LIVECLASSROOM_MODULE_VERSION,
                   "X-Wimba-PlatformType: ".INTEGRATION_MODULE_NAME,
                   "X-Wimba-PlatformVersion: ".INTEGRATION_MODULE_VERSION);
    }
    
    /**
     * Returns an "&" delimited string of Platform Bridge header elements
     */
    function get_bridge_header_string() {
      $headarr = $this->get_bridge_header_elements();
      for ($x=0; $x<sizeof($headarr);$x++) {
        $headarr[$x] = str_replace(": ","=",$headarr[$x]);
      }
      
      return implode("&",$headarr);
    } 

    /**
     * Creates a CURL session with the Live Classroom server. Upon success, it
     * returns a CURL Handle authenticated and ready for use. It returns false 
     * otherwise.
     * 
     * @param string $server : the URL of the server
     * @param string $login : the administrator level login
     * @param string $password : the password wassowiated with $login
     * @param string $tmppath : a path to a writeable, temporary folder
     * @return object - a CURL Handle authenticated and ready for use, false otherwise
     * If false is returned, the error code can be retrieved through a call to lcapi_errno().
     */
    function lcapi_authenticate() {
      if (!isset ($this->_lcapi_config)) {
        // $this->lcapi_error(self::LCAPI_ENOCONF);
        wimba_add_log(WIMBA_ERROR,WC,__FUNCTION__ . ": LCAPI not configured in " . print_r(debug_backtrace(), true));
        return false;
      }

      if (!$this->lcapi_check_auth()) {
        $authInvalidated = 0;
        while ($authInvalidated <= 1) {
          wimba_add_log(WIMBA_DEBUG,WC,__FUNCTION__ . ": not authenticated");

          $params = "&AuthType=AuthCookieHandler".
                    "&AuthName=Horizon".
                    "&credential_0=".$this->_lcapi_config->login.
                    "&credential_1=".$this->_lcapi_config->password;

          $respcode = $this->lcapi_send_query(LCAPI_FUNCTION_NOOP,$params,true);
            
          if ($respcode == true) {
            $tstampFile = fopen($this->tstampPath, "w+"); //temporary file to check the date
            fputs($tstampFile, time()); // Add the current time into the file = last authenticate time
            wimba_add_log(WIMBA_DEBUG,WC,__FUNCTION__ . ": authentication successfull");
            return true;
          } else {
            if ($authInvalidated == 0) {
              wimba_add_log(WIMBA_DEBUG,WC,__FUNCTION__ . ": authentication failed, will invalidate auth and retry");
              $this->lcapi_invalidate_auth();
              $authInvalidated++;
            } else {
              wimba_add_log(WIMBA_DEBUG,WC,__FUNCTION__ . ": authentication failed after invalidating auth");
              return false;
            }
          }
        }
      }

	  return true;
    } 

    function lcapi_check_auth() {
        wimba_add_log(WIMBA_DEBUG,WC,__FUNCTION__ . ": checking authentication");
        if(file_exists($this->tstampPath)) {
            $tstamp = file_get_contents($this->tstampPath);
            if (file_exists($this->cookiePath) && time() < $tstamp + LCAPI_DELAY) {
                wimba_add_log(WIMBA_DEBUG,WC,__FUNCTION__ . ": already authenticated");
                return true;
            } else if (!file_exists($this->cookiePath)) {
                wimba_add_log(WIMBA_DEBUG,WC,__FUNCTION__ . ": no cookie exists");
                return false;
            } else {
                wimba_add_log(WIMBA_DEBUG,WC,__FUNCTION__ . ": cookie has expired");
                return false;
            }
        } else {
            wimba_add_log(WIMBA_DEBUG,WC,__FUNCTION__ . ": no cookie timestamp exists: ".$this->tstampPath);
            return false;
        }
    }

    function lcapi_invalidate_auth() {
        if(file_exists($this->tstampPath)) {
          wimba_add_log(WIMBA_DEBUG,WC,__FUNCTION__ . ": deleting cookie timestamp file");
          unlink($this->tstampPath);
        }
        if(file_exists($this->cookiePath)) {
            wimba_add_log(WIMBA_DEBUG,WC,__FUNCTION__ . ": deleting cookie file");
            unlink($this->cookiePath);
        }
    }

    /**
     * Send a query to the server with the given function and the given attributes  
     * Check if the session cookie exist and get the cookie creation date.
     * If it's less than LCAPI_DELAY minutes, the cookie is good for the request.
     * Otherwise a new authentication is performed.
     * 
     * @param  $function : the LC function invoked
     * @param  $params : a string containing the parameters of the query
     * @return $data : the result of the query
     */
    function lcapi_send_query($function, $params, $disable_maxredirs=false)
    {
        global $CFG;
        
        wimba_add_log(WIMBA_DEBUG,WC,__FUNCTION__ . ": Executing function=".$function);

        if($function != LCAPI_FUNCTION_NOOP && $function != LCAPI_FUNCTION_STATUS && !$this->lcapi_check_auth()) {
            wimba_add_log(WIMBA_DEBUG,WC,__FUNCTION__ . ": not authenticated, trying");
            if (!$this->lcapi_authenticate()) {
              return false;
            }
        }

        $url = $this->_lcapi_config->server . LCAPI_COMMAND . $function . $params;
        
        $bridgeHeaderElements = $this->get_bridge_header_elements();

        // Setup cURL
        $copts = array('CURLOPT_URL' => $url,
                        'CURLOPT_RETURNTRANSFER' => 1,
                        'CURLOPT_TIMEOUT' => 10,
                        'CURLOPT_FOLLOWLOCATION' => 1,
                        'CURLOPT_CAINFO' => $CFG->dataroot."/ca.crt",
                        'CURLOPT_HTTPHEADER' => $bridgeHeaderElements);
        $c = new curl();
        $c->setopt($copts);
        wimba_add_log(WIMBA_DEBUG,WC,__FUNCTION__ . ": Bridge header element: ".join(" *** ",$bridgeHeaderElements));

        if(!$disable_maxredirs) {
            $c->setopt(array('CURLOPT_MAXREDIRS' => 0));
        }
        if($function == LCAPI_FUNCTION_NOOP) {
            $c->setopt(array('CURLOPT_COOKIEJAR' => $this->cookiePath));
        } else {
            $c->setopt(array('CURLOPT_COOKIEFILE' => $this->cookiePath));
        }

        wimba_add_log(WIMBA_DEBUG,WC,__FUNCTION__ . ": executing request: " . $url);
        $response = $c->get($url);
        wimba_add_log(WIMBA_DEBUG,WC,__FUNCTION__ . ": RESPONSE=".$response);
        $errno = $c->error;

        if($errno > 0) {
            $this->lcapi_error(LCAPI_ECURL);
            wimba_add_log(WIMBA_ERROR,WC,__FUNCTION__ . ": HTTP Request failed: " . $c->error . ':' . $c->error);
        }

        // If the address wasn't valid return false
        if ($errno == '6' || $errno == '7') {
            $this->lcapi_error(LCAPI_EADDR);
            return false;
        }
        
        if ($errno == '56') {
            $this->lcapi_error(LCAPI_EQRY);
            return false;
        }
        
        // This is what I believe is a bug in the curl API
        // The error is thrown when we can't connect sometimes
        // It's basically saying it can't follow a redirect
        // Just trap it and report a generic error
        if ($errno == '47') {
            $this->lcapi_error(LCAPI_EQRY);
            return false;
        }

        // If HTTPS is not suported return false
        if(preg_match('/HTTPS Not Supported/',$response)) {
            $this->lcapi_error(LCAPI_EHTTPS);
            wimba_add_log(WIMBA_ERROR,WC,__FUNCTION__ . ": ".$this->error_msg_map[LCAPI_EHTTPS]);
            return false;
        }

        // If HTTPS is required return false
        if($errno > 0 && preg_match('/^http:\/\//i',$url)) {
            $this->lcapi_error(LCAPI_EHTTP);
            wimba_add_log(WIMBA_ERROR,WC,__FUNCTION__ . ": ".$this->error_msg_map[LCAPI_EHTTP]);
            return false;
        }

        // If we were redirected to a https url when we supplied a http one return false
        if(preg_match('/^http:\/\//i',$url) && preg_match('/^https:\/\//i',$c->info['url'])) {
            $this->lcapi_error(LCAPI_EHTTP);
            wimba_add_log(WIMBA_DEBUG,WC,__FUNCTION__ . ": change of protocol detected (http request sent https url received)");
            wimba_add_log(WIMBA_ERROR,WC,__FUNCTION__ . ": HTTP Request failed: HTTPS is required for this service.");
            return false;
        }
        // The Platform Bridge will actually return a valid 100 code in the case of a 401 error
        // We need to detect this error
        if(preg_match('/HTTP Status 401.*This request requires HTTP authentication/',$response)) {
            $this->lcapi_error(LCAPI_EAUTH);
            wimba_add_log(WIMBA_ERROR,WC,__FUNCTION__ . ": Platform Bridge Authentication Failure.");
            return false;
        }
        
        // More Bridge error detection witha valid 100 return code
        if(preg_match('/HTTP Status 500.*cluster status is not OK/',$response)) {
            $this->lcapi_error(LCAPI_EPBCON);
            wimba_add_log(WIMBA_ERROR,WC,__FUNCTION__ . ": Platform Bridge Configuration Error.");
            return false;
        }

        // Check auth if required
        if($function == LCAPI_FUNCTION_NOOP) {
            if (isset($response)) {
                preg_match("/^\s*(\d+)\s/", $response, $matches);
                if (!empty($matches)) {
                    $resp_code = $matches[0];
                }
            } else {
                // more than likely a timeout issue
                $this->lcapi_error(LCAPI_ETIME);
                wimba_add_log(WIMBA_ERROR,WC,__FUNCTION__ . ": Empty response, treating as a timeout (408)");
                return false;
            }
            if (isset($resp_code) && $resp_code == 204) {
                $this->lcapi_error(LCAPI_EAUTH);
                wimba_add_log(WIMBA_ERROR,WC,__FUNCTION__ . ": Authentication failed (204)");
                return false;
            } else if (!isset($resp_code) || ($resp_code != 100 && $resp_code != 301)) {
                $this->lcapi_error(LCAPI_EAUTH);
                if (!isset($resp_code)) {
                    wimba_add_log(WIMBA_ERROR,WC,__FUNCTION__ . ": Authentication Failed \n $response");
                } else {
                    wimba_add_log(WIMBA_ERROR,WC,__FUNCTION__ . ": Authentication Failed: $resp_code \n $response");
                }
                return false;
            }

            wimba_add_log(WIMBA_DEBUG,WC,__FUNCTION__ . ": Authentication successful");
            return true;
        }

        return $response;
    } 
    /**
     * Create a Live Classroom User
     * 
     * @param  $userid string - LC login for this profile
     * @param  $firstname string - LC User first name
     * @param  $lastname string - LC User last name.
     * @return bool - true if the user is successfuly created or already exist, false otherwise
     */
    function lcapi_create_user($userid, $firstname="", $lastname="")
    {
        $params = "&target=".$this->prefix.$userid. 
                  "&first_name=".$firstname.
                  "&last_name=".$lastname;
        
        $enc_params = str_replace(" ", "+", $params);
        $response = $this->lcapi_send_query(LCAPI_FUNCTION_CREATE_USER, $enc_params);
        preg_match("(\d*)", $response, $matches);
        $lc_respcode = $matches[0];
      
        if ($lc_respcode == 301) 
        {
            $this->lcapi_error(LCAPI_EEXIST);
            wimba_add_log(WIMBA_DEBUG,WC,"User ($this->prefix$userid, $firstname, $lastname) already exist, not created.");
            return false;
        } 
        if ($lc_respcode != 100) 
        {
            $this->lcapi_error(LCAPI_ECRUD);
            wimba_add_log(WIMBA_ERROR,WC,__FUNCTION__ . ": User Creation Failed for ($this->prefix$userid, $firstname, $lastname) with $lc_respcode");
            return false;
        } 

        wimba_add_log(WIMBA_DEBUG,WC,"User ($this->prefix$userid, $firstname, $lastname) created.");
        return true;
    } 
    
    /**
     * Returns a session id (hzA) to be inserted in URLs to access the LC server
     * 
     * @param  $userid string - the LC userid of the profile to be used
     * @param  $nickname string - the name of the user that will be displayed in the LC
     * @return string - the session token (hzA) if the request was successful, 
     * false otherwise.
     */
    function lcapi_get_session($userid, $nickname)
    {
    wimba_add_log(WIMBA_ERROR,WC,__FUNCTION__ . " IN lcapi_get_session");
        $params = "&target=".$this->prefix.$userid. 
                  "&nickname=$nickname";
        
        $enc_params = str_replace("&nbsp;", "+", $params);

        $response = $this->lcapi_send_query(LCAPI_FUNCTION_GET_TOKEN, $enc_params);

        preg_match("(\d*)", $response, $matches);
        $respcode = $matches[0];
        if ($respcode != 100) 
        {
            $this->lcapi_error(LCAPI_ENOSESN);
            wimba_add_log(WIMBA_ERROR,WC,__FUNCTION__ . ": Cannot Create Session ($respcode).");
            return false;
        } 
        $currentline = strtok($response, "\n");
        while (!empty ($currentline) && !preg_match("/authToken=/", $currentline, $matches)) 
        {
            $currentline = strtok("\n");
        } 
        if (empty ($currentline)) 
        {
            $this->lcapi_error(LCAPI_ENOSESN);
            wimba_add_log(WIMBA_ERROR,WC,__FUNCTION__ . ": Session was returned with good code, but session not available ($enc_params => $response)");
            return false;
        } 
        $authtoken = substr($currentline, 10);
        if (empty ($authtoken)) {
        
            $this->lcapi_error(LCAPI_ENOSESN);
            wimba_add_log(WIMBA_ERROR,WC,__FUNCTION__ . ": Token empty, response: $response");
            return false;
        } 

        wimba_add_log(WIMBA_DEBUG,WC,__FUNCTION__ . ": Auth OK (hzA=$authtoken)");
        return $authtoken;
    } 
    /**
     * Filters the room parameters and makes sure all parameters make sense
     * 
     * @param  $ &$attributes a reference to the attributes
     * @return the $attributes array with (name, value) pairs that 
     * can be assembled and sent to the LC server.
     */
    function lcapi_check_room_params(&$attributes)
    {
        unset ($attributes['longname']);
        unset ($attributes['led']); 
        // TODO: more checking
        return $attributes;
    } 
    /**
     * Create a room on the LC server
     * 
     * @param  $roomid : LC id of the room created
     * @param  $roomname : name of the room created
     * return true if the room is created on the server, false otherwise
     */
    function lcapi_create_class($roomid, $roomname, $attributes)
    {
        $params = '';
        $this->lcapi_check_room_params($attributes);
        while (list ($key, $val) = each($attributes)) 
        {
            if (($key != 'class_id') && (isset ($val)) && ($val != "")) 
            {
                $params .= "&" . $key . "=" . $val;
            } 
        } 
        $params = "&target=" . $this->prefix . $roomid . "&longname=$roomname" . $params;

        $enc_params = str_replace(" ", "+", $params);
        $response = $this->lcapi_send_query(LCAPI_FUNCTION_CREATE_CLASS, $enc_params);
        preg_match("(\d*)", $response, $matches);
        $respcode = $matches[0];
        if ($respcode == 301) 
        {
            $this->lcapi_error(LCAPI_EEXIST);
            wimba_add_log(WIMBA_ERROR,WC,__FUNCTION__ . ": Class ($roomid, $roomname) already exist, not created");
            return false;
        } 
        if ($respcode != 100) 
        {
            $this->lcapi_error(LCAPI_ECRUD);
            wimba_add_log(WIMBA_ERROR,WC,__FUNCTION__ . ": Cannot Create Class with id : $roomid, and name : $roomname.");
            return false;
        } 

        wimba_add_log(WIMBA_INFO,WC,__FUNCTION__ . ": Class ($roomid, $roomname) created with success.");
        return true;
    } 
    /**
     * Add a role to a user for a room given
     * 
     * @param  $roomid : id of the room on the LC server
     * @param  $userid : id of the user on the LC server
     * @param  $role : role of the user for the room
     * @return true if the role is added, false otherwise
     */
    function lcapi_add_user_role($roomid, $userid, $role)
    {
        if ($userid != "Guest") {
            $userid = $this->prefix . $userid;
        } 
        
        $params = "&target=".$this->prefix.$roomid .
                  "&user_id=".$userid.
                  "&role_id=".$role;;
        $response = $this->lcapi_send_query(LCAPI_FUNCTION_CREATE_ROLE,$params);
        preg_match("(\d*)", $response, $matches);
        $respcode = $matches[0];
        if ($respcode != 100) 
        {
            $this->lcapi_error(LCAPI_ECRUD);
            wimba_add_log(WIMBA_ERROR,WC,__FUNCTION__ . ": Cannot Add Role $role to userid $userid in Class with id:$roomid");
            return false;
        } 

        wimba_add_log(WIMBA_DEBUG,WC,__FUNCTION__ . ": Role $role added for $userid in Class $roomid");
        return true;
    } 
    /**
     * Remove a role to a user for a room given
     * 
     * @param  $roomid : id of the room on the server
     * @param  $userid : id of the user on the server
     * @param  $role : role of the user for the room
     * @return true if the role is removed, false otherwise
     */
    function lcapi_remove_user_role($roomid, $userid, $role)
    {
        if ($userid != "Guest") 
        {
            $userid = $this->prefix . $userid;
        }
        $params = "&target=".$this->prefix.$roomid.
                  "&user_id=".$userid.
                  "&role_id=".$role;
        
        $response = $this->lcapi_send_query(LCAPI_FUNCTION_DELETE_ROLE, $params);
        preg_match("(\d*)", $response, $matches);
        $respcode = $matches[0];
        if ($respcode != 100) 
        {
            $this->lcapi_error(LCAPI_ECRUD);
            wimba_add_log(WIMBA_ERROR,WC,__FUNCTION__ . ": Cannot Removed Role $role from userid $this->prefix.$userid in Class with id:" . $roomid);
            return false;
        } 

        wimba_add_log(WIMBA_DEBUG,WC,__FUNCTION__ . ": Role $role removed from $userid in Class $roomid");
        return true;
    } 
    /**
     * delete the Room $roomid from the server
     * 
     * @param roomid $ : id of the room to delete
     * @return true if the room is deleted, false otherwise
     */
    function lcapi_delete_room($roomid)
    {
        $params = "&target=".$this->prefix.$roomid;
        $response = $this->lcapi_send_query(LCAPI_FUNCTION_DELETE_ROOM, $params);
        
        preg_match("(\d*)", $response, $matches);
        $respcode = $matches[0];
        if ($respcode == 302) 
        {
            $this->lcapi_error(LCAPI_ECRUD);
            wimba_add_log(WIMBA_ERROR,WC,__FUNCTION__ . ": $roomid suppression failed");
            return false;
        } 
        else
        {
            if ($respcode != 100) 
            {
                $this->lcapi_error(LCAPI_ECRUD);
                wimba_add_log(WIMBA_ERROR,WC,__FUNCTION__ . ": $roomid suppression failed for an unknown reason (response: $response)");
                return false;
            } 
        }

        wimba_add_log(WIMBA_DEBUG,WC,__FUNCTION__ . ": $roomid deleted successfully.");
        return true;
    } 
    
    
    
    /**
     * Modify the settings of a room
     * 
     * @param  $roomid : the id of the room
     * @param  $attributes : an associative array containing the room parameters
     * @return true if the room has being modified, false otherwise
     */
    function lcapi_modify_room($roomid, $attributes)
    {
        $params = "&target=" . $this->prefix . $roomid;
        while (list ($key, $val) = each($attributes)) 
        {
            if (($key != 'class_id') && (isset ($val)) && ($val != "")) 
            {
                $params .= "&" . $key . "=" . $val;
            } 
        } 
        $enc_params = str_replace(" ", "+", $params);

        $response = $this->lcapi_send_query(LCAPI_FUNCTION_MODIFY_ROOM, $enc_params);
        preg_match("(\d*)", $response, $matches);
        $respcode = $matches[0];
        if ($respcode != 100) 
        {
            $this->lcapi_error(LCAPI_ECRUD);
            wimba_add_log(WIMBA_ERROR,WC,__FUNCTION__ . ": Room modification failed ($roomid): $response");
            return false;
        } 

        wimba_add_log(WIMBA_DEBUG,WC,__FUNCTION__ . "$roomid has been modified with success");
        return true;
    } 
    /**
     * List all the archives this user has access to for the roomid.
     * If no roomid is given, it return all the archives.
     * 
     * @param  $userid : the id of the user
     * @return an array containing all the room info (also associative arrays)
     * for all rooms this LC user has access to.
     */
    function lcapi_get_archives($userid, $roomid)
    {
        $params = "&attribute=".LCAPI_ATTR_PREVIEW.
                  "&attribute=".LCAPI_ATTR_ARCHIVE.
                  "&attribute=".LCAPI_ATTR_CLASSID.
                  "&attribute=".LCAPI_ATTR_LONGNAME.
                  "&AccessUser=archive_of".
                  "&filter00name=".$roomid;
        
        $rooms = null;
        $enc_params = str_replace(" ", "+", $params);
        $response = $this->lcapi_send_query(LCAPI_FUNCTION_GET_ROOM_LIST, $enc_params);

        preg_match("(\d*)", $response, $matches);
        $respcode = $matches[0];

        if ($respcode != 100) 
        {
            $this->lcapi_error(LCAPI_ECRUD);
            wimba_add_log(WIMBA_ERROR,WC,__FUNCTION__ . ": List of archive for the room id $roomid query failed");
            return false;
        } 
        list (, $body) = explode("\n", $response, 2);
        $records = explode(LCAPI_RECORD_SEPARATOR, $body);

        for ($i = 0; $i < sizeof($records); $i++) 
        {
            $lines = explode("\n", $records[$i]);
            $record = null;
            foreach ($lines as $line) 
            {   
                if(!empty($line)){
                    list ($key, $value) = explode('=', $line);
    
                    if (!empty ($key) && $key != "") 
                    {
                        $record[$key] = $value;
                    } 
                }
            } 

            if ((empty ($roomid) && !empty ($record)) || // orphaned archive or room
                    (!empty ($roomid) && !empty($record[ LCAPI_ATTR_CLASSID]) && strstr($record[ LCAPI_ATTR_CLASSID], $roomid))) { // not orphaned
                $room = new LCRoom();
                $room->setByRecord($record, $this->prefix);
                $rooms[] = $room;
            } 
        } 
        
        wimba_add_log(WIMBA_DEBUG,WC,__FUNCTION__ . ": retrieved Rooms for $userid and roomid '$roomid': " . print_r($rooms, true)); 
        return $rooms;
    } 
    /**
     * List all the rooms and archives this user has access to for the roomid.
     * 
     * @param  $userid : the id of the user
     * @return an array containing all the room info (also associative arrays)
     * for all rooms this LC user has access to.
     */
    function lcapi_get_rooms($userid)
    {
        $params = "&attribute=".LCAPI_ATTR_PREVIEW.
                  "&attribute=".LCAPI_ATTR_ARCHIVE.
                  "&attribute=".LCAPI_ATTR_CLASSID.
                  "&attribute=".LCAPI_ATTR_LONGNAME.
                  "&attribute=".LCAPI_ATTR_GUEST_URL.
                  "&attribute=".LCAPI_ATTRIB_ROOM_CAN_DOWNLOAD_MP3.
                  "&attribute=".LCAPI_ATTRIB_ROOM_CAN_DOWNLOAD_MP4.
                  "&attribute=".LCAPI_ATTRIB_ROOM_AUTO_OPEN_NEW_ARCHIVES.
                  "&attribute=".LCAPI_ATTR_CANARCH.
                  "&attribute=".ATTRIB_ROOM_ARCHIVE_REMINDER_ENABLED.
                  "&AccessUser=".$this->prefix.$userid;
        
        $rooms = array();
        $enc_params = str_replace(" ", "+", $params);
        wimba_add_log(WIMBA_DEBUG,WC,__FUNCTION__.": getting a list of rooms");
        $response = $this->lcapi_send_query(LCAPI_FUNCTION_GET_ROOM_LIST, $enc_params);

        preg_match("(\d*)", $response, $matches);
        $respcode = $matches[0];

        if ($respcode != 100 || isset($this->error))
        {
            if(!isset($this->error)) { $this->lcapi_error(LCAPI_ECRUD); }
            wimba_add_log(WIMBA_ERROR,WC,__FUNCTION__ . ": List of room query failed");
            return false;
        } 
        list (, $body) = explode("\n", $response, 2);
        $records = explode(LCAPI_RECORD_SEPARATOR, $body);

        for ($i = 0; $i < sizeof($records); $i++) 
        {
            $lines = explode("\n", $records[$i]);
            $record = null;
            foreach ($lines as $line) 
            {
                if(!empty($line)){
                    list ($key, $value) = explode('=', $line, 2);
                    if (!empty ($key) && $key != "") 
                    {
                        $record[$key] = $value;
                    } 
                }
            } 

            if ((empty ($roomid) && !empty ($record)) || // orphaned archive or room
                    (!empty ($roomid) && !empty($record[ LCAPI_ATTR_CLASSID]) && strstr($record[ LCAPI_ATTR_CLASSID], $roomid))) { // not orphaned
                $room = new LCRoom();
                $room->setByRecord($record, $this->prefix);
                $rooms[] = $room;
            } 
        } 
        
        wimba_add_log(WIMBA_DEBUG,WC,__FUNCTION__ . ": retrieved ".sizeof($rooms)." Rooms for $userid : " . print_r($rooms, true));
        return $rooms;
    } 
    /**
     * Check if a user exist with teh given parameters
     * 
     * @param  $userid : Optionnal LC UserId
     * @param  $firstname : Optionnal LC firstname
     * @param  $lastname : Optionnal LC lastname
     * @return array : containing the list of users
     */
    function lcapi_get_users($userid = '', $firstname = '', $lastname = '')
    {
        $request = "&attribute=" . LCAPI_ATTR_USERID . 
                   "&attribute=" . LCAPI_ATTR_FIRSTNAME . 
                   "&attribute=" . LCAPI_ATTR_LASTNAME;
        if (!empty ($userid)) 
        {
            $params[ LCAPI_ATTR_USERID] = $this->prefix . $userid;
        } 
        if (!empty ($firstname)) 
        {
            $params[ LCAPI_ATTR_FIRSTNAME] = str_replace(" ", "_", $firstname);
        } 
        if (!empty ($lastname)) 
        {
            $params[ LCAPI_ATTR_FIRSTNAME] = str_replace(" ", "_", $lastname);
        } 
       
        $i = 1;
        foreach (array_keys($params) as $key) 
        {
            $request .= "&filter0" . $i . "=" . $key;
            $request .= "&filter0" . $i . "value=" . $params[$key];
        } 
        $request = str_replace(" ", "+", $request);
        $response = $this->lcapi_send_query(LCAPI_FUNCTION_LIST_USER, $request);

        wimba_add_log(WIMBA_DEBUG,WC,__FUNCTION__ . ": request '$request', response: $response");
       
        preg_match("(\d*)", $response, $matches);
        $respcode = $matches[0];
        if ($respcode != 100) 
        {
            wimba_add_log(WIMBA_ERROR,WC,__FUNCTION__ . ": Unable to query the server. request: '$request', response: '$response'");
            return false;
        } 
        list (, $body) = explode("\n", $response, 2);
       
        if (empty($body)) 
        { // no users for this id
            return false;
        } 
        $records = explode(LCAPI_RECORD_SEPARATOR, $body);
        $answers = array ();
        foreach ($records as $record) 
        {
            $lines = explode("\n", $record);
            $empty = true;
            foreach ($lines as $line) {
               if(!empty($line)){
                    list ($key, $value) = explode('=', $line);
                    
                    if (!empty ($key)) 
                    {
                        $answer[$key] = $value;
                        $empty = false;
                    } 
               }
            } 
            if (!$empty) 
            {
                $answers[$answer[ LCAPI_ATTR_USERID]] = $answer;
            } 
        } 

        wimba_add_log(WIMBA_DEBUG,WC,__FUNCTION__ . ": returning answers: " . print_r($answers, true));
        return $answer;
    } 
    
    /**
     * Give the role of the user on a given room
     * 
     * @param  $roomid : the LC id of the room
     * @param  $userid : the LC id of the user
     * @return a string : the role of the user
     */
    function lcapi_get_user_role($roomid, $userid)
    {
        if ($userid != "Guest")
        {
            $userid = $this->prefix.$userid;
        }
        $params = "&attribute=".LCAPI_ATTR_ROLEID. 
                  "&filter01=".LCAPI_ATTR_USERID. 
                  "&filter01value=".$userid. 
                  "&filter02=".LCAPI_ATTR_OBJECTID. 
                  "&filter02value=".$this->prefix.$roomid;

        $response = $this->lcapi_send_query(LCAPI_FUNCTION_LIST_ROLE, $params);
        preg_match("(\d*)", $response, $matches);
        $respcode = $matches[0];
        if ($respcode != 100) 
        {
            $this->lcapi_error(LCAPI_EQRY);
            wimba_add_log(WIMBA_ERROR,WC,__FUNCTION__ . ": request '$params', response: $reponse");
            return false;
        } 
        // Some dichotomy to retreive the role name
        list (, $body) = explode("\n", $response, 2);
        $records = explode(LCAPI_RECORD_SEPARATOR, $body);
        $result = explode(LCAPI_ATTR_ROLEID . "=", $records[0]);
        if(isset($result[1])){
          $result = explode("\n", $result[1]);
        }
        wimba_add_log(WIMBA_DEBUG,WC,__FUNCTION__ . ": returning answer: " . $result[0]);
        return $result[0];
    } 
    
    /**
     * Check if the room is an archive
     * 
     * @param  $roomid : the id of the room
     * @return a boolean : true if the room is an archive, false if it's otherwize
     */
    function lcapi_room_is_archive($roomid)
    {
        $params = "&filter01=".LCAPI_ATTR_CLASSID .
                  "&filter01value=".$this->prefix . $roomid . 
                  "&filter02=".LCAPI_ATTR_ARCHIVE . 
                  "&filter02value=1";
        
        $response = $this->lcapi_send_query(LCAPI_FUNCTION_GET_ROOM_LIST, $params);
        preg_match("(\d*)", $response, $matches);
        $respcode = $matches[0];
        if ($respcode != 100) 
        {
            $this->lcapi_error(LCAPI_EQRY);
            wimba_add_log(WIMBA_ERROR,WC,__FUNCTION__ . ": Cannot perform query: $params, reponse: $response");
            return false;
        } 
        list (, $body) = explode("\n", $response, 2);
        $records = explode(LCAPI_RECORD_SEPARATOR, $body);
        return (count($records) > 1);
    } 
    
    /**
     * Give the name of the room given
     * 
     * @param  $roomid : the id of the room
     * @return a String :name of the room
     */
    function lcapi_get_room_name($roomid)
    {
        $params = "&attribute=".LCAPI_ATTR_LONGNAME . 
                  "&filter00=".LCAPI_ATTR_CLASSID .
                  "&filter00value=".$roomid;

        wimba_add_log(WIMBA_DEBUG,WC,__FUNCTION__ . ": getting a list of rooms");
        $response = $this->lcapi_send_query(LCAPI_FUNCTION_GET_ROOM_LIST, $params);
        preg_match("(\d*)", $response, $matches);
        $respcode = $matches[0];
        if ($respcode != 100) 
        {
            $this->lcapi_error(LCAPI_EQRY);
            wimba_add_log(WIMBA_ERROR,WC,__FUNCTION__ . ": cannot retrieve the room name");
            return false;
        } 
        list (, $body) = explode("\n", $response, 2);
        $records = explode(LCAPI_RECORD_SEPARATOR, $body);
        $lines = explode("\n", $records[0]);
        foreach ($lines as $line) 
        {
            if(!empty($line)){
                list ($key, $value) = explode("=", $line);
                if ($key == LCAPI_ATTR_LONGNAME) 
                {
                    wimba_add_log(WIMBA_DEBUG,WC,__FUNCTION__ . ": $this->prefix$roomid's room name is $value");
                    return $value;
                } 
            }
        } 

        wimba_add_log(WIMBA_DEBUG,WC,__FUNCTION__ . ": did not find name of room: $roomid");
        return false;
    } 
    
    /**
     * Give the media type of the room given
     * 
     * @param  $roomid : the id of the room
     * @return a String :media type of the room
     */
    function lcapi_get_media_type($roomid)
    {
        $params = "&attribute=" . LCAPI_ATTR_MEDIATYPE . "&filter00=" . LCAPI_ATTR_CLASSID . "&filter00value=" . $this->prefix . $roomid;
        $response = lcapi_send_query(LCAPI_FUNCTION_GET_ROOM_LIST, $params);
        preg_match("(\d*)", $response, $matches);
        $respcode = $matches[0];
        if ($respcode != 100) 
        {
            $this->lcapi_error(LCAPI_EQRY);
            wimba_add_log(WIMBA_ERROR,WC,__FUNCTION__ . ": Cannot query the media type of $roomid");
            return false;
        } 
        list (, $body) = explode("\n", $response, 2);
        $records = explode(LCAPI_RECORD_SEPARATOR, $body);
        $lines = explode("\n", $records[0]);
        foreach ($lines as $line) 
        {
            if(!empty($line)){
                list ($key, $value) = explode("=", $line);
                if ($key == LCAPI_ATTR_MEDIATYPE) 
                {
                    wimba_add_log(WIMBA_DEBUG,WC,__FUNCTION__ . ": $roomid's media type is $value");
                    return $value;
                } 
                
                wimba_add_log(WIMBA_DEBUG,WC,__FUNCTION__ . ": did not find media type for $roomid");
                return false;
            }
        } 
    } 
    /**
     * Give the information about a room given
     * 
     * @param  $roomid : the id of the room
     * @return an array with the key as the name of the attribute
     *   and the value as the value of the attribute
     */
    function lcapi_get_room_info($roomid)
    {
        $roominfo=array();
        $params = "&filter00=".LCAPI_ATTR_CLASSID.
                  "&filter00value=".$this->prefix.$roomid.
                  "&attribute=".LCAPI_ATTR_ARCHIVE.
                  "&attribute=".LCAPI_ATTR_CANARCH. 
                  "&attribute=".LCAPI_ATTR_CANEBRD. 
                  "&attribute=".LCAPI_ATTR_CANAPPSHR.
                  "&attribute=".LCAPI_ATTR_CANPPTIMPRT. 
                  "&attribute=".LCAPI_ATTR_CHTNBL. 
                  "&attribute=".LCAPI_ATTR_PRVTCHTNBL. 
                  "&attribute=".LCAPI_ATTR_DESCR.
                  "&attribute=".LCAPI_ATTR_HMSSIMCAST.
                  "&attribute=".LCAPI_ATTR_HMSSIMCAST_RSTRD.
                  "&attribute=".LCAPI_ATTR_HMS_2WYENBLD. 
                  "&attribute=".LCAPI_ATTR_MEDIATYPE.
                  "&attribute=".LCAPI_ATTR_PREVIEW. 
                  "&attribute=".LCAPI_ATTR_STDNT_WBENBLD. 
                  "&attribute=".LCAPI_ATTR_STDNT_WBLVAPP. 
                  "&attribute=".LCAPI_ATTR_USRLIMT. 
                  "&attribute=".LCAPI_ATTR_LONGNAME. 
                  "&attribute=".LCAPI_ATTR_VIDEO_BANDWIDTH. 
                  "&attribute=".LCAPI_ATTR_ENABLE_STUDENT_VIDEO_ON_STARTUP. 
                  "&attribute=".LCAPI_ATTR_USERSTATUS_ENABLED. 
                  "&attribute=".LCAPI_ATTR_SEND_USERSTATUS_UPDATES. 
                  "&attribute=".LCAPI_ATTR_ENABLED. 
                  "&attribute=".LCAPI_ATTR_CAROUSELS_PUBLIC. 
                  "&attribute=".LCAPI_ATTR_SHOW_ROOM_CAROUSELS. 
                  "&attribute=".LCAPI_ATTR_PARTICIPANT_PIN. 
                  "&attribute=".LCAPI_ATTR_PRESENTER_PIN.
                  "&attribute=".LCAPI_ATTRIB_ROOM_HMS_VIDEO_WINDOW_SIZE_ON_STARTUP.
                  "&attribute=".LCAPI_ATTRIB_ROOM_HMS_VIDEO_WINDOW_ENCODING_SIZE.
                  "&attribute=".LCAPI_ATTRIB_ROOM_HMS_VIDEO_DEFAULT_BIT_RATE.
                  "&attribute=".LCAPI_ATTRIB_ROOM_CAN_DOWNLOAD_MP3.
                  "&attribute=".LCAPI_ATTRIB_ROOM_CAN_DOWNLOAD_MP4.
                  "&attribute=".LCAPI_ATTRIB_ROOM_MP4_ENCODING_TYPE.
                  "&attribute=".LCAPI_ATTRIB_ROOM_MP4_MEDIA_PRIORITY.
                  "&attribute=".LCAPI_ATTRIB_ARCHIVE_VERSION.
                  "&attribute=".LCAPI_ATTRIB_ROOM_AUTO_OPEN_NEW_ARCHIVES.
                  "&attribute=".LCAPI_ATTR_GUEST_URL.
                  "&attribute=".LCAPI_ATTRIB_ROOM_ARCHIVE_REMINDER_ENABLED;
        
        $response = $this->lcapi_send_query(LCAPI_FUNCTION_GET_ROOM_LIST, $params);
        preg_match("(\d*)", $response, $matches);
        $respcode = $matches[0];
        if ($respcode != 100) 
        {
            // $this->lcapi_error(self::LCAPIEQRY);
            wimba_add_log(WIMBA_ERROR,WC,__FUNCTION__ . ": cannot query room info for $roomid");
            return false;
        } 
        
        list (, $body) = explode("\n", $response, 2);
        $records = explode(LCAPI_RECORD_SEPARATOR, $body);
        $lines = explode("\n", $records[0]);
        foreach ($lines as $line) 
        {
            if(!empty($line)){
                list ($key, $value) = explode("=", $line, 2);
                if (!empty ($key)) 
                {
                    $roominfo[$key] = $value;
                }
            } 
        } 
        
        $room = new LCRoom();
        $room->setByRecord($roominfo, $this->prefix);
        
        wimba_add_log(WIMBA_DEBUG,WC,__FUNCTION__ . ": returning room info for $roomid: " . print_r($roominfo, true));
        return $room;
    } 
    
    /**
     * Give the preview information about a room given
     * 
     * @param  $roomid : the id of the room
     * @return an array with the key as the name of the attribute
     *   and the value as the value of the attribute
     */
    function lcapi_get_room_preview($roomid)
    {
        $roominfo=array();
        $params = "&filter00=".LCAPI_ATTR_CLASSID.
                  "&filter00value=".$this->prefix.$roomid.
                  "&attribute=".LCAPI_ATTR_PREVIEW;
               

        $response = $this->lcapi_send_query(LCAPI_FUNCTION_GET_ROOM_LIST, $params);
        preg_match("(\d*)", $response, $matches);
        $respcode = $matches[0];
        if ($respcode != 100) 
        {
            // $this->lcapi_error(self::LCAPIEQRY);
            wimba_add_log(WIMBA_ERROR,WC,__FUNCTION__ . ": cannot query room info for $roomid");
            return false;
        } 

        list (, $body) = explode("\n", $response, 2);
        $records = explode(LCAPI_RECORD_SEPARATOR, $body);
        $lines = explode("\n", $records[0]);
        foreach ($lines as $line) 
        {
            if(!empty($line)){
                list ($key, $value) = explode("=", $line);
                if (!empty ($key)) 
                {
                    $roominfo[$key] = $value;
                }
            } 
        } 
        
        $room = new LCRoom();
        $room->setByRecord($roominfo, $this->prefix);
        
        wimba_add_log(WIMBA_DEBUG,WC,__FUNCTION__ . ": returning room availability for $roomid: " . print_r($roominfo, true));
        return $room->isPreview();
    } 
    
    /**
     * Returns status information from the server
     * 
     * @return an associative array containing the server status as $result[$key]=$value;
     */
    function lcapi_get_status()
    {
        $response = $this->lcapi_send_query(LCAPI_FUNCTION_STATUS, "");
        wimba_add_log(WIMBA_DEBUG,WC,__FUNCTION__ . ": called send_query and got response:". $response);
        preg_match("(\d*)", $response, $matches);
        $respcode = $matches[0];
        if ($respcode != 100) 
        {
            $this->lcapi_error(LCAPI_EQRY);
            wimba_add_log(WIMBA_ERROR,WC,__FUNCTION__ . ": Query failed: " . $response);
            return false;
        } 
        list (, $body) = explode("\n", $response, 2);
        $records = explode(LCAPI_RECORD_SEPARATOR, $body);
        $lines = explode("\n", $records[0]);
        foreach ($lines as $line) 
        {
            if(!empty($line)){
                list ($key, $value) = explode("=", $line);
                $answer[trim($key)] = trim($value);
            }
        } 

        wimba_add_log(WIMBA_DEBUG,WC,__FUNCTION__ . ": returning server status: " . print_r($answer, true));
        return $answer;
    } 
    
    /**
     * Returns the system configuration
     * 
     * @return an associative array containing the system configuration as $result[$key]=$value;
     */
    function lcapi_get_system_config()
    {
        $response = $this->lcapi_send_query(LCAPI_FUNCTION_LIST_SYSCONF, '');
        preg_match("(\d*)", $response, $matches);
        $respcode = $matches[0];
        if ($respcode != 100) 
        {
            $this->lcapi_error(LCAPI_EQRY);
            wimba_add_log(WIMBA_ERROR,WC,__FUNCTION__ . ": Query failed: " . $response);
            return false;
        } 
        list (, $body) = explode("\n", $response, 2);
        $records = explode(LCAPI_RECORD_SEPARATOR, $body);
        foreach ($records as $record) 
        {
            if(!empty($record)){
                $lines = explode("\n", $record);
                list (, $key) = explode("=", $lines[0], 2);
                list (, $value) = explode("=", $lines[1], 2);
                $answers[trim($key)] = trim($value);
            }
        } 

        wimba_add_log(WIMBA_DEBUG,WC,__FUNCTION__ . ": returning server config: " . print_r($answers, true));
        return $answers;
    } 
    
    function lcapi_get_simulcast()
    {
        $response = $this->lcapi_send_query(LCAPI_FUNCTION_LIST_SIMULCAST, '');
        preg_match("(\d*)", $response, $matches);
        $respcode = $matches[0];
        if ($respcode != 100) 
        {
            $this->lcapi_error(LCAPI_EQRY);
            wimba_add_log(WIMBA_ERROR,WC,__FUNCTION__ . ": Query failed: " . $response);
            return false;
        } 
        list (, $body) = explode("\n", $response, 2);
        $records = explode(LCAPI_RECORD_SEPARATOR, $body);
        foreach ($records as $record)
        {
            if(!empty($record)){
                $lines = explode("\n", $record);
                list (, $key) = explode("=", $lines[0], 2);
                list (, $value) = explode("=", $lines[1], 2);
                $answers[trim($key)] = trim($value);
            }
        } 

        wimba_add_log(WIMBA_DEBUG,WC,__FUNCTION__ . ": returning server config: " . print_r($answers, true));
        return $answers;
    } 

 /**
     * Copy a room
     * 
     * @param  $target string - room id of the room to copy
     * @param  $new_id string - room id of the new room 
     * @param  $no_content bool - new class will not contain content from original class.
     * @param  $newName string - longname of new class.
     * @param  $no_roles bool - new class will not contain roles present in original class
     * @return bool - true if the user is successfuly created or already exist, false otherwise
     */
    function lcapi_clone_class($target, $new_id, $no_content="", $newName="",$no_roles="1")
    {
        $params = "&target=" . $this->prefix . $target . "&new_id=".$this->prefix .$new_id;
        if(isset($newName))
        {
            $params .="&new_longname=$newName";
        }
        if(isset($no_content))
        {
            $params .="&no_content=$no_content";
        }
        if(isset($no_roles))
        {
            $params .="&no_roles=$no_roles";
        }
        $enc_params = str_replace(" ", "+", $params);
        $response = $this->lcapi_send_query(LCAPI_FUNCTION_CLONE_CLASS, $enc_params);
        preg_match("(\d*)", $response, $matches);
        $lc_respcode = $matches[0];
        if ($lc_respcode == 301) 
        {
            $this->lcapi_error(LCAPI_EEXIST);
            wimba_add_log(WIMBA_DEBUG,WC,"");
            return false;
        } 
        if ($lc_respcode != 100) 
        {
            $this->lcapi_error(LCAPI_ECRUD);
            wimba_add_log(WIMBA_ERROR,WC,__FUNCTION__ . ") with $lc_respcode");
            return false;
        } 

        wimba_add_log(WIMBA_DEBUG,WC,"The copy of the room $target was done with success. The new room $new_id was created.");
        return true;
    } 
    
    function lcapi_getMP3Status($archiveId, $startGenerate,$userid)
    {
        if(empty($archiveId))
        {
          wimba_add_log(WIMBA_ERROR,WC,__FUNCTION__ ."archive should not be empty");
          return false;
        }
        
        $params = "&class_id=".$this->prefix .$archiveId;
        if(isset($startGenerate))
        {
            $params .="&start_generate=$startGenerate";
        }
        if(!empty($userid))
        {
            $params .="&user_id=".$userid;
        }
        $enc_params = str_replace(" ", "+", $params);
        $response = $this->lcapi_send_query(LCAPI_FUNCTION_GET_MP3_STATUS, $enc_params);
        preg_match("(\d*)", $response, $matches);
        $lc_respcode = $matches[0];
        if ($lc_respcode != 100) 
        {
            $this->lcapi_error(LCAPI_ECRUD);
            wimba_add_log(WIMBA_ERROR,WC,__FUNCTION__ . ") with $lc_respcode");
            return false;
        } 
        
        list (, $body) = explode("\n", $response, 2);
        $records = explode(LCAPI_RECORD_SEPARATOR, $body);
        $lines = explode("\n", $records[0]);
        foreach ($lines as $line) 
        {
            if(!empty($line)){
                list ($key, $value) = explode("=", $line);
                if (!empty ($key)) 
                {
                    $audioInfo[$key] = $value;
                }
            } 
        } 
        if($userid != null){
          $authToken = $this->lcapi_get_session($userid,"");
        }
        $audioFile = new LCAudioFileStatus($audioInfo,$authToken);
     
       
        return $audioFile;
        
        
    }
    
    function lcapi_getMP4Status($archiveId, $startGenerate, $userid)
    {
        if(empty($archiveId))
        {
          wimba_add_log(WIMBA_ERROR,WC,__FUNCTION__ ."archive should not be empty");
          return false;
        }
        
        $params = "&class_id=".$this->prefix.$archiveId;
        if(!empty($startGenerate))
        {
            $params .="&start_generate=$startGenerate";
        }
        if(!empty($userid))
        {
            $params .="&user_id=".$userid;
        }
        
        $enc_params = str_replace(" ", "+", $params);
        $response = $this->lcapi_send_query(LCAPI_FUNCTION_GET_MP4_STATUS, $enc_params);
        preg_match("(\d*)", $response, $matches);
        
        $lc_respcode = $matches[0];
        if ($lc_respcode != 100) 
        {
            $this->lcapi_error(LCAPI_ECRUD);
            wimba_add_log(WIMBA_ERROR,WC,__FUNCTION__ . ") with $lc_respcode");
            return false;
        } 
        
        list (, $body) = explode("\n", $response, 2);
        $records = explode(LCAPI_RECORD_SEPARATOR, $body);
        $lines = explode("\n", $records[0]);
        foreach ($lines as $line) 
        {
            if(!empty($line)){
                list ($key, $value) = explode("=", $line);
                if (!empty ($key)) 
                {
                    $audioInfo[$key] = $value;
                }
            } 
        } 
        if($userid != null){
          $authToken = $this->lcapi_get_session($userid,"");
        }
        $audioFile = new LCAudioFileStatus($audioInfo,$authToken);
        
        return $audioFile;
    }
} 

?>
