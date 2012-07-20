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
    
    /**
     * Renders the office hours and contact information table to be displayed
     * on the course webpage.
     * 
     * @param array     $instructors        Array of instructors
     * @param array     $instructor_types   Array of instructor types
     * @param mixed     $course             Current course
     * @param mixed     $context            Course context
     * 
     * @return string HTML code
     */
    public static function render_office_hours_table($instructors, $instructor_types, $course, $context)
    {
        global $DB, $OUTPUT, $PAGE, $USER, $CFG;
        
        $has_capability_edit_office_hours = has_capability('block/ucla_office_hours:editothers', $context);
        $editing = $PAGE->user_is_editing();
        $streditsummary     = get_string('editcoursetitle', 'format_ucla');
        $instr_info_table = '';
        
        foreach ($instructor_types as $title => $rolenames) {
            $goal_users = array();
            foreach ($instructors as $user) {
                if (in_array($user->shortname, $rolenames)) {
                    $goal_users[$user->id] = $user;
                }
            }

            if (empty($goal_users)) {
                continue;
            }

            $table = new html_table();
            $table->width = '*';

            // TODO make this more modular
            $desired_info = array(
                'fullname' => $title,
                'email' => get_string('email', 'format_ucla'),                            
                'office' => get_string('office', 'format_ucla'),
                'office_hours' => get_string('office_hours', 'format_ucla'),
                'phone' => get_string('phone', 'format_ucla'),                            
            );

            $cdi = count($desired_info);
            $aligns = array();
            for ($i = 0; $i < $cdi; $i++) {
                $aligns[] = 'left';
            }

            $table->align = $aligns;

            $table->attributes['class'] = 'boxalignleft';

            // use array_values, to remove array keys, which are 
            // mistaken as another css class for given column
            $table->head = array_values($desired_info);

            //BEGIN UCLA MOD: CCLE-2381 - Update Office Hours and Contact Info
            
            // Determine if the user is enrolled in the course or is an admin
            // Assuming 'moodle/course:update' is a sufficient capability to 
            // to determine if a user is an admin or not
            $enrolled_or_admin = is_enrolled($context, $USER) || has_capability('moodle/course:update', $context);
            
            foreach ($goal_users as $user) {
                $user_row = array();
                $office_info = $DB->get_record('ucla_officehours', 
                        array('courseid' => $course->id, 'userid' => $user->id));
                $instr = $DB->get_record('user', array('id' => $user->id));
                $email_display = $instr->maildisplay;
                $instr_website = $instr->url;
                foreach ($desired_info as $field => $header) {
                    $dest_data = '';
                    if ($field == 'fullname') {
                        if ($editing && $has_capability_edit_office_hours) {
                            //Need to only display the update string for certain users
                            $update_url = new moodle_url($CFG->wwwroot . '/blocks/ucla_office_hours/officehours.php',
                                            array('courseid' => $course->id, 'editid' => $user->id));
                            $strupdate = get_string('editofficehours', 'format_ucla');

                            // Add an edit icon/text (based on preference)
                            $link_options = array('title' => $strupdate);
                            $img_options = array(
                                    'class' => 'icon edit iconsmall',
                                    'alt' => $streditsummary
                                );

                            $innards = new pix_icon('t/edit', $link_options['title'], 
                                'moodle', $img_options);

                            $dest_data = html_writer::tag('span', 
                                    $OUTPUT->render(new action_link($update_url, 
                                        $innards, null, $link_options)),
                                    array('class' => 'editbutton'));
                        }
                        if (!empty($instr_website)) {
                            if (strpos($instr_website, 'http://') === false 
                                    && strpos($instr_website, 'https://') === false) {
                                $instr_website = 'http://' . $instr_website;
                            }
                            $dest_data .= html_writer::link($instr_website, fullname($user));
                        } else {
                            $dest_data .= fullname($user);
                        }
                    } else {
                        $has_alt_email = !empty($office_info->email);
                        /* Determine if we should display the instructor's email:
                         * 2 -> Allow only other course members to see my email address
                         * 1 -> Allow everyone to see my email address
                         * 0 -> Hide my email address from everyone
                         */
                        $display_email = ($email_display == 2 && $enrolled_or_admin) || 
                                         ($email_display == 1) || 
                                         ($email_display == 0 && $has_alt_email);
                        // If there is an entry in the database
                        if ($office_info) {
                            if ($field == 'email' && $display_email) {
                                if (!$has_alt_email) {
                                    // If no email is specified, then use profile email
                                    $dest_data = $user->$field;
                                } else {
                                    // Otherwise, class specific email
                                    $dest_data = $office_info->email;
                                }
                            } else if ($field == 'office') {
                                $dest_data = $office_info->officelocation;
                            } else if ($field == 'phone') {
                                $dest_data = $office_info->phone;
                            } else if ($field == 'office_hours') {
                                $dest_data = $office_info->officehours;
                            }
                        } else {
                            if ($field == 'email' && $display_email) {
                                $dest_data = $user->$field;
                            }
                        }
                    }

                    $user_row[$field] = $dest_data;
                }
                $table->data[] = $user_row;
            }
            //END UCLA MOD: CCLE-2381

            $instr_info_table .= html_writer::table($table);
        }
        
        return $instr_info_table;
    }
}

?>
