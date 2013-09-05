<?php
// This file is part of Moodle - http://moodle.org/
//
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
 *  This configuration file has been preconfigured to set certain variables
 *  such that launching and upgrades will run as smoothly as possible.
 *
 *  Currently, the plan is to symbolically link this file as such:
 *  moodle/config.php -> moodle/local/ucla/config/<this file>
 *
 *  The original configuration file should not be used, as it does not have
 *  any capability of saying that another configuration file can be 
 *  included before starting the Moodle session.
 *
 *  If you want configurations to be not within revisioning, then place
 *  your secrets @ moodle/config_private.php.
 *
 **/

unset($CFG);
global $CFG;

$CFG = new stdClass();

$CFG->dbtype    = 'mysqli';
$CFG->dblibrary = 'native';
$CFG->dbhost    = 'localhost';
$CFG->dbname    = '';
$CFG->dbuser    = '';
$CFG->dbpass    = '';
$CFG->prefix    = 'mdl_';
$CFG->dboptions = array(
    'dbpersist' => 0,
    'dbsocket'  => 1 
);

$CFG->wwwroot  = '';
$CFG->dataroot = ''; 

// This determines what the admin folder is called.
$CFG->admin    = 'admin';

$CFG->directorypermissions = 0777;

// Registrar
$CFG->registrar_dbtype = 'odbc';
$CFG->registrar_dbhost = 'REGISTRAR';
$CFG->registrar_dbuser = '';
$CFG->registrar_dbpass = '';
$CFG->registrar_dbencoding = 'ISO-8859-1';

// This should never change after the first install, or else any special
// logins using the Moodle login will not work.
$CFG->passwordsaltmain = '';

// Format and browseby and anything else that requires instructors to be 
// displayed, we need to determine which roles should be displayed.
$CFG->instructor_levels_roles['Instructor'] = array('editinginstructor', 'ta_instructor');
$CFG->instructor_levels_roles['Teaching Assistant'] = array('ta', 'ta_admin');

// Friendly URLs now exist at the server level
//$CFG->forced_plugin_settings['local_ucla']['friendly_urls_enabled'] = true; 
$CFG->forced_plugin_settings['local_ucla']['remotetermcutoff'] = '12F'; 
$CFG->forced_plugin_settings['local_ucla']['archiveserver'] = null; 

// My Sites CCLE-2810
// Term limiting
$CFG->forced_plugin_settings['local_ucla']['student_access_ends_week'] = 0;
$CFG->forced_plugin_settings['local_ucla']['oldest_available_term'] = '08S';

// Browseby CCLE-2894
$CFG->forced_plugin_settings['block_ucla_browseby']['use_local_courses'] = true;
$CFG->forced_plugin_settings['block_ucla_browseby']['ignore_coursenum'] = '194,295,296,375';
$CFG->forced_plugin_settings['block_ucla_browseby']['allow_acttypes'] = 'LEC,SEM';

// Course builder \\

// Course Requestor
$CFG->forced_plugin_settings['tool_uclacourserequestor']['mailinst_default'] = 0; 
$CFG->forced_plugin_settings['tool_uclacourserequestor']['nourlupdate_default'] = 1;

// Course Creator
$CFG->forced_plugin_settings['tool_uclacoursecreator']['course_creator_email'] = '';
$CFG->forced_plugin_settings['tool_uclacoursecreator']['email_template_dir'] = '';
$CFG->forced_plugin_settings['tool_uclacoursecreator']['make_division_categories'] = false;
$CFG->forced_plugin_settings['format_ucla']['hideregsummary'] = 1;

// MyUCLA url updater
$CFG->forced_plugin_settings['tool_myucla_url']['url_service'] = 'https://test.ccle.ucla.edu/myucla_url_updater/update.php';  // test server
$CFG->forced_plugin_settings['tool_myucla_url']['user_name'] = 'SSC TEST';   // name for registering URL with My.UCLA
$CFG->forced_plugin_settings['tool_myucla_url']['user_email'] = 'ssc-test@ssc.ucla.edu';  // email for registering URL with My.UCLA
$CFG->forced_plugin_settings['tool_myucla_url']['override_debugging'] = true;   // test sending MyUCLA urls

