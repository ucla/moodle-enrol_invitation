<?php

/**
 * Backup_PublicPrivate_Course_Structure_Step
 *
 * Course backup step that handles public/private metadata.
 *
 * @author ebollens
 *
 * @uses Backup_Structure_Step
 * @uses Backup_Nested_Element
 * @uses $DB;
 */

class Backup_PublicPrivate_Course_Structure_Step extends Backup_Structure_Step {

    /**
     * Creates an XML file with elements:
     * 
     *  <course>
     *    <enable></enable>
     *    <group_name></group_name>
     *    <grouping_name</groupingname>
     *  </course>
     *
     * @return Backup_Nested_Element
     */
    protected function define_structure() {

        global $DB;

        $ele = new Backup_Nested_Element('course', array(), array('enable', 'group_name', 'grouping_name'));

        $course = $DB->get_record('course', array('id' => $this->task->get_courseid()));
        $group = $DB->get_record('groups', array('id'=>$course->grouppublicprivate));
        $grouping = $DB->get_record('groupings', array('id'=>$course->groupingpublicprivate));

        $rec = new stdClass();
        $rec->enable = $course->enablepublicprivate;
        $rec->group_name = $group->name;
        $rec->grouping_name = $grouping->name;

        $ele->set_source_array(array($rec));

        return $ele;
    }
}