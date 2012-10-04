<?php

defined('MOODLE_INTERNAL') || die();

/**
 *  This is not really a class, just a namespace for a collection of
 *  static functions.
 **/
class ucla_synced_grouping {
    
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

        // Could use self::groups_equals, but this is optimized
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
