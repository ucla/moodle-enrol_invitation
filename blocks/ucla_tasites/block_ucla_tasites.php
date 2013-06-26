<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/moodleblock.class.php');
require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/course/lib.php');

/**
 *  Class that contains the library of function calls that control logic
 *  for TA-site functionality.
 **/
class block_ucla_tasites extends block_base {
    /**
     *  API call. Called when loading Moodle.
     **/
    function init() {
        $this->title = get_string('pluginname', 'block_ucla_tasites');
    }

    /**
     * Do not make this block available to add via "Add a block" dropdown.
     * 
     * @return array
     */
    public function applicable_formats() {
        return array(
            'site-index' => false,
            'course-view' => false,
            'my' => false,
            'not-really-applicable' => true
        );
    }

    /**
     *  Semantic (currying?) function.
     **/
    static function get_ta_admin_role_id() {
        return self::get_ta_role_id(true);
    }

    /**
     *  Gets the role id of one of the roles that are relevant.
     *  Cached using static.
     *
     *  @param $promoted Return the promoted role?
     **/
    static function get_ta_role_id($promoted=false) {
        global $CFG, $DB;
        static $roleids;

        $tarsn = self::get_ta_role_shortname($promoted);

        if (!isset($roleids[$tarsn])) {
            $roleids[$tarsn] = $DB->get_field('role', 'id', 
                array('shortname' => $tarsn));
        }

        return $roleids[$tarsn];
    }

    /**
     *  Gets the shortnames expected of the TA roles.
     **/
    static function get_ta_role_shortname($promoted=false) {
        $role_substr = $promoted ? '_admin' : '';
        $role_str = 'ta' . $role_substr;
        $var_field = $role_str . '_role_shortname';

        // Tarzan, but actually, TA role short-name
        $tarsn = isset($CFG->{$var_field}) 
            ? $CFG->{$var_field}
            : $role_str;

        return $tarsn; 
    }

    /**
     *  Checks if a particular user can have a TA-site.
     **/
    static function can_have_tasite($user, $courseid) {
        $tas = self::get_tasite_users($courseid);
        foreach ($tas as $ta) {
            // Check if I am one of the TAs, but this may NOT
            // be the correct way
            if ($ta->userid == $user->id) {
                return true;
            }
        }

        return false;
    }

    /**
     *  Checks if the TA-site can be made (does not exist).
     *  @param $context Optimization if you already have a context instance.
     **/
    static function can_make_tasite($user, $courseid) {
        // This might be a redundant check
        // Maybe throw an exception if !self::can_have_tasite()?
        return self::can_have_tasite($user, $courseid) 
                && !self::get_tasite($courseid, $user);
    }

    /**
     *  Gets all the enrol_meta instances associated that signifies
     *  that a course is a TA-site.
     **/
    static function get_tasite_enrolments($courseid) {
        global $DB;
        
        // Find all enrolments
        $enrols = $DB->get_records(
            'enrol', 
            array(
                'enrol' => 'meta',
                'customint1' => $courseid,
                'customint2' => self::get_ta_role_id(),
                'customint3' => self::get_ta_admin_role_id()
            ), 
            '',
            'customint4 as ownerid, '
                . 'courseid, '
                . 'customint1 as parentcourseid, '
                . 'customint2 as ta_roleid, '
                . 'customint3 as ta_admin_roleid'
        );

        return $enrols; 
    }

    /**
     *  Gets all the TA-sites that are associated with a course.
     *  Optimization, uses SQL-layer logic.
     *  @return Array enrol_meta instances, indexed-by customint4,
     *      with course field pointing to relevant {course} row
     **/
    static function get_tasites($courseid) {
        global $DB;

        $enrols = self::get_tasite_enrolments($courseid);

        // Find related courses
        $courseids = array();
        foreach ($enrols as $enrolkey => $enrol) {
            $courseids[$enrolkey] = $enrol->courseid;
        }

        $courses = $DB->get_records_list('course', 'id', $courseids);

        // match users to courses
        $tacourses = array();
        foreach ($enrols as $key => $enrol) {
            $course = $courses[$courseids[$key]];
            $course->enrol = $enrol;

            // get default grouping for each course
            $course->defaultgroupingname =
                    groups_get_grouping_name($course->defaultgroupingid);

            $tacourses[$enrol->ownerid] = $course;
        }
    
        return $tacourses;
    }

    /**
     *  Checks if there are any valid users that can have a TA-site.
     *  Cached using static.
     *
     *  @return Array of role_assignments for users that can have 
     *      TA-sites.
     **/
    static function get_tasite_users($courseid) {
        static $retrar;

        if (!isset($retrar[$courseid])) {
            // allow ta and ta-admins to have ta sites
            $role = new object();
            $context = context_course::instance($courseid);

            $role->id = self::get_ta_role_id();
            $tas = get_users_from_role_on_context($role, $context);

            $role->id = self::get_ta_admin_role_id();
            $taadmins = get_users_from_role_on_context($role, $context);

            // merge both roles
            $ta_users = $tas + $taadmins;

            // then remove any duplicated users
            $userids = array();
            foreach ($ta_users as $index => $ta_user) {
                if (in_array($ta_user->userid, $userids)) {
                    // exist already, so unset it
                    unset($ta_users[$index]);
                } else {
                    $userids[] = $ta_user->userid;
                }
            }
            $retrar[$courseid] = $ta_users;
        }

        return $retrar[$courseid];
    }
   
