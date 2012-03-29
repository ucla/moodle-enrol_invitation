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
 * Prints the Export Roles page along with appropriate forms and / or actions.
 * @package   moodlerolesmigration
 * @copyright 2011 NCSU DELTA | <http://delta.ncsu.edu> and others
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__).'/../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once(dirname(__FILE__).'/exportroles_form.php');

// Site context
$context = get_context_instance(CONTEXT_SYSTEM);

// Set context ID vars
$contextid = $context->id;
$filecontextid = optional_param('filecontextid', 0, PARAM_INT);

// File parameters needed by non js interface 
$component  = optional_param('component', null, PARAM_ALPHAEXT);
$filearea   = optional_param('filearea', null, PARAM_ALPHAEXT);
$itemid     = optional_param('itemid', null, PARAM_INT);
$filepath   = optional_param('filepath', null, PARAM_PATH);
$filename   = optional_param('filename', null, PARAM_FILE);

// Require user to be logged in with permission to manage roles
require_login();
require_capability('moodle/role:manage', $context);
admin_externalpage_setup('exportroles');

// Init the form object
$form = new export_roles_form(null, array('contextid'=>$contextid));

// Process the form if it has been submitted
$data = $form->get_data();
if ($data && !empty($data->export)){
    // This file processes the export and delivers the XML file 
    include_once('do-export.php'); 
}elseif($form->is_submitted()){
    $errormsg = get_string('error_noselect', 'report_rolesmigration');
}

// Print the page header
echo $OUTPUT->header();

// Print the page heading
echo $OUTPUT->heading(get_string('selectrolestoexport', 'report_rolesmigration'));
echo $OUTPUT->container_start();

// Print the error message if one is present
if(isset($errormsg)) {
    echo '<div class="box errorbox">'.$errormsg.'</div>';
}

// Print the form
$form->display();

// Print the end of the page
echo $OUTPUT->container_end();
echo $OUTPUT->footer();
