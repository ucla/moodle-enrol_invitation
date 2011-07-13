<?php

include_once('course_exception.class.php');

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
    private $_course = null;

    /**
     * Constructor for a PublicPrivate_Course object bound to $course.
     *
     * @param int|object $course Integer course id or course record object
     * @throws PublicPrivate_Course_Exception [100|101]
     */
    public function __construct($course)
    {
        global $DB;

        if(is_scalar($course))
        {
            try
            {
                $this->_course = $DB->get_record('course', array('id'=>$id), '*', MUST_EXIST);
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

        if(!isset($this->_course->grouppublicprivate) || !isset($this->_course->groupautoassign))
        {
            throw new PublicPrivate_Course_Exception('Required course properties not available for __construct.', 101);
        }
    }

    /**
     * Returns a PublicPrivate_Course object for the provided $course.
     *
     * @param int|object $course Integer courseid or course record object
     * @throws PublicPrivate_Course_Exception [100|101]
     */
    public static function build($course)
    {
        return new PublicPrivate_Course($course);
    }

    /**
     * The course object that this instance refers to.
     *
     * @return object Course record object this object refers to
     */
    public function get_course()
    {
        return $this->_course;
    }

    /**
     * Activates public/private for a course that does not already have public/
     * private enabled.
     *
     * @throws PublicPrivate_Course_Exception [200-208]
     */
    public function activate()
    {
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
        
        if($groupid = groups_get_group_by_name($courseid, get_string('publicprivategroupname')))
        {
            $data = groups_get_group($groupid);
            if(!groups_get_group_by_name($courseid,  $data->name . ' ' . get_string('publicprivategroupdeprecated')))
            {
                $data->name = $data->name . ' ' . get_string('publicprivategroupdeprecated');
            }
            else
            {
                for($i = 1;
                    groups_get_group_by_name($courseid,  $data->name . ' ' . get_string('autoassigndeprecatedgroup') . ' [' . $i . ']');
                    $i++);
                $data->name = $data->name . ' ' . get_string('autoassigndeprecatedgroup') . ' [' . $i . ']';
            }
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

        /*
         * Change name of an existing grouping with name get_string('publicprivategroupingname')
         */

        if($groupingid = groups_get_grouping_by_name($courseid, get_string('publicprivategroupingname')))
        {
            $data = groups_get_group($groupingid);
            if(!groups_get_grouping_by_name($courseid,  $data->name . ' ' . get_string('publicprivategroupingdeprecated')))
            {
                $data->name = $data->name . ' ' . get_string('publicprivategroupingdeprecated');
            }
            else
            {
                for($i = 1;
                    groups_get_grouping_by_name($courseid,  $data->name . ' ' . get_string('publicprivategroupingdeprecated') . ' [' . $i . ']');
                    $i++);
                $data->name = $data->name . ' ' . get_string('publicprivategroupingdeprecated') . ' [' . $i . ']';
            }
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

        /*
         * Create new publicprivategroupname group and publicprivategroupingname grouping.
         */

        $data = new object();
        $data->courseid = $courseid;
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
        $data->courseid = $courseid;
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

        $this->_course->grouppublicprivate = $newgroupid;
        $this->_course->groupingpublicprivate = $newgroupingid;
        $this->_course->guest = 1;

        try
        {
            if(!update_course($this->_course))
            {
                throw new PublicPrivate_Course_Exception('Failed to update course settings for public/private.', 206);
            }
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
            set_field('course_modules', 'groupingid', $newgroupingid, 'course', $this->_course->id, 'groupmembersonly', 0, 'groupingid', 0);
            set_field('course_modules', 'groupmembersonly', 1, 'course', $this->_course->id, 'groupmembersonly', 0, 'groupingid', 0);
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
     * @throws PublicPrivate_Course_Exception [300-303]
     */
    public function deactivate()
    {
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
        $this->_course->grouppublicprivate = 0;
        $this->_course->groupingpublicprivate = 0;

        try
        {
            if(!update_course($this->_course))
            {
                throw new PublicPrivate_Course_Exception('Failed to update course settings to disable public/private.', 301);
            }
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
            set_field('course_modules', 'groupingid', 0, 'course', $this->_course->id, 'groupmembersonly', 0, 'groupingid', $this->_course->groupingpublicprivate);
            set_field('course_modules', 'groupmembersonly', 0, 'course', $this->_course->id, 'groupmembersonly', 0, 'groupingid', $this->_course->groupingpublicprivate);
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
     * @global object $DB
     * @global object $CFG
     * @throws PublicPrivate_Course_Exception [400-401]
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
                                    WHERE groupid = '.$this->_course->grouppublicprivate.');');
        }
        catch(DML_Exception $e)
        {
            try
            {
                $sql = 'SELECT DISTINCT ra.userid
                        FROM '.$CFG->prefix.'role_assignments ra
                        WHERE ra.contextid = '.$context->id.'
                            AND ra.userid NOT IN (
                                    SELECT DISTINCT userid
                                    FROM '.$CFG->prefix.'groups_members
                                    WHERE groupid = '.$this->_course->grouppublicprivate.');';
                $rs = get_recordset_sql($sql);
                
                while($row = rs_fetch_next_record($rs))
                {
                    $member = new object();
                    $member->groupid = $this->_course->grouppublicprivate;
                    $member->userid = $row->userid;
                    $member->timeadded = time();
                    if(!insert_record('groups_members', $member))
                    {
                        throw new PublicPrivate_Course_Exception('Failed to add users with an explicit assignment to public/private group.', 401);
                    }
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
     * @global object $DB
     * @global object $CFG
     * @throws PublicPrivate_Course_Exception [500-502]
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
                    : is_object($user) && isset($user->id)
                        ? $user->id
                        : is_array($user) && isset($user['id'])
                            ? $user['id']
                            : false;

        if(!$userid)
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
            if(!insert_record('groups_members', $member))
            {
                throw new PublicPrivate_Course_Exception('Failed to add user to public/private group.', 502);
            }
        }
        catch(DML_Exception $e)
        {
            throw new PublicPrivate_Course_Exception('Failed to add user to public/private group.', 502, $e);
        }
    }

    /**
     * Remove user from the public/private group.
     *
     * @param int|object $user Integer user id or user record object
     * @throws PublicPrivate_Course_Exception [600-602]
     */
    public function remove_user($user)
    {
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
                    : is_object($user) && isset($user->id)
                        ? $user->id
                        : is_array($user) && isset($user['id'])
                            ? $user['id']
                            : false;

        if(!$userid)
        {
            throw new PublicPrivate_Course_Exception('Required user properties not available to remove user to public/private group.', 601);
        }

        /*
         * Delete rows from groups_members for userid in public/private group.
         */

        try
        {
            if(!delete_records('groups_members', 'groupid', $this->_course->grouppublicprivate, 'userid', $userid))
            {
                throw new PublicPrivate_Course_Exception('Failed to add user to public/private group.', 602);
            }
        }
        catch(DML_Exception $e)
        {
            throw new PublicPrivate_Course_Exception('Failed to add user to public/private group.', 602, $e);
        }
    }

    /**
     * Check if the user is currently a member of public/private group.
     *
     * @param int|object $user Integer user id or user record object
     * @return boolean true if user is a member or false if not
     * @throws PublicPrivate_Course_Exception [700-702]
     */
    public function is_member($user)
    {
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
                    : is_object($user) && isset($user->id)
                        ? $user->id
                        : is_array($user) && isset($user['id'])
                            ? $user['id']
                            : false;

        if(!$userid)
        {
            throw new PublicPrivate_Course_Exception('Required user properties not available to remove user to public/private group.', 701);
        }

        /*
         * Return boolean on if record exists.
         */

        try
        {
            return record_exists('groups_members', 'groupid', $this->_course->grouppublicprivate, 'userid', $userid);
        }
        catch(DML_Exception $e)
        {
            throw new PublicPrivate_Course_Exception('Failed to check if user is in public/private group.', 702, $e);
        }
    }
}

?>
