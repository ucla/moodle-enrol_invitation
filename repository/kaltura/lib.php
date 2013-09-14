<?php

// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Kaltura Repository local library
 *
 * @package    Repository
 * @subpackage Kaltura
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . '/repository/lib.php');
require_once(dirname(__FILE__) . '/locallib.php');

$version = local_kaltura_is_moodle_pre_twothree();

// This condition check is required due to the repository class changes from 2.2 to 2.3
// If Moodle is pre 2.3 then we need to create a class that doesn't throw a fatal error
if ($version) { ///////////////////////// MOODLE 2.2 OR LESS ///////////////////////////////
    class repository_kaltura extends repository {

        var $sort;
        var $root_path = '';
        var $root_path_id = '';

        // Search criteria
        var $search_name          = ''; // Video name
        var $search_tags          = ''; // Video tags
        var $search_course_filter = ''; // Filter courses names
        var $search_course_name   = ''; // Course name
        var $search_for           = ''; // Search for videos shared with courses or used in courses

        private static $page_size = 0;

        public function __construct($repositoryid, $context = SITEID, $options = array()) {
            global $COURSE, $PAGE;

            try {

                parent::__construct($repositoryid, $context, $options);

                self::$page_size = get_config(REPOSITORY_KALTURA_PLUGIN_NAME, 'itemsperpage');

                $kaltura = new kaltura_connection();
                $connection = $kaltura->get_connection(true, KALTURA_SESSION_LENGTH);

                $rootcategory = get_config(REPOSITORY_KALTURA_PLUGIN_NAME, 'rootcategory');
                $rootcategory_id = get_config(REPOSITORY_KALTURA_PLUGIN_NAME, 'rootcategory_id');

                $this->root_path     = $rootcategory;
                $this->root_path_id  = $rootcategory_id;

                if ($connection && !empty($rootcategory)) {

                    // First check if root category path already exists.  If the path exists then use it
                    $existing_root_category = repository_kaltura_category_path_exists($connection, $rootcategory);

                    if ($existing_root_category) {

                        // Set root category id configuration setting if it hasn't bee set
                        if (empty($rootcategory_id)) {
                            set_config('rootcategory_id', $existing_root_category->id, REPOSITORY_KALTURA_PLUGIN_NAME);
                        }
                    }

                    // If the root category id has not been set, attempt to create the root category
                    if (empty($rootcategory_id) ) {
                        $result = repository_kaltura_create_root_category($connection);

                        if (is_array($result)) {
                            $this->root_path    = $result[0];
                            $this->root_path_id = $result[1];
                        }
                    }

                }

                // Lastly, check to see if the root category and root category id have been initialized
                if (empty($this->root_path) || empty($this->root_path_id)) {
                    throw new Exception("Kaltura Repository root cateogry or root category id not set");
                }

            } catch (Exception $exp) {
                $courseid = get_courseid_from_context($PAGE->context);

                if (empty($courseid)) {
                    $courseid = 1;
                }

                add_to_log($courseid, 'repository_kaltura', 'Error while initializing constructor', '', $exp->getMessage());
            }

        }

        private function root_category_initialized() {

            if (empty($this->root_path) && empty($this->root_path_id)) {
                return false;
            }

            return true;
        }

        public static function get_type_option_names() {
            return array_merge(parent::get_type_option_names(), array('itemsperpage', 'rootcategory'));
        }


        /**
         * Type config form
         */
        public function type_config_form($mform, $classname = 'kaltura') {
            global $CFG, $DB;

            parent::type_config_form($mform);

            // Display connection information
            $login = local_kaltura_login(true);
            if ($login) {
                $mform->addElement('static', 'connection', get_string('connection_status', 'repository_kaltura'),
                    get_string('connected', 'repository_kaltura'));
            } else {
                $mform->addElement('static', 'connection', get_string('connection_status', 'repository_kaltura'),
                    get_string('not_connected', 'repository_kaltura'));
            }

            // Create connection class
            $kaltura = new kaltura_connection();
            $connection = $kaltura->get_connection(true, KALTURA_SESSION_LENGTH);

            // Check if the client's account has permissions to use custom metadata
            if (!repository_kaltura_account_enabled_metadata($connection)) {
                $mform->addElement('static', 'connection', get_string('no_permission_metadata_error', 'repository_kaltura'),
                                   '<b>'.get_string('no_permission_metadata', 'repository_kaltura').'</b>');

                // Force the connection to false
                $connection = false;
            }

            if ($connection) {

                $rootcategory    = get_config(REPOSITORY_KALTURA_PLUGIN_NAME, 'rootcategory');
                $rootcategory_id = get_config(REPOSITORY_KALTURA_PLUGIN_NAME, 'rootcategory_id');

                // If the root category path is empty then remove the rootcategory id
                if (empty($rootcategory)) {
                    $param = array('plugin' => REPOSITORY_KALTURA_PLUGIN_NAME,
                                   'name'   => 'rootcategory_id');
                    $DB->delete_records('config_plugins', $param);

                    $rootcategory_id = '';
                }



                // Display pager setting
                $page_options = array('10' => get_string('ten', 'repository_kaltura'),
                                      '15' => get_string('fifteen', 'repository_kaltura'),
                                      '20' => get_string('twenty', 'repository_kaltura'),
                                      '25' => get_string('twentyfive', 'repository_kaltura'),
                                      '30' => get_string('thirty', 'repository_kaltura'),
                                      '50' => get_string('fifty', 'repository_kaltura'),
                                      '100' => get_string('onehundred', 'repository_kaltura'),
                                      '200' => get_string('twohundred', 'repository_kaltura'),
                                      '300' => get_string('threehundred', 'repository_kaltura'),
                                      '400' => get_string('fourhundred', 'repository_kaltura'),
                                      '500' => get_string('fivehundred', 'repository_kaltura'));

                $mform->addElement('select', 'itemsperpage', get_string('itemsperpage', 'repository_kaltura'),
                                    $page_options);
                $mform->setDefault('itemsperpage', '100');
                $mform->addHelpButton('itemsperpage', 'itemsperpage', 'repository_kaltura');

                if (empty($rootcategory_id)) {

                    // Display Root category setting
                    $strrequired = get_string('required');
                    $mform->addElement('text', 'rootcategory', get_string('rootcategory', 'repository_kaltura'));
                    $mform->addRule('rootcategory', $strrequired, 'required', null, 'client');
                    $mform->addHelpButton('rootcategory', 'rootcategory', 'repository_kaltura');

                    $status = '';
                    if (empty($rootcategory_id) && !empty($rootcategory)) {
                        $status = get_string('unable_to_create', 'repository_kaltura', $rootcategory);
                    } else {
                        $status = get_string('rootcategory_create', 'repository_kaltura', $rootcategory);
                    }

                    $mform->addElement('static', 'rootcategory_status', '', $status);

                } else {
                     $mform->addElement('hidden', 'rootcategory', $rootcategory);
                     $mform->addElement('static', 'rootcategory_status',
                                        get_string('rootcategory', 'repository_kaltura'),
                                        get_string('rootcategory_created', 'repository_kaltura', $rootcategory) .
                                        '&nbsp;&nbsp;<a href="'.$CFG->wwwroot.'/repository/kaltura/resetcategory.php">'.get_string('resetroot', 'repository_kaltura').'</a>');

                }

                // List Kaltura metadata profile information
                $profile = repository_kaltura_get_metadata_profile_info($connection);

                // If doesn't exist, create a new profile
                if (!$profile) {
                    $profileid = repository_kaltura_create_metadata_profile($connection);

                    if (!$profileid) {
                        $mform->addElement('static', 'metadataprofile', get_string('using_metadata_profile', 'repository_kaltura'),
                            get_string('metadata_profile_error', 'repository_kaltura'));
                    }

                    // Save profile id in config_plugins table
                    set_config('metadata_profile_id', $profileid, REPOSITORY_KALTURA_PLUGIN_NAME);

                    // Get profile information again
                    $profile = repository_kaltura_get_metadata_profile_info($connection);

                    } else {
	                    // Check if the metadata profile id exists in the mdl_config_table
	                    $profile_id = get_config(REPOSITORY_KALTURA_PLUGIN_NAME, 'metadata_profile_id');

	                    // If empty then set the profile id
	                    if (empty($profile_id)) {
	                        $profile_obj = repository_kaltura_get_metadata_profile($connection);
	                        set_config('metadata_profile_id', $profile_obj->id, REPOSITORY_KALTURA_PLUGIN_NAME);
	                    }
                }

                $mform->addElement('static', 'metadata', get_string('using_metadata_profile', 'repository_kaltura'),
                                   $profile);
            }

        }

        /**
         * file types supported by Kaltura plugin
         * @return array
         */
        public function supported_filetypes() {
            return array('web_video', 'web_audio', 'web_image');
        }

        /**
         * Kaltura plugin only return external links
         * @return int
         */
        public function supported_returntypes() {
            return FILE_EXTERNAL;
        }

        public function get_listing($path='', $page = 1) {
            global $USER, $DB;

            $course_access = array();
            $ret = array();

            $system_access = repository_kaltura_get_course_access_list('repository/kaltura:systemvisibility');
            $shared_access = repository_kaltura_get_course_access_list('repository/kaltura:sharedvideovisibility');

            // Create Kaltura category for Moodle course
            $kaltura = new kaltura_connection();
            $connection = $kaltura->get_connection(true, KALTURA_SESSION_LENGTH);

            $courseid = get_courseid_from_context($this->context);

            if (!$this->root_category_initialized() || empty($connection) ||
                (empty($system_access) && empty($shared_access))) {
                $ret['nologin'] = true;
                $ret['nosearch'] = true; // See print_search() for search form
                $ret['logouttext'] = 'not configured propertly';
                $ret['list'] = array();
                return $ret;
            }

            if ($courseid) {
                repository_kaltura_create_course_category($connection, $courseid);
            }

            // Page is set to 0 when the first page of output is displayed
            // Manually set it to 1 so that the first page link is highlighted
            if (0 == $page) {
                $page = 1;
            }

            $ret['nologin'] = true;
            $ret['dynload'] = true;
            $ret['norefresh'] = true;
            $ret['nosearch'] = false; // See print_search() for search form

            // If the user has both system and shared access to courses then their view will contain
            // a root directory with a system and a shared folder.  Below those folders will be course
            // folders.
            if (!empty($system_access) && !empty($shared_access)) {

                $ret = repository_kaltura_get_system_shared_listing($ret, $path, $system_access, $shared_access, $page);

            } else if (!empty($system_access)) {

                // If the user only has system access then their root directory will only contain courses that
                // they have system access to.
                $newpath = $path;

                if (empty($path)) {
                    $newpath = REPOSITORY_KALTURA_USED_PATH;
                }

                $ret_temp = repository_kaltura_get_course_video_listing($system_access, $newpath, REPOSITORY_KALTURA_USED_PATH, $page);
                $ret = array_merge($ret, $ret_temp);


            } else if (!empty($shared_access)) {

                $ret = repository_kaltura_get_shared_listing($ret, $path, $shared_access, $page);

            }

            return $ret;
        }

        /**
         * This functions retrieves all the courses the user has access to and meets
         * the course filter criteria.
         *
         * @param none
         * @return array - array of Moodle course ids
         */
        private function get_courses_from_filter() {

            global $DB;

            $course_criteria = '';
            $params          = '';
            $sql             = '';
            $course_access   = array();

            switch ($this->search_for) {
                case 'shared':
                    $course_access = repository_kaltura_get_course_access_list('repository/kaltura:sharedvideovisibility');
                    break;
                case 'used':
                    $course_access = repository_kaltura_get_course_access_list('repository/kaltura:systemvisibility');
                    break;
                case 'site_shared':
                    // when searching for videos shared with site, course name filtering is excluded
                    $this->search_course_name = '';
                    break;
            }

            // If no course name was specified then return the list of all available courses
            if (empty($this->search_course_name)) {
                return $course_access;
            }

            $course_access = array_keys($course_access);
            $course_access = implode(',', $course_access);

            // Find courses based on filter selection
            switch ($this->search_course_filter) {
                case 'contains':
                    $course_criteria = $DB->sql_like('fullname', ':name', false);
                    $params = array('name' => '%' .$this->search_course_name. '%');
                    break;
                case 'equals':
                    $params = array('fullname' => $this->search_course_name);
                    break;
                case 'startswith':
                    $course_criteria = $DB->sql_like('fullname', ':name', false);
                    $params = array('name' => $this->search_course_name. '%');
                    break;
                case 'endswith':
                    $course_criteria = $DB->sql_like('fullname', ':name', false);
                    $params = array('name' => '%' .$this->search_course_name);
                    break;
            }


            if (!empty($course_criteria)) {

                $sql = "SELECT id ".
                       "  FROM {course} ".
                       "  WHERE {$course_criteria} ".
                       "   AND id IN ($course_access) ";

                $records = $DB->get_records_sql($sql, $params);
            } else {

                $records = $DB->get_records('course', $params);
            }


            if (empty($records)) {
                return array();
            }

            return $records;
        }


        private function print_search_form() {

            require_once('search_form.php');

            global $USER, $SESSION;


            $str = '';
            $system_access = repository_kaltura_get_course_access_list('repository/kaltura:systemvisibility');
            $shared_access = repository_kaltura_get_course_access_list('repository/kaltura:sharedvideovisibility');

            // Clear search session data
            if (array_key_exists('search', $SESSION->kalrepo) &&
                array_key_exists($USER->id, $SESSION->kalrepo['search'])) {

                unset($SESSION->kalrepo['search'][$USER->id]);
            }

            // if the user has both the system and shared video capability, display an option to choose to search
            // for videos shared with courses or used in courses.  Because of the restrictions in the API search
            // and technical difficulties with paging, the user must choose one of the other
            if (!empty($system_access) &&
                !empty($shared_access)) {

                $str .= repository_kaltura_print_shared_used_selection();
                $str .= '<br /><br />';
            } else if (!empty($system_access)) {


                $str .= repository_kaltura_print_used_selection();
                $str .= '<br /><br />';

            } else {

                $str .= repository_kaltura_print_shared_selection();
                $str .= '<br /><br />';
            }

            $str .= repository_kaltura_print_search_form($this);
            $str .= '<br /><br />';

            return $str;
        }

        /**
         * Show the search screen, if required
         * @return null
         */
        public function print_search() {

            $search_form = $this->print_search_form();

            return $search_form;
        }

        /**
         *
         * @return bool
         */
        public function check_login() {
            return true;
        }

        /**
         * Produce results from search
         *
         * @param string $search_text
         * @return array
         */
        public function search($search_text, $page = 0) {

            global $USER, $SESSION, $OUTPUT;

            // Get search parameters if passed
            $name_search        = optional_param('s', '', PARAM_NOTAGS);
            $tag_search         = optional_param('t', '', PARAM_NOTAGS);
            $course_name_filter = optional_param('course_with', 'contains', PARAM_NOTAGS);
            $course_name        = optional_param('c', '', PARAM_NOTAGS);
            $search_own         = optional_param('own', '', PARAM_TEXT);
            $search_for         = optional_param('shared_used', 'shared', PARAM_TEXT);
            $page_param         = optional_param('page', 1, PARAM_INT);

            if (0 == $page_param) {
                $page_param = 1;
            }

            $search_data = new stdClass();

            if (empty($name_search) &&
                empty($tag_search) &&
                empty($course_name) &&
                empty($search_own) &&
                 (array_key_exists('search', $SESSION->kalrepo) &&
                  array_key_exists($USER->id, $SESSION->kalrepo['search']))) {

                $this->search_name          = $SESSION->kalrepo['search'][$USER->id]->search_name;
                $this->search_tags          = $SESSION->kalrepo['search'][$USER->id]->search_tags;
                $this->search_course_name   = $SESSION->kalrepo['search'][$USER->id]->search_course_name;
                $this->search_course_filter = $SESSION->kalrepo['search'][$USER->id]->search_course_filter;
                $this->search_for           = $SESSION->kalrepo['search'][$USER->id]->search_for;

            } else {

                $this->search_name          = trim($name_search);
                $this->search_tags          = trim($tag_search);
                $this->search_course_filter = $course_name_filter;
                $this->search_course_name   = trim($course_name);
                $this->search_for           = $search_for;

                $search_data->search_name          = $this->search_name;
                $search_data->search_tags          = $this->search_tags;
                $search_data->search_course_filter = $this->search_course_filter;
                $search_data->search_course_name   = $this->search_course_name;
                $search_data->search_for           = $this->search_for;

                $SESSION->kalrepo['search'][$USER->id] = $search_data;
            }

            $kaltura = new kaltura_connection();
            $connection = $kaltura->get_connection(true, KALTURA_SESSION_LENGTH);

            if (0 !== strcmp('own', $this->search_for)) {

                // Get a list of courses (system access and shared access) that match the course filter criteria
                $course_access = $this->get_courses_from_filter();

                $search_results = repository_kaltura_search_videos($connection, $this->search_name, $this->search_tags,
                                                                   $course_access, $page_param, $this->search_for);

            } else {

                $search_results = repository_kaltura_search_own_videos($connection, $this->search_name, $this->search_tags, $page_param);

            }

            $ret = array();

            if (empty($search_results)) {
                $ret['nologin'] = true;
                $ret['dynload'] = false;
                $ret['nosearch'] = false; // See print_search() for search form
                $ret['list'] = array();

                return $ret;
            }

            $ret['nologin'] = true;
            $ret['dynload'] = true;
            $ret['nosearch'] = false; // See print_search() for search form

            $uri         = local_kaltura_get_host();
            $partner_id  = local_kaltura_get_partner_id();
            $ui_conf_id  = local_kaltura_get_player_uiconf();
            $ret['list'] = repository_kaltura_format_data($search_results, $uri, $partner_id, $ui_conf_id);

            if ($search_results->totalCount > self::$page_size) {

                $ret['page'] = $page_param;
                $ret['pages'] = ceil($search_results->totalCount / self::$page_size);
                $ret['total'] = $search_results->totalCount;
                $ret['perpage'] = (int) self::$page_size;

            }

            return $ret;
        }

        function get_link($source) {
            //$context = get_context_by_id();
            //print_object($this);
            return $source;
        }

        /**
         * This is a dummy function created as a workaround for ELIS-8326
         * @return void
         */
        function category_tree() {}
    }
} else {                                              ///////////////////////// MOODLE 2.3 OR GREATER ///////////////////////////////

    class repository_kaltura extends repository {

        var $sort;
        var $root_path = '';
        var $root_path_id = '';

        // Search criteria
        var $search_name          = ''; // Video name
        var $search_tags          = ''; // Video tags
        var $search_course_filter = ''; // Filter courses names
        var $search_course_name   = ''; // Course name
        var $search_for           = ''; // Search for videos shared with courses or used in courses

        private static $page_size = 0;

        public function __construct($repositoryid, $context = SITEID, $options = array()) {
            global $PAGE, $DB;

            try {

                parent::__construct($repositoryid, $context, $options);

                self::$page_size = get_config(REPOSITORY_KALTURA_PLUGIN_NAME, 'itemsperpage');

                $kaltura = new kaltura_connection();
                $connection = $kaltura->get_connection(true, KALTURA_SESSION_LENGTH);

                $rootcategory    = get_config(REPOSITORY_KALTURA_PLUGIN_NAME, 'rootcategory');
                $rootcategory_id = get_config(REPOSITORY_KALTURA_PLUGIN_NAME, 'rootcategory_id');

                $this->root_path     = $rootcategory;
                $this->root_path_id  = $rootcategory_id;

                if ($connection && !empty($rootcategory)) {

                    // First check if root category path already exists.  If the path exists then use it
                    $existing_root_category = repository_kaltura_category_path_exists($connection, $rootcategory);

                    if ($existing_root_category) {

                        // Set root category id configuration setting if it hasn't bee set
                        if (empty($rootcategory_id)) {
                            set_config('rootcategory_id', $existing_root_category->id, REPOSITORY_KALTURA_PLUGIN_NAME);
                        }
                    }

                    // If the root category id has not been set, attempt to create the root category
                    if (empty($rootcategory_id) ) {
                        $result = repository_kaltura_create_root_category($connection);

                        if (is_array($result)) {
                            $this->root_path    = $result[0];
                            $this->root_path_id = $result[1];
                        }
                    }

                }

                // Lastly, check to see if the root category and root category id have been initialized
                if (empty($this->root_path) || empty($this->root_path_id)) {
                    throw new Exception("Kaltura Repository root cateogry or root category id not set");
                }

            } catch (Exception $exp) {
                $courseid = get_courseid_from_context($PAGE->context);

                if (empty($courseid)) {
                    $courseid = 1;
                }

                add_to_log($courseid, 'repository_kaltura', 'Error while initializing constructor', '', $exp->getMessage());
            }

        }

        private function root_category_initialized() {

            if (empty($this->root_path) && empty($this->root_path_id)) {
                return false;
            }

            return true;
        }

        public static function get_type_option_names() {
            return array_merge(parent::get_type_option_names(), array('itemsperpage', 'rootcategory'));
        }


        /**
         * Type config form
         */
        public static function type_config_form($mform, $classname = 'kaltura') {
            global $CFG, $DB;

            parent::type_config_form($mform);

            // Display connection information
            $login = local_kaltura_login(true);
            if ($login) {
                $mform->addElement('static', 'connection', get_string('connection_status', 'repository_kaltura'),
                    get_string('connected', 'repository_kaltura'));
            } else {
                $mform->addElement('static', 'connection', get_string('connection_status', 'repository_kaltura'),
                    get_string('not_connected', 'repository_kaltura'));
            }

            // Create connection class
            $kaltura = new kaltura_connection();
            $connection = $kaltura->get_connection(true, KALTURA_SESSION_LENGTH);

            // Check if the client's account has permissions to use custom metadata
            if (!repository_kaltura_account_enabled_metadata($connection)) {
                $mform->addElement('static', 'connection', get_string('no_permission_metadata_error', 'repository_kaltura'),
                                   '<b>'.get_string('no_permission_metadata', 'repository_kaltura').'</b>');

                // Force the connection to false
                $connection = false;
            }

            if ($connection) {

                $rootcategory    = get_config(REPOSITORY_KALTURA_PLUGIN_NAME, 'rootcategory');
                $rootcategory_id = get_config(REPOSITORY_KALTURA_PLUGIN_NAME, 'rootcategory_id');

                // If the root category path is empty then remove the rootcategory id
                if (empty($rootcategory)) {
                    $param = array('plugin' => REPOSITORY_KALTURA_PLUGIN_NAME,
                                   'name'   => 'rootcategory_id');
                    $DB->delete_records('config_plugins', $param);

                    $rootcategory_id = '';
                }

                // Display pager setting
                $page_options = array('10' => get_string('ten', 'repository_kaltura'),
                                      '15' => get_string('fifteen', 'repository_kaltura'),
                                      '20' => get_string('twenty', 'repository_kaltura'),
                                      '25' => get_string('twentyfive', 'repository_kaltura'),
                                      '30' => get_string('thirty', 'repository_kaltura'),
                                      '50' => get_string('fifty', 'repository_kaltura'),
                                      '100' => get_string('onehundred', 'repository_kaltura'),
                                      '200' => get_string('twohundred', 'repository_kaltura'),
                                      '300' => get_string('threehundred', 'repository_kaltura'),
                                      '400' => get_string('fourhundred', 'repository_kaltura'),
                                      '500' => get_string('fivehundred', 'repository_kaltura'));

                $mform->addElement('select', 'itemsperpage', get_string('itemsperpage', 'repository_kaltura'),
                                    $page_options);
                $mform->setDefault('itemsperpage', '10');
                $mform->addHelpButton('itemsperpage', 'itemsperpage', 'repository_kaltura');

                if (empty($rootcategory_id)) {

                    // Display Root category setting
                    $strrequired = get_string('required');
                    $mform->addElement('text', 'rootcategory', get_string('rootcategory', 'repository_kaltura'));
                    $mform->addRule('rootcategory', $strrequired, 'required', null, 'client');
                    $mform->addHelpButton('rootcategory', 'rootcategory', 'repository_kaltura');

                    $status = '';
                    if (empty($rootcategory_id) && !empty($rootcategory)) {
                        $status = get_string('unable_to_create', 'repository_kaltura', $rootcategory);
                    } else {
                        $status = get_string('rootcategory_create', 'repository_kaltura', $rootcategory);
                    }

                    $mform->addElement('static', 'rootcategory_status', '', $status);

                } else {
                     $mform->addElement('hidden', 'rootcategory', $rootcategory);
                     $mform->addElement('static', 'rootcategory_status',
                                        get_string('rootcategory', 'repository_kaltura'),
                                        get_string('rootcategory_created', 'repository_kaltura', $rootcategory) .
                                        '&nbsp;&nbsp;<a href="'.$CFG->wwwroot.'/repository/kaltura/resetcategory.php">'.get_string('resetroot', 'repository_kaltura').'</a>');

                }

                // List Kaltura metadata profile information
                $profile = repository_kaltura_get_metadata_profile_info($connection);

                // If doesn't exist, create a new profile
                if (!$profile) {
                    $profileid = repository_kaltura_create_metadata_profile($connection);

                    if (!$profileid) {
                        $mform->addElement('static', 'metadataprofile', get_string('using_metadata_profile', 'repository_kaltura'),
                            get_string('metadata_profile_error', 'repository_kaltura'));
                    }

                    // Save profile id in config_plugins table
                    set_config('metadata_profile_id', $profileid, REPOSITORY_KALTURA_PLUGIN_NAME);

                    // Get profile information again
                    $profile = repository_kaltura_get_metadata_profile_info($connection);


                } else {
                    // Check if the metadata profile id exists in the mdl_config_table
                    $profile_id = get_config(REPOSITORY_KALTURA_PLUGIN_NAME, 'metadata_profile_id');

                    // If empty then set the profile id
                    if (empty($profile_id)) {
                        $profile_obj = repository_kaltura_get_metadata_profile($connection);
                        set_config('metadata_profile_id', $profile_obj->id, REPOSITORY_KALTURA_PLUGIN_NAME);
                    }

                }

                $mform->addElement('static', 'metadata', get_string('using_metadata_profile', 'repository_kaltura'),
                                   $profile);

            }

        }

        /**
         * file types supported by Kaltura plugin
         * @return array
         */
        public function supported_filetypes() {
            return array('video', 'web_image');
        }

        /**
         * Kaltura plugin only return external links
         * @return int
         */
        public function supported_returntypes() {
            return FILE_EXTERNAL;
        }

        public function get_listing($path='', $page = 1) {
            global $USER, $DB;

            $course_access = array();
            $ret = array();

            $system_access = repository_kaltura_get_course_access_list('repository/kaltura:systemvisibility');
            $shared_access = repository_kaltura_get_course_access_list('repository/kaltura:sharedvideovisibility');

            // Create Kaltura category for Moodle course
            $kaltura = new kaltura_connection();
            $connection = $kaltura->get_connection(true, KALTURA_SESSION_LENGTH);

            $courseid = get_courseid_from_context($this->context);

            if (!$this->root_category_initialized() || empty($connection) ||
                (empty($system_access) && empty($shared_access))) {
                $ret['nologin'] = true;
                $ret['nosearch'] = true; // See print_search() for search form
                $ret['logouttext'] = 'not configured propertly';
                $ret['list'] = array();
                return $ret;

            }

            if ($courseid) {
                repository_kaltura_create_course_category($connection, $courseid);
            }

            // Page is set to 0 when the first page of output is displayed
            // Manually set it to 1 so that the first page link is highlighted
            if (0 == $page) {
                $page = 1;
            }

            $ret['nologin'] = true;
            $ret['dynload'] = true;
            $ret['norefresh'] = true;
            $ret['nosearch'] = false; // See print_search() for search form

            // If the user has both system and shared access to courses then their view will contain
            // a root directory with a system and a shared folder.  Below those folders will be course
            // folders.
            if (!empty($system_access) && !empty($shared_access)) {

                $ret = repository_kaltura_get_system_shared_listing($ret, $path, $system_access, $shared_access, $page);

            } else if (!empty($system_access)) {

                // If the user only has system access then their root directory will only contain courses that
                // they have system access to.
                $newpath = $path;

                if (empty($path)) {
                    $newpath = REPOSITORY_KALTURA_USED_PATH;
                }

                $ret_temp = repository_kaltura_get_course_video_listing($system_access, $newpath, REPOSITORY_KALTURA_USED_PATH, $page);
                $ret = array_merge($ret, $ret_temp);


            } else if (!empty($shared_access)) {

                $ret = repository_kaltura_get_shared_listing($ret, $path, $shared_access, $page);

            }

            return $ret;
        }

        /**
         * This functions retrieves all the courses the user has access to and meets
         * the course filter criteria.
         *
         * @param none
         * @return array - array of Moodle course ids
         */
        private function get_courses_from_filter() {

            global $DB;

            $course_criteria = '';
            $params          = '';
            $sql             = '';
            $course_access   = array();

            switch ($this->search_for) {
                case 'shared':
                    $course_access = repository_kaltura_get_course_access_list('repository/kaltura:sharedvideovisibility');
                    break;
                case 'used':
                    $course_access = repository_kaltura_get_course_access_list('repository/kaltura:systemvisibility');
                    break;
                case 'site_shared':
                    // when searching for videos shared with site, course name filtering is excluded
                    $this->search_course_name = '';
                    break;
            }

            // If no course name was specified then return the list of all available courses
            if (empty($this->search_course_name)) {
                return $course_access;
            }

            $course_access = array_keys($course_access);
            $course_access = implode(',', $course_access);

            // Find courses based on filter selection
            switch ($this->search_course_filter) {
                case 'contains':
                    $course_criteria = $DB->sql_like('fullname', ':name', false);
                    $params = array('name' => '%' .$this->search_course_name. '%');
                    break;
                case 'equals':
                    $params = array('fullname' => $this->search_course_name);
                    break;
                case 'startswith':
                    $course_criteria = $DB->sql_like('fullname', ':name', false);
                    $params = array('name' => $this->search_course_name. '%');
                    break;
                case 'endswith':
                    $course_criteria = $DB->sql_like('fullname', ':name', false);
                    $params = array('name' => '%' .$this->search_course_name);
                    break;
            }


            if (!empty($course_criteria)) {

                $sql = "SELECT id ".
                       "  FROM {course} ".
                       "  WHERE {$course_criteria} ".
                       "   AND id IN ($course_access) ";

                $records = $DB->get_records_sql($sql, $params);
            } else {

                $records = $DB->get_records('course', $params);
            }


            if (empty($records)) {
                return array();
            }

            return $records;
        }

        private function print_search_form() {

            require_once('search_form.php');

            global $USER, $SESSION;


            $str = '';
            $system_access = repository_kaltura_get_course_access_list('repository/kaltura:systemvisibility');
            $shared_access = repository_kaltura_get_course_access_list('repository/kaltura:sharedvideovisibility');

            // Clear search session data
            if (array_key_exists('search', $SESSION->kalrepo) &&
                array_key_exists($USER->id, $SESSION->kalrepo['search'])) {

                unset($SESSION->kalrepo['search'][$USER->id]);
            }

            // if the user has both the system and shared video capability, display an option to choose to search
            // for videos shared with courses or used in courses.  Because of the restrictions in the API search
            // and technical difficulties with paging, the user must choose one of the other
            if (!empty($system_access) &&
                !empty($shared_access)) {

                $str .= repository_kaltura_print_shared_used_selection(false);
            } else if (!empty($system_access)) {


                $str .= repository_kaltura_print_used_selection(false);

            } else {

                $str .= repository_kaltura_print_shared_selection(false);
            }

            $str .= '&nbsp;&nbsp; ' . repository_kaltura_print_new_search_form($this);

            // Print container tag
            $param = array('id' => 'kal_repo_search',
                           'style' => 'display:none;white-space: nowrap;');

            $str = html_writer::tag('div', $str, $param);

            // Print search link
            $param = array('href' => '#',
                           'onclick' => repository_kaltura_print_search_form_javascript());
            $search_text_link = get_string('keyword', 'repository_kaltura');
            $str = html_writer::tag('a', $search_text_link, $param) . '<br />' . $str;

            return $str;
        }

        /**
         * Show the search screen, if required
         * @return null
         */
        public function print_search() {

            $search_form = $this->print_search_form();

            return $search_form;
        }

        /**
         *
         * @return bool
         */
        public function check_login() {
            return true;
        }

        /**
         * Produce results from search
         *
         * @param string $search_text
         * @return array
         */
        public function search($search_text, $page = 0) {

            global $USER, $SESSION, $OUTPUT;

            // Get search parameters if passed
            $name_search        = optional_param('s', '', PARAM_NOTAGS);
            $tag_search         = optional_param('t', '', PARAM_NOTAGS);
            $course_name_filter = optional_param('course_with', 'contains', PARAM_NOTAGS);
            $course_name        = optional_param('c', '', PARAM_NOTAGS);
            $search_own         = optional_param('own', '', PARAM_TEXT);
            $search_for         = optional_param('shared_used', 'shared', PARAM_TEXT);
            $page_param         = optional_param('page', 1, PARAM_INT);

            if (0 == $page_param) {
                $page_param = 1;
            }

            $search_data = new stdClass();

            if (empty($name_search) &&
                empty($tag_search) &&
                empty($course_name) &&
                empty($search_own) &&
                 (array_key_exists('search', $SESSION->kalrepo) &&
                  array_key_exists($USER->id, $SESSION->kalrepo['search']))) {

                $this->search_name          = $SESSION->kalrepo['search'][$USER->id]->search_name;
                $this->search_tags          = $SESSION->kalrepo['search'][$USER->id]->search_tags;
                $this->search_course_name   = $SESSION->kalrepo['search'][$USER->id]->search_course_name;
                $this->search_course_filter = $SESSION->kalrepo['search'][$USER->id]->search_course_filter;
                $this->search_for           = $SESSION->kalrepo['search'][$USER->id]->search_for;

            } else {

                $this->search_name          = trim($name_search);
                $this->search_tags          = trim($tag_search);
                $this->search_course_filter = $course_name_filter;
                $this->search_course_name   = trim($course_name);
                $this->search_for           = $search_for;

                $search_data->search_name          = $this->search_name;
                $search_data->search_tags          = $this->search_tags;
                $search_data->search_course_filter = $this->search_course_filter;
                $search_data->search_course_name   = $this->search_course_name;
                $search_data->search_for           = $this->search_for;

                $SESSION->kalrepo['search'][$USER->id] = $search_data;
            }

            $kaltura = new kaltura_connection();
            $connection = $kaltura->get_connection(true, KALTURA_SESSION_LENGTH);

            if (0 !== strcmp('own', $this->search_for)) {

                // Get a list of courses (system access and shared access) that match the course filter criteria
                $course_access = $this->get_courses_from_filter();

                // Special case for Moodle versions 2.3 and beyond - Passing search name value into name and tags argument to force
                // the function to use tagsNameMultiLikeOr search filter
                $search_results = repository_kaltura_search_videos($connection, $this->search_name, $this->search_name,
                                                                   $course_access, $page, $this->search_for);

            } else {

                // Special case for Moodle versions 2.3 and beyond - Passing search name value into name and tags argument to force
                // the function to use tagsNameMultiLikeOr search filter
                $search_results = repository_kaltura_search_own_videos($connection, $this->search_name, $this->search_name, $page);

            }

            $ret = array();

            if (empty($search_results)) {
                $ret['nologin'] = true;
                $ret['dynload'] = false;
                $ret['nosearch'] = false; // See print_search() for search form
                $ret['list'] = array();

                return $ret;
            }

            $ret['nologin'] = true;
            $ret['dynload'] = true;
            $ret['nosearch'] = false; // See print_search() for search form

            $uri         = local_kaltura_get_host();
            $partner_id  = local_kaltura_get_partner_id();
            $ui_conf_id  = local_kaltura_get_player_uiconf();
            $ret['list'] = repository_kaltura_format_data($search_results, $uri, $partner_id, $ui_conf_id);

            if ($search_results->totalCount > self::$page_size) {

                $ret['page'] = $page_param;
                $ret['pages'] = ceil($search_results->totalCount / self::$page_size);
                $ret['total'] = $search_results->totalCount;
                $ret['perpage'] = (int) self::$page_size;

            }

            return $ret;
        }

        function get_link($source) {
            //$context = get_context_by_id();
            //print_object($this);
            return $source;
        }

        /**
         * This is a dummy function created as a workaround for ELIS-8326
         * @return void
         */
        function category_tree() {}
    }
}
