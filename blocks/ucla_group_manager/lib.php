<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/group/lib.php');
require_once($CFG->dirroot . '/enrol/database/lib.php');
require_once($CFG->dirroot . '/blocks/ucla_group_manager/ucla_synced_group.class.php');
require_once($CFG->dirroot . '/blocks/ucla_group_manager/ucla_synced_grouping.class.php');

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

            echo "* " . make_idnumber($reqarr) . " has " . count($sections) 
                . " sections\n";
        
            // Check this roster against section rosters to look for
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
            echo "* $reqidnumber has " . count($indexedrequestroster) 
                . " students.\n";


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
                    echo "-   " . make_idnumber($termsrsarr) . ' has ' 
                        . count($sectionroster) . " students\n";
                }

                $sectioninfo->roster = $indexedsectionroster;
                $sectionrosters[make_idnumber($termsrsarr)] = $sectioninfo;
            }

            $reqobj->sectionsinfo = $sectionrosters;

            //*/
            $requestsectioninfos[make_idnumber($reqarr)] = $reqobj;
        }

        return $requestsectioninfos;
    }

    /**
     *  Fully sets up and synchronizes groups for a course.
     **/
    static function sync_course($courseid) {
        $reqsecinfos = self::course_sectionroster($courseid);
        if ($reqsecinfos === false) {
            echo "Not a real course $courseid!\n";
            return true;
        }

        $isnormalcourse = count($reqsecinfos) == 1;

        echo "Syncing section groups...";

        // groups created should NOT be divisible in any logical way, 
        // we should try to enforce usage of groupings
        foreach ($reqsecinfos as $termsrs => &$reqinfo) {
            // If there are no sections, then we want to do something later
            if (empty($reqinfo->sectionsinfo)) {
                if ($isnormalcourse) {
                    continue;
                }

                // Otherwise, we need to treat the crosslist as a section of
                // its own
                echo "crosslist-forced ";
                $fakesection = new object();

                $fakesection->roster = $reqinfo->roster;
                $fakesection->courseinfo = $reqinfo->courseinfo;

                $reqinfo->sectionsinfo = array($termsrs => $fakesection);
            }

            foreach ($reqinfo->sectionsinfo as $secttermsrs => &$sectioninfo) {
                $moodleusers = array();

                // TODO speed this loop up
                foreach ($sectioninfo->roster as $student) {
                    $moodleuser = ucla_registrar_user_to_moodle_user($student);

                    if ($moodleuser) {
                        $moodleusers[$moodleuser->id] = $moodleuser;
                    }
                }

                $sectiongroup = new ucla_synced_group(
                        $sectioninfo->courseinfo
                    );

                echo "[{$sectiongroup->id}] {$sectiongroup->name}";

                $sectiongroup->sync_members($moodleusers);
                $sectiongroup->save();

                $sectioninfo->group = $sectiongroup;

                echo "...";
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
        $alltrackedgroups = ucla_synced_group::get_tracked_groups(
                $courseid
            );

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
        // Groupings are not necessary for courses that are not crosslisted,
        // Just use the public private grouping for ALL students
        if ($isnormalcourse) {
            //return true;
        }

        // Set of all tracked groupids in course
        $alltrackedgroupids = array();
        foreach ($alltrackedgroups as $trackedgroup) {
            $alltrackedgroupids[] = $trackedgroup->groupid;
        }

        sort($alltrackedgroupids, SORT_NUMERIC);

        // All the tracked groupings, used when checking which existing
        // tracked groupings to delete
        $trackedgroupings = array();

        // Groupings
        $classmethods = get_class_methods('ucla_group_manager');

        $groupingspecfns = array();

        // Dyanmically figure out which groups to create
        foreach ($classmethods as $classmethod) {
            $matches = array();
            // Get the type, and the function name
            if (preg_match('/(.*)_name_fields$/', $classmethod, $matches)) {
                $groupingspecfns[$matches[1]] = $classmethod;
            }
        }

        foreach ($groupingspecfns as $groupingtype => $groupingtypefn) {
            $organizedgroupings = array();

            echo "Syncing groupings based on \"$groupingtype\"...";

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
                $groupingdata = ucla_synced_grouping::get_groupingdata(
                        $groupingname, $courseid
                    );

                // We want to avoid groupings that just has ALL groups
                // There is an unoptimization, can't simply compare
                // equality for an array of groups
                $groupids = array();
                foreach ($groups as $group) {
                    $groupids[] = $group->id;
                }

                sort($groupids, SORT_NUMERIC);
                if ($groupids == $alltrackedgroupids) {
                    echo "skip-all-group-grouping " 
                        . $groupingdata->name . '...';
                    continue;
                }

                $trackedgrouping = 
                    ucla_synced_grouping::create_tracked_grouping(
                            $groupingdata, $groups
                        );
               
                $sameflag = '';
                if (!isset($trackedgroupings[$trackedgrouping])) {
                    $trackedgroupings[$trackedgrouping] = true;
                } else {
                    $sameflag = 'repeat-';
                }

                echo '[' . $sameflag . $trackedgrouping . '] '
                    . $groupingdata->name . "...";

            }

            echo "done.\n";
        }

        // Remove no-longer used groupings
        echo "Deleting obsolete groupings...";
        $coursetrackedgroupings = 
                ucla_synced_grouping::get_course_tracked_groupings(
                        $courseid
                    );

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
}