// For MyUCLA url updater phpunit tests.
define('MYUCLA_URL_UPDATER_TEST_CONFIG_URL',
        $CFG->forced_plugin_settings['tool_myucla_url']['url_service']);
define('MYUCLA_URL_UPDATER_TEST_CONFIG_NAME',
        $CFG->forced_plugin_settings['tool_myucla_url']['user_name']);
define('MYUCLA_URL_UPDATER_TEST_CONFIG_EMAIL',
        $CFG->forced_plugin_settings['tool_myucla_url']['user_email']);
define('MYUCLA_URL_UPDATER_TEST_CONFIG_OVERRIDE_DEBUGGING',
        $CFG->forced_plugin_settings['tool_myucla_url']['override_debugging']);
define('MYUCLA_URL_UPDATER_TEST_CONFIG_ACCESSDENIED_URL',
        'https://test.ccle.ucla.edu/myucla_url_updater/accessdenied.php');

// turn off messaging (CCLE-2318 - MESSAGING)
$CFG->messaging = false;

// CCLE-2763 - Use new $CFG->divertallemailsto setting in 1.9 and 2.x 
// development/testing environments
$CFG->divertallemailsto = 'franks@ssc.ucla.edu';

// CCLE-2590 - Implement Auto-detect Shibboleth Login
$CFG->shib_logged_in_cookie = '_ucla_sso';

// CCLE-2306 - HELP SYSTEM BLOCK
// if using JIRA, jira_user, jira_password, jira_pid should be defined in config_private.php
//$CFG->forced_plugin_settings['block_ucla_help']['send_to'] = 'jira';
//$CFG->forced_plugin_settings['block_ucla_help']['jira_endpoint'] = 'https://jira.ats.ucla.edu/CreateIssueDetails.jspa';
//$CFG->forced_plugin_settings['block_ucla_help']['jira_default_assignee'] = 'dkearney';
//$block_ucla_help_support_contacts['System'] = 'dkearney';  // default

// CCLE-2311 - VIEDO FURNACE BLOCK
$CFG->forced_plugin_settings['block_ucla_video_furnace']['source_url'] = 'http://164.67.141.31/~guest/VF_LINKS.TXT';

// CCLE-2312 - LIBRARY RESERVES BLOCK
$CFG->forced_plugin_settings['block_ucla_library_reserves']['source_url'] = 'ftp://ftp.library.ucla.edu/incoming/eres/voyager_reserves_data.txt';

// CCLE-2301 - COURSE MENU BLOCK
$CFG->forced_plugin_settings['block_ucla_course_menu']['trimlength'] = 22;

// useful TEST settings
$CFG->debug = 38911;    // DEVELOPER level debugging messages
$CFG->debugdisplay = true;  // show the debugging messages
$CFG->perfdebug = true; // show performance information
$CFG->debugpageinfo = true; // show page information

// UCLA Theme settings
$CFG->forced_plugin_settings['theme_uclashared']['running_environment'] = 'test';

// Newly created courses for ucla formats should only have the course menu block
$CFG->defaultblocks_ucla = 'ucla_course_menu';

// Enable conditional activities
$CFG->enableavailability = true;
$CFG->enablecompletion = true;  // needs to be enabled so that completion
                                // of tasks can be one of the conditions

// CCLE-2229 - Force public/private to be on
$CFG->enablegroupmembersonly = true; // needs to be on for public-private to work
$CFG->enablepublicprivate = true;

// CCLE-2792 - Enable multimedia filters
// NOTE: you still need to manually set the "Active?" value of the "Multimedia 
// plugins" filter at "Site administration > Plugins > Filters > Manage filters"
$CFG->filter_mediaplugin_enable_youtube = true;
$CFG->filter_mediaplugin_enable_vimeo = true;
$CFG->filter_mediaplugin_enable_mp3 = true;
$CFG->filter_mediaplugin_enable_flv = true;
$CFG->filter_mediaplugin_enable_swf = false;    // security risk if enabled
$CFG->filter_mediaplugin_enable_html5audio = true;
$CFG->filter_mediaplugin_enable_html5video = true;
$CFG->filter_mediaplugin_enable_qt = true;
$CFG->filter_mediaplugin_enable_wmp = true;
$CFG->filter_mediaplugin_enable_rm = true;

