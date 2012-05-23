<?php

require_once(dirname(__FILE__).'/../../config.php');
global $CFG, $USER, $DB;

require_once($CFG->dirroot.'/lib/moodlelib.php');
require_once($CFG->dirroot.'/lib/accesslib.php');
require_once($CFG->dirroot.'/local/ucla/lib.php');
$course_id = required_param('course_id', PARAM_INT); // course ID

if (! $course = $DB->get_record('course', array('id' => $course_id))) {
    print_error('coursemisconf');
}       

require_login($course);

$context = get_context_instance(CONTEXT_COURSE, $course->id, MUST_EXIST);
// Are we allowed to display this page?
if (is_enrolled($context)) {
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
            .'). For more help, go to the' 
            .html_writer::link('http://www.oid.ucla.edu/units/imlab/faq/vf/index.html', 'Media Lab Video Furnace FAQ')
            .'.'
        ,array('size' => '1'))    
    ,array('id' => 'courseHdrSecondary'));

    settype($term, 'string');
    settype($srs, 'string');

    $info = ucla_map_courseid_to_termsrses($course->id);
    foreach ($info as $each_course) {
        $term = $each_course->term;
        $srs = $each_course->srs;


        //Start UCLA SSC MODIFICATION 601
        echo html_writer::start_tag('div', array('id'=>'vidFurnaceContent'));
        if (count($info) > 1)  {
                echo html_writer::tag('h2', $each_course->class_num.": ".$each_course->fullname);
        }
        //End UCLA SSC MODIFICATION 601

        $videos = $DB->get_records_select('ucla_vidfurn', '`term` = "'. $term .'" AND `srs` = "'. $srs .'"');

        // $cur_date = time() - strtotime('-3 week');  // testing not yet available
        // $cur_date = time() + strtotime('40 week');  // testing no longer available
        $cur_date = time();
        $cur_vids = array();
        $future_vids = array();
        $past_vids = array();
        foreach($videos as $video) {
            if ($cur_date >= $video->start_date && $cur_date <= $video->stop_date) {
            $cur_vids[] = $video;
            }
            else if($cur_date <= $video->start_date) {
                $future_vids[] = $video;
            }
            else if($cur_date >= $video->stop_date) {
                $past_vids[] = $video;
            }
        }

        // sort the different videos depending on their current status
        usort($cur_vids, 'cmp_title');
        usort($future_vids, 'cmp_start_date');
        usort($past_vids, 'cmp_start_date_r');
        echo html_writer::tag('h3','Current Videos');
        
        echo html_writer::start_tag('div', array('class' => 'vidFurnaceLinks'));
            foreach($cur_vids as $video) {
                echo html_writer::tag('p', 
                    html_writer::tag('a', 
                            html_writer::tag('em', $video->video_title) 
                    ,array('href' => $video->video_url)));
            }
            if (empty($cur_vids)) {
                echo 'There are no videos currently available.';
            }
        echo html_writer::end_tag('div'); //array('class' => 'vidFurnaceLinks')
        
        if (!empty($future_vids)) {
            echo html_writer::tag('h3', 'Future Videos');
            echo html_writer::start_tag('div', array('class'=>'vidFurnaceFuture'));
            foreach($future_vids as $video) {
                echo html_writer::tag('p', 
                        html_writer::tag('em',$video->video_title)
                        .html_writer::empty_tag('br')
                        .'&nbsp;&nbsp;&nbsp;&nbsp;This video will be available on '.date("Y-m-d",$video->start_date));
            }
            echo html_writer::end_tag('div'); //array('class'=>'vidFurnaceFuture')
        }
        if (!empty($past_vids)) {
            echo html_writer::tag('h3','Past Videos');
            echo html_writer::start_tag('div', array('class'=>'vidFurnacePast'));
            foreach($past_vids as $video) {
                echo html_writer::tag('p', 
                    html_writer::tag('em',$video->video_title) 
                    .html_writer::empty_tag('br')
                    .'&nbsp;&nbsp;&nbsp;&nbsp;This video no longer available as of '. date("Y-m-d",$video->stop_date));
            }
            echo html_writer::end_tag('div'); //array('class'=>'vidFurnacePast')
        }
        echo html_writer::end_tag('div'); //array('id'=>'vidFurnaceContent')      
    }
    echo html_writer::end_tag('div'); //array('id'=>'vidfurn-wrapper')   
}
else {
    
    echo "Guests can not view this page";
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
