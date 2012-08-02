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
/* $Id: controller.php 149 2012-06-19 00:33:23Z azeckoski@gmail.com $ */

/**
 * Handles controller functions related to the views
 */

require_once ('../../config.php');
global $CFG,$USER,$COURSE;
require_once ('iclicker_service.php');

class iclicker_controller {

    // constants
    const TYPE_HTML = 'html';
    const TYPE_XML = 'xml';
    const TYPE_TEXT = 'txt';

    const PASSWORD = '_password';
    const LOGIN = '_login';
    const SSO_KEY = '_key';
    const SEPARATOR = '/';
    const PERIOD = '.';
    const XML_HEADER = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\n";
    const SESSION_ID = '_sessionId';
    const COMPENSATE_METHOD = '_method';
    const XML_DATA = '_xml';

    // class vars

    // REQUEST
    public $method = 'GET';
    public $path = '';
    public $query = '';
    public $body = NULL;

    // RESPONSE
    public $status = 200;
    public $message = '';
    public $headers = array();

    public $results = array(
    );

    public function __construct($getBody = false) {
        // set some headers
        $this->headers['Content-Encoding'] = 'UTF8';
        //header('Content-type: text/plain');
        //header('Cache-Control: no-cache, must-revalidate');
        // get the rest path
        $full_path = me();
        if ($full_path) {
            $path = $full_path;
            $pos = strripos($full_path, '.php');
            if ($pos > 1) {
                $path = substr($full_path, $pos+4);
                $path = trim($path, '/ '); // trim whitespace and slashes
            }
            if (stripos($path, '?')) {
                $qloc = stripos($path, '?');
                $this->query = trim(substr($path, $qloc), '?');
                $path = substr($path, 0, $qloc);
            }
            $this->path = $path;
        }
        // get the body
        if ($getBody) {
            // Moodlerooms does not allow use of php://input
            //$this->body = @file_get_contents('php://input');
            $this->body = stream_get_contents(STDIN);
        }
        // allow for method overrides
        $current_method = $_SERVER['REQUEST_METHOD'];
        if (isset($_REQUEST[self::COMPENSATE_METHOD])) {
            $comp_method = $_REQUEST[self::COMPENSATE_METHOD];
            if (! empty($comp_method)) {
                // Allows override to GET or DELETE
                $comp_method = strtoupper(trim($comp_method));
                if ('GET' == $comp_method) {
                    $current_method = 'GET';
                } else {
                    if ('DELETE' == $comp_method) {
                        $current_method = 'DELETE';
                    } else {
                        if ('POST' == $comp_method) {
                            $current_method = 'POST';
                        }
                    }
                }
            }
        }
        $this->method = $current_method;
    }

    public function setStatus($status) {
        if ($status) {
            $this->status = $status;
        }
    }

    public function setMessage($msg) {
        $this->message = $msg;
    }

    public function setContentType($mime_type) {
        $this->headers['Content-Type'] = $mime_type;
    }

    public function setHeader($name, $value) {
        $this->headers[$name] = $value;
    }

    /**
     * Send the response
     *
     * @param string $content [optional] the content to send
     * @param string $message [optional] the message to send, defaults to "Invalid request"
     */
    public function sendResponse($content = NULL, $message = null) {
        $code = $this->status;
        if (!isset($message)) {
            switch ($code) {
                case 200: $message='OK'; break;
                case 204: $message='No Content'; break;
                case 302: $message='Found'; break;
                case 400: $message='Bad Request'; break;
                case 401: $message='Unauthorized'; break;
                case 403: $message='Forbidden'; break;
                case 404: $message='Not Found'; break;
                default: $message='Internal Server Error';
            }
        }
        header("HTTP/1.0 $code ".str_replace("\n", "", $message));
        if ($code >= 400) {
            // force plain text encoding when errors occur
            $this->setContentType('text/plain');
        }
        $headers = $this->headers;
        if (isset($headers) && ! empty($headers)) {
            foreach ($headers as $key=> & $value) {
                header($key.': '.$value, false);
            }
            unset($value);
        }
        // dump the body content
        if (isset($content)) {
            echo $content;
        }
    }


