<?php
/**
 * Delete group
 *
 * @copyright &copy; 2008 The Open University
 * @author s.marshall AT open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package groups
 */

require_once('../config.php');
require_once('lib.php');

// Get and check parameters
$courseid = required_param('courseid', PARAM_INT);
$groupids = required_param('groups', PARAM_SEQUENCE);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

$PAGE->set_url('/group/delete.php', array('courseid'=>$courseid,'groups'=>$groupids));

// Make sure course is OK and user has access to manage groups
if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('invalidcourseid');
}
require_login($course);
$context = get_context_instance(CONTEXT_COURSE, $course->id);
require_capability('moodle/course:managegroups', $context);

// Make sure all groups are OK and belong to course
$groupidarray = explode(',',$groupids);
$groupnames = array();
foreach($groupidarray as $groupid) {
    if (!$group = $DB->get_record('groups', array('id' => $groupid))) {
        print_error('invalidgroupid');
    }
    if ($courseid != $group->courseid) {
        print_error('groupunknown', '', '', $group->courseid);
    }
    $groupnames[] = format_string($group->name);
}

$returnurl='index.php?id='.$course->id;

if(count($groupidarray)==0) {
    print_error('errorselectsome','group',$returnurl);
}

// Public private special group
require_once($CFG->libdir.'/publicprivate/course.class.php');
$publicprivate_course = new PublicPrivate_Course($courseid);

// Synced section groups special groups
require_once($CFG->dirroot . '/blocks/ucla_group_manager/ucla_synced_group.class.php');
$trackedgroupobjs = ucla_synced_group::get_tracked_groups($courseid);
$trackedgroups = array();

foreach ($trackedgroupobjs as $groupobj) {
    $trackedgroups[] = $groupobj->groupid;
}

if ($confirm && data_submitted()) {
    if (!confirm_sesskey() ) {
        print_error('confirmsesskeybad','error',$returnurl);
    }

    /**
     * Remove all groups except for the public/private group.
     *
     * @author ebollens
     * @version 20110719
     * 
     * Remove all groups except special groups.
     * @version 2012061900
     */
    foreach($groupidarray as $key => $groupid) {
        if (!$publicprivate_course->is_group($groupid)) {
            unset($groupidarray[$key]);
        }

        if (in_array($groupid, $trackedgroups)) {
            unset($groupidarray[$key]);
        }
    }

    foreach ($groupidarray as $groupid) {
        groups_delete_group($groupid);
    }

    redirect($returnurl);
} else {
    $PAGE->set_title(get_string('deleteselectedgroup', 'group'));
    $PAGE->set_heading($course->fullname . ': '. get_string('deleteselectedgroup', 'group'));
    echo $OUTPUT->header();

    /**
     *  For specially tracked-groups, prevent deletion.
     *  @version 2012061900
     */
    $istrackedgroup = false;
    $pluginname = null;
    foreach($groupidarray as $groupid) {
        if ($publicprivate_course->is_group($groupid)) {
            $istrackedgroup= 'publicprivate';
            break;
        } else if (in_array($groupid, $trackedgroups)) {
            $istrackedgroup = 'ucla_groupmanager';
            $pluginname = 'block_ucla_group_manager';
            break;
        }
    }
    
    if ($istrackedgroup) {
        $pluralize = $istrackedgroup . 'cannotremove_oneof';
        if (count($groupidarray) <= 1) {
            $pluralize = $istrackedgroup . 'cannotremove_one';
        }

        $pluralizestr = get_string($pluralize, $pluginname);
        echo $OUTPUT->notification($pluralizestr);
        echo $OUTPUT->continue_button('index.php?id='.$courseid);
    } else {
        $optionsyes = array('courseid'=>$courseid, 'groups'=>$groupids, 'sesskey'=>sesskey(), 'confirm'=>1);
        $optionsno = array('id'=>$courseid);
        if(count($groupnames)==1) {
            $message=get_string('deletegroupconfirm', 'group', $groupnames[0]);
        } else {
            $message=get_string('deletegroupsconfirm', 'group').'<ul>';
            foreach($groupnames as $groupname) {
                $message.='<li>'.$groupname.'</li>';
            }
            $message.='</ul>';
        }
        $formcontinue = new single_button(new moodle_url('delete.php', $optionsyes), get_string('yes'), 'post');
        $formcancel = new single_button(new moodle_url('index.php', $optionsno), get_string('no'), 'get');
        echo $OUTPUT->confirm($message, $formcontinue, $formcancel);
    }

    echo $OUTPUT->footer();
}
