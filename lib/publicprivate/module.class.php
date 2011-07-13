<?php

/** @todo need to set enablegroupmembersonly $CFG var in install */

include_once($CFG->libdir.'/publicprivate/module_exception.class.php');
include_once($CFG->libdir.'/publicprivate/course.class.php');
include_once($CFG->libdir.'/datalib.php');

class PublicPrivate_Module
{
    private $_course_module_id;
    private $_course_module_obj = null;
    private $_publicprivate_course_obj = null;

    public function __construct($course_module)
    {
        global $DB;

        if(is_scalar($course_module))
        {
            $this->_course_module_id = (int)$course_module;
        }
        else
        {
            $this->_course_module_obj = is_object($course_module) ? $course_module : (object)$course_module;
            $this->_course_module_id = $this->_course_module_obj->id;
        }
    }
    
    public static function build($course_module)
    {
        return new PublicPrivate_Module($course_module);
    }

    public function is_public()
    {
        return !$this->is_private();
    }

    public function is_private()
    {
        return $this->get_grouping() > 0
                && $this->get_groupmembersonly() != 0
                && $this->_publicprivate_course()->is_grouping($this->_course_module()->groupingid);
    }

    public function is_protected()
    {
        return !coursemodule_visible_for_user($this->_course_module(), 1);
    }

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

    public function get_id()
    {
        return $this->_course_module_id;
    }

    public function get_course()
    {
        return $this->_course_module()->course;
    }

    public function get_grouping()
    {
        return $this->_course_module()->groupingid;
    }

    public function get_groupmembersonly()
    {
        return $this->_course_module()->groupmembersonly;
    }

    private function &_publicprivate_course()
    {
        if(!$this->_publicprivate_course_obj)
        {
            try
            {
                $this->_publicprivate_course_obj = new PublicPrivate_Course($this->get_course());
            }
            catch(PublicPrivate_Course_Exception $e)
            {
                throw new PublicPrivate_Module_Exception('Failed to build public/private course object.', 500, $e);
            }
        }

        return $this->_publicprivate_course_obj;
    }

    private function &_course_module()
    {
        global $DB;
        
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
