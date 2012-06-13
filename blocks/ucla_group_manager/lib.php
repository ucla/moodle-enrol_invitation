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
    // These are what are used to distinguish grouping names

    // Hierarchical groupings
    // This is at the level of the request, per request
    static function crosslist_name_fields() {
        return 'subj_area coursenum acttype sectnum';
    }

    // This is at the level of the request
    // for same course number, but can have more than one lecture
    static function course_lecture_name_fields() {  
        return 'subj_area coursenum acttype';
    }

    // Bi-lateral groupings
    // This is at the level of the request's section
    static function section_type_name_fields() {
        return 'acttype sectnum';
    }

    // This is at the level of the request's section
    // This is for each section, super explicit
    static function name_section_fields() {
        return 'subj_area coursenum acttype sectnum';
    }

    /**
     *  Fetches section enrollments for a particular course set.
     *  @param $courseid int
     **/
    static function course_sectionroster($courseid) {
        global $PAGE;

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
                $section['subj_area'] = $reqarr['subj_area'];
                // This is the discussion section info
                $section['acttype'] = $section['cls_act_typ_cd'];
                $section['sectnum'] = ltrim($section['sect_no'], '0');
                // This is the lecture section info
                $section['lectnum'] = ltrim($reqarr['sectnum'], '0');
                $section['lectacttype'] = $reqarr['acttype'];
                // This is the course number
                $section['coursenum'] = ltrim($reqarr['coursenum'], '0');

                $sectioninfo = new object();
                $sectioninfo->courseinfo = $section;

                $termsrsarr = $section;

                $rqa = array(array(
                        $termsrsarr['term'], $termsrsarr['srs']
                    ));

                $sectionroster = 
                    registrar_query::run_registrar_query(
                        'ccle_roster_class', $rqa, true);

                $indexedsectionroster = array();
                foreach ($sectionroster as $student) {
                    $indexedsectionroster[] = 
                        $enrol->translate_ccle_roster_class($student);
                }
                
                if ($debug) {
                    echo "    " . make_idnumber($termsrsarr) . ' has ' 
                        . count($sectionroster) . " students\n";
                }

                $sectioninfo->roster = $indexedsectionroster;
                $sectionrosters[make_idnumber($termsrsarr)] = $sectioninfo;
            }

            $reqobj->sectionsinfo = $sectionrosters;

            // TODO check this roster against section rosters to look for
            // stragglers
            $requestroster = 
                registrar_query::run_registrar_query(
                        'ccle_roster_class', 
                        array(array($reqarr['term'], $reqarr['srs'])), 
                        true
                    );

            $indexedrequestroster = array();
            foreach ($requestroster as $student) {
                $indexedrequestroster[] = 
                    $enrol->translate_ccle_roster_class($student);
            }

            $reqobj->roster = $indexedrequestroster;
            $reqidnumber = make_idnumber($reqarr);
            echo "    $reqidnumber has " . count($indexedrequestroster) 
                . " students.\n";

            $requestsectioninfos[make_idnumber($reqarr)] = $reqobj;
        }

        return $requestsectioninfos;
    }

    /**
     *  Fully sets up and synchronizes groups for a course.
     **/
    static function sync_course($courseid) {
        $reqsecinfos = self::course_sectionroster($courseid);

        echo "Syncing groups...";
        // groups created should NOT be divisible in any logical way, 
        // we should try to enforce usage of groupings
        foreach ($reqsecinfos as $termsrs => &$reqinfo) {
            // If there are no sections, then we want to create
            // a group per course...
            if (empty($reqinfo->sectionsinfo)) {
                $reqinfo->sectionsinfo[] = $reqinfo;
            }

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

                echo "[{$sectiongroup->id}] {$sectiongroup->name}...";
            }
        }
        
        // When we hit that next foreach, if this is not unset, for some
        // reason PHP decides to set the address that $reqinfo is pointing
        // to to be the first object of the iterating array
        unset($reqinfo);
        unset($sectioninfo);

        echo "done.\n";

        echo "Deleting obsolete groups...";
        // Delete unused groups
        $alltrackedgroups = self::get_tracked_groups($courseid);
        // Re-index this...optimization
        $existingtrackedgroups = array();
        foreach ($alltrackedgroups as $trackedgroup) {
            $existingtrackedgroups[$trackedgroup->groupid] = $trackedgroup;
        }

        foreach ($reqsecinfos as $reqinfo) {
            foreach ($reqinfo->sectionsinfo as $sectioninfo) {
                $secgroupid = $sectioninfo->group->id;
                if (isset($existingtrackedgroups[$secgroupid])) {
                    unset($existingtrackedgroups[$secgroupid]);
                } else {
                    echo "ERROR: Could not find recently created group!";
                }
            }
        }

        foreach ($existingtrackedgroups as $nolongertrackedgroup) {
            $delgroupid = $nolongertrackedgroup->groupid;
            groups_delete_group($delgroupid);
            echo "[$delgroupid] {$nolongertrackedgroup->name}...";
        }

        echo "done.\n";

        // Create groupings
        // Groupings are not necessary for courses that are not crosslisted
        if (count($reqsecinfos) == 1) {
            return true;
        }

        $trackedgroupings = array();

        // Groupings
        $classmethods = get_class_methods('ucla_group_manager');

        $groupingspecfns = array();

        foreach ($classmethods as $classmethod) {
            $matches = array();
            // Get the type, and the function name
            if (preg_match('/(.*)_name_fields$/', $classmethod, $matches)) {
                $groupingspecfns[$matches[1]] = $classmethod;
            }
        }

        foreach ($groupingspecfns as $groupingtype => $groupingtypefn) {
            $organizedgroupings = array();

            echo "Syncing groupings based on $groupingtype...";

            foreach ($reqsecinfos as $reqinfo) {
                foreach ($reqinfo->sectionsinfo as $sectioninfo) {
                    $groupfieldsid = self::get_grouping_type_key(
                            $groupingtypefn, $sectioninfo->courseinfo
                        );

                    if (!isset($organizedgroupings[$groupfieldsid])) {
                        $organizedgroupings[$groupfieldsid] = array();
                    }

                    $organizedgroupings[$groupfieldsid][] = $sectioninfo->group;
                }
            }

            foreach ($organizedgroupings as $groupingname => $groups) {
                $groupingdata = self::get_groupingdata(
                        $groupingname, $courseid
                    );

                $trackedgrouping = self::create_tracked_grouping(
                        $groupingdata, $groups
                    );

                $trackedgroupings[$trackedgrouping] = true;

                echo '[' . $trackedgrouping . '] '
                    . $groupingdata->name . "...";

            }

            echo "done.\n";
        }


        // Remove no-longer used groupings
        echo "Deleting obsolete groupings...";
        $coursetrackedgroupings = self::get_course_tracked_groupings(
            $courseid);

        foreach ($coursetrackedgroupings as $ctg) {
            if (!isset($trackedgroupings[$ctg->id])) {
                groups_delete_grouping($ctg->id);
                echo "[{$ctg->id}] {$ctg->name}...";

            }
        }

        echo "done.\n";

        return true;
    }

    /**
     *  Gets all the tracked group info.
     **/
    static function get_tracked_groups($courseid) {
        global $DB;

        return $DB->get_records_sql('
            SELECT ugs.*, g.name, g.courseid
            FROM {ucla_group_sections} ugs
            INNER JOIN {groups} g ON g.id = ugs.groupid
            WHERE g.courseid = ?
        ', array($courseid));
    }

    /**
     *  This is how we distinguish sections.
     **/
    static function get_grouping_type_key($fn, $info) {
        $specstr = self::$fn();

        $fieldsused = explode(' ', self::$fn());
        
        $namestrs = array();
        foreach ($fieldsused as $field) {
            if (isset($info[$field])) {
                $namestrs[] = $info[$field];
            }
        }

        return implode(' ', $namestrs);
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
     *  Simple wrapper.
     **/
    static function get_groupingdata($name, $courseid) {
        $groupingdata = new object();
        $groupingdata->name = $name;
        $groupingdata->courseid = $courseid;

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
     *  TODO make faster
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
