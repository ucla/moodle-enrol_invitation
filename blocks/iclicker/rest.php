<?php
/**
 * Copyright (c) 2012 i>clicker (R) <http://www.iclicker.com/dnn/>
 *
 * This file is part of i>clicker Moodle integrate.
 *
 * i>clicker Moodle integrate is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * i>clicker Moodle integrate is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with i>clicker Moodle integrate.  If not, see <http://www.gnu.org/licenses/>.
 */
/* $Id: rest.php 164 2012-08-22 20:11:41Z azeckoski@gmail.com $ */

// this includes lib/setup.php and the standard set:
//setup.php : setup which creates the globals
//'/textlib.class.php');   // Functions to handle multibyte strings
//'/weblib.php');          // Functions for producing HTML
//'/dmllib.php');          // Functions to handle DB data (DML) - inserting, updating, and retrieving data from the database
//'/datalib.php');         // Legacy lib with a big-mix of functions. - user, course, etc. data lookup functions
//'/accesslib.php');       // Access control functions - context, roles, and permission related functions
//'/deprecatedlib.php');   // Deprecated functions included for backward compatibility
//'/moodlelib.php');       // general-purpose (login, getparams, getconfig, cache, data/time)
//'/eventslib.php');       // Events functions
//'/grouplib.php');        // Groups functions

//ddlib.php : modifying, creating, or deleting database schema
//blocklib.php : functions to use blocks in a typical course page
//formslib.php : classes for creating forms in Moodle, based on PEAR QuickForms

require_once ('../../config.php');
global $CFG,$USER,$COURSE;
require_once ('iclicker_service.php');
require_once ('controller.php');


// INTERNAL METHODS
/**
 * This will check for a user and return the user_id if one can be found
 * @param string $msg the error message
 * @return int the user_id
 * @throws ClickerSecurityException if no user can be found
 */
function iclicker_get_and_check_current_user($msg) {
    $user_id = iclicker_service::get_current_user_id();
    if (! $user_id) {
        throw new ClickerSecurityException("Only logged in users can $msg");
    }
    if (! iclicker_service::is_admin($user_id) && ! iclicker_service::is_instructor($user_id)) {
        throw new ClickerSecurityException("Only instructors can " . $msg);
    }
    return $user_id;
}

/**
 * Attempt to authenticate the current request based on request params and basic auth
 * @param iclicker_controller $cntlr the controller instance
 * @throws ClickerSecurityException if authentication is impossible given the request values
 * @throws ClickerSSLRequiredException if the auth request is bad (requires SSL but SSL not used)
 */
function iclicker_handle_authn($cntlr) {
    global $CFG;
    // extract the authn params
    $auth_username = optional_param(iclicker_controller::LOGIN, NULL, PARAM_NOTAGS);
    $auth_password = optional_param(iclicker_controller::PASSWORD, NULL, PARAM_NOTAGS);
    if (empty($auth_username) && isset($_SERVER['PHP_AUTH_USER'])) {
        // no username found in normal params so try to get basic auth
        $auth_username = $_SERVER['PHP_AUTH_USER'];
        $auth_password = $_SERVER['PHP_AUTH_PW'];
        if (empty($auth_username)) {
            // attempt to get it from the header as a final try
            list($auth_username, $auth_password) = explode(':', base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6)));
        }
    }
    if (iclicker_service::$block_iclicker_sso_enabled && !empty($auth_password)) {
        // when SSO is enabled and the password is set it means this is not actually a user password so we can proceed without requiring SSL
    } else {
        // this is a user password so https must be used if the loginhttps option is enabled
        $ssl_request = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $ssl_required = (isset($CFG->forcehttps) && $CFG->forcehttps == true) || (isset($CFG->loginhttps) && $CFG->loginhttps == true);
        if ($ssl_required && !$ssl_request) {
            throw new ClickerSSLRequiredException('SSL is required when performing a user login (and sending user passwords)');
        }
    }
    //$session_id = optional_param(iclicker_controller::SESSION_ID, NULL, PARAM_NOTAGS);
    if (!empty($auth_username)) {
        $sso_key = optional_param(iclicker_controller::SSO_KEY, NULL, PARAM_NOTAGS);
        iclicker_service::authenticate_user($auth_username, $auth_password, $sso_key); // throws exception if fails
    //} else if ($session_id) {
    //    $valid = FALSE; // validate the session key
    //    if (! $valid) {
    //        throw new SecurityException("Invalid "+iclicker_controller::SESSION_ID+" provided, session may have expired, send new login credentials");
    //    }
    }
    $current_user_id = iclicker_service::get_current_user_id();
    if (isset($current_user_id)) {
        $cntlr->setHeader(iclicker_controller::SESSION_ID, sesskey());
        $cntlr->setHeader('_userId', $current_user_id);
    }
}

/**
 * Extracts the XML data from the request
 * @param object $cntlr the controller instance
 * @return string the XML data OR null if none can be found
 */
function iclicker_get_xml_data($cntlr) {
    $xml = optional_param(iclicker_controller::XML_DATA, NULL, PARAM_RAW_TRIMMED);
    if (empty($xml)) {
        $xml = $cntlr->body;
    } else {
        $xml = stripslashes($xml);
    }
    return $xml;
}


