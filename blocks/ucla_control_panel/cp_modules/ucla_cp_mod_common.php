<?php
require_once(dirname(__FILE__) . '/ucla_cp_module.php');

class ucla_cp_mod_common extends ucla_cp_module {
    var $handler = 'general_icon_link';
    var $orientation = 'row';

    function get_content_array($course) {
        global $CFG;

        $has_cap = has_capability('moodle/course:update', 
                get_context_instance(CONTEXT_COURSE, $course->id));

        $pre_contents = array(
            'add_link' => 'Link', 
            'email_students' => null,
            'add_file' => 'File', 
            'office_hours' => 'Office Hours...',
            'modify_sections' => 'Modify Sections?', 
            'rearrange' => 'Rearrange!',
            'turn_editing_on' => null
        );

        // This is so that we can pre-filter out contents before
        // we throw this to the render handler. That way, for things
        // like TA and Student control panels, we can remove many
        // functions without compromising the appearance.
        $contents = array();
        $row_cont = array();

        $rc = 0;
        foreach ($pre_contents as $pre => $content) {
            if ($rc == 2) {
                $contents[] = $row_cont;
                $row_cont = array();

                $rc = 0;
            } 

            if ($has_cap || $pre != 'office_hours') {
                $row_cont[$pre] = $content;
                $rc++;
            }
        }

        if (!empty($row_cont)) {
            $contents[] = $row_cont;
        }

        return $contents;
    }

    function handler_turn_editing_on($course, $item) {
        global $CFG;

        return $this->general_icon_link($item, 
            new moodle_url($CFG->wwwroot . '/course/view.php',
                array('id' => $course->id, 'editing' => '1')),
                false, $item . '_post');
    }

    function handler_email_students($course, $self_item) {
        global $CFG;

        $mi = get_fast_modinfo($course);

        $forums = $mi->instances['forum'];

        if (empty($forums)) {
            print_error('someerror');
        }

        // Please see forum_get_course_forum()
        $forum_name = get_string('namenews', 'forum');

        $link = null;
        $item = null;

        $pre = false;
        $post = $self_item . '_post';
        foreach ($forums as $forum) {
            if ($forum->name == $forum_name) {
                if ($forum->visible == '1') {
                    $link = new moodle_url($CFG->wwwroot 
                        . '/mod/forum/post.php', array(
                            'forum' => $forum->id
                        ));
                    $item = $self_item;
                } else {
                    $link = new moodle_url($CFG->wwwroot
                        . '/blocks/ucla_control_panel/view.php',
                        array('courseid' => $course->id,
                            'setemailvisible' => '1'));
                    $item = 'visible_forum';
                    $pre = $self_item . '_warn';
                }
            }
        }

        if ($link != null && $item != null) {
            return $this->general_icon_link($item, $link, $pre, $post);
        }
    }
}
