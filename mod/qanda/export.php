<?php
require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/lib.php');

$id = required_param('id', PARAM_INT);      // Course Module ID

$mode = optional_param('mode', '', PARAM_ALPHA);           // term entry cat date letter search author approval
$hook = optional_param('hook', '', PARAM_CLEAN);           // the term, entry, cat, etc... to look for based on mode
$cat = optional_param('cat', 0, PARAM_ALPHANUM);

$url = new moodle_url('/mod/qanda/export.php', array('id' => $id));
if ($cat !== 0) {
    $url->param('cat', $cat);
}
if ($mode !== '') {
    $url->param('mode', $mode);
}

$PAGE->set_url($url);

if (!$cm = get_coursemodule_from_id('qanda', $id)) {
    print_error('invalidcoursemodule');
}

if (!$course = $DB->get_record("course", array("id" => $cm->course))) {
    print_error('coursemisconf');
}

if (!$qanda = $DB->get_record("qanda", array("id" => $cm->instance))) {
    print_error('invalidid', 'qanda');
}

require_login($course, false, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/qanda:export', $context);

$strqandas = get_string("modulenameplural", "qanda");
$strqanda = get_string("modulename", "qanda");
$strallcategories = get_string("allcategories", "qanda");
$straddentry = get_string("addentry", "qanda");
$strnoentries = get_string("noentries", "qanda");
$strsearchinanswer = get_string("searchinanswer", "qanda");
$strsearch = get_string("search");
$strexportfile = get_string("exportfile", "qanda");
$strexportentries = get_string('exportentriestoxml', 'qanda');

$PAGE->set_url('/mod/qanda/export.php', array('id' => $cm->id));
$PAGE->navbar->add($strexportentries);
$PAGE->set_title(format_string($qanda->name));
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading($strexportentries);
echo $OUTPUT->box_start('qanda-display generalbox');
$exporturl = moodle_url::make_pluginfile_url($context->id, 'mod_qanda', 'export', 0, "/$cat/", 'export.xml', true);
?>
<form action="<?php echo $exporturl->out(); ?>" method="post">
    <table border="0" cellpadding="6" cellspacing="6" width="100%">
        <tr><td align="center">
                <input type="submit" value="<?php p($strexportfile) ?>" />
            </td></tr></table>
    <div>
    </div>
</form>
<?php
// don't need cap check here, we share with the general export.
if (!empty($CFG->enableportfolios) && $DB->count_records('qanda_entries', array('qandaid' => $qanda->id))) {
    require_once($CFG->libdir . '/portfoliolib.php');
    $button = new portfolio_add_button();
    $button->set_callback_options('qanda_full_portfolio_caller', array('id' => $cm->id), 'mod_qanda');
    $button->render();
}
echo $OUTPUT->box_end();
echo $OUTPUT->footer();
?>