// REST HANDLING

//require_login();
//echo "me=".me().", qualified=".qualified_me();
//echo "user: id=".$USER->id.", auth=".$USER->auth.", username=".$USER->username.", lastlogin=".$USER->lastlogin."\n";
//echo "course: id=".$COURSE->id.", title=".$COURSE->fullname."\n";
//echo "CFG: wwwroot=".$CFG->wwwroot.", httpswwwroot=".$CFG->httpswwwroot.", dirroot=".$CFG->dirroot.", libdir=".$CFG->libdir."\n";

// activate the controller
$cntlr = new iclicker_controller(true); // with body

// init the vars to success
$valid = true;
$status = 200; // ok
$output = '';

// check to see if this is one of the paths we understand
if (! $cntlr->path) {
    $valid = false;
    $output = "Unknown path ($cntlr->path) specified";
    $status = 404; // not found
}
if ($valid
        && "POST" != $cntlr->method
        && "GET" != $cntlr->method) {
    $valid = false;
    $output = "Only POST and GET methods are supported";
    $status = 405; // method not allowed
}
if ($valid) {
    // check against the ones we know and process
    $parts = explode('/', $cntlr->path);
    $pathSeg0 = count($parts) > 0 ? $parts[0] : NULL;
    $pathSeg1 = count($parts) > 1 ? $parts[1] : NULL;
    try {
        if ($pathSeg0 == 'verifykey') {
            // SPECIAL case handling (no authn handling)
            $ssoKey = optional_param(iclicker_controller::SSO_KEY, NULL, PARAM_NOTAGS);
            if (iclicker_service::verifyKey($ssoKey)) {
                $cntlr->setStatus(200);
                $output = "Verified";
            } else {
                $cntlr->setStatus(501);
                $output = "Disabled";
            }
            $cntlr->setContentType("text/plain");
            $cntlr->sendResponse($output);
            return;
        } else {
            // NORMAL case handling
            // handle the request authn if needed
            iclicker_handle_authn($cntlr);
            if ("GET" == $cntlr->method) {
                if ("courses" == $pathSeg0) {
                    // handle retrieving the list of courses for an instructor
                    $user_id = iclicker_get_and_check_current_user("access instructor courses listings");
                    $output = iclicker_service::encode_courses($user_id);

                } else if ("students" == $pathSeg0) {
                    // handle retrieval of the list of students
                    $course_id = $pathSeg1;
                    if ($course_id == null) {
                        throw new InvalidArgumentException(
                                "valid course_id must be included in the URL /students/{course_id}");
                    }
                    iclicker_get_and_check_current_user("access student enrollment listings");
                    $output = iclicker_service::encode_enrollments($course_id);

                } else {
                    // UNKNOWN
                    $valid = false;
                    $output = "Unknown path ($cntlr->path) specified for method GET";
                    $status = 404; //NOT_FOUND
                }
            } else {
                // POST
                if ("gradebook" == $pathSeg0) {
                    // handle retrieval of the list of students
                    $course_id = $pathSeg1;
                    if ($course_id == null) {
                        throw new InvalidArgumentException(
                                "valid course_id must be included in the URL /gradebook/{course_id}");
                    }
                    iclicker_get_and_check_current_user("upload grades into the gradebook");
                    $xml = iclicker_get_xml_data($cntlr);
                    try {
                        $gradebook = iclicker_service::decode_gradebook($xml);
                        // process gradebook data
                        $results = iclicker_service::save_gradebook($gradebook);
                        // generate the output
                        $output = iclicker_service::encode_gradebook_results($results);
                        if (! $output) {
                            // special RETURN, non-XML, no failures in save
                            $cntlr->setStatus(200);
                            $cntlr->setContentType("text/plain");
                            $output = "True";
                            $cntlr->sendResponse($output);
                            return; // SHORT CIRCUIT
                        } else {
                            // failures occurred during save
                            $status = 200; //OK;
                        }
                    } catch (InvalidArgumentException $e) {
                        // invalid XML
                        $valid = false;
                        $output = "Invalid gradebook XML in request, unable to process:/n $xml";
                        $status = 400; //BAD_REQUEST;
                    }

                } else if ("authenticate" == $pathSeg0) {
                    iclicker_get_and_check_current_user("authenticate via iclicker");
                    // special return, non-XML
                    $cntlr->setStatus(204); //No content
                    $cntlr->sendResponse();
                    return; // SHORT CIRCUIT

                } else if ("register" == $pathSeg0) {
                    iclicker_get_and_check_current_user("upload registrations data");
                    $xml = iclicker_get_xml_data($cntlr);
                    $cr = iclicker_service::decode_registration($xml);
                    $owner_id = $cr->owner_id;
                    $message = '';
                    $reg_status = false;
                    try {
                        iclicker_service::create_clicker_registration($cr->clicker_id, $owner_id);
                        // valid registration
                        $message = iclicker_service::msg('reg.registered.below.success', $cr->clicker_id);
                        $reg_status = true;
                    } catch (ClickerIdInvalidException $e) {
                        // invalid clicker id
                        $message = iclicker_service::msg('reg.registered.clickerId.invalid', $cr->clicker_id);
                    } catch (InvalidArgumentException $e) {
                        // invalid user id
                        $message = "Student not found in the CMS";
                    } catch (ClickerRegisteredException $e) {
                        // already registered
                        $key = '';
                        if ($e->owner_id == $e->registered_owner_id) {
                            // already registered to this user
                            $key = 'reg.registered.below.duplicate';
                        } else {
                            // already registered to another user
                            $key = 'reg.registered.clickerId.duplicate.notowned';
                        }
                        $message = iclicker_service::msg($key, $cr->clicker_id);
                    }
                    $registrations = iclicker_service::get_registrations_by_user($owner_id, true);
                    $output = iclicker_service::encode_registration_result($registrations, $reg_status, $message);
                    if ($reg_status) {
                        $status = 200; //OK;
                    } else {
                        $status = 400; //BAD_REQUEST;
                    }

                } else {
                    // UNKNOWN
                    $valid = false;
                    $output = "Unknown path ($cntlr->path) specified for method POST";
                    $status = 404; //NOT_FOUND;
                }
            }
        }
    } catch (ClickerSecurityException $e) {
        $valid = false;
        $current_user_id = iclicker_service::get_current_user_id();
        if (! $current_user_id) {
            $output = "User must be logged in to perform this action: " . $e;
            $status = 403; //UNAUTHORIZED;
        } else {
            $output = "User ($current_user_id) is not allowed to perform this action: " . $e;
            $status = 401; //FORBIDDEN;
        }
    } catch (InvalidArgumentException $e) {
        $valid = false;
        $output = "Invalid request: " . $e;
        //log.warn("i>clicker: " + $output, $e);
        $status = 400; //BAD_REQUEST;
    } catch (ClickerSSLRequiredException $e) {
        $valid = false;
        $output = "SSL_REQUIRED: " . $e;
        //log.warn("i>clicker: " + $output, $e);
        $status = 426; //UPGRADE_REQUIRED;
    } catch (Exception $e) {
        $valid = false;
        $output = "Failure occurred: " . $e;
        //log.warn("i>clicker: " + $output, $e);
        $status = 500; //INTERNAL_SERVER_ERROR;
    }
}
if ($valid) {
    // send the response
    $cntlr->setStatus(200);
    $cntlr->setContentType('application/xml');
    $output = iclicker_controller::XML_HEADER . $output;
    $cntlr->sendResponse($output);
} else {
    // error with info about how to do it right
    $cntlr->setStatus($status);
    $cntlr->setContentType('text/plain');
    // add helpful info to the output
    $msg = "ERROR $status: Invalid request (".$cntlr->method." /".$cntlr->path.")" .
        "\n\n=INFO========================================================================================\n".
        $output.
        "\n\n-HELP----------------------------------------------------------------------------------------\n".
        "Valid request paths include the following (without the block prefix: ".iclicker_service::block_url('rest.php')."):\n".
        "POST /authenticate             - authenticate by sending credentials (".iclicker_controller::LOGIN.",".iclicker_controller::PASSWORD.") \n".
        "                                 return status 204 (valid login) \n".
        "POST /verifykey                - check the encoded key is valid and matches the shared key \n".
        "                                 return 200 if valid OR 501 if SSO not enabled OR 400/401 if key is bad \n".
        "POST /register                 - Add a new clicker registration, return 200 for success or 400 with \n".
        "                                 registration response (XML) for failure \n".
        "GET  /courses                  - returns the list of courses for the current user (XML) \n".
        "GET  /students/{course_id}     - returns the list of student enrollments for the given course (XML) \n".
        "                                 or 403 if user is not an instructor in the specified course \n".
        "POST /gradebook/{course_id}    - send the gradebook data into the system, returns errors on failure (XML) \n".
        "                                 or 'True' if no errors, 400 if the xml is missing or course_id is invalid, \n".
        "                                 403 if user is not an instructor in the specified course \n".
        "\n".
        " - Authenticate by sending credentials (".iclicker_controller::LOGIN.",".iclicker_controller::PASSWORD.") in the request parameters \n".
        " -- SSO authentication requires an encoded key (".iclicker_controller::SSO_KEY.") in the request parameters \n".
        " -- The response headers will include the sessionId when credentials are valid \n".
        " -- Invalid credentials or sessionId will result in a 401 (invalid credentials) or 403 (not authorized) status \n".
        " - Use ".iclicker_controller::COMPENSATE_METHOD." param to override the http method being used (e.g. POST /courses?".iclicker_controller::COMPENSATE_METHOD."=GET will force the method to be a GET despite sending as a POST) \n".
        " - Send data as the http request BODY or as a form parameter called ".iclicker_controller::XML_DATA." \n".
        " - All endpoints return 403 if user is not an instructor \n".
        " - Version: ".iclicker_service::VERSION." (".iclicker_service::BLOCK_VERSION.") \n";
    $cntlr->sendResponse($msg);
}