/// CCLE-2810 - My Sites - disallow customized "My Moodle" page
$CFG->forcedefaultmymoodle = false;

// to enable database unit testing
$CFG->unittestprefix = 'tst_';

// CCLE-3966 - Include self when messaging participants.
// Emails should still be sent to users that are logged in.
$CFG->forced_plugin_settings['message']['message_provider_moodle_instantmessage_loggedin'] = 'popup,email';

// Site administration > Advanced features
$CFG->usetags = 0;
$CFG->enablenotes = 0;
$CFG->bloglevel = 0; // Disable blog system completely
$CFG->enablebadges = 0; // Disable badges

// Site administration > Users > Permissions > User policies
$CFG->autologinguests = true;
$CFG->showuseridentity = 'idnumber,email';

// Site administration > Courses > Course default settings
$CFG->forced_plugin_settings['moodlecourse']['format'] = 'ucla';
$CFG->forced_plugin_settings['moodlecourse']['maxbytes'] = 2147483648;  // 2GB
// CCLE-2903 - Don't set completion tracking to be course default
$CFG->forced_plugin_settings['moodlecourse']['enablecompletion'] = 0;

// Site administration > Courses > Course request
$CFG->enablecourserequests = 1;

// Site administration > Courses > Backups > General backup defaults
$CFG->forced_plugin_settings['backup']['backup_general_users'] = 0;

// Site administration > Language > Language settings
$CFG->langstringcache = false;

// Site administration > Plugins > Activity modules > Assignment
$CFG->assignment_maxbytes = 10485760;   // 100MB

// Site administration > Plugins > Activity modules > Folder
$CFG->forced_plugin_settings['folder']['requiremodintro'] = 0;

// Site administration > Plugins > Activity modules > IMS content package
$CFG->forced_plugin_settings['imscp']['requiremodintro'] = 0;

// Site administration > Plugins > Activity modules > Page
$CFG->forced_plugin_settings['page']['requiremodintro'] = 0;
$CFG->forced_plugin_settings['page']['printheading'] = 1;

// Site administration > Plugins > Activity modules > File
$CFG->forced_plugin_settings['resource']['requiremodintro'] = 0;
$CFG->forced_plugin_settings['resource']['printheading'] = 1;
$CFG->forced_plugin_settings['resource']['display'] = 4;   // "Force Download"

// Site administration > Plugins > Activity modules > Turnitin Assignment
$CFG->turnitin_apiurl = 'https://api.turnitin.com/api.asp';
$CFG->turnitin_studentemail = 0;
$CFG->turnitin_tutoremail = 0;

// Site administration > Plugins > Activity modules > URL
$CFG->forced_plugin_settings['url']['requiremodintro'] = 0;
$CFG->forced_plugin_settings['url']['displayoptions'] = '0,1,2,3,4,5,6';    // allow every option
$CFG->forced_plugin_settings['url']['printheading'] = 1;
$CFG->forced_plugin_settings['url']['display'] = 5; // RESOURCELIB_DISPLAY_OPEN

// Site administration > Plugins > Enrollments > UCLA registrar
$CFG->forced_plugin_settings['enrol_database']['localuserfield'] = 'idnumber';
$CFG->forced_plugin_settings['local_ucla']['overrideenroldatabase'] = 1;

// Site administration > Plugins > Enrollments > Guest access
$CFG->forced_plugin_settings['enrol_guest']['defaultenrol'] = 1;
$CFG->forced_plugin_settings['enrol_guest']['status'] = 0;  // 0 is yes, 1 is no

// Site administration > Plugins > Licences > Manage licences
$CFG->sitedefaultlicense = 'tbd';

// Site administration > Plugins > Repositories > Common repository settings
$CFG->legacyfilesinnewcourses = 0;  // disallow new course to enable legacy course files

// Site administration > Plugins > Local plugins > UCLA configurations
$CFG->forced_plugin_settings['local_ucla']['registrar_cache_ttl'] = 3600;   // 1 hour

