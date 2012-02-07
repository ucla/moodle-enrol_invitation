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
 * Prints the Import Roles page along with appropriate forms and / or actions
 * @package   moodlerolesmigration
 * @copyright 2011 NCSU DELTA | <http://delta.ncsu.edu> and others
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__).'/../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once(dirname(__FILE__).'/importroles_form.php');
require_once('lib.php');

// Site context
$context = get_context_instance(CONTEXT_SYSTEM);

// Context id vars
$contextid = $context->id;
$filecontextid = optional_param('filecontextid', 0, PARAM_INT);

// File parameters needed by non js interface
$component  = optional_param('component', null, PARAM_ALPHAEXT);
$filearea   = optional_param('filearea', null, PARAM_ALPHAEXT);
$itemid     = optional_param('itemid', null, PARAM_INT);
$filepath   = optional_param('filepath', null, PARAM_PATH);
$filename   = optional_param('filename', null, PARAM_FILE);

// Parameters from import configuration
$roles_to_create = optional_param('to_create', array(), PARAM_RAW);
$roles_to_replace = optional_param('to_replace', array(), PARAM_RAW);
$roles = array('create'=>$roles_to_create,'replace'=>$roles_to_replace);
$actions = optional_param('actions',array(), PARAM_RAW);

// Require user to be logged in with permission to manage roles
require_login();
require_capability('moodle/role:manage', $context);
require_capability('moodle/restore:uploadfile', $context);
admin_externalpage_setup('importroles');

// check if tmp dir exists
$tmpdir = $CFG->dataroot . '/local/rolesmigration/temp/';
if (!check_dir_exists($tmpdir, true, true)) {
    throw new restore_controller_exception('cannot_create_backup_temp_dir');
}

// Print the page header
echo $OUTPUT->header();

// Print the page heading
echo $OUTPUT->heading(get_string('importroles','local_rolesmigration'));
echo $OUTPUT->container_start();

// Print the error message if one is present
if(isset($errormsg)){
    echo "<div>$errormsg</div>";
}

// Print the form
$mform = new import_roles_form(null, array('roles'=>$roles, 'actions'=>$actions));
if($mform->is_validated()){
    require_once(dirname(__FILE__).'/do-import.php');
    $r = $CFG->wwwroot . '/' . $CFG->admin . '/roles/manage.php';
    echo '<p>'.get_string('link_to_define_roles', 'local_rolesmigration', $r), '</p>';
}else{
    $mform->display();
}

// Print the end of the page
echo $OUTPUT->container_end();
echo $OUTPUT->footer();

