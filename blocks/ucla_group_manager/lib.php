<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/group/lib.php');
//require_once($CFG->dirroot . '/lib/enrollib.php');
require_once($CFG->dirroot . '/enrol/database/lib.php');
require_once($CFG->dirroot . '/blocks/ucla_group_manager/ucla_synced_group.class.php');

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

        echo "Creating groups...";
        // groups created should NOT be divisible in any logical way, 
        // we should try to enforce usage of groupings
        foreach ($reqsecinfos as $termsrs => &$reqinfo) {
            foreach ($reqinfo->sectionsinfo as $secttermsrs => &$sectioninfo) {
                $sectiongroup = new ucla_synced_group(
                        $sectioninfo->courseinfo
                    );
               
                $moodleusers = array();
                foreach ($sectioninfo->roster as $student) {
                    $moodleuser = ucla_registrar_user_to_moodle_user($student);
                    if ($moodleuser) {
                        $moodleusers[$moodleuser->id] = $moodleuser;
                    }
                }

                $sectiongroup->sync_members($moodleusers);
                $sectiongroup->save();

                $sectioninfo->group = $sectiongroup;
            }
        }

        echo "done.\n";

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

        $trackedgroupings = array();

        //   Groupings: per crosslist
        echo "Creating crosslist groupings...";
        foreach ($reqsecinfos as $termsrs => $reqinfo) {
            $reqgroups = array();

            // Compile all the request's section's groups together
            foreach ($reqinfo->sectionsinfo as $sectioninfo) {
                $reqgroups[] = $sectioninfo->group;
            }

            $groupingdata = self::get_groupingdata(
                    $reqinfo->courseinfo, 'crosslist'
                );

            $trackedgrouping = 
                self::create_tracked_grouping($groupingdata, $reqgroups);

            $trackedgroupings[$trackedgrouping] = $trackedgrouping;
        }

        echo "done.\n";

        //   Groupings: per act-type && number
        // First sort into correct groupings
        echo "Creating section groupings...";
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
            $trackedgrouping = self::create_tracked_grouping(
                    $secttypegrouping->groupingdata,
                    $secttypegrouping->groups
                );

            $trackedgroupings[$trackedgrouping] = $trackedgrouping;
        }

        $coursetrackedgroupings = self::get_course_tracked_groupings(
            $courseid);

        echo "done.\n";

        foreach ($coursetrackedgroupings as $ctg) {
            if (!isset($trackedgroupings[$ctg->id])) {
                groups_delete_grouping($ctg->id);
            }
        }
    }

    /**
     *  This is how we distinguish sections.
     **/
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
     *  @return int grouping.id
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

    /**
     *  Get tracked groupings from a course.
     **/
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

    /**
     *  Attempts to search through all course trackings, finding
     *  a matching grouping, based on groups assigned.
     **/
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
            $groupingsgroups = groups_get_all_groups($courseid,
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
}