// Site administration > Security > Site policies
$CFG->forceloginforprofiles = true; 
$CFG->forceloginforprofileimage = true; // temporary until "CCLE-2368 - PIX.PHP security fix" is done
$CFG->maxeditingtime = 900; // 15 minutes
$CFG->fullnamedisplay = 'language'; // CCLE-2550 - Lastname, Firstname sorting
$CFG->cronclionly = true;

// Site administration > Security > HTTP security
//$CFG->loginhttps = true;
$CFG->cookiesecure = true;
$CFG->allowframembedding = 1; // CCLE-3021 - enabled because some collab sites need to be embedded

// Site administration > Security > Anti-Virus
$CFG->runclamonupload = true;
$CFG->pathtoclam = '/usr/bin/clamscan';
$CFG->clamscan = '/usr/bin/clamscan';
$CFG->quarantinedir = '/usr/local/clamquarantine';
$CFG->clamfailureonupload = 'donothing';

// Site administration > Appearance > Themes
$CFG->theme = 'uclashared';

// Site administration > Appearance > Navigation
$CFG->defaulthomepage = 0;    // user's home page should be "My Moodle" (HOMEPAGE_MY)
$CFG->navlinkcoursesections = 1; // CCLE-3031 - Section Titles breadcrumbs aren't links

// Site administration > Appearance > Courses
$CFG->courselistshortnames = 1;

// Site administration > Server > System paths
$CFG->pathtodu = '/usr/bin/du';
$CFG->aspellpath = '/usr/bin/aspell';

// Site administration > Server > Session handling
$CFG->dbsessions = false;

// Site administration > Server > Performance
$CFG->extramemorylimit = '1024M';

// If you want to have un-revisioned configuration data, place in this file.
// $CFG->dirroot is overwritten later
$_dirroot_ = dirname(realpath(__FILE__)) . '/../../..';

// Try an alternative directory setup.
if (!file_exists($_dirroot_ . '/config.php')) {
    $_dirroot_ = dirname(realpath(__FILE__));

    if (!file_exists($_dirroot_ . '/config.php')) {
        die ('Improper setup of configuration file.');
    }
}

/** 
 *  Automatic Shibboleth configurations.
 *  Disabling in favor for GUI configurations.
 *  Keeping in code for sake of quick re-enabling and reference.
 *  To re-enable, add a '/' at the end of the following line.
 **/
$CFG->auth = 'shibboleth';
$CFG->alternateloginurl = $CFG->wwwroot . '/login/ucla_login.php?shibboleth';

$CFG->forced_plugin_settings['auth/shibboleth'] = array(
    'user_attribute'    => 'HTTP_SHIB_EDUPERSON_PRINCIPALNAME',
    'convert_data'      => $_dirroot_ . '/shib_transform.php',
    'logout_handler'    => $CFG->wwwroot . '/Shibboleth.sso/Logout',
    'logout_return_url' => 'https://shb.ais.ucla.edu/shibboleth-idp/Logout',
    'login_name'        => 'Shibboleth Login',

    'field_map_firstname'         => 'HTTP_SHIB_GIVENNAME',
    'field_updatelocal_firstname' => 'onlogin',
    'field_lock_firstname'        => 'locked',

    'field_map_lastname'         => 'HTTP_SHIB_PERSON_SURNAME',
    'field_updatelocal_lastname' => 'onlogin',
    'field_lock_lastname'        => 'locked',

    'field_map_email'        => 'HTTP_SHIB_MAIL',
    'field_updatelocal_mail' => 'onlogin',
    'field_lock_email'       => 'unlockedifempty',

    'field_map_idnumber'         => 'HTTP_SHIB_UID',
    'field_updatelocal_idnumber' => 'onlogin',
    'field_lock_idnumber'        => 'locked',

    'field_map_institution'         => 'HTTP_SHIB_IDENTITY_PROVIDER',
    'field_updatelocal_institution' => 'onlogin',
    'field_lock_institution'        => 'locked'
);
/**
 *  End shibboleth configurations.
 **/

// Load a custom private data
$_private_ = $_dirroot_ . '/config_private.php';
if (file_exists($_private_)) {
    require_once($_private_);
}

// This will bootstrap the moodle functions.
require_once($_dirroot_ . '/lib/setup.php');

// EOF
