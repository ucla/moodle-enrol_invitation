<?php
/**
 * List available reports for UCLA stats console.
 *
 * @package    report
 * @subpackage uclastats
 * @copyright  UC Regents
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot.'/report/uclastats/locallib.php');

require_login();
$context = get_context_instance(CONTEXT_COURSE, SITEID);
$PAGE->set_context($context);
require_capability('report/uclastats:view', $context);

$PAGE->set_url(new moodle_url('/report/uclastats/index.php'));

admin_externalpage_setup('reportuclastats');

echo $OUTPUT->header();
echo $output->render_header();

$output = $PAGE->get_renderer('report_uclastats');

echo get_string('index_welcome', 'report_uclastats');

$reports = get_all_reports();
echo $output->render_report_list($reports);

echo $OUTPUT->footer();
