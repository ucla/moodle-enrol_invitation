<?php

/**
 * Block class for UCLA Copyright Status Update
 *
 * @package    ucla
 * @subpackage ucla_copyright_status
 * @copyright  2012 UC Regents    
 * @author     Jun Wan <jwan@humnet.ucla.edu>                                         
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later 
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . "/licenselib.php");



/*
 * Initializes all $PAGE variables.
*/
function init_copyright_page($course, $courseid, $context){
	global $PAGE;
	$PAGE->set_url('/blocks/ucla_copyright_status/view.php', 
		array('courseid' => $courseid));

	$page_title = $course->shortname.': '.get_string('pluginname',
		'block_ucla_copyright_status');

	$PAGE->set_context($context);
	$PAGE->set_title($page_title);

	$PAGE->set_heading($course->fullname);

	$PAGE->set_pagelayout('course');
	$PAGE->set_pagetype('course-view-'.$course->format);

}


/*
 * Retrive copyright information for all the files uploaded through add a resource
 * @param $courseid
 * @return filename,author and copyright information
*/
function get_files_copyright_status_by_course($courseid,$filter=null){
	global $DB;
	$sql = "SELECT f.id, f.filename, f.author, f.license, f.timemodified, f.contenthash
         FROM {files} f
		 INNER JOIN {context} c
		 ON c.id = f.contextid
		 INNER JOIN {course_modules} cm
		 ON cm.id = c.instanceid
		 INNER JOIN {resource} r
		 ON cm.instance = r.id
		 WHERE r.course = $courseid
		 AND f.filename <> '.'";
	if ($filter && $filter != 'all'){
		$sql .= " AND f.license = '$filter'";
	}
	$sql .= " GROUP BY f.contenthash";
	return $DB->get_records_sql($sql);
}


/*
 * Calculate files copyright status statistics.  Files with same contenthash treated as one file
 * Files have license as null will be included as Copyright status not yet identified.
 * @param $filelist, $licensetypes
 * @return statistics array with file license type as key, and number of the files of that type as value. 
 * @including total file count. File with same contenthash treated as one file.
*/

function calculate_copyright_status_statistics($filelist){
	global $CFG;
	$sum_array = array(); // array stored license type and its quantity
	$total = 0;
	foreach($filelist as $record){
		// include null license as not yet identified statistics
		$license = !empty($record->license)?$record->license:$CFG->sitedefaultlicense;
		// initialize
		$sum_array[$license] = isset($sum_array[$license])?$sum_array[$license]:0;
		$sum_array[$license]++; // calculate each type total
		$total++;
	}
	$sum_array['total'] = $total;
	return $sum_array;
}

/*
 * Return a group of file ids that have the same content hash
 * @param $fileid
 * @return array of file ids 
*/

function get_file_ids($fileid){
	global $DB;
	$sql_get_contenthash = "SELECT f.id, f.contenthash FROM {files} f WHERE f.id = ".$fileid;
	$res_contenthash = $DB->get_records_sql($sql_get_contenthash);
	$sql_get_fileids = "SELECT f.id FROM {files} f where f.contenthash = '".$res_contenthash[$fileid]->contenthash ."'";
	return $DB->get_records_sql($sql_get_fileids); // array of file ids with the same contenthash
}

/*
 * Update copyright status for files
 * @param form post data include string with file id and license the user choose
 * @param $user
*/

function update_copyright_status($data){
	// loop through submitted data
	global $DB, $USER;
	foreach ($data as $key => $value){
		$a = explode('_', $key);
		$id = trim($a[1]);
		$value = trim($value);
		if (isset($id)){
			$id_array = array();// stored file id with same contenthash
			$id_array_with_same_contenthash = get_file_ids($id);
			// loop through all files with same contenthash
			foreach ($id_array_with_same_contenthash as $fid => $other){
				$params = array('id'=>$fid, 'license'=>$value, 'timemodified'=>time(), 'author'=>trim($USER->lastname).", ".trim($USER->firstname));
				$DB->update_record_raw('files', $params);
			} 
		}
	}
}


