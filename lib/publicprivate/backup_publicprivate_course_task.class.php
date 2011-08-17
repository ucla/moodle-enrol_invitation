<?php

include_once($CFG->dirroot.'/backup/moodle2/backup_course_task.class.php');
include_once($CFG->libdir.'/publicprivate/backup_publicprivate_stepslib.php');
include_once($CFG->libdir.'/publicprivate/course.class.php');
include_once($CFG->libdir.'/publicprivate/site.class.php');

/**
 * Backup_PublicPrivate_Course_Task
 *
 * Course backup task that extends Backup_Course_Task with an additional step
 * to define public/private metadata if the course is using public/private.
 *
 * @author ebollens
 * @version 20110719
 *
 * @uses Backup_Course_Task
 * @uses Backup_PublicPrivate_Course_Structure_Step
 */

class Backup_PublicPrivate_Course_Task extends Backup_Course_Task {

    /**
     * Overloaded build method from Backup_Course_Task that includes an
     * additional step to handle public/private if the course is using
     * public/private.
     *
     * @return void
     */
    public function build() {

        /**
         * Extend all the build functionality from Backup_Course_Task by
         * invoking the parent::build() method.
         */
        parent::build();

        /**
         * Return early if the site does not have public/private enabled.
         */
        if(!PublicPrivate_Site::is_enabled()) {
            return;
        }

        /**
         * Add a new step to handle public/private metadata.
         */
        $this->add_step(new Backup_PublicPrivate_Course_Structure_Step('publicprivate_course', 'publicprivate.xml'));

    }
}
