<?php

require_once(dirname(__FILE__).'/../../config.php');
global $CFG, $DB;

require_once($CFG->dirroot.'/lib/moodlelib.php');
require_once($CFG->dirroot.'/lib/accesslib.php');
require_once($CFG->dirroot.'/local/ucla/lib.php');
$course_id = required_param('course_id', PARAM_INT); // course ID

if (! $course = $DB->get_record('course', array('id' => $course_id))) {
    print_error('coursemisconf');
}       

require_login($course);
$context = get_context_instance(CONTEXT_COURSE, $course_id, MUST_EXIST);

init_page($course, $course_id, $context);
set_editing_mode_button();

echo $OUTPUT->header();

// Are we allowed to display this page?
if (is_enrolled($context)) {
    display_video_furnace_contents($course);
}
else {
    echo "Guests can not view this page";
}        

echo $OUTPUT->footer();

/**
 *  Prints out all of the html for displaying the video furnace page contents. 
 */
function display_video_furnace_contents($course){
    echo html_writer::start_tag('div', array('id' => 'vidfurn-wrapper'));
    
    echo html_writer::tag('h1','Video Furnace',array('class' => 'classHeader'));
    
    echo html_writer::tag('span',
        html_writer::tag('font',
                        'Please note that this media is intended for on-campus use only. Off-campus use is possible through use of the ' 
            .html_writer::link('http://www.bol.ucla.edu/services/vpn/', 'BOL VPN')   
            .',however, you will likely experience hiccups, skips or other problems due to insufficient bandwidth. When launching the application, click "yes" 
            or "always" to allow the applet to run on your computer. If the file does not run and you do not see the "certificate acceptance" message then 
            you must install Java in order to use this media ('
            .html_writer::link('http://java.sun.com', 'java.sun.com')
            .'). For more help, go to the ' 
            .html_writer::link('http://www.oid.ucla.edu/units/imlab/faq/vf/index.html', 'Media Lab Video Furnace FAQ')
            .'.'
        ,array('size' => '1'))    
    ,array('id' => 'courseHdrSecondary'));

    $course_info = ucla_get_course_info($course->id);

    foreach ($course_info as $each_course) {
        //Start UCLA SSC MODIFICATION 601
        echo html_writer::start_tag('div', array('id'=>'vidFurnaceContent'));
        if (count($course_info) > 1)  {
                echo html_writer::tag('h2', ucla_make_course_title($each_course));
        }
        //End UCLA SSC MODIFICATION 601

        $videos = get_video_data($each_course);
        print_video_list($videos['current'], 'Current Videos', array('class'=>'vidFurnaceLinks'));
        if(!empty($videos['future'])) {
            print_video_list($videos['future'], 'Future Videos', array('class'=>'vidFurnaceFuture'));
        }
        if(!empty($videos['past'])) {
            print_video_list($videos['past'], 'Past Videos', array('class'=>'vidFurnacePast'));
        }
        echo html_writer::end_tag('div'); //array('id'=>'vidFurnaceContent')      
    }
    echo html_writer::end_tag('div'); //array('id'=>'vidfurn-wrapper')       
}

/**
 * Obtains raw video data from the db, and returns a sorted version of that data based on 
 * the current system time.
 * @param $courseinfo - the course info of the course that the video data is from.
 * 
 * @return An array of arrays of the current, future, and past videos relative
 * to the system date, sorted chronologically.
 */
function get_video_data($course_info){
    //Get the video data
    global $DB;
    $term = $course_info->term;
    $srs = $course_info->srs;
    $videos = $DB->get_records_select('ucla_video_furnace', '`term` = "'. $term .'" AND `srs` = "'. $srs .'"');

    $cur_date = time();
    $cur_vids = array();
    $future_vids = array();
    $past_vids = array();
    // Sort the data chronologically
    foreach($videos as $video) {
        if ($cur_date >= $video->start_date && $cur_date <= $video->stop_date) {
            $cur_vids[] = $video;
        }
        else if($cur_date < $video->start_date) {
            $future_vids[] = $video;
        }
        else if($cur_date > $video->stop_date) {
            $past_vids[] = $video;
        }
		
    }
    // sort the different videos depending on their current status
    usort($cur_vids, 'cmp_title');
    usort($future_vids, 'cmp_start_date');
    usort($past_vids, 'cmp_end_date');
    
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
        echo html_writer::tag('p', 
            html_writer::tag('em',
			html_writer::link($video->video_url, $video->video_title)
            .html_writer::empty_tag('br')));
		if ($header_title == "Past Videos"){
			echo '&nbsp;&nbsp;&nbsp;&nbsp;This video no longer available as of '. date("Y-m-d",$video->stop_date);
		}
		else if ($header_title == "Future Videos"){
			echo '&nbsp;&nbsp;&nbsp;&nbsp;This video will be available on '. date("Y-m-d",$video->start_date);
		}
    }
    echo html_writer::end_tag('div'); //array('class'=>'vidFurnacePast')
      
}

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
