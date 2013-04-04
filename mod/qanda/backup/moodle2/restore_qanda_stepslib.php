<?php

/**
 * @package    mod_qanda
 * @copyright 2013 UC Regents
 */
/**
 * Define all the restore steps that will be used by the restore_qanda_activity_task
 */

/**
 * Structure step to restore one qanda activity
 */
class restore_qanda_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('qanda', '/activity/qanda');
        $paths[] = new restore_path_element('qanda_category', '/activity/qanda/categories/category');
        if ($userinfo) {
            $paths[] = new restore_path_element('qanda_entry', '/activity/qanda/entries/entry');
            $paths[] = new restore_path_element('qanda_alias', '/activity/qanda/entries/entry/aliases/alias');
            $paths[] = new restore_path_element('qanda_rating', '/activity/qanda/entries/entry/ratings/rating');
            $paths[] = new restore_path_element('qanda_category_entry',
                            '/activity/qanda/categories/category/category_entries/category_entry');
        }

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    protected function process_qanda($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        $data->assesstimestart = $this->apply_date_offset($data->assesstimestart);
        $data->assesstimefinish = $this->apply_date_offset($data->assesstimefinish);
        if ($data->scale < 0) { // scale found, get mapping
            $data->scale = -($this->get_mappingid('scale', abs($data->scale)));
        }
        $formats = get_list_of_plugins('mod/qanda/formats'); // Check format
        if (!in_array($data->displayformat, $formats)) {
            $data->displayformat = 'dictionary';
        }
        if (!empty($data->mainqanda) and $data->mainqanda == 1 and
                $DB->record_exists('qanda', array('mainqanda' => 1, 'course' => $this->get_courseid()))) {
            // Only allow one main qanda in the course
            $data->mainqanda = 0;
        }

        // insert the qanda record
        $newitemid = $DB->insert_record('qanda', $data);
        $this->apply_activity_instance($newitemid);
    }

    protected function process_qanda_entry($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;

        $data->qandaid = $this->get_new_parentid('qanda');
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->sourceqandaid = $this->get_mappingid('qanda', $data->sourceqandaid);

        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        // insert the entry record
        $newitemid = $DB->insert_record('qanda_entries', $data);
        $this->set_mapping('qanda_entry', $oldid, $newitemid, true); // childs and files by itemname
    }

    protected function process_qanda_alias($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;

        $data->entryid = $this->get_new_parentid('qanda_entry');
        $data->alias = $data->alias_text;
        $newitemid = $DB->insert_record('qanda_alias', $data);
    }

    protected function process_qanda_rating($data) {
        global $DB;

        $data = (object) $data;

        // Cannot use ratings API, cause, it's missing the ability to specify times (modified/created)
        $data->contextid = $this->task->get_contextid();
        $data->itemid = $this->get_new_parentid('qanda_entry');
        if ($data->scaleid < 0) { // scale found, get mapping
            $data->scaleid = -($this->get_mappingid('scale', abs($data->scaleid)));
        }
        $data->rating = $data->value;
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        // Make sure that we have both component and ratingarea set. These were added in 2.1.
        // Prior to that all ratings were for entries so we know what to set them too.
        if (empty($data->component)) {
            $data->component = 'mod_qanda';
        }
        if (empty($data->ratingarea)) {
            $data->ratingarea = 'entry';
        }

        $newitemid = $DB->insert_record('rating', $data);
    }

    protected function process_qanda_category($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;

        $data->qandaid = $this->get_new_parentid('qanda');
        $newitemid = $DB->insert_record('qanda_categories', $data);
        $this->set_mapping('qanda_category', $oldid, $newitemid);
    }

    protected function process_qanda_category_entry($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;

        $data->categoryid = $this->get_new_parentid('qanda_category');
        $data->entryid = $this->get_mappingid('qanda_entry', $data->entryid);
        $newitemid = $DB->insert_record('qanda_entries_categories', $data);
    }

    protected function after_execute() {
        // Add qanda related files, no need to match by itemname (just internally handled context)
        $this->add_related_files('mod_qanda', 'intro', null);
        // Add entries related files, matching by itemname (qanda_entry)
        $this->add_related_files('mod_qanda', 'entry', 'qanda_entry');
        $this->add_related_files('mod_qanda', 'question', 'qanda_entry');
        $this->add_related_files('mod_qanda', 'answer', 'qanda_entry');

        $this->add_related_files('mod_qanda', 'attachment', 'qanda_entry');
    }

}
