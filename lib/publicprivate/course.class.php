<?php

include_once($CFG->libdir.'/publicprivate/course_exception.class.php');
include_once($CFG->dirroot.'/group/lib.php');

/**
 * PublicPrivate_Course
 *
 * Object that represents a course in terms of public/private, providing related
 * accessors and mutators for enabling/disabling public/private, adding and
 * removing users from the public/private group, and checking if a user is a
 * member of the public/private group.
 *
 * @uses PublicPrivate_Course_Exception
 * @uses $DB
 * @uses $CFG
 */

class PublicPrivate_Course
{
    /**
     * The represented record from the `course` table.
     *
     * @var object
     */
    private $_course = null;

    /**
     * Constructor for a PublicPrivate_Course object bound to $course.
     *
     * @param int|object|array $course
     * @throws PublicPrivate_Course_Exception
     */
    public function __construct($course)
    {
        global $DB;

        if(is_scalar($course))
        {
            try
            {
                $this->_course = $DB->get_record('course', array('id'=>$course), '*', MUST_EXIST);
            }
            catch(DML_Exception $e)
            {
                throw new PublicPrivate_Course_Exception('Database query failed for __construct.', 100, $e);
            }
        }
        else if(is_array($course))
        {
            $this->_course = (object)$course;
        }
        else
        {
            $this->_course = $course;
        }

        if(!isset($this->_course->enablepublicprivate) || !isset($this->_course->grouppublicprivate) || !isset($this->_course->groupingpublicprivate))
        {
            throw new PublicPrivate_Course_Exception('Required course properties not available for __construct.', 101);
        }
    }

    /**
     * Returns a PublicPrivate_Course object for the provided $course.
     *
     * @param int|object|array $course
     * @throws PublicPrivate_Course_Exception
     * @return PublicPrivate_Course
     */
    public static function build($course)
    {
        return new PublicPrivate_Course($course);
    }

    /**
     * Get the id of the course that this object is bound to.
     *
     * @return int
     */
    public function get_course()
    {
        return $this->_course->id;
    }

    /**
     * Get the id of the course public/private group or false if not active.
     *
     * @return int
     */
    public function get_group()
    {
        return $this->is_activated() ? $this->_course->grouppublicprivate : false;
    }

    /**
     * Passed a group id or either a group record object or array, returns true
     * if the group is the same as the course's public/private group or false
     * otherwise.
     *
     * @param int|object|array $group
     * @return boolean
     */
    public function is_group($group)
    {
        $groupid = is_scalar($group)
                    ? $group
                    : (is_object($group) && isset($group->id)
                        ? $group->id
                        : (is_array($group) && isset($group['id'])
                            ? $group['id']
                            : false));

        $cgroupid = $this->get_group();

        return $cgroupid !== false && $groupid !== false && $cgroupid == $groupid;
    }

    /**
     * Get the id of the course public/private group or false if not active.
     *
     * @return int
     */
    public function get_grouping()
    {
        return $this->is_activated() ? $this->_course->groupingpublicprivate : false;
    }

    /**
     * Passed a grouping id or either a grouping record object or array, returns
     * true if the grouping is the same as the course's public/private grouping
     * or false otherwise.
     *
     * @param int|object|array $group
     * @return boolean
     */
    public function is_grouping($grouping)
    {
        $groupingid = is_scalar($grouping)
                    ? $grouping
                    : (is_object($grouping) && isset($grouping->id)
                        ? $grouping->id
                        : (is_array($grouping) && isset($grouping['id'])
                            ? $grouping['id']
                            : false));

        $cgroupingid = $this->get_grouping();

        return $cgroupingid !== false && $groupingid !== false && $cgroupingid == $groupingid;
    }

