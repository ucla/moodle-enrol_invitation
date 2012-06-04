<?php
/**
 * CCLE-2493
 * UCLA footer links
 */
require_once('config.php');
$page = required_param('page', PARAM_TEXT);

switch($page) {
    case 'copyright':
        $title = 'Copyright Information';
        break;
    case 'privacy':
        $title = 'Privacy Policy';
        break;
    case 'links':
        $title = 'UCLA Links';
        break;
    default:
        $title = 'Error';
        
}

$PAGE->set_url('/view.php', array('page' => $page));
$PAGE->set_course($SITE);
$PAGE->set_pagelayout('standard');
$PAGE->set_title($SITE->fullname . ': ' .$title);
$PAGE->navbar->add($title);

$PAGE->set_heading($SITE->fullname);
echo $OUTPUT->header();

$include_path = $CFG->dirroot . '/theme/uclashared/layout/links/' . $page . '.php';

if(file_exists($include_path)) {
    include $CFG->dirroot . '/theme/uclashared/layout/links/' . $page . '.php';
} else {
    print_error(get_string('page_notfound', 'theme_uclashared'));
}

echo $OUTPUT->footer();