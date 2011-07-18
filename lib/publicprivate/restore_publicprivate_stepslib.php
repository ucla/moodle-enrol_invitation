<?php

/**
 * Restore_PublicPrivate_Course_Structure_Step
 *
 * Course restore step that handles public/private metadata.
 *
 * @author ebollens
 *
 * @uses Restore_Structure_Step
 * @uses Restore_Path_Element
 * @uses $DB;
 */

class Restore_PublicPrivate_Course_Structure_Step extends Restore_Structure_Step {

    /**
     * Defines a structure of data within the <course> element.
     *
     * @return array
     */
    protected function define_structure() {

        $course = new Restore_Path_Element('course', '/course');
        return array($course);

    }

    /**
     * Parses course public/private data and sets public/private attributes.
     */
    public function process_course($data) {

        global $DB;

        if(intval($data['enable']) == 0)
            return;

        $courseid = $this->get_courseid();

        $course = $DB->get_record('course', array('id' => $courseid));
        $group = $DB->get_record('groups', array('courseid' => $courseid, 'name'=>$data['group_name']));
        $grouping = $DB->get_record('groupings', array('courseid' => $courseid, 'name'=>$data['grouping_name']));

        $course->enablepublicprivate = 1;
        $course->grouppublicprivate = $group->id;
        $course->groupingpublicprivate = $grouping->id;

        $DB->update_record('course', $course);

    }
}