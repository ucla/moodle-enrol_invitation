<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/moodleblock.class.php');
require_once($CFG->dirroot . '/course/lib.php');

class block_ucla_office_hours extends block_base {
    const DISPLAYKEY_PREG = '/([0-9]+[_])(.*)/';

    // This is a hack for displaying table-dependent header
    const TITLE_FLAG = '01__title__';

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

        $instructor_types = $CFG->instructor_levels_roles;

        $instr_info_table = '';
            
        $appended_info = self::blocks_office_hours_append($instructors, 
                $course, $context);

        list($table_headers, $ohinstructors) = 
            self::combine_blocks_office_hours($appended_info);

        // Optionally remove some instructors from display
        $block_filtered_users = self::blocks_office_hours_filter_instructors(
                $instructors, $course, $context
            );

        // Flatten out results
        $filtered_users = array();
        foreach ($block_filtered_users as $block => $filtered_user_keys) {
            foreach ($filtered_user_keys as $filtered_user_key) {
                $filtered_users[$filtered_user_key] = $filtered_user_key;
            }
        }

        // Filter and organize users here?
        foreach ($instructor_types as $title => $rolenames) {
            $type_table_headers = $table_headers;
            $goal_users = array();

            foreach ($instructors as $uk => $user) {
                if (in_array($uk, $filtered_users)) {
                    continue;
                }


                if (in_array($user->shortname, $rolenames)) {
                    $goal_users[$user->id] = $ohinstructors[$uk];
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
                    'boxalignleft generaltable cellborderless office-hours-table';

            $table->head = array();

            // Cleaning headers
            foreach ($type_table_headers as $field => $header) {
                $found = false;
                foreach ($goal_users as $user) {
                    if (!empty($user->{$field})) {
                        $found = true;
                        break;
                    }
                }

                if (!$found) {
                    unset($type_table_headers[$field]);
                }
            }

            // Determine which data to display.
            foreach ($goal_users as $user) {
                $user_row = array();

                foreach ($type_table_headers as $field => $header) {
                    $value = '';
                    if (isset($user->{$field})) {
                        $value = $user->{$field};
                    }
                    
                    // We need to attach attribute in order to make 
                    // this table responsive
                    $cell = new html_table_cell($value);
                    if ($header == self::TITLE_FLAG) {
                        $header = $title;
                    }
                    // Put in the header title in a special attribute
                    $cell->attributes['data-content'] = $header; 

                    $user_row[$field] = $cell;                    
                }

                $table->data[] = $user_row;
            }
            
            // use array_values, to remove array keys, which are 
            // mistaken as another css class for given column
            foreach ($type_table_headers as $table_header) {
                if ($table_header == self::TITLE_FLAG) {
                    $table_header = $title;
                }

                $table->head[] = $table_header;
            }

            $instr_info_table .= html_writer::table($table);
        }
        
        return $instr_info_table;
    }

    /**
     *  Turns a set of combined instructor informations and 
     *  detemines potential headers.
     **/
    static function combine_blocks_office_hours($appended_info) {
        $instructors = array();
        // append to $desired_info and delegate values
        foreach ($appended_info as $blockname => $instructor_data) {
            foreach ($instructor_data as $instkey => $instfields) {
                if (!isset($instructors[$instkey])) {
                    $instructors[$instkey] = new object();
                }

                foreach ($instfields as $field => $value) {
                    $fieldname = self::blocks_process_displaykey(
                            $field, $blockname
                        );
                    $stripfield = self::blocks_strip_displaykey_sort($field);

                    if (!isset($desired_info[$fieldname])) {
                        // Hack for titles
                        if ($field == self::TITLE_FLAG) {
                             $infoheader = self::TITLE_FLAG;
                        } else {
                            $infoheader = get_string(
                                $stripfield,
                                'block_' . $blockname
                            );
                        }

                        $desired_info[$fieldname] = $infoheader;
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

        return array($table_headers, $instructors);
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
                $blockresults[$blockname] = $blockres;
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
     *  @param Array(
     *      'instructors' => array the instructors that have been selected 
     *          to be in the office hours,
     *      'course'  => object the course,
     *      'context' => object the context
     *    )
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
            array(
                'instructors' => $instructors, 
                'course' => $course, 
                'context' => $context
            ));
    }

    /**
     *  Calculates the field in $instructor that is displayed per
     *  display field in a block. Blocks implementing 
     *  office_hours_append() should use this function.
     **/
    static function blocks_process_displaykey($displaykey, $blockname) {
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
     *  @param Array()
     *  @return Array(<key for $instructors>, ...)
     **/
    static function blocks_office_hours_filter_instructors($instructors,
                                                           $course, $context) {
        return self::all_blocks_method_results(
            'office_hours_filter_instructors',
            array(
                'instructors' => $instructors, 
                'course' => $course, 
                'context' => $context
            ));
    }

    function office_hours_append($params) {
        global $CFG, $OUTPUT, $PAGE, $USER;
        require_once($CFG->dirroot . '/mod/url/locallib.php');

        extract($params);

        $has_capability_edit_office_hours = has_capability(
                'block/ucla_office_hours:editothers', $context);
        $editing = $PAGE->user_is_editing();
        $editing_office_hours = $editing && $has_capability_edit_office_hours;
        
        // Determine if the user is enrolled in the course or is an admin
        // Assuming 'moodle/course:update' is a sufficient capability to 
        // to determine if a user is an admin or not
        $enrolled_or_admin = is_enrolled($context, $USER) 
                || has_capability('moodle/course:update', $context);
      
        $streditsummary     = get_string('editcoursetitle', 'format_ucla');
        $link_options = array('title' => get_string('editofficehours',
            'format_ucla'), 'class' => 'editing_instr_info');

        // The number is an informal sorting system.
        // Note the naming schema
        $fullname = '01_fullname';
        $defaultinfo = array(
            $fullname,
            '02_email',
            '03_officelocation',
            '04_officehours',
            '05_phone'
        );

        // Add another column for the "Update" link
        if ($editing_office_hours) {
            // This get_string should be blank
            $defaultinfo[] =  '00_update_icon';
        }

        $defaults = array();
        foreach ($defaultinfo as $defaultdata) {
            $defaults[self::blocks_strip_displaykey_sort($defaultdata)] = 
                $defaultdata;
        }

        // custom hack for fullname
        $defaults[self::blocks_strip_displaykey_sort($fullname)] = 
            self::TITLE_FLAG;

        // calculate invariants, and locally dependent data
        foreach ($instructors as $uk => $user) {
            // Name field
            $fullname = fullname($user);

            // Try be be lenient on URL, because Moodle doesn't enforce adding
            // http://.
            if (!empty($user->url) && !validateUrlSyntax($user->url, 's+')) {
                // See if it failed because is missing the http:// at the beginning.
                if (validateUrlSyntax('http://' . $user->url, 's+')) {
                    // It was.
                    $user->url = 'http://' . $user->url;
                }
            }

            if (!empty($user->url) && url_appears_valid_url($user->url)) {
                $fullname = html_writer::link(
                    new moodle_url($user->url),
                    $fullname,
                    array('target' => '_blank')
                );
            }

            $user->fullname = $fullname;
          
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
                    ),
                    array('class' => 'editbutton')
                );
            }
            
            // Determine if we should display the instructor's email:
            // 2 - Allow only other course members to see my email address
            // 1 - Allow everyone to see my email address
            // 0 - Hide my email address from everyone
            // * - always display email if an alterative was set
            $email_display = $user->maildisplay;
            $display_email = ($email_display == 2 && $enrolled_or_admin) || ($email_display == 1);
            if (!empty($user->officeemail)) {
                $user->email = $user->officeemail;
            } else if (!$display_email) {
                unset($user->email);
            }
        }

        $officehoursusers = array();
        foreach ($instructors as $uk => $user) {
            foreach ($user as $field => $value) {
                if (isset($defaults[$field])) {
                    $officehoursusers[$uk][$defaults[$field]] = $value;
                }
            }
        }

        return $officehoursusers;
    }
}