    /**
     * Activates public/private for a course that does not already have public/
     * private enabled.
     *
     * @global Moodle_Database $DB
     * @throws PublicPrivate_Course_Exception
     */
    public function activate()
    {
        global $DB;

        /*
         * Cannot activate if already activated.
         */

        if($this->is_activated())
        {
            throw new PublicPrivate_Course_Exception('Illegal action trying to activate public/private where already active.', 200);
        }

        /*
         * Change name of an existing group with name get_string('publicprivategroupname')
         */
        
        if($groupid = groups_get_group_by_name($this->_course->id, get_string('publicprivategroupname')))
        {
            $data = groups_get_group($groupid);
            if(!groups_get_group_by_name($this->_course->id,  $data->name . ' ' . get_string('publicprivategroupdeprecated')))
            {
                $data->name = $data->name . ' ' . get_string('publicprivategroupdeprecated');
            }
            else
            {
                for($i = 1;
                    groups_get_group_by_name($this->_course->id,  $data->name . ' ' . get_string('autoassigndeprecatedgroup') . ' [' . $i . ']');
                    $i++);
                $data->name = $data->name . ' ' . get_string('autoassigndeprecatedgroup') . ' [' . $i . ']';
            }
            
            try
            {
                if(!groups_update_group($data))
                {
                    throw new PublicPrivate_Course_Exception('Failed to move existing group with required group name.', 201);
                }
            }
            catch(DML_Exception $e)
            {
                throw new PublicPrivate_Course_Exception('Failed to move existing group with required group name.', 201, $e);
            }
        }

        /*
         * Change name of an existing grouping with name get_string('publicprivategroupingname')
         */

        if($groupingid = groups_get_grouping_by_name($this->_course->id, get_string('publicprivategroupingname')))
        {
            $data = groups_get_group($groupingid);
            if(!groups_get_grouping_by_name($this->_course->id,  $data->name . ' ' . get_string('publicprivategroupingdeprecated')))
            {
                $data->name = $data->name . ' ' . get_string('publicprivategroupingdeprecated');
            }
            else
            {
                for($i = 1;
                    groups_get_grouping_by_name($this->_course->id,  $data->name . ' ' . get_string('publicprivategroupingdeprecated') . ' [' . $i . ']');
                    $i++);
                $data->name = $data->name . ' ' . get_string('publicprivategroupingdeprecated') . ' [' . $i . ']';
            }

            try
            {
                if(!groups_update_grouping($data))
                {
                    throw new PublicPrivate_Course_Exception('Failed to move existing grouping with required group name.', 202);
                }
            }
            catch(DML_Exception $e)
            {
                throw new PublicPrivate_Course_Exception('Failed to move existing grouping with required group name.', 202, $e);
            }
        }

        /*
         * Create new publicprivategroupname group and publicprivategroupingname grouping.
         */

        $data = new object();
        $data->courseid = $this->_course->id;
        $data->name = get_string('publicprivategroupname');
        $data->description = get_string('publicprivategroupdescription');

        try
        {
            if(!$newgroupid = groups_create_group($data))
            {
                throw new PublicPrivate_Course_Exception('Failed to create public/private group.', 203);
            }
        }
        catch(DML_Exception $e)
        {
            throw new PublicPrivate_Course_Exception('Failed to create public/private group.', 203, $e);
        }

        /*
         * Create new publicprivategroupingname grouping.
         */

        $data = new object();
        $data->courseid = $this->_course->id;
        $data->name = get_string('publicprivategroupingname');
        $data->description = get_string('publicprivategroupingdescription');

        try
        {
            if(!$newgroupingid = groups_create_grouping($data))
            {
                throw new PublicPrivate_Course_Exception('Failed to create public/private grouping.', 204);
            }
        }
        catch(DML_Exception $e)
        {
            throw new PublicPrivate_Course_Exception('Failed to create public/private grouping.', 204, $e);
        }

        /*
         * Bind public/private group to grouping.
         */

        try
        {
            if(!groups_assign_grouping($newgroupingid, $newgroupid))
            {
                throw new PublicPrivate_Course_Exception('Failed to bind public/private group to grouping.', 205);
            }
        }
        catch(DML_Exception $e)
        {
            throw new PublicPrivate_Course_Exception('Failed to bind public/private group to grouping.', 205, $e);
        }

        /*
         * Update course settings for public/private.
         */

        $this->_course->enablepublicprivate = 1;
        $this->_course->grouppublicprivate = $newgroupid;
        $this->_course->groupingpublicprivate = $newgroupingid;
        $this->_course->guest = 1;

        try
        {
            update_course($this->_course);
        }
        catch(DML_Exception $e)
        {
            throw new PublicPrivate_Course_Exception('Failed to update course settings for public/private.', 206, $e);
        }

        /*
         * Set all 'public' course modules private initially.
         */

        try
        {
            $conditions = array('course'=>$this->_course->id, 'groupmembersonly'=>0, 'groupingid'=>0);
            $DB->set_field('course_modules', 'groupingid', $newgroupingid, $conditions);
            $DB->set_field('course_modules', 'groupmembersonly', 1, $conditions);
        }
        catch(DML_Exception $e)
        {
            throw new PublicPrivate_Course_Exception('Failed to set public modules private on activation.', 207, $e);
        }

        /*
         * Add enrolled users to public/private group.
         */

        try
        {
            $this->add_enrolled_users();
        }
        catch(PublicPrivate_Course_Exception $e)
        {
            throw new PublicPrivate_Course_Exception('Failed to add enrolled users to public/private group.', 208, $e);
        }
    }

