<?php

/**
 * @package    mod_qanda
 * @copyright 2013 UC Regents
 */
/**
 * Define all the backup steps that will be used by the backup_qanda_activity_task
 */

/**
 * Define the complete qanda structure for backup, with file and id annotations
 */
class backup_qanda_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // To know if we are including userinfo
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated
        $qanda = new backup_nested_element('qanda', array('id'), array(
                    'name', 'intro', 'introformat', 'allowduplicatedentries', 'displayformat',
                    'mainqanda', 'showspecial', 'showalphabet', 'showall',
                    'allowcomments', 'allowprintview', 'usedynalink', 'defaultapproval',
                    'globalqanda', 'entbypage', 'editalways', 'rsstype',
                    'rssarticles', 'assessed', 'assesstimestart', 'assesstimefinish',
                    'scale', 'timecreated', 'timemodified', 'completionentries'));

        $entries = new backup_nested_element('entries');

        $entry = new backup_nested_element('entry', array('id'), array(
                    'userid', 'question', 'questionformat', 'answer', 'answerformat',
                    'answertrust', 'attachment', 'timecreated', 'timemodified',
                    'teacherentry', 'sourceqandaid', 'usedynalink', 'casesensitive',
                    'fullmatch', 'approved'));

        $aliases = new backup_nested_element('aliases');

        $alias = new backup_nested_element('alias', array('id'), array(
                    'alias_text'));

        $ratings = new backup_nested_element('ratings');

        $rating = new backup_nested_element('rating', array('id'), array(
                    'component', 'ratingarea', 'scaleid', 'value', 'userid', 'timecreated', 'timemodified'));

        $categories = new backup_nested_element('categories');

        $category = new backup_nested_element('category', array('id'), array(
                    'name', 'usedynalink'));

        $categoryentries = new backup_nested_element('category_entries');

        $categoryentry = new backup_nested_element('category_entry', array('id'), array(
                    'entryid'));

        // Build the tree
        $qanda->add_child($entries);
        $entries->add_child($entry);

        $entry->add_child($aliases);
        $aliases->add_child($alias);

        $entry->add_child($ratings);
        $ratings->add_child($rating);

        $qanda->add_child($categories);
        $categories->add_child($category);

        $category->add_child($categoryentries);
        $categoryentries->add_child($categoryentry);

        // Define sources
        $qanda->set_source_table('qanda', array('id' => backup::VAR_ACTIVITYID));

        $category->set_source_table('qanda_categories', array('qandaid' => backup::VAR_PARENTID));

        // All the rest of elements only happen if we are including user info
        if ($userinfo) {
            $entry->set_source_table('qanda_entries', array('qandaid' => backup::VAR_PARENTID));

            $alias->set_source_table('qanda_alias', array('entryid' => backup::VAR_PARENTID));
            $alias->set_source_alias('alias', 'alias_text');

            $rating->set_source_table('rating', array('contextid' => backup::VAR_CONTEXTID,
                'itemid' => backup::VAR_PARENTID,
                'component' => backup_helper::is_sqlparam('mod_qanda'),
                'ratingarea' => backup_helper::is_sqlparam('entry')));
            $rating->set_source_alias('rating', 'value');

            $categoryentry->set_source_table('qanda_entries_categories', array('categoryid' => backup::VAR_PARENTID));
        }

        // Define id annotations
        $qanda->annotate_ids('scale', 'scale');

        $entry->annotate_ids('user', 'userid');

        $rating->annotate_ids('scale', 'scaleid');

        $rating->annotate_ids('user', 'userid');

        // Define file annotations
        $qanda->annotate_files('mod_qanda', 'intro', null); // This file area hasn't itemid

        $entry->annotate_files('mod_qanda', 'entry', 'id');
        $entry->annotate_files('mod_qanda', 'attachment', 'id');
        $entry->annotate_files('mod_qanda', 'question', 'id');
        $entry->annotate_files('mod_qanda', 'answer', 'id');

        // Return the root element (qanda), wrapped into standard activity structure
        return $this->prepare_activity_structure($qanda);
    }

}
