<?php
defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/../moodleblock.class.php');

// sort functions
function cmp_title($a, $b) {
	if ($a->video_title == $b->video_title) {
		return 0;
	}
	return ($a->video_title < $b->video_title) ? -1 : 1;
}
// sort from least recent to most recent
function cmp_start_date($a, $b) {
	if ($a->start_date == $b->start_date) {
		return 0;
	}
	return ($a->start_date < $b->start_date) ? -1 : 1;
}

// sort from most recent to least recent
function cmp_end_date($a, $b) {
	if ($a->end_date == $b->end_date) {
		return 0;
	}
	return ($a->end_date < $b->end_date) ? 1 : -1;
} 


/**
 * Obtains raw video data from the db, and returns a sorted version of that data based on 
 * the current system time.
 * @param $courseinfo - the course info of the course that the video data is from.
 * courseinfo is array of courses array(0=>array(term, srs), 1=>array(term, srs))
 * 
 * @return An array of arrays of the current, future, and past videos relative
 * to the system date, sorted chronologically.
 */

function get_video_data($courseinfo){
	//Get the video data
	global $DB;
	$nodes = array();
	$videos = $DB->get_records('ucla_video_furnace', array('term' => $courseinfo->term, 'srs' => $courseinfo->srs));
	$cur_date = time();
	$cur_vids = array();
	$future_vids = array();
	$past_vids = array();
	// Sort the data chronologically
	foreach($videos as $video) {
		if ($cur_date >= $video->start_date && $cur_date <= $video->stop_date) {
			$cur_vids[] = $video;
		} else if($cur_date < $video->start_date) {
			$future_vids[] = $video;
		} else if($cur_date > $video->stop_date) {
			$past_vids[] = $video;
		}
	}
	// sort the different videos depending on their current status
	if (!empty($cur_vids)){
		usort($cur_vids, 'cmp_title');
	}
	if (!empty($future_vids)){
		usort($future_vids, 'cmp_start_date');
	}
	if (!empty($past_vids)){
		usort($past_vids, 'cmp_end_date');
	}
	return array('current'=>$cur_vids, 'future' => $future_vids, 'past' => $past_vids);
}

/**
 * Prints all of the html associated with a particular video list. 
 * 
 * @param array $video_list a list of videos to be displayed. Meant to be
 * used with data obtained from get_video_data.  
 * @param $string $header_title - The header title of the list to be displayed.
 * @param $section_attr an array containing the attributes to be associated with the div tag.
 */
function print_video_list($video_list, $header_title, $section_attr){

	echo html_writer::tag('h3', $header_title);
	echo html_writer::start_tag('div', $section_attr);
	foreach($video_list as $video) {
		$output_str = "";
		if ($header_title == "Past Videos"){
			$output_str = $video->video_title . '&nbsp;&nbsp;&nbsp;&nbsp;This video no longer available as of '. date("Y-m-d",$video->stop_date).'<br>';
		} else if ($header_title == "Future Videos"){
			$output_str = $video->video_title . '&nbsp;&nbsp;&nbsp;&nbsp;This video will be available on '. date("Y-m-d",$video->start_date).'<br>';
		} else{
			$output_str = html_writer::link($video->video_url, $video->video_title);
		}
		echo html_writer::tag('p', $output_str, array('class'=>'vidFurnaceText')); 
	}
	echo html_writer::end_tag('div'); //array('class'=>'vidFurnacePast')
	  
}

/**
 *  Prints out all of the html for displaying the video furnace page contents. 
 */
function display_video_furnace_contents($course){
	echo html_writer::start_tag('div', array('id' => 'vidfurn-wrapper'));
	echo html_writer::tag('h1','Video Furnace',array('class' => 'classHeader'));
	echo html_writer::tag('p', get_string('videofurnaceintro', 'block_ucla_video_furnace'), array('id' => 'course-header-secondary'));
	$course_info = ucla_get_course_info($course->id);// return more than one class with the same class id

	foreach ($course_info as $each_course) {
		//Start UCLA SSC MODIFICATION 601
		echo html_writer::start_tag('div');
		if (count($course_info) > 1)  {
				echo html_writer::tag('h2', ucla_make_course_title($each_course));
		}
		//End UCLA SSC MODIFICATION 601

		$videos = get_video_data($each_course);
		if (!empty($videos['current'])){
			print_video_list($videos['current'], get_string('currentvideo','block_ucla_video_furnace'), array('class'=>'vidFurnaceLinks'));
		}else{
			echo html_writer::tag('span', get_string('novideo', 'block_ucla_video_furnace'), array('class'=>'vidFurnaceText'));
		}

		if(!empty($videos['future'])) {
			print_video_list($videos['future'], get_string('futurevideo','block_ucla_video_furnace'), array('class'=>'vidFurnaceText'));
		}
		if(!empty($videos['past'])) {
			print_video_list($videos['past'], get_string('pastvideo','block_ucla_video_furnace'), array('class'=>'vidFurnaceText'));
		}
		echo html_writer::end_tag('div'); //array('id'=>'vidFurnaceContent')      
	}
	echo html_writer::end_tag('div'); //array('id'=>'vidfurn-wrapper')       
}


//Initializes all $PAGE variables.
function init_page($course, $course_id, $context){
	global $PAGE;
	$PAGE->set_url('/blocks/ucla_video_furnace/view.php', 
		array('course_id' => $course_id));

	$page_title = $course->shortname.': '.get_string('pluginname',
		'block_ucla_video_furnace');

	$PAGE->set_context($context);
	$PAGE->set_title($page_title);

	$PAGE->set_heading($course->fullname);

	$PAGE->set_pagelayout('course');
	$PAGE->set_pagetype('course-view-'.$course->format);

}