    // XHTML view processors

    public function processRegistration() {
        // process calls to the registration view
        $this->results['new_reg'] = false;
        $this->results['clicker_id_val'] = "";
        if ('POST' == $this->method) {
            if (optional_param('register', null, PARAM_ALPHANUM) != null) {
                // we are registering a clicker
                $clicker_id = optional_param('clickerId', null, PARAM_ALPHANUMEXT);
                if ($clicker_id == null) {
                    $this->addMessage(self::KEY_ERROR, 'reg.registered.clickerId.empty');
                } else {
                    $this->results['clicker_id_val'] = $clicker_id;
                    // save a new clicker registration
                    try {
                        iclicker_service::create_clicker_registration($clicker_id);
                        $this->addMessage(self::KEY_INFO, 'reg.registered.success', $clicker_id);
                        $this->addMessage(self::KEY_BELOW, 'reg.registered.below.success');
                        $this->results['new_reg'] = true;
                    }
                    catch (ClickerRegisteredException $e) {
                        $this->addMessage(self::KEY_ERROR, 'reg.registered.clickerId.duplicate', $clicker_id);
                        $this->addMessage(self::KEY_BELOW, 'reg.registered.below.duplicate', $clicker_id);
                    }
                    catch (ClickerIdInvalidException $e) {
                        if (ClickerIdInvalidException::F_EMPTY == $e->type) {
                            $this->addMessage(self::KEY_ERROR, 'reg.registered.clickerId.empty');
                        } else {
                            $this->addMessage(self::KEY_ERROR, 'reg.registered.clickerId.invalid', $clicker_id);
                        }
                    }
                }

            } else {
                if (optional_param('activate', null, PARAM_ALPHANUM) != null) {
                    // First arrived at this page
                    $activate = optional_param('activate', 'false', PARAM_ALPHANUM);
                    $activate = ($activate == 'true' ? true : false);
                    $reg_id = optional_param('registrationId', null, PARAM_INT);
                    if ($reg_id == null) {
                        $this->addMessage(self::KEY_ERROR, 'reg.activate.registrationId.empty', null);
                    } else {
                        // save a new clicker registration
                        $cr = iclicker_service::set_registration_active($reg_id, $activate);
                        if ($cr) {
                            $this->addMessage(self::KEY_INFO, 'reg.activate.success.' . ($cr->activated ? 'true' : 'false'), $cr->clicker_id);
                        }
                    }

                } else {
                    if (optional_param('remove', null, PARAM_ALPHANUM) != null) {
                        $reg_id = optional_param('registrationId', null, PARAM_ALPHANUMEXT);
                        if (($reg_id == null)) {
                            $this->addMessage(self::KEY_ERROR, 'reg.activate.registrationId.empty', null);
                        } else {
                            // remove a new clicker registration by deactivating it
                            $cr = iclicker_service::set_registration_active($reg_id, false);
                            if ($cr) {
                                $this->addMessage(self::KEY_INFO, 'reg.remove.success', $cr->clicker_id);
                            }
                        }

                    } else {
                        // invalid POST
                        echo('WARN: Invalid POST: does not contain register or activate, nothing to do');
                    }
                }
            }
        }

        $this->results['regs'] = iclicker_service::get_registrations_by_user(null, true);
        $this->results['is_instructor'] = iclicker_service::is_instructor();
        $this->results['sso_enabled'] = iclicker_service::$block_iclicker_sso_enabled;
        // added to allow special messages below the forms
        $this->results['below_messages'] = $this->getMessages(self::KEY_BELOW);
    }

