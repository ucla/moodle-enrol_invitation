<?php

include_once($CFG->dirroot.'/backup/moodle2/restore_course_task.class.php');
include_once($CFG->libdir.'/publicprivate/restore_publicprivate_stepslib.php');
include_once($CFG->libdir.'/publicprivate/course.class.php');
include_once($CFG->libdir.'/publicprivate/site.class.php');

/**
 * Restore_PublicPrivate_Course_Task
 *
 * Course restore task that extends Restore_Course_Task with an additional step
 * to handle public/private settings if they are included in the backup.
 *
 * @author ebollens
 * @version 20110719
 * 
 * @uses Restore_Course_Task
 * @uses Restore_PublicPrivate_Course_Structure_Step
 */

class Restore_PublicPrivate_Course_Task extends Restore_Course_Task {

    /**
     * Overloaded build method from Restore_Course_Task that includes an
     * additional step to handle public/private if public/private metadata is
     * included in the backup.
     *
     * @return void
     */
    public function build() {

        /**
         * Extend all the build functionality from Restore_Course_Task by
         * invoking the parent::build() method.
         */
        parent::build();

        /**
         * Return early if no public/private metadata to be processed.
         */
        if (!file_exists(rtrim($this->get_taskbasepath(), '/') . '/publicprivate.xml')) {
            return;
        }

        /**
         * Return early if the site does not have public/private enabled.
         */
        if(!PublicPrivate_Site::is_enabled()) {
            return;
        }

        /**
         * Add a new step to handle public/private metadata.
         */
        $this->add_step(new Restore_PublicPrivate_Course_Structure_Step('publicprivate_course', 'publicprivate.xml'));

    }
}