    /**
     *  Checks if the current user can have TA-sites.
     *  @throws 
     **/
    static function check_access($courseid) {
        return self::enabled() && self::can_access($courseid);
    }
   
    /**
     *  Checks if a user can create a specific or any TA-site.
     *  @throws
     **/
    static function can_access($courseid, $user=false) {
        global $USER;
        $user = $user ? $user : $USER;

        return self::can_have_tasite($user, $courseid)
            || has_capability('moodle/course:update', 
                    get_context_instance(CONTEXT_COURSE, $courseid), $user)
            || require_capability('moodle/site:config', 
                    get_context_instance(CONTEXT_SYSTEM), $user);
    }
  
    /**
     *  Semantic function that checks if there is any point in doing 
     *  anything.
     *  @throws block_ucla_tasites_exception 
     **/
    static function enabled() {
        return self::validate_enrol_meta() && self::validate_roles();
    }

    /**
     *  Checks that the roles have been correctly detected.
     *  @throws block_ucla_tasites_exception 
     **/
    static function validate_roles() {
        if (!self::get_ta_role_id()) {
            throw new block_ucla_tasites_exception('setuprole', 
                self::get_ta_role_shortname());
        }

        if (!self::get_ta_admin_role_id()) {
            throw new block_ucla_tasites_exception('setuprole', 
                self::get_ta_role_shortname(true));
        }

        return true;
    }

    /**
     *  Checks that enrol_meta is enabled, and then enables the plugin
     *  if possible. 
     *
     *  @throws block_ucla_tasites_exception
     **/
    static function validate_enrol_meta() {
        if (!enrol_is_enabled('meta')) {
            // Reference admin/enrol.php
            try {
                require_capability('moodle/site:config', 
                    get_context_instance(CONTEXT_SYSTEM));
            } catch (moodle_exception $e) {
                throw new block_ucla_tasites_exception('setupenrol');
            }
       
            $enabled = array_keys(enrol_get_plugins(true));
            $enabled[] = 'meta';
            set_config('enrol_plugins_enabled', implode(',', $enabled));

            $syscontext = context_system::instance();
            $syscontext->mark_dirty();
        }

        return true;
    }

    /**
     *  Checks if a site is a TA-site.
     *  Essentially distinguishes between one type of enrol_meta.
     *  A site is a TA-site if there is a specialized enrol_meta.
     **/
    static function is_tasite($courseid) {
        return is_object(self::get_tasite_enrol_meta_instance($courseid));
    }

    /**
     *  Returns the relevant enrollment entry that is related to
     *  the particular ta_site.
     **/
    static function get_tasite_enrol_meta_instance($courseid) {
        $instances = enrol_get_instances($courseid, true);

        $tasite_enrol = false;

        // Do a search?
        foreach ($instances as $instance) {
            if ($instance->enrol == 'meta') {
                // check to see if instance is a tasite enrol meta instance
                if (self::is_tasite_enrol_meta_instance($instance)) {
                    $tasite_enrol = $instance;
                    // Small convenience naming
                    $tasite_enrol->ownerid = $tasite_enrol->customint4;
                }
            }
        }

        return $tasite_enrol;
    }

    /**
     *  Checks if the instance of enrol_meta is for a TA-site.
     *  Maybe named is enrol meta instance for tasite?
     **/
    static function is_tasite_enrol_meta_instance($enrol) {
        // this can get called a lot from the meta sync
        static $cache_is_tasite;
        if (!empty($cache_is_tasite) || !isset($cache_is_tasite[$enrol->id])) {
            $result = true;
            if (empty($enrol->customint2)
                    || empty($enrol->customint3)
                    || empty($enrol->customint4)
                    || $enrol->customint2 != self::get_ta_role_id()
                    || $enrol->customint3 != self::get_ta_admin_role_id()) {
                $result = false;
            }
            $cache_is_tasite[$enrol->id] = $result;
        }
        return $cache_is_tasite[$enrol->id];
    }

    /**
     *  Used to keep track of naming schemas used in the form.
     **/
    static function checkbox_naming($tainfo) {
        return $tainfo->id . '-checkbox';
    }

    /**
     *  Used to keep track of naming schemas used in the form.
     **/
    static function action_naming($tainfo) {
        return $tainfo->id . '-action';
    }