    /**
     * Deactivates public/private for a course that has public/private enabled.
     *
     * @global Moodle_Database $DB
     * @throws PublicPrivate_Course_Exception
     */
    public function deactivate()
    {
        global $DB;

        /*
         * Cannot deactivate if not activated.
         */

        if(!$this->is_activated())
        {
            throw new PublicPrivate_Course_Exception('Illegal action trying to deactivate public/private where not active.', 300);
        }

        /*
         * Update course to no longer have an public/private group or grouping setting.
         */

        $oldgrouppublicprivate = $this->_course->grouppublicprivate;
        $oldgroupingpublicprivate = $this->_course->groupingpublicprivate;
        $this->_course->enablepublicprivate = 0;
        $this->_course->grouppublicprivate = 0;
        $this->_course->groupingpublicprivate = 0;

        try
        {
            update_course($this->_course);
        }
        catch(DML_Exception $e)
        {
            throw new PublicPrivate_Course_Exception('Failed to update course settings to disable public/private.', 301, $e);
        }

        /*
         * Unset public/private module visibilities.
         */

        try
        {
            $conditions = array('course'=>$this->_course->id, 'groupmembersonly'=>1, 'groupingid'=>$this->_course->groupingpublicprivate);
            $DB->set_field('course_modules', 'groupingid', 0, $conditions);
            $DB->set_field('course_modules', 'groupmembersonly', 0, $conditions);
        }
        catch(DML_Exception $e)
        {
            throw new PublicPrivate_Course_Exception('Failed to unset public/private module visibilities.', 302, $e);
        }

        /*
         * Delete public/private group and grouping.
         */

        try
        {
            groups_delete_group($oldgrouppublicprivate);
            groups_delete_grouping($oldgroupingpublicprivate);
        }
        catch(DML_Exception $e)
        {
            throw new PublicPrivate_Course_Exception('Failed to delete public/private group and grouping.', 303, $e);
        }
    }

    /**
     * Returns true for a course that has public/private enabled.
     *
     * @return boolean
     */
    public function is_activated()
    {
        return $this->_course->grouppublicprivate > 0;
    }

