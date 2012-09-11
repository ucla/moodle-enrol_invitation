<?php
/**
 * UCLA copyright status report: copyright status by course
 * 
 * @package     tool
 * @subpackage  uclacopyrightstatusreports
 * @copyright   UC Regents
 */

require_once(dirname(__FILE__) . '/../../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$thisdir = '/' . $CFG->admin . '/tool/uclacopyrightstatusreports/';
require_once($CFG->dirroot . $thisdir . 'lib.php');
require_once($CFG->dirroot . '/blocks/ucla_copyright_status/lib.php');

$baseurl = $CFG->wwwroot . '/' . $CFG->admin . '/tool/uclacopyrightstatusreports';
$courseid = optional_param('id', 0, PARAM_INT);
$filter_copyright = optional_param('filter_copyright', 'all', PARAM_TEXT);

require_login();

$syscontext = get_context_instance(CONTEXT_SYSTEM);
require_capability('tool/uclacopyrightstatusreports:view', $syscontext);

// Initialize $PAGE
$PAGE->set_context($syscontext);
$PAGE->set_heading(get_string('pluginname', 'tool_uclacopyrightstatusreports'));
$PAGE->set_pagetype('admin-*');
$PAGE->set_pagelayout('admin');

// Prepare and load Moodle Admin interface
admin_externalpage_setup('uclacopyrightstatusreports');

// prepare table sorting functionality
$tableid = setup_js_tablesorter('copyright_status_table');

// Render page
echo $OUTPUT->header();

// Heading
echo $OUTPUT->heading(get_string('detail_copyright', 'tool_uclacopyrightstatusreports'), 2, 'headingblock');
echo html_writer::link($baseurl . '/index.php', get_string('back', 'tool_uclacopyrightstatusreports'));

// get license types
$licensemanager = new license_manager();
$licenses = $licensemanager->get_licenses(array('enabled' => 1));
$license_options = array();
$license_options['all'] = 'All';
foreach ($licenses as $license) {
    $license_options[$license->shortname] = $license->fullname;
}

// if javascript disabled
echo html_writer::tag('noscript',
        get_string('javascriptdisabled', 'block_ucla_copyright_status'),
        array('id' => 'block-ucla-copyright-status-noscript'));


// start output
$PAGE->set_url($thisdir . 'report/course_copyright_detail.php', array('id'=>$courseid));
echo html_writer::start_tag('div', array('id' => 'tool_uclacopyrightstatusreports_list_div'));
echo html_writer::start_tag('form',
            array('id' => 'tool_uclacopyrightstatusreports_course_detail_form', 'action' => $PAGE->url->out(), 'method' => 'post'));


// display copyright filter
echo html_writer::start_tag('div',
        array('id' => 'tool_uclacopyrightstatusreports_filter_div'));
echo html_writer::tag('span',
        get_string('copyright_status', 'block_ucla_copyright_status'),
        array('id' => 'tool_uclacopyrightstatusreports_course_detail_t1'));
echo html_writer::select($license_options, 'filter_copyright', $filter_copyright, false,
        array('id' => 'tool_uclacopyrightstatusreports_course_detail_filter'));
$PAGE->requires->js_init_call('M.util.init_select_autosubmit',
        array('tool_uclacopyrightstatusreports_course_detail_form', 'tool_uclacopyrightstatusreports_course_detail_filter', ''));
echo html_writer::end_tag('div');
// end display copyright filter


// display copyright status list
$t = new html_table();
$t->id = 'copyright_status_table';
$t->head = array(get_string('file_name', 'tool_uclacopyrightstatusreports'), get_string('choosecopyright', 'local_ucla'),
    get_string('updated_dt', 'block_ucla_copyright_status'),
    get_string('author', 'block_ucla_copyright_status'));
$course_copyright_status_list = get_files_copyright_status_by_course($courseid, $filter_copyright);
$files_list = process_files_list($course_copyright_status_list);

foreach ($files_list as $contenthash_record) {
    $file_names = array();
    $file_copyrights = array();
    $file_dates = array();
    $file_authors = array();

    //loop through all the files with the same content hash        
    foreach ($contenthash_record as $id => $record) {
        $file_names[] = html_writer::tag('a', $record['filedisplayname'],
                        array('href' => $CFG->wwwroot . '/mod/resource/view.php?id=' . $record['cmid']));
        $file_copyrights[] = array_key_exists($record['license'], $license_options)?$license_options[$record['license']]:null;
        $file_dates[] = strftime("%B %d %Y %r", $record['timemodified']);
        $file_authors[] = $record['author'];
    }

    // if there are mutliple records for a given contenthash, then display
    // then in a ordered list
    if (count($contenthash_record) > 1) {
        $file_names = html_writer::alist($file_names, null, 'ol');
        $file_copyrights = html_writer::alist($file_copyrights, null, 'ol');
        $file_dates = html_writer::alist($file_dates, null, 'ol');
        $file_authors = html_writer::alist($file_authors, null, 'ol');
    } else {
        // only one file, so just show information normally
        $file_names = array_pop($file_names);
        $file_copyrights = array_pop($file_copyrights);
        $file_dates = array_pop($file_dates);
        $file_authors = array_pop($file_authors);
    }

    $t->data[] = array($file_names, $file_copyrights, $file_dates, $file_authors);
}

echo html_writer::start_tag('div',
        array('id' => 'block_ucla_copyright_status_id_cp_list'));
if (count($course_copyright_status_list) > 0) {
    echo html_writer::table($t);
} else {
    echo html_writer::tag('span',
            get_string('no_files', 'block_ucla_copyright_status'),
            array('class' => 'block-ucla-copyright-status-red-text-item'));
}
echo html_writer::end_tag('div');

echo html_writer::end_tag('form');
echo html_writer::end_tag('div');


echo $OUTPUT->footer();
