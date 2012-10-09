<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/moodleblock.class.php');
require_once($CFG->dirroot . '/course/lib.php');

class block_ucla_office_hours extends block_base {
    const DISPLAYKEY_PREG = '/([0-9]*[_])(.*)/';
    const TITLE_FLAG = '__title__';

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
     * @param mixed     $course             Current course
     * @param mixed     $context            Course context
     * 
     * @return string HTML code
     */
    public static function render_office_hours_table($instructors, 
                                                     $course, $context) {
        global $DB, $OUTPUT, $PAGE, $USER, $CFG;

        require_once($CFG->dirroot . '/mod/url/locallib.php');

        $instructor_types = $CFG->instructor_levels_roles;
        
        $has_capability_edit_office_hours = has_capability(
                'block/ucla_office_hours:editothers', $context);
        $editing = $PAGE->user_is_editing();
        $editing_office_hours = $editing && $has_capability_edit_office_hours;

        $streditsummary     = get_string('editcoursetitle', 'format_ucla');
        $instr_info_table = '';
            
        // Determine if the user is enrolled in the course or is an admin
        // Assuming 'moodle/course:update' is a sufficient capability to 
        // to determine if a user is an admin or not
        $enrolled_or_admin = is_enrolled($context, $USER) 
                || has_capability('moodle/course:update', $context);
      
        // The number is an informal sorting system.
        // Note the naming schema
        // TODO put this in office_hours_append()
        $desired_info = array(
            '01_fullname' => self::TITLE_FLAG,
            '02_email' => get_string('email', 
                    'block_ucla_office_hours'),
            '03_officelocation' => get_string('office', 
                    'block_ucla_office_hours'),
            '04_officehours' => get_string('office_hours', 
                    'block_ucla_office_hours'),
            '05_phone' => get_string('phone', 
                    'block_ucla_office_hours'),
        );

        // Add another column for the "Update" link
        if ($editing_office_hours) {
            // This get_string should be blank
            $desired_info['00_update_icon'] =  
                get_string('update_', 'block_ucla_office_hours');
        }

        $appended_info = self::blocks_office_hours_append($instructors, 
                $course, $context);

        // append to $desired_info and delegate values
        foreach ($appended_info as $blockname => $instructor_data) {
            foreach ($instructor_info as $instkey => $instfields) {
                foreach ($instfields as $field => $value) {
                    $fieldname = self::blocks_process_displaykey(
                            $field, $blockname
                        );

                    if (!isset($desired_info[$fieldname])) {
                        $desired_info[$fieldname] = get_string($field,
                            'block_' . $blockname);
                    } else {
                        debugging('repeated info key ' . $fieldname);
                    }
                    
                    if (empty($instructors[$instkey])) {
                        debugging('got a custom office hours field'
                            . ' for non-existant instructor: ' . $instkey);
                    }
                    
                    $instructors[$instkey]->{
                            self::blocks_strip_displaykey_sort($fieldname)
                        } = $value;
                }
            }
        }
        
        ksort($desired_info);

        $table_headers = array();

        // Clean up the keys to match
        foreach ($desired_info as $dispkey => $dispval) {
            $stripdispkey = self::blocks_strip_displaykey_sort($dispkey);
            $table_headers[$stripdispkey] = $dispval;
        }

        // Optionally remove some instructors from display
        $filtered_users = self::blocks_office_hours_filter_instructors(
                $instructors, $course, $context
            );

        $strupdate = get_string('editofficehours', 'format_ucla');
        $streditsummary = get_string('editcoursetitle', 'format_ucla');
        $link_options = array('title' => $strupdate);

        // calculate invariants, and locally dependent data
        foreach ($instructors as $uk => $user) {
            // Name field
            $user->fullname = fullname($user);

            if (!empty($user->url) && url_appears_valid_url($user->url)) {
                $user->fullname = html_writer::link(
                    new moodle_url($user->url),
                    $user->fullname
                );
            }
          
            // Update button
            if ($editing_office_hours) {
                $user->update_icon = html_writer::tag(
                    'span',
                    $OUTPUT->render(
                        new action_link(
                            new moodle_url(
                                '/blocks/ucla_office_hours/officehours.php',
                                array(
                                    'courseid' => $course->id,
                                    'editid' => $user->id
                                )
                            ),
                            new pix_icon(
                                't/edit', 
                                $link_options['title'],
                                'moodle',
                                array(
                                    'class' => 'icon edit iconsmall',
                                    'alt' => $streditsummary
                                )
                            ),
                            null,
                            $link_options
                        )
                    )
                );
            }
            
            // Determine if we should display the instructor's email:
            // 2 - Allow only other course members to see my email address
            // 1 - Allow everyone to see my email address
            // 0 - Hide my email address from everyone
            // * - always display email if an alterative was set
            $email_display = $user->maildisplay;
            $display_email = ($email_display == 2 && $enrolled_or_admin) 
                || ($email_display == 1) 
                || $email_display == 0;
            if (!empty($user->officeemail)) {
                $user->email = $user->officeemail;
            } else if (!$display_email) {
                unset($user->email);
            }
        }

        // Filter and organize users here?
        foreach ($instructor_types as $title => $rolenames) {
            $goal_users = array();

            foreach ($instructors as $uk => $user) {
                if (in_array($uk, $filtered_users)) {
                    continue;
                }

                if (in_array($user->shortname, $rolenames)) {
                    $goal_users[$user->id] = $user;
                }
            }

            if (empty($goal_users)) {
                continue;
            }

            $table = new html_table();
            $table->width = '*';

            $cdi = count($table_headers);
            $aligns = array();
            for ($i = 0; $i < $cdi; $i++) {
                $aligns[] = 'left';
            }

            $table->align = $aligns;

            $table->attributes['class'] = 
                    'boxalignleft generaltable cellborderless';

            $table->head = array();

            // use array_values, to remove array keys, which are 
            // mistaken as another css class for given column
            foreach ($table_headers as $table_header) {
                if ($table_header == self::TITLE_FLAG) {
                    $table_header = $title;
                }

                $table->head[] = $table_header;
            }

            foreach ($goal_users as $user) {
                $user_row = array();

                foreach ($table_headers as $field => $header) {
                    $user_row[$field] = $user->{$field};
                }

                $table->data[] = $user_row;
            }

            $instr_info_table .= html_writer::table($table);
        }
        
        return $instr_info_table;
    }