    /**
     * Adds all users with an explicit role assignment in the course to the
     * public/private group if they're not already in the public/private
     * group.
     *
     * @global object $CFG
     * @global Moodle_Database $DB
     * @throws PublicPrivate_Course_Exception
     */
    public function add_enrolled_users()
    {
        global $DB, $CFG;
        
        /*
         * Cannot add enrolled if public/private not activated.
         */

        if(!$this->is_activated())
        {
            throw new PublicPrivate_Course_Exception('Illegal action trying to add enrolled where public/private is not active.', 400);
        }

        $context = get_context_instance(CONTEXT_COURSE, $this->_course->id);

        /*
         * Add users with an explicit assignment to public/private group.
         *
         * Attempts to do this with INSERT...SELECT statement. If this is not
         * a supported query type, then takes O(N) to add all members 1-by-1.
         */
        try
        {
            $DB->execute('INSERT IGNORE INTO '.$CFG->prefix.'groups_members
                            SELECT DISTINCT '.$this->_course->grouppublicprivate.' AS groupid, ra.userid AS userid, '.time().' AS timeadded
                            FROM '.$CFG->prefix.'role_assignments ra
                            WHERE ra.contextid = '.$context->id.'
                                AND ra.userid NOT IN (
                                    SELECT DISTINCT userid 
                                    FROM '.$CFG->prefix.'groups_members
                                    WHERE groupid = '.$this->_course->grouppublicprivate.')');
        }
        catch(DML_Exception $e)
        {
            try
            {
                $rs = $DB->get_records('role_assignments', array('contextid'=>$context->id));

                $seen = array();
                foreach($rs as $row)
                {
                    if(isset($seen[$row->id]))
                    {
                        continue;
                    }

                    $seen[$row->id] = true;

                    $member = new object();
                    $member->groupid = $this->_course->grouppublicprivate;
                    $member->userid = $row->userid;
                    $member->timeadded = time();
                    
                    $DB->insert_record('groups_members', $member);
                }
            }
            catch(DML_Exception $e)
            {
                throw new PublicPrivate_Course_Exception('Failed to add users with an explicit assignment to public/private group.', 401, $e);
            }
        }
    }

    /**
     * Add user to the public/private group if they're not already in the
     * public/private group.
     *
     * @global object $CFG
     * @global Moodle_Database $DB
     * @throws PublicPrivate_Course_Exception
     */
    public function add_user($user)
    {
        global $DB, $CFG;

        /*
         * Cannot add enrolled if public/private not activated.
         */

        if(!$this->is_activated())
        {
            throw new PublicPrivate_Course_Exception('Illegal action trying to add user to course where public/private is not active.', 500);
        }

        /*
         * Parse $user parameter as scalar, object or array, or else throw exception.
         */

        $userid = is_scalar($user)
                    ? $user
                    : (is_object($user) && isset($user->id)
                        ? $user->id
                        : (is_array($user) && isset($user['id'])
                            ? $user['id']
                            : false));

        if($userid === false)
        {
            throw new PublicPrivate_Course_Exception('Required user properties not available for add user to public/private group.', 501);
        }

        /*
         * Return before adding if user is already a member of the group.
         */

        if($this->is_member($userid))
        {
            return;
        }
        
        /*
         * Add row to groups_members for userid in public/private group.
         */

        try
        {
            $member = new object();
            $member->groupid = $this->_course->grouppublicprivate;
            $member->userid = $userid;
            $member->timeadded = time();
            $DB->insert_record('groups_members', $member);
        }
        catch(DML_Exception $e)
        {
            throw new PublicPrivate_Course_Exception('Failed to add user to public/private group.', 502, $e);
        }
    }

    /**
     * Remove user from the public/private group.
     *
     * @param int|object $user
     * @throws PublicPrivate_Course_Exception
     */
    public function remove_user($user)
    {
        global $DB;

        /*
         * Cannot add enrolled if public/private not activated.
         */

        if(!$this->is_activated())
        {
            throw new PublicPrivate_Course_Exception('Illegal action trying to remove user where public/private is not active.', 600);
        }

        /*
         * Parse $user parameter as scalar, object or array, or else throw exception.
         */

        $userid = is_scalar($user)
                    ? $user
                    : (is_object($user) && isset($user->id)
                        ? $user->id
                        : (is_array($user) && isset($user['id'])
                            ? $user['id']
                            : false));

        if($userid === false)
        {
            throw new PublicPrivate_Course_Exception('Required user properties not available to remove user to public/private group.', 601);
        }

        /*
         * Delete rows from groups_members for userid in public/private group.
         */

        try
        {
            $DB->delete_records('groups_members', array('groupid'=>$this->_course->grouppublicprivate, 'userid'=>$userid));
        }
        catch(DML_Exception $e)
        {
            throw new PublicPrivate_Course_Exception('Failed to add user to public/private group.', 602, $e);
        }
    }

    /**
     * Check if the user is currently a member of public/private group.
     *
     * @param int|object $user
     * @return boolean
     * @throws PublicPrivate_Course_Exception
     */
    public function is_member($user)
    {
        global $DB;

        /*
         * Cannot add enrolled if public/private not activated.
         */

        if(!$this->is_activated())
        {
            throw new PublicPrivate_Course_Exception('Illegal action trying to check if user is in public/private group where public/private is not active.', 700);
        }

        /*
         * Parse $user parameter as scalar, object or array, or else throw exception.
         */

        $userid = is_scalar($user)
                    ? $user
                    : (is_object($user) && isset($user->id)
                        ? $user->id
                        : (is_array($user) && isset($user['id'])
                            ? $user['id']
                            : false));

        if($userid === false)
        {
            throw new PublicPrivate_Course_Exception('Required user properties not available to remove user to public/private group.', 701);
        }

        /*
         * Return boolean on if record exists.
         */

        try
        {
            return $DB->record_exists('groups_members', array('groupid'=>$this->_course->grouppublicprivate, 'userid'=>$userid));
        }
        catch(DML_Exception $e)
        {
            throw new PublicPrivate_Course_Exception('Failed to check if user is in public/private group.', 702, $e);
        }
    }
}

?>
