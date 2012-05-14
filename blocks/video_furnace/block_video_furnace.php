<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/../moodleblock.class.php');
global $CFG, $USER;


class block_video_furnace extends block_base {
    
    public function init(){
        $this->title = get_string('plugin_name', 'block_video_furnace');
    }
    
    public function get_content(){

        require_login($course);

        // Are we allowed to display this page?
        if (is_user_enrolled_in_course($course)) {
            echo '<h1 class="classHeader">Video Furnace</h1>';
                echo '<span id="courseHdrSecondary"><font size=1>
            Please note that this media is intended for on-campus use only. Off-campus use is possible through use of the 
        <a href="http://www.bol.ucla.edu/services/vpn/">BOL VPN</a>,
        however, you will likely experience hiccups, skips or other problems due to insufficient bandwidth. When launching the application, click "yes" 
        or "always" to allow the applet to run on your computer. If the file does not run and you do not see the "certificate acceptance" message then 
        you must install Java in order to use this media (<a href="http://java.sun.com">java.sun.com</a>). For more help, go to the 
        <a href="http://www.oid.ucla.edu/units/imlab/faq/vf/index.html">Media Lab Video Furnace FAQ</a>.
            </font></span>';
            settype($term, 'string');
            settype($srs, 'string');

            list($term, $srs) = explode('-', $course->idnumber);

            $info = get_course_info($course->id);
            foreach ($info as $each_course) {
                $term = $each_course['term'];
                $srs = $each_course['srs'];


                //Start UCLA SSC MODIFICATION 601
                echo '<div id="vidFurnaceContent">';
                if (count($info) > 1)  {
                        echo '<h2>'.$each_course['class_num'].": ".$each_course['fullname'].'.</h2>';
                }
                //End UCLA SSC MODIFICATION 601

                $videos = get_records_select('ucla_vidfurn', '`term` = "'. $term .'" AND `srs` = "'. $srs .'"');

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
                echo '<h3>Current Videos</h3>';
                echo '<div class="vidFurnaceLinks">';
                foreach($cur_vids as $video) {
                    echo '<p><a href="'. $video->video_url .'"><em>'. $video->video_title .'</em></a></p>'; 
                }
                if (empty($cur_vids)) {
                    echo 'There are no videos currently available.';
                }
                echo '</div>';
                if (!empty($future_vids)) {
                    echo '<h3>Future Videos</h3>';
                    echo '<div class="vidFurnaceFuture">';
                    foreach($future_vids as $video) {
                        echo '<p><em>'. $video->video_title .'</em><br />';
                        echo '&nbsp;&nbsp;&nbsp;&nbsp;This video will be available on '. date("Y-m-d",$video->start_date) .'.</p>';
                    }
                    echo '</div>';
                }
                if (!empty($past_vids)) {
                    echo '<h3>Past Videos</h3>';
                    echo '<div class="vidFurnacePast">';
                    foreach($past_vids as $video) {
                        echo '<p><em>'. $video->video_title .'</em><br />';
                        echo '&nbsp;&nbsp;&nbsp;&nbsp;This video no longer available as of '. date("Y-m-d",$video->stop_date) .'.</p>';
                    }
                    echo '</div>';
                }
                echo '</div>';
            }
        }
        else {
            print "Guests can not view this page";
        }        
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
    
}


//EOF
