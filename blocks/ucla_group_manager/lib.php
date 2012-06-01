<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/group/lib.php');
//require_once($CFG->dirroot . '/lib/enrollib.php');
require_once($CFG->dirroot . '/enrol/database/lib.php');
ucla_require_registrar();

/**
 *  
 **/
class ucla_group_manager {
    /**
     *  Fetches section enrollments for a particular course set.
     *  @param $courseid int
     **/
    static function course_sectionroster($courseid) {
        $requests = ucla_get_course_info($courseid);
        $debug = debugging();

        if (!$requests) {
            return false;
        }

        $requests_arr = array_map('get_object_vars', $requests);

        if ($debug) {
            echo "  course $courseid maps to " . count($requests) 
                . " request(s)\n";
        }
    
        $requestsectioninfos = array();

        // get the information needed
        // TODO extract this out and make it available globally
        $enrol = enrol_get_plugin('database');

        foreach ($requests_arr as $reqarr) {
            $sections = registrar_query::run_registrar_query(
                'ccle_class_sections', array($reqarr), true);

            if ($debug) {
                echo "  " . make_idnumber($reqarr) . " has " . count($sections) 
                    . " sections\n";
            }

            $reqobj = new stdclass();
            $reqarr['courseid'] = $courseid;
            $reqobj->courseinfo = $reqarr;

            $sectionrosters = array();
            foreach ($sections as $section) {
                // So some translating since the SP names fields strangely
                // Maybe we should have a consistent-course object?
                $section['courseid'] = $courseid;
                $section['term'] = $reqarr['term'];
                $section['srs'] = $section['srs_crs_no'];
                $section['acttype'] = $section['cls_act_typ_cd'];
                $section['subj_area'] = $reqarr['subj_area'];
                $section['sectnum'] = ltrim($section['sect_no'], '0');

                $sectioninfo = new object();
                $sectioninfo->courseinfo = $section;

                $termsrsarr = $section;

                $rqa = array(array(
                        $termsrsarr['term'], $termsrsarr['srs']
                    ));

                $sectionroster = 
                    registrar_query::run_registrar_query(
                        'ccle_roster_class', $rqa, true);
                 
                if ($debug) {
                    echo "    " . make_idnumber($termsrsarr) . ' has ' 
                        . count($sectionroster) . " students\n";
                }

                $indexedsectionroster = array();
                foreach ($sectionroster as $student) {
                    $indexedsectionroster[] = 
                        $enrol->translate_ccle_roster_class($student);
                }

                $sectioninfo->roster = $indexedsectionroster;
                $sectionrosters[make_idnumber($termsrsarr)] = $sectioninfo;
            }

            $reqobj->sectionsinfo = $sectionrosters;

            $requestsectioninfos[make_idnumber($reqarr)] = $reqobj;
        }

        return $requestsectioninfos;
    }

    /**
     *  Fully sets up and synchronizes groups for a course.
     **/
    static function sync_course($courseid) {
        $reqsecinfos = self::course_sectionroster($courseid);

        // should NOT be divisible in any logical way, we should try to enforce
        // usage of groupings
        foreach ($reqsecinfos as $termsrs => &$reqinfo) {
            $groups = array();
            foreach ($reqinfo->sectionsinfo as $secttermsrs => &$sectioninfo) {
                $sectioninfo->group = self::create_tracked_group(
                        $sectioninfo
                    );
            }
        }

        // When we hit that next foreach, if this is not unset, for some
        // reason PHP decides to set the address that $reqinfo is pointing
        // to to be the first object of the iterating array
        unset($reqinfo);
        unset($sectioninfo);

        // Create groupings
        // Groupings are not necessary for courses that are not crosslisted?
        if (count($reqsecinfos) == 1) {
            return true;
        }

        //   Groupings: per crosslist
        foreach ($reqsecinfos as $termsrs => $reqinfo) {
            $reqgroups = array();

            // Compile all the request's section's groups together
            foreach ($reqinfo->sectionsinfo as $sectioninfo) {
                $reqgroups[] = $sectioninfo->group;
            }

            $groupingdata = self::get_groupingdata(
                    $reqinfo->courseinfo, 'crosslist'
                );

            self::create_tracked_grouping($groupingdata, $reqgroups);
        }

        //   Groupings: per act-type && number
        // First sort into correct groupings
        $secttypegroupings = array();
        foreach ($reqsecinfos as $termsrs => $reqinfo) {
            foreach ($reqinfo->sectionsinfo as $secttermsrs => $sectioninfo) {
                $skey = self::get_section_type_key($sectioninfo->courseinfo);

                if (!isset($secttypegroupings[$skey])) {
                    $secttypegrouping = new object();
                    $secttypegrouping->groupingdata = self::get_groupingdata(
                            $sectioninfo->courseinfo, 'section_type'
                        );

                    $secttypegrouping->groups = array();
                    $secttypegroupings[$skey] = $secttypegrouping;
                }

                $secttypegroupings[$skey]->groups[$secttermsrs] = 
                    $sectioninfo->group;
            }    
        }

        // Make groupings and assign groups
        $secttypegroupingids = array();
        foreach ($secttypegroupings as $skey => $secttypegrouping) {
            self::create_tracked_grouping($secttypegrouping->groupingdata,
                $secttypegrouping->groups);
        }

        //return $users;
    }

