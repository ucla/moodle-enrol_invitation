<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/group/lib.php');

// Extends imaginary "moodle_group"
class ucla_synced_group {
    var $sectioninfo;

    // This is the tracked memberships
    var $memberships = array();

    // Internal caching mechanism, used when deleting group enrolments
    var $membershipids = array();

    // These are the memberships that need to be removed when 
    var $removed_memberships = array();

    /**
     *  Creates a new tracked group.
     **/
    function __construct($sectioninfo, $autoload=true) {
        $this->term = $sectioninfo['term'];
        $this->srs = $sectioninfo['srs'];
        $this->courseid = $sectioninfo['courseid'];

        $this->sectioninfo = $sectioninfo;

        if ($autoload) {
            $this->load();
        }
    }

    function add_membership($moodleuserid, $membershipid=null) {
        if (!isset($this->memberships[$moodleuserid])) {
            $this->memberships[$moodleuserid] = $moodleuserid;
        }

        if ($membershipid !== null) {
            $this->membershipids[$moodleuserid] = $membershipid;
        }
    }

    function remove_membership($moodleuserid) {
        if (isset($this->memberships[$moodleuserid])) {
            unset($this->memberships[$moodleuserid]);
            $this->removed_memberships[$moodleuserid] = $moodleuserid;
        }
    }

    function sync_members($moodleusers) {
        foreach ($moodleusers as $moouid => $moodleuser) {
            $this->add_membership($moouid);
        }

        foreach ($this->memberships as $membership) {
            if (!isset($moodleusers[$membership])) {
                $this->remove_membership($membership);
            }
        }
    }

    // This probably could experience proper polymorphism
    function save() {
        global $DB;

        $retval = false;

        if (isset($this->id)) {
           $retval = $this->update();
        } else {
            $tsi = $this->sectioninfo;

            // Slightly not DRY
            $groupnamefields = array('subj_area', 'coursenum', 'lectacttype', 
                'lectnum', 'acttype', 'sectnum');
    
            $namestrs = array();
            foreach ($groupnamefields as $groupnamefield) {
                if (isset($tsi[$groupnamefield])) {
                    $namestrs[] = $tsi[$groupnamefield];
                }
            }

            $namestr = implode(' ', $namestrs);

            // The name of this won't change...
            $this->name = $namestr;

            $this->description = get_string('group_desc', 
                'block_ucla_group_manager', $tsi);

            $this->id = groups_create_group($this);

            $ucla_group_section = new object();
            $ucla_group_section->groupid = $this->id;
            $ucla_group_section->term = $this->term;
            $ucla_group_section->srs = $this->srs;

            $this->ucla_group_sections_id = $DB->insert_record(
                'ucla_group_sections', $ucla_group_section);

            $this->save_memberships();

            if ($this->id && $this->ucla_group_sections_id) {
                $retval = true;
            }
        }

        return $retval;
    }

    /**
     *  Synchronizes the group memberships with moodle groups.
     *  This function has to assume that $this->memberships is in its desired
     *  state, and will not change its value.
     **/
    function save_memberships() {
        // Remove memberships in the DB
        foreach ($this->removed_memberships as $moouid) {
            groups_remove_member($this->id, $moouid);

            if (isset($this->membershipids[$moouid])) {
                self::delete_membership($this->membershipids[$moouid]);
                unset($this->membershipids[$moouid]);
            }
        }
        
        $this->removed_memberships = array();

        // Create new memberships in the DB if needed
        foreach ($this->memberships as $moouid) {
            if (!isset($this->membershipids[$moouid])) {
                $this->membershipids[$moouid] = self::new_membership($moouid);
            }

            groups_add_member($this->id, $moouid);
        }
    }

    static function delete_membership($membershipid) {
        global $DB;

        $DB->delete_record('ucla_group_memberships',
            array('id' => $membershipid));
    }

    static function new_membership($groups_membersid) {
        global $DB;

        $tracker = new object();
        $tracker->groups_membersid = $groups_membersid;

        return $DB->insert_record('ucla_group_members', $tracker);
    }

    function create_membership($moodleuserid) {
        global $DB;

        $groupid = $this->id;
        groups_add_member($groupid, $moodleuserid);
   
        // since the above function returns nothing, have to go in and
        // find the group enrolment
        $groupmember = $DB->get_record('groups_members', 
            array('groupid' => $groupid, 'userid' => $moodleuserid));

        return self::new_membership($groupmember->id);
    }

    function update() {
        return groups_update_group($this) && $this->save_memberships();
    }

    function load() {
        global $DB;

        $params = array(
                'term' => $this->term,
                'srs' => $this->srs,
                'courseid' => $this->courseid
            );

        $dbsaved = $DB->get_record_sql('
            SELECT gr.*, ugs.id AS ucla_group_sections_id 
            FROM {groups} gr
            INNER JOIN {ucla_group_sections} ugs
                ON ugs.groupid = gr.id
            WHERE 
                    ugs.term = :term
                AND ugs.srs = :srs
                AND gr.courseid = :courseid
        ', $params);

        if (!$dbsaved) {
            return false;
        }

        foreach ($dbsaved as $field => $val) {
            $this->{$field} = $val;
        }
        
        $this->load_members();
    }
    
    /**
     *  Gets group memberships, but only those with data about ucla
     *  group membership tracking.
     **/
    static function get_tracked_memberships($groupid) {
        global $DB;

        return $DB->get_records_sql(
            'SELECT u.*, ugm.id AS ucla_tracked_id
             FROM {groups_members} gm 
             INNER JOIN {user} u
                ON u.id = gm.userid
             INNER JOIN {ucla_group_members} ugm
                ON ugm.groups_membersid = gm.id
             WHERE gm.groupid = ?', array($groupid)
        );
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

    function load_members() {
        if (!isset($this->id)) {
            return;
        }

        $groupmembers = self::get_tracked_memberships($this->id);

        foreach ($groupmembers as $groupmember) {
            $this->add_membership($groupmember->id, 
                $groupmember->ucla_tracked_id);
        }
    }
}