    public function processInstructor() {
        $this->results['instPath'] = iclicker_service::block_url('instructor.php');
        // admin/instructor check
        if (!iclicker_service::is_admin() && !iclicker_service::is_instructor()) {
            throw new SecurityException("Current user is not an instructor and cannot access the instructor view");
        }
        $course_id = optional_param('courseId', false, PARAM_INT);
        $this->results['course_id'] = $course_id;
        $courses = array();
        if ($course_id) {
            $course = iclicker_service::get_course($course_id);
            $this->results['course_title'] = $course->fullname;
            $courses[] = $course;
        } else {
            $courses = iclicker_service::get_courses_for_instructor();
        }
        $this->results['courses'] = $courses;
        $this->results['courses_count'] = count($courses);
        $this->results['show_students'] = false;
        if ($course_id && count($courses) == 1) {
            $course = $courses[0];
            $this->results['show_students'] = true;
            $this->results['course'] = $course;
            $students = iclicker_service::get_students_for_course_with_regs($course_id);
            $this->results['students'] = $students;
            $this->results['students_count'] = count($students);
        }
        $this->results['sso_enabled'] = iclicker_service::$block_iclicker_sso_enabled;
    }

    public function processInstructorSSO() {
        $this->results['instPath'] = iclicker_service::block_url('instructor.php');
        // admin/instructor check
        if (!iclicker_service::is_admin() && !iclicker_service::is_instructor()) {
            throw new SecurityException("Current user is not an instructor and cannot access the instructor view");
        }
        $this->results['sso_enabled'] = iclicker_service::$block_iclicker_sso_enabled;
        $current_user_id = iclicker_service::get_current_user_id();
        if (iclicker_service::$block_iclicker_sso_enabled) {
            $current_user_key = null;
            if ('POST' == $this->method) {
                if ( optional_param('generateKey', false, PARAM_ALPHANUM) != null ) {
                    // handle generating a new key
                    $current_user_key = iclicker_service::makeUserKey($current_user_id, true);
                    $this->addMessage(self::KEY_INFO, 'inst.sso.generated.new.key', null);
                }
            }
            if ($current_user_key == null) {
                $current_user_key = iclicker_service::makeUserKey($current_user_id, false);
            }
            $this->results['sso_user_key'] = $current_user_key;
        }
        //$current_user_key = iclicker_service::makeUserKey($current_user_id);
        //$this->results['sso_user_key'] = $current_user_key;
    }

