<?php
/**
 * Create group OR edit group settings.
 *
 * @copyright &copy; 2006 The Open University
 * @author N.D.Freear AT open.ac.uk
 * @author J.White AT open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package groups
 */

require_once('../config.php');
require_once('lib.php');
require_once('group_form.php');

/// get url variables
$courseid = optional_param('courseid', 0, PARAM_INT);
$id       = optional_param('id', 0, PARAM_INT);
$delete   = optional_param('delete', 0, PARAM_BOOL);
$confirm  = optional_param('confirm', 0, PARAM_BOOL);

// This script used to support group delete, but that has been moved. In case
// anyone still links to it, let's redirect to the new script.
if($delete) {
    redirect('delete.php?courseid='.$courseid.'&groups='.$id);
}

if ($id) {
    if (!$group = $DB->get_record('groups', array('id'=>$id))) {
        print_error('invalidgroupid');
    }
    if (empty($courseid)) {
        $courseid = $group->courseid;

    } else if ($courseid != $group->courseid) {
        print_error('invalidcourseid');
    }

    if (!$course = $DB->get_record('course', array('id'=>$courseid))) {
        print_error('invalidcourseid');
    }

} else {
    if (!$course = $DB->get_record('course', array('id'=>$courseid))) {
        print_error('invalidcourseid');
    }
    $group = new stdClass();
    $group->courseid = $course->id;
}

if ($id !== 0) {
    $PAGE->set_url('/group/group.php', array('id'=>$id));
} else {
    $PAGE->set_url('/group/group.php', array('courseid'=>$courseid));
}

require_login($course);
$context = get_context_instance(CONTEXT_COURSE, $course->id);
require_capability('moodle/course:managegroups', $context);

$returnurl = $CFG->wwwroot.'/group/index.php?id='.$course->id.'&group='.$id;

// Prepare the description editor: We do support files for group descriptions
$editoroptions = array('maxfiles'=>EDITOR_UNLIMITED_FILES, 'maxbytes'=>$course->maxbytes, 'trust'=>false, 'context'=>$context, 'noclean'=>true);
if (!empty($group->id)) {
    $group = file_prepare_standard_editor($group, 'description', $editoroptions, $context, 'group', 'description', $group->id);
} else {
    $group = file_prepare_standard_editor($group, 'description', $editoroptions, $context, 'group', 'description', null);
}

/**
 * Require that group assignments are not made on the public/private group.
 *
 * @author ebollens
 * @version 20110719
 * 
 * Group assignments for tracked course section groups prevented.
 * @version 2012062000
 **/
require_once($CFG->libdir.'/publicprivate/course.class.php');
$publicprivate_course = new PublicPrivate_Course($course);

require_once($CFG->dirroot . '/blocks/ucla_group_manager/ucla_synced_group.class.php');
$trackedgroups = ucla_synced_group::get_tracked_groups($course->id);
$trackedgroupids = array();
foreach ($trackedgroups as $trackedgroup) {
    $trackedgroupids[] = (int)$trackedgroup->groupid;
}

$istrackedgroupmanager = in_array($group->id, $trackedgroupids);

if ($istrackedgroupmanager) {
    $cannoteditmessage = 'ucla_groupmanagercannotremove';
    $cannoteditplugin = 'block_ucla_group_manager';
} else {
    $cannoteditmessage = 'publicprivatecannotremove';
    $cannoteditplugin = '';
}

$istrackedgroup = $publicprivate_course->is_group($group) 
    || $istrackedgroupmanager;

/// First create the form
$editform = new group_form(null, array('editoroptions'=>$editoroptions));
$editform->set_data($group);

if ($editform->is_cancelled()) {
    redirect($returnurl);
// CCLE-2302 - Prevent editing of tracked groups
} elseif ($data = $editform->get_data() && !$istrackedgroup) {

    if ($data->id) {
        groups_update_group($data, $editform, $editoroptions);
    } else {
        $id = groups_create_group($data, $editform, $editoroptions);
        $returnurl = $CFG->wwwroot.'/group/index.php?id='.$course->id.'&group='.$id;
    }

    redirect($returnurl);
}

$strgroups = get_string('groups');
$strparticipants = get_string('participants');

if ($id) {
    $strheading = get_string('editgroupsettings', 'group');
} else {
    $strheading = get_string('creategroup', 'group');
}

$PAGE->navbar->add($strparticipants, new moodle_url('/user/index.php', array('id'=>$courseid)));
$PAGE->navbar->add($strgroups, new moodle_url('/group/index.php', array('id'=>$courseid)));
$PAGE->navbar->add($strheading);

/// Print header
$PAGE->set_title($strgroups);
$PAGE->set_heading($course->fullname . ': '.$strgroups);
echo $OUTPUT->header();

/**
 * Alert that public/private group cannot be edited.
 *
 * @author ebollens
 * @version 20110719
 */
if($istrackedgroup) {
    echo $OUTPUT->notification(get_string($cannoteditmessage, $cannoteditplugin));
    echo $OUTPUT->continue_button('index.php?id='.$course->id);
    echo $OUTPUT->footer();
    die;
}

echo '<div id="grouppicture">';
if ($id) {
    print_group_picture($group, $course->id);
}
echo '</div>';
$editform->display();
echo $OUTPUT->footer();
