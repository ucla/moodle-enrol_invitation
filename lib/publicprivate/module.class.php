<?php

include_once($CFG->libdir.'/publicprivate/module_exception.class.php');
include_once($CFG->libdir.'/publicprivate/course.class.php');
include_once($CFG->libdir.'/datalib.php');

/**
 * PublicPrivate_Module
 *
 * Object that represents a course module (course_modules tuple) in terms of
 * public/private, providing related accessors and mutators for checking
 * protections and enabling/disabling
 *
 * @uses PublicPrivate_Module_Exception
 * @uses $DB
 * @uses $CFG
 *
 * @todo PUBPRI - need to set enablegroupmembersonly $CFG var in install
 */

class PublicPrivate_Module
{
    /**
     * The key value for the record from the `course_modules` table.
     *
     * @var int
     */
    private $_course_module_id;

    /**
     * The represented record from the `course_modules` table.
     *
     * @var object
     */
    private $_course_module_obj = null;

    /**
     * The record for the course from `course` table as bounded to the module.
     *
     * @var PublicPrivate_Course
     */
    private $_publicprivate_course_obj = null;

    /**
     * Constructor for a PublicPrivate_Module object bound to $course_module.
     * 
     * @global Moodle_Database $DB
     * @param int|object|array $course_module
     */
    public function __construct($course_module)
    {
        global $DB;

        /**
         * If passed a scalar, only store the id. Record will be lazy
         * instantiated on first access throgh _course_module().
         */
        if(is_scalar($course_module))
        {
            $this->_course_module_id = (int)$course_module;
        }
        /**
         * If passed a record, store it as the represented record.
         */
        else
        {
            $this->_course_module_obj = is_object($course_module) ? $course_module : (object)$course_module;
            $this->_course_module_id = $this->_course_module_obj->id;
        }
    }

    /**
     * Returns a PublicPrivate_Module object for the provided $course_module.
     *
     * @param int|object|array $course
     * @return PublicPrivate_Module
     */
    public static function build($course_module)
    {
        return new PublicPrivate_Module($course_module);
    }

    /**
     * Returns true if the course module is visible to the public (guest).
     *
     * @throws PublicPrivate_Course_Exception
     * @throws PublicPrivate_Module_Exception
     * @return bool
     */
    public function is_public()
    {
        return coursemodule_visible_for_user($this->_course_module(), 1);
    }

    /**
     * Returns true if public/private is enabled on the course_module, meaning
     * that the grouping id set for it is the public/private grouping and that
     * it is visible to members only.
     *
     * @throws PublicPrivate_Course_Exception
     * @throws PublicPrivate_Module_Exception
     * @return bool
     */
    public function is_private()
    {
        return $this->get_grouping() > 0
                && $this->get_groupmembersonly() != 0
                && $this->_publicprivate_course()->is_grouping($this->_course_module()->groupingid);
    }

    /**
     * Returns true if there is some sort of visibility setting that forbids
     * guests from accessing the course_module.
     *
     * @throws PublicPrivate_Module_Exception
     * @return bool
     */
    public function is_protected()
    {
        return !$this->public();
    }

    /**
     * Enables public/private for the represented course_module by setting the
     * groupingid to the course's public/private grouping and setting the module
     * so that it can only be viewed by group members.
     *
     * @global Moodle_Database $DB
     * @link $CFG->enablegroupmembersonly
     * @throws PublicPrivate_Module_Exception
     */
    public function enable()
    {
        global $DB;
        
        try
        {
            $conditions = array('id'=>$this->get_id());
            $DB->set_field('course_modules', 'groupingid', $this->_publicprivate_course()->get_grouping(), $conditions);
            $DB->set_field('course_modules', 'groupmembersonly', 1, $conditions);
        }
        catch(DML_Exception $e)
        {
            throw new PublicPrivate_Module_Exception('Failed to set public/private visibility settings for module.', 300, $e);
        }
    }

    /**
     * Disables public/private for the represented course_module by setting the
     * groupingid to 0 and setting the module so that it can only be viewed by
     * anyone rather than just group members.
     *
     * @global Moodle_Database $DB
     * @throws PublicPrivate_Module_Exception
     */
    public function disable()
    {
        global $DB;
        
        try
        {
            $conditions = array('id'=>$this->get_id());
            $DB->set_field('course_modules', 'groupingid', 0, $conditions);
            $DB->set_field('course_modules', 'groupmembersonly', 0, $conditions);
        }
        catch(DML_Exception $e)
        {
            throw new PublicPrivate_Module_Exception('Failed to set public/private visibility settings for module.', 400, $e);
        }
    }

    /**
     * Returs `course_module`.`id` (the key).
     *
     * @return int
     */
    public function get_id()
    {
        return $this->_course_module_id;
    }

    /**
     * Returns `course_module`.`course` which corresponds to `course`.`id`.
     *
     * @throws PublicPrivate_Module_Exception
     * @return int
     */
    public function get_course()
    {
        return $this->_course_module()->course;
    }

    /**
     * Returns `course_module`.`groupingid`.
     *
     * @throws PublicPrivate_Module_Exception
     * @return int
     */
    public function get_grouping()
    {
        return $this->_course_module()->groupingid;
    }

    /**
     * Returns `course_module`.`groupmembersonly`.
     *
     * @throws PublicPrivate_Module_Exception
     * @return int
     */
    public function get_groupmembersonly()
    {
        return $this->_course_module()->groupmembersonly;
    }

    /**
     * Returns a PublicPrivate_Course object that is bounded to the record for
     * the course from `course` table as bounded to the module.
     *
     * @throws PublicPrivate_Course_Exception
     * @return PublicPrivate_Course
     */
    private function &_publicprivate_course()
    {
        /**
         * If object does not already have a cached version, build it.
         */
        if(!$this->_publicprivate_course_obj)
        {
            $this->_publicprivate_course_obj = new PublicPrivate_Course($this->get_course());
        }

        return $this->_publicprivate_course_obj;
    }

    /**
     * Returns the represented record from the `course_modules` table.
     *
     * @global Moodle_Database $DB
     * @throws PublicPrivate_Module_Exception
     * @return object
     */
    private function &_course_module()
    {
        global $DB;

        /**
         * If object does not already have a cached version, retrieve it.
         */
        if(!$this->_course_module_obj)
        {
            try
            {
                $this->_course_module_obj = $DB->get_record('course_modules', array('id'=>$this->_course_module_id), '*', MUST_EXIST);
            }
            catch(DML_Exception $e)
            {
                throw new PublicPrivate_Module_Exception('Failed to retrieve course module object.', 600, $e);
            }
        }

        return $this->_course_module_obj;
    }
}

?>