    /**
     *  Creates a tracked group and populates it.
     *  @param $sectioninfo
     *      object with fields
     *          courseinfo => registrar data for making the group
     *          roster     => registrar data for users, object with field:
     *              moodleuserinfo => Moodle user info (optional) for speed
     **/
    static function create_tracked_group($sectioninfo) {
        $group = self::make_tracked_group($sectioninfo->courseinfo);

        // TODO clean up
        foreach ($sectioninfo->roster as $k => $roster) {
            if (!isset($roster['moodleuserinfo'])) {
                $roster['moodleuserinfo'] = self::match_registrar_user($roster);
                $sectioninfo->roster[$k] = $roster;
            }
        }

        $moodleusers = array();
        foreach ($sectioninfo->roster as $roster) {
            if (!empty($roster['moodleuserinfo'])) {
                $moodleuser = $roster['moodleuserinfo'];
                $moodleusers[$moodleuser->id] = $moodleuser;
            }
        }

        self::groups_sync_members($group, $moodleusers);

        return $group;
    }

    static function get_section_type_key($sectioninfo) {
        return get_string('grouping_section_type_name', 
            'block_ucla_group_manager', $sectioninfo);
    }

    /**
     *  Convenience function, adds many groups to a grouping.
     **/
    static function groups_many_assign_grouping($groupingid, $groups) {
        foreach ($groups as $group) {
            if (!isset($group->id)) {
                // TODO Handle less-gracefully
                continue;
            }

            groups_assign_grouping($groupingid, $group->id);
        }
    }

    static function groups_sync_members($group, $groupusers) {
        // Determine which users to CUT...
        // Actually don't bother, enrolments take care of that
        //$currentusers = groups_get_members($group->id);
        //$userenrols = self::get_users_enrolments_in_course($group->courseid);

        return groups_add_members($group, $groupusers);
    }

    /**
     *  Convenience function, adds many members to a group.
     **/
    static function groups_add_members($group, $users) {
        $results = array();
        foreach ($users as $key => $moodleuser) {
            // Make it an object to save a pointless dbq
            $results[$key] = groups_add_member($group, (object)$moodleuser);
        }

        return $results;
    }

    /**
     *  Takes the result of the stored procedure ccle_class_sections and some
     *  with which it creates a moodle group, which will be kept track of
     *  as an auto-sync group.
     **/
    static function make_tracked_group($reginfo) {
        $group = new object();

        $exists = self::get_tracked_group($reginfo);
        if ($exists) {
            return $exists;
        }

        // TODO standardize procedure for making name?
        $group->name = get_string('group_name', 'block_ucla_group_manager',
            $reginfo);
        
        $group->description = get_string('group_desc', 
            'block_ucla_group_manager', $reginfo);

        // TODO Is this useful?
        $group->enrolmentkey = '';

        $group->courseid = $reginfo['courseid'];

        // TODO is there are policy?
        $group->hidepicture = 0;

        $group->id = groups_create_group($group);
        
        // TODO add some mechanism to know that this group is speshul
        self::track_new_group($group, $reginfo);

        return $group;
    }

