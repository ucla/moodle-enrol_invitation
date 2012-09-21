<?php

defined('MOODLE_INTERNAL') || die();

class tool_subsites {
    static function check_requirements($context) {
        self::enabled() 
            ? self::can_access($context) 
            : self::throw_plugin_disabled();
    }

    static function throw_plugin_disabled() {
        throw new moodle_exception('plugin-disabled');
    }

    /**
     *  Convenience function used for checking access rights.
     *  @throws 
     **/
    static function can_access($context) {
        return has_capability('tool/subsites:canhavesubsite', $context)
            || has_capability('moodle/course:update', $context)
            || require_capability('moodle/site:doanything', CONTEXT_SYSTEM);
    }

    /**
     *  Semantic function that checks if there is any point in doing 
     *  anything.
     **/
    static function enabled() {
        return self::validate_enrol_meta();
    }

    /**
     *  Checks that enrol_meta is enabled, and then enables the plugin
     *  if possible. 
     *  @throws tool_subsites_exception()
     **/
    static function validate_enrol_meta() {
        if (!enrol_is_enabled('meta')) {
            // Reference admin/enrol.php
            try {
                require_capability('moodle/site:config', 
                    get_context_instance(CONTEXT_SYSTEM));
            } catch (moodle_exception $e) {
                return false;
            }
       
            $enabled = array_keys(enrol_get_plugins(true));
            $enabled[] = 'meta';
            set_config('enrol_plugins_enabled', implode(',', $enabled));

            $syscontext = context_system::instance();
            $syscontext->mark_dirty();
        }

        return true;
    }
}

class subsite {
    // id as per table course_subsite
    var $id;

    // The course data
    var $course;

    // This is a field for convience sake in commit()
    // DO NOT USE THIS FIELD
    var $courseid;

    // The courseid of the super-course
    var $supercourseid;

    // The userid of the sub-site-user
    var $userid;

    const T = 'course_subsite';

    const COURSE_FIELDS = 'id, shortname, fullname';
    const USER_FIELDS = 'id, firstname, lastname';

    static function const_fields_prefixed($fields, $prefix) {
        $constname = strtoupper($fields) . '_FIELDS';

        // Danger mouse
        eval('$constvalue = self::' . $constname . ';');

        return $prefix . implode($prefix, explode(' ', $constvalue));
    }

    /**
     *  Strangely prototypical function.
     **/
    function __construct($supercourse, $user) {
        $this->supercourseid = $supercourse->id;

        $subsite = clone($supercourse);
        unset($subsite->id);

        $this->userid = $user->id;

        // Try to load data
        if (!$this->load()) {
            $subsite = self::prepare($subsite, $user);
            $this->course = $subsite;
        }
    }

    /**
     *  Attempts to load a sub-site from the DB.
     *  In full.
     **/
    function load() {
        global $DB;

        $subsite = $DB->get_record(
            self::T, 
            array(
                'supercourseid' => $this->supercourseid,
                'userid' => $this->userid
            )
        );

        if ($subsite) {
            foreach ($subsite as $field => $val) {
                $this->{$field} = $val;
            }

            $this->course = $DB->get_record('course',
                array('id' => $this->courseid));
        }

        return $subsite;
    }

    /**
     *  Generates the things required for the field.
     **/
    static function prepare($supersite, $user) {
        $subsite = clone($supersite);
        // TODO
        $subsite->shortname = '';
        $subsite->idnumber = '';

        return $subsite;
    }

    /**
     *  Checks if the sub-site exists in the DB.
     **/
    function exists() {
        return !empty($this->course->id);
    }

    /**
     *  Creates a sub-site in the DB.
     **/
    function create() {
        $subsiteid = create_course($this->course);
        $this->commit();
        $this->course->id = $subsiteid;
    }

    /**
     *  Complete semantic save.
     **/
    function save() {
        // update_course
    }

    /**
     *  Saves data to non-core-Moodle-DBs.
     **/
    function commit() {
        global $DB;

        if (isset($this->id)) {
            $DB->update_record(self::T, $this);
        } else {
            $DB->insert_record(self::T, $this);
        }
    }
}
