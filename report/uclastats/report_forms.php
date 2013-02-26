<?php
/**
 * Collection of forms used by UCLA stats console.
 *
 * @package    report
 * @subpackage uclastats
 * @copyright  UC Regents
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');

/**
 * Form to generate form to run a given report.
 */
class runreport_form extends moodleform {
    /**
     * Using custom data, determine what form fields to show
     *
     * Custom data can be in following format:
     * array (
     *  [index] => [form_name]
     *  [index] => [form_name] => array(values for dropdowns)
     * )
     *
     */
    public function definition(){
        global $CFG, $DB;
        $mform =& $this->_form;
        $fields = $this->_customdata['fields'];

        $mform->addElement('header', 'run-report-header',
                get_string('run_report', 'report_uclastats'));
        if (!empty($fields)) {
            foreach ($fields as $field) {
                if (is_array($field)) {
                    $fieldname = $field;
                } else {
                    $fieldname = $field;
                }
                switch ($fieldname) {
                    case 'term':
                        // get terms
                        $terms = $DB->get_records_select_menu(
                                'ucla_request_classes', '1=1', null, null,
                                'DISTINCT term, term');
                        // format terms
                        foreach ($terms as $term => $value) {
                            $terms[$term] = $term;
                        }
                        $terms = terms_arr_sort($terms, true);
                        // need to give user friendly names
                        $mform->addElement('select', 'term', get_string('term',
                                'report_uclastats'), $terms);
                        $mform->setDefault('term', $CFG->currentterm);
                        break;
                    case 'subjarea':
                        $query = "
                            SELECT urs . *
                                FROM {ucla_reg_subjectarea} AS urs
                                JOIN {ucla_request_classes} AS urc ON urc.department = urs.subjarea
                            WHERE urc.action = 'built'
                            GROUP BY urs.id 
                            ";
                        $subjareas = $DB->get_records_sql($query);
                        
                        $s = array();
                        foreach ($subjareas as $subjarea) {
                            $s[$subjarea->subj_area_full] = ucla_format_name($subjarea->subj_area_full);
                        }
                        
                        $mform->addElement('select', 'subjarea', 
                                get_string('subjarea', 'report_uclastats'), $s);
                        break;
                }
            }
        } else {
            $mform->addElement('html', get_string('noparams', 'report_uclastats'));
        }
        $this->add_action_buttons(false,
                get_string('run_report', 'report_uclastats'));
    }
}