    public function processAdmin() {
        global $CFG;
        $adminPath = iclicker_service::block_url('admin.php');
        $this->results['adminPath'] = $adminPath;
        $this->results['status_url'] = iclicker_service::block_url('runner_status.php');
        // admin check
        if (!iclicker_service::is_admin()) {
            throw new SecurityException("Current user is not an admin and cannot access the admin view");
        }

        // get sorting params
        $pageNum = 1;
        $perPageNum = 20; // does not change
        if (optional_param('page', null, PARAM_ALPHANUM) != null) {
            $pageNum = required_param('page', PARAM_INT);
            if ($pageNum < 1) {
                $pageNum = 1;
            }
        }
        $this->results['page'] = $pageNum;
        $this->results['perPage'] = $perPageNum;
        $sort = 'clicker_id';
        if (optional_param('sort', null, PARAM_ALPHANUM) != null) {
            $sort = required_param('sort', PARAM_ALPHANUMEXT);
        }
        $this->results['sort'] = $sort;

        if ("POST" == $this->method) {
            if (optional_param('activate', null, PARAM_ALPHANUM) != null) {
                // First arrived at this page
                $activate = required_param('activate', PARAM_BOOL);
                if (optional_param('registrationId', null, PARAM_ALPHANUMEXT) == null) {
                    $this->addMessage(self::KEY_ERROR, "reg.activate.registrationId.empty", null);
                } else {
                    $reg_id = required_param('registrationId', PARAM_INT);
                    // save a new clicker registration
                    $cr = iclicker_service::set_registration_active($reg_id, $activate);
                    if ($cr) {
                        $args = new stdClass ;
                        $args->cid = $cr->clicker_id;
                        $args->user = iclicker_service::get_user_displayname($cr->owner_id);
                        $this->addMessage(self::KEY_INFO, "admin.activate.success.".($cr->activated ? 'true' : 'false'), $args);
                    }
                }
            } else {
                if (optional_param('remove', null, PARAM_ALPHANUM) != null) {
                    if (optional_param('registrationId', null, PARAM_ALPHANUMEXT) == null) {
                        $this->addMessage(self::KEY_ERROR, "reg.activate.registrationId.empty", null);
                    } else {
                        $reg_id = required_param('registrationId', PARAM_INT);
                        $cr = iclicker_service::get_registration_by_id($reg_id);
                        if ($cr) {
                            iclicker_service::remove_registration($reg_id);
                            $args = new stdClass();
                            $args->cid = $cr->clicker_id;
                            $args->rid = $reg_id;
                            $args->user = iclicker_service::get_user_displayname($cr->owner_id);
                            $this->addMessage(self::KEY_INFO, "admin.delete.success", $args);
                        }
                    }
                } else {
                    // invalid POST
                    error('WARN: Invalid POST: does not contain remove, or activate, nothing to do');
                }
            }
        }

        // put config data into page
        $this->results['sso_enabled'] = iclicker_service::$block_iclicker_sso_enabled;
        $this->results['sso_shared_key'] = iclicker_service::$block_iclicker_sso_shared_key;
        $this->results['domainURL'] = iclicker_service::$domain_URL;
        $this->results['adminEmailAddress'] = $CFG->block_iclicker_notify_emails;

        // put error data into page
        $this->results['recent_failures'] = iclicker_service::get_failures();

        // handling the calcs for paging
        $first = ($pageNum - 1) * $perPageNum;
        $totalCount = iclicker_service::count_all_registrations();
        $pageCount = floor(($totalCount + $perPageNum - 1) / $perPageNum);
        $this->results['total_count'] = $totalCount;
        $this->results['page_count'] = $pageCount;
        $this->results['registrations'] = iclicker_service::get_all_registrations($first, $perPageNum, $sort, null);

        $pagerHTML = "";
        if ($totalCount > 0) {
            $timestamp = microtime();
            for ($i = 0; $i < $pageCount; $i++) {
                $currentPage = $i + 1;
                $currentStart = $currentPage + ($i * $perPageNum);
                $currentEnd = $currentStart + $perPageNum - 1;
                if ($currentEnd > $totalCount) {
                    $currentEnd = $totalCount;
                }
                $marker = '['.$currentStart.'..'.$currentEnd.']';
                if ($currentPage == $pageNum) {
                    // make it bold and not a link
                    $pagerHTML .= '<span class="paging_current paging_item">'.$marker.'</span>'."\n";
                } else {
                    // make it a link
                    $pagerHTML .= '<a class="paging_link paging_item" href="'.$adminPath.'&page='.$currentPage.'&sort='.$sort.'&nc='.($timestamp.$currentPage).'">'.$marker.'</a>'."\n";
                }
            }
            $this->results['pagerHTML'] = $pagerHTML;
        }
    }


    // MESSAGING

    var $messages = array(
    );

    const KEY_INFO = "INFO";
    const KEY_ERROR = "ERROR";
    const KEY_BELOW = "BELOW";

    /**
     * Adds a message
     *
     * @param string $key the KEY const
     * @param string $message the message to add
     * @throws Exception if the key is invalid
     */
    public function addMessageStr($key, $message) {
        if ($key == null) {
            throw new Exception("key (".$key.") must not be null");
        }
        if ($message) {
            if (!isset($this->messages[$key])) {
                $this->messages[$key] = array(
                );
            }
            $this->messages[$key][] = $message;
        }
    }

    /**
     * Add an i18n message based on a key
     *
     * @param string $key the KEY const
     * @param string $messageKey the i18n message key
     * @param object $args [optional] args to include
     * @throws Exception if the key is invalid
     */
    public function addMessage($key, $messageKey, $args = null) {
        if ($key == null) {
            throw new Exception("key (".$key.") must not be null");
        }
        if ($messageKey) {
            $message = iclicker_service::msg($messageKey, $args);
            $this->addMessageStr($key, $message);
        }
    }

    /**
     * Get the messages that are currently waiting in this request
     *
     * @param string $key the KEY const
     * @return array the list of messages to display
     * @throws Exception if the key is invalid
     */
    public function getMessages($key) {
        if ($key == null) {
            throw new Exception("key (".$key.") must not be null");
        }
        $messages = null;
        if (isset($this->messages[$key])) {
            $messages = $this->messages[$key];
            if (!isset($messages)) {
                $messages = array(
                );
            }
        } else {
            $messages = array(
            );
        }
        return $messages;
    }

}
