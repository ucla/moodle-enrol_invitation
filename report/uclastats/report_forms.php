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
        $is_high_load = $this->_customdata['is_high_load'];

        $mform->addElement('header', 'run-report-header',
                get_string('run_report', 'report_uclastats'));

        // does the report run a long time? if so, we need to note that
        if ($is_high_load) {
            $mform->addElement('html', html_writer::tag('div',
                    get_string('warning_high_load', 'report_uclastats'),
                    array('class' => 'alert alert-warning')));
        }

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
                    case    'threshold':
                        $mform->addElement('text', 'threshold',
                                get_string('threshold', 'report_uclastats'));
                        $mform->setDefault('threshold', 5);
                        break;
                    case 'subjarea':
                        $query = "
                            SELECT DISTINCT urs.subjarea, urs.subj_area_full
                                FROM {ucla_reg_subjectarea} AS urs
                                JOIN {ucla_request_classes} AS urc ON
                                    urc.department = urs.subjarea
                            WHERE urc.action = 'built'
                            ORDER BY urs.subjarea
                            ";
                        $subjareas = $DB->get_records_sql($query);

                        $s = array();
                        foreach ($subjareas as $subjarea) {
                            $s[$subjarea->subjarea] =
                                    ucla_format_name($subjarea->subj_area_full);
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