    /**
     *  Gets the blocks to iterate for.
     **/
    static function load_blocks() {
        global $PAGE;

        return $PAGE->blocks->get_installed_blocks();
    }

    /**
     *  Maybe move this somewhere more useful?
     **/
    static function all_blocks_method_results($function, $param, 
                                              $filter=array()) {
        $blocks = self::load_blocks();
        $blockresults = array();

        foreach ($blocks as $block) {
            // http://en.wikipedia.org/wiki/Brock_%28Pok%C3%A9mon%29
            $blockname = $block->name;
            $blockres = @block_method_result($blockname, $function, $param);

            if ($blockres) {
                $blocksresults[$blockname] = $blockres;
            }
        }

        return $blockresults;
    }

    /**
     *  Polling Hook API.
     *  Calls block::office_hours_append()
     *  
     *  Allows blocks to specify arbitrary fields to add onto the display
     *      in section 0 of the course site.
     *  @param $instructors The instructors that have been selected to be
     *      in the office hours.
     *  @param $course The course
     *  @param $context The context
     *  @return Array(
     *      <key in $instructors> => array(
     *          <field name to be appended> => <value>,
     *          ...
     *      ),
     *      ...
     *  ); -- the field names will be computed over, and any unique
     *      entry with a field name will result in the field name displayed
     *      in the table header, while the other users without a value for
     *      said field will have no value for said field.
     *  NOTE: Use blocks_process_displaykey() to set 
     *      <field name to be appended>.
     *  NOTE: You can force sorting by APPENDING a 2-digit integer to the 
     *      name of the key.
     *      
     **/
    static function blocks_office_hours_append($instructors, $course, 
                                               $context) {
        return self::all_blocks_method_results('office_hours_append',
            array($instructors, $course, $context));
    }

    /**
     *  Calculates the field in $instructor that is displayed per
     *  display field in a block. Blocks implementing 
     *  office_hours_append() should use this function.
     **/
    static function blocks_process_displaykey($displaykey, $dislaykey) {
        return $displaykey . '_' . $blockname;
    }

    static function blocks_strip_displaykey_sort($displaykey) {
        $retval = $displaykey;
        if (preg_match(self::DISPLAYKEY_PREG, $displaykey)) {
            $retval = preg_replace(self::DISPLAYKEY_PREG, '$2', 
                    $displaykey);
        } 

        return $retval;
    }

    /**
     *  Polling hook API.
     *  Calls block::office_hours_filter_instructors()
     *
     *  Allows blocks to specify that a certain instructor should NOT
     *      be displayed in the office hours block of the course site.
     *  @param $instructors
     *  @param $course
     *  @param $context
     *  @return Array(<key for $instructors>, ...)
     **/
    static function blocks_office_hours_filter_instructors($instructors,
                                                           $course, $context) {
        return self::all_blocks_method_results(
            'office_hours_filter_instructors',
            array($instructors, $course, $context)
        );
    }
}