/*
 * Display file list with copyright status associated with the file for a course
 * @param $courseid, $filter
 * @return array of file ids 
*/

function display_copyright_status_contents($courseid, $filter){
	global $CFG;
	global $OUTPUT;
	global $PAGE;
	
	$url = '/blocks/ucla_copyright_status/view.php';
	$PAGE->set_url($url, array('courseid' => $courseid));	// get copyright data
	$PAGE->requires->js('/theme/uclashared/javascript/jquery-1.5.2.min.js');
	$PAGE->requires->js('/blocks/ucla_copyright_status/view.js');
	$PAGE->requires->string_for_js('changes_saved', 'block_ucla_copyright_status');

	// get license types
	$licensemanager = new license_manager();
    $licenses = $licensemanager->get_licenses(array('enabled'=>1));
	$license_options = array();
	$license_options['all']='All';
	foreach($licenses as $license){
		$license_options[$license->shortname]=$license->fullname;
	}


	// start output screen
	echo html_writer::tag('h1','Copyright Status',array('class' => 'classHeader'));

	// display statistics 
	$all_copyrights = get_files_copyright_status_by_course($courseid, 'all');
	$stat_array = calculate_copyright_status_statistics($all_copyrights, $licenses);
	echo html_writer::start_tag('div', array('id' => 'stat'));
	echo html_writer::start_tag('ul');
	foreach($license_options as $k=>$v){
		if ($k != 'all'){
			echo html_writer::tag('li', $v.':'.html_writer::start_tag('span', array('class'=>'stat_num')).'('.$stat_array[$k].'/'.$stat_array['total'].', '.number_format($stat_array[$k]*100/$stat_array['total'],0,'','').'%)'.html_writer::end_tag('span'));
		}
	}
	echo html_writer::end_tag('ul');
	echo html_writer::end_tag('div');
	// end display statistics

	echo html_writer::start_tag('form', array('id'=>'form_copyright_status_list', 'action'=>$PAGE->url->out(), 'method'=>'post'));
	echo html_writer::start_tag('div', array('id' => 'cp'));

	// display copyright filter
	echo html_writer::start_tag('div', array('id' => 'filter'));
	echo html_writer::tag('span', get_string('copyright_status', 'block_ucla_copyright_status'));
	echo html_writer::select($license_options, 'filter_copyright', $filter, false, array('id'=>'id_filter_copyright'));
	$PAGE->requires->js_init_call('M.util.init_select_autosubmit', array('form_copyright_status_list', 'id_filter_copyright', ''));
	echo html_writer::end_tag('div');
	// end display copyright filter

	// display copyright status list
	unset($license_options['all']);
    $t = new html_table();
    $t->head = array(get_string('file', 'block_ucla_copyright_status'), 
		get_string('updated_dt', 'block_ucla_copyright_status'),
		get_string('author', 'block_ucla_copyright_status'),
		get_string('license', 'block_ucla_copyright_status'));   
	$course_copyright_status_list = get_files_copyright_status_by_course($courseid,$filter);
    foreach ($course_copyright_status_list as $record) {
		$select_copyright = html_writer::select($license_options, 'filecopyright_'.$record->id, $record->license);
		$t->data[] = array($record->filename, strftime("%B %d %Y %r",$record->timemodified), $record->author, $select_copyright); 
	}
	echo html_writer::start_tag('div', array('id'=>'id_cp_list'));
    echo html_writer::table($t);    
	echo html_writer::end_tag('div');
	// end display copyright status list

	echo html_writer::end_tag('div'); // div id = cp

	// display button
   // $bt_options['action'] = 'edit';
	//echo $OUTPUT->render(new single_button(new moodle_url($url, $bt_options), get_string('save_button', 'block_ucla_copyright_status'), 'form_copyright_status_list'));
	echo html_writer::end_tag('form');
	echo "<input type='button' id ='btn1' value='save changes'>";
	echo html_writer::start_tag('span', array('id'=>'changes_saved'));
	echo html_writer::end_tag('span');
	echo html_writer::start_tag('div', array('id' => 'd1'));
	echo html_writer::end_tag('div');
	// end output screen
}