    /**
     *  Checks if a particular section has an associated group.
     **/
    static function get_tracked_group($reginfo) {
        global $DB;

        $params = array(
                'term' => $reginfo['term'],
                'srs' => $reginfo['srs'],
                'courseid' => $reginfo['courseid']
            );

        return $DB->get_record_sql('
            SELECT gr.*
            FROM {groups} gr
            INNER JOIN {ucla_group_sections} ugs
                ON ugs.groupid = gr.id
            WHERE 
                    ugs.term = :term
                AND ugs.srs = :srs
                AND gr.courseid = :courseid
        ', $params);
    }

    /**
     *  Adds a newly tracked group into the db. Does not check for existing,
     *  relies on the caller checking first.
     **/
    static function track_new_group($group, $reginfo) {
        global $DB;

        $dbobj = new object();
        $dbobj->groupid = $group->id;
        $dbobj->term = $reginfo['term'];
        $dbobj->srs = $reginfo['srs'];
        $DB->insert_record('ucla_group_sections', $dbobj);
    }

    /** 
     *  Preps data for creating a grouping based on crosslist information.
     **/
    static function get_groupingdata($info, $type) {
        $groupingdata = new object();
        $groupingdata->name = get_string('grouping_' . $type . '_name',
            'block_ucla_group_manager', $info);
        $groupingdata->name = get_string('grouping_' . $type . '_name',
            'block_ucla_group_manager', $info);
        $groupingdata->courseid = $info['courseid'];
        return $groupingdata;
    }

    /**
     *  Convenience function to create a tracked grouping, and then assigns
     *  a bunch of groups to it.
     **/
    static function create_tracked_grouping($groupingdata, $groups=array()) {
        $exists = self::get_tracked_grouping($groupingdata, $groups);
        if ($exists) {
            return $exists->id;
        }

        $groupingid = groups_create_grouping($groupingdata);
        self::track_new_grouping($groupingid);

        if (!empty($groups)) {
            self::groups_many_assign_grouping($groupingid, $groups);
        }

        return $groupingid;
    }

    static function get_course_tracked_groupings($courseid) {
        global $DB;
        return $DB->get_records_sql(
                'SELECT gg.* 
                 FROM {groupings} gg
                 INNER JOIN {ucla_group_groupings} ugg
                    ON ugg.groupingid = gg.id
                 WHERE courseid = ?', 
                 array($courseid)
             );
    }

    static function get_tracked_grouping($groupingdata, $groups) {
        $courseid = $groupingdata->courseid;

        $trackedcoursegroupings =
            self::get_course_tracked_groupings($courseid);

        if (empty($trackedcoursegroupings)) {
            return false;
        }

        $neededgroupids = array();
        foreach ($groups as $gkey => $group) {
            $neededgroupids[] = $group->id;
        }

        sort($neededgroupids, SORT_NUMERIC);
    
        foreach ($trackedcoursegroupings as $grouping) {
            $groupingsgroups = groups_get_all_groups($groupingdata->courseid,
                null, $grouping->id);
            $providedgroupids = array(); 

            foreach($groupingsgroups as $group) {
                $providedgroupids[] = $group->id;
            }

            sort($providedgroupids, SORT_NUMERIC);
            if ($providedgroupids == $neededgroupids) {
                return $grouping;
            }
        }

        return false;
    }

    /**
     *  Creates a tracked grouping. Does not check for existance.
     **/
    static function track_new_grouping($groupingid) {
        global $DB;

        $dbobj = new object();
        $dbobj->groupingid = $groupingid;

        $DB->insert_record('ucla_group_groupings', $dbobj);
    }

    protected static function match_registrar_user($reguser) {
        return ucla_registrar_user_to_moodle_user($reguser);
    }

    static function get_users_enrolments_in_course($courseid) {
        global $DB;

        $userenrols = $DB->get_records_sql('
                SELECT ue.id, ue.userid, e.enrol
                FROM {user_enrolments} ue
                INNER JOIN {enrol} e ON ue.enrolid = e.id
                WHERE e.courseid = ?
            ', array($courseid));

        $enrolsperuser = array();
        foreach ($userenrols as $userenrol) {
            $userid = $userenrol->userid;
            if (!isset($enrolsperuser[$userid])) {
                $enrolsperuser[$userid] = array();
            }

            $enrolname = $userenrol->enrol;

            $enrolsperuser[$userid][$enrolname] = $enrolname;
        }

        return $enrolsperuser;
    }
}
