<?php

class ucla_cp_module_email_students extends ucla_cp_module {
    function __construct($course) {
        global $CFG, $DB;
        
        // See if we want to do stuff
        $unhide = optional_param('unhide', false, PARAM_INT);

        // Create a news forum is it doesn't exist?
        $course_forum = forum_get_course_forum($course->id, 'news');
      
        // Get all the forums
        $course_forums = $DB->get_records('forum', 
            array('course' => $course->id, 'type' => 'news'));

        // Let's try to save some cycles and use moodle's modinfo mechanism
        $fast_modinfo = get_fast_modinfo($course);

        // This is used to have a slightly more stimulative visual notice.
        $this->pre_enabled = false;
        $this->post_enabled = true;

        // Setting default capability
        $this->capability = 'moodle/course:update';

        // Test for forum functionality and catching
        $init_name = 'email_students';

        // explicit unset
        unset($init_action);

        // Check that there is only one news forum
        if (count($course_forums) > 1) {
            $course_forum = false;
            $init_name = 'email_students_fix';

            $init_action = new moodle_url($CFG->wwwroot
                . '/course/view.php', array('topic' => '-1',
                    'id' => $course->id));
        } else if (empty($course_forums)) {
            if ($course_forum !== false) {
                $course_forums = array($course_forum);
            }
        }
        
        $course_module = null;

        // This means that we found 1 news forum
        // Now we need to find the course module associated with it...
        if (count($course_forums) == 1) {
            $instances = $fast_modinfo->get_instances();

            $target_forum = reset($course_forums);

            foreach ($instances['forum'] as $instance) {
                if ($instance->instance == $target_forum->id) {
                    $course_module = $instance;
                    break;
                }
            }
        }

        if ($unhide !== false) {
            confirm_sesskey();

            require_capability('moodle/course:activityvisibility',
                get_context_instance(CONTEXT_MODULE, $course_module->id));

            set_coursemodule_visible($course_module->id, 0);
            rebuild_course_cache($course->id);

            $course_module->visible = '1';
        }
                
        if ($course_module->visible == '1') {
            // This means that the forum is fine
            $init_action = new moodle_url($CFG->wwwroot 
                . '/mod/forum/post.php',
                array('forum' => $target_forum->id));
        } else {
            // This means that the forum exists and the forum
            // is hidden.
            $this->pre_enabled = true;
            $this->post_enabled = false;

            $this->capability = 'moodle/course:activityvisibility';

            $init_action = new moodle_url($CFG->wwwroot
                . '/blocks/ucla_control_panel/view.php',
                array('unhide' => $target_forum->id,
                    'sesskey' => sesskey(),
                    'courseid' => $course->id));


            $init_name = 'email_students_hidden';
        }

        $this->course_module = $course_module;

        if (!isset($init_action)) {
            $init_name = 'email_students_exception';

            // Disable the action
            $init_action = null;
        }

        parent::__construct($init_name, $init_action);
    }

    function get_key() {
        return 'email_students';
    }

    function autotag() {
        return array('ucla_cp_mod_common');
    }

    function autocap() {
        return $this->capability;
    }

    function autoopts() {
        return array('pre' => $this->pre_enabled, 'post' => $this->post_enabled);
    }

    function validate($course, $context) {
        $context = get_context_instance(CONTEXT_MODULE,
            $this->course_module->id);

        return has_capability($this->autocap(), $context);
    }
}