    /**
     *  Creates a new course, assigns enrolments.
     **/
    static function create_tasite($tainfo) {
        $course = clone($tainfo->parent_course);

        $course->shortname = self::new_name($tainfo);
        
        $fullnamedata = new object();
        $fullnamedata->course_fullname = $course->fullname;
        // This is the fullname of the TA, sorry
        $fullnamedata->fullname = $tainfo->fullname;

        $course->fullname = get_string('tasitefor', 'block_ucla_tasites',
            $fullnamedata);

        // Hacks for public private
        unset($course->grouppublicprivate);
        unset($course->groupingpublicprivate);

        // remove course description, because it doesn't make sense for tasites
        unset($course->summary);

        // @throws
        $newcourse = create_course($course);

        self::set_site_indicator($newcourse);
    
        $course->id = $newcourse->id;

        // TODO move into function?
        $meta = new enrol_meta_plugin();
        $meta->add_instance($course, array(
            'customint1' => $tainfo->parent_course->id,
            'customint2' => self::get_ta_role_id(),
            'customint3' => self::get_ta_admin_role_id(),
            'customint4' => $tainfo->id
        ));

        $meta->course_updated(false, $course, null);

        return $newcourse;
    }

    /**
     *  Attempts to attach a site indicator.
     **/
    static function set_site_indicator($newcourse) {
        global $CFG;
        static $has_uclasiteindicator;

        if (!isset($has_uclasiteindicator)) {
            require_once($CFG->dirroot . '/lib/pluginlib.php');
            $pm = plugin_manager::instance();
            $plugins = $pm->get_plugins();
            $has_uclasiteindicator = isset(
                    $plugins['tool']['uclasiteindicator']
                ); 
        }

        if ($has_uclasiteindicator) {
            require_once($CFG->dirroot . '/' . $CFG->admin 
                    . '/tool/uclasiteindicator/lib.php');
            $sitetype = siteindicator_site::create($newcourse->id);
            $sitetype->set_type('tasite');
        }
    }

    /**
     *  Generates a shortname for the TA-site.
     **/
    static function new_name($tainfo, $usefirstname=false, $cascade=0) {
        global $DB;

        // would use calculate_course_names but that adds "Copy" to shortname
        $coursename = $tainfo->parent_course->shortname . '-' 
            . $tainfo->lastname;

        if ($usefirstname) {
            $coursename .= '-' . $tainfo->firstname;
        }

        if ($cascade) {
            // This will be epically confusing
            $coursename .= '-' . $cascade;
        }

        if ($DB->record_exists('course', array('shortname' => $coursename))) {
            if ($usefirstname) {
                $cascade++;
            }

            return self::new_name($tainfo, true, $cascade);
        }

        return strtoupper($coursename);
    }

    /**
     *  Hook API call for control panel.
     **/
    static function ucla_cp_hook($course, $context) {
        $courseid = $course->id;
        $cp_module = false;

        $accessible = false;

        try {
            $accessible = self::check_access($courseid) 
                && self::get_tasite_users($courseid)
                && !self::is_tasite($courseid);
        } catch (moodle_exception $e) {
            // aka do nothing
            $accessible = false;
        }

        if ($accessible) {
            $cp_module = array(
                array(
                    'item_name' => 'ucla_make_tasites',
                    'action' => new moodle_url(
                        '/blocks/ucla_tasites/index.php',
                        array(
                            'courseid' => $course->id
                        )
                    ),
                    'tags' => array('ucla_cp_mod_other')
                )
            );
        }

        return $cp_module;
    }

    /**
     *  Reply to hook. 
     *  If the course is a TA site, we only want to display the valid
     *  TA.
     **/
    function office_hours_filter_instructors($params) {
        $filtered = array();
        $course = $params['course'];
        $instructors = $params['instructors'];

        if (($tasite_enrol = self::get_tasite_enrol_meta_instance($course->id))
                && self::is_tasite_enrol_meta_instance($tasite_enrol)) {

            // Filter out all the people displayed in the office hours block
            // that is not the TA
            foreach ($instructors as $key => $instructor) {
                if ($tasite_enrol->ownerid != $instructor->id) {
                    $filtered[] = $key;
                }
            }
        }

        return $filtered;
    }

    function office_hours_append($params) {
        $instructors = $params['instructors'];
        $course = $params['course'];
        $tasites = self::get_tasites($course->id);

        $appended_instdata = array();

        if ($tasites) {
            $fieldname = block_ucla_office_hours::blocks_process_displaykey(
                'tasite', 'block_ucla_tasites'
            );
                
            foreach ($instructors as $ik => $instructor) {
                $iid = $instructor->id;
                if (isset($tasites[$iid])) {
                    $appended_instdata[$ik]['tasite'] = html_writer::link(
                        new moodle_url(
                            '/course/view.php',
                            array('id' => $tasites[$iid]->id)
                        ),
                        get_string('view_tasite', 'block_ucla_tasites')
                    );
                } else {
                    $appended_instdata[$ik]['tasite'] = '';
                }
            }
        }
        return $appended_instdata;
    }
}

class block_ucla_tasites_exception extends moodle_exception {
    function __construct($errorcode, $a=null) {
        parent::__construct($errorcode, 'block_ucla_tasites', '', $a);
    }
}
