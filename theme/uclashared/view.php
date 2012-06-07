<?php
/**
 * CCLE-2493
 * UCLA footer links
 */
require_once(dirname(__FILE__) . '/../../config.php');
$page = required_param('page', PARAM_ALPHA);

$PAGE->set_url('/theme/uclashared/view.php', array('page' => $page));
$PAGE->set_course($SITE);
$PAGE->set_pagelayout('standard');

if (!in_array($page, array('copyright', 'privacy', 'links'))) {
    $title = get_string('error', 'theme_uclashared');
    $page = 'error';
} else {
    $title = get_string($page, 'theme_uclashared');    
}

$PAGE->set_title($title);
$PAGE->navbar->add($title);
$PAGE->set_heading($SITE->fullname);
echo $OUTPUT->header();

$include_path = dirname(__FILE__) . '/layout/links/' . basename($page) . '.php';
if ($page != 'error' && file_exists($include_path)) {
    include $include_path;
} else {
    print_error(get_string('page_notfound', 'theme_uclashared'));
}    

echo $OUTPUT->footer();