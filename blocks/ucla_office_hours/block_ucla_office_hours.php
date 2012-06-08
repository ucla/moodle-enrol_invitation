<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/moodleblock.class.php');
require_once($CFG->dirroot . '/course/lib.php');

class block_ucla_office_hours extends block_base {
    public function init() {
        $this->title = get_string('pluginname', 'block_ucla_office_hours');
    }
    
    public function get_content() {
        if($this->content !== null) {
            return $this->content;
        }
        
        $this->content = new stdClass;
        
        return $this->content;
    }
    
    public function applicable_formats() {
        return array(
            'site-index' => false,
            'course-view' => false,
            'my' => false,
            'block-ucla_office_hours' => false,
            'not-really-applicable' => true
        );
    }
    
    /**
     * Adds link to control panel.
     * 
     * @param mixed $course
     * @param mixed $context
     * @return type 
     */
    public static function ucla_cp_hook($course, $context) {
        global $USER;
        
        // display office hours link if user has ability to edit office hours
        if (block_ucla_office_hours::allow_editing($context, $USER->id)) {
            return array(array(
                'item_name' => 'edit_office_hours', 
                'action' => new moodle_url(
                        '/blocks/ucla_office_hours/officehours.php', 
                        array('courseid' => $course->id, 'editid' => $USER->id)
                    ),
                'tags' => array('ucla_cp_mod_common', 'ucla_cp_mod_other'),
                'required_cap' => null,
                'options' => array('post' => true)
            ));            
        }        
    }
    
    /**
    * Makes sure that $edit_user is an instructing role for $course. Also makes 
    * sure that user initializing editing has the ability to edit office hours.
    * 
    * @param mixed $course_context  Course context
    * @param mixed $edit_user_id    User id we are editing
    * 
    * @return boolean
    */
    public static function allow_editing($course_context, $edit_user_id) {
        global $CFG, $USER;

        // do capability check (but always let user edit their own entry)
        if ($edit_user_id != $USER->id  && 
                !has_capability('block/ucla_office_hours:editothers', $course_context)) {
            //debugging('failed capability check');
            return false;
        }

        /**
        * Course and edit_user must be in the same course and must be one of the 
        * roles defined in $CFG->instructor_levels_roles, which is currently:
        * 
        * $CFG->instructor_levels_roles = array(
        *   'Instructor' => array(
        *       'editinginstructor',
        *       'ta_instructor'
        *   ),
        *   'Teaching Assistant' => array(
        *       'ta',
        *       'ta_admin'
        *   )
        * );
        */    

        // format $CFG->instructor_levels_roles so it is easier to search
        $allowed_roles = array_merge($CFG->instructor_levels_roles['Instructor'],
                $CFG->instructor_levels_roles['Teaching Assistant']);

        // get user's roles
        $roles = get_user_roles($course_context, $edit_user_id);

        // now see if any of those roles match anything in 
        // $CFG->instructor_levels_roles
        foreach ($roles as $role) {
            if (in_array($role->shortname, $allowed_roles)) {
                return true;
            }        
        }

        //debugging('role not in instructor_levels_roles');    
        return false;
    }
}

?>
