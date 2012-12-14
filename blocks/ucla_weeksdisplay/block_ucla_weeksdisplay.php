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

global $CFG;
require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once(dirname(__FILE__) . '/locallib.php');

class block_ucla_weeksdisplay extends block_base {
    
    public function init() {
        $this->title = get_string('pluginname', 'block_ucla_weeksdisplay');
    }
    
    public function cron() {
        self::set_current_week_display(date('c'));
        return true;    // crons need to return true or they run all the time
    }
    
    /**
     * Returns the current week's display string
     */    
    public static function get_week_display() {
        return get_config('local_ucla', 'current_week_display');
    }    
    
    public static function set_term($term) {
        set_config('currentterm', $term);
    }
    
    public static function set_week_display($str) {
        set_config('current_week_display', $str, 'local_ucla');
    }
    
    public static function set_current_week($week) {
        set_config('current_week', $week, 'local_ucla');
    }
    
    public static function set_active_terms($terms) {
        $term_string = implode(',', $terms);
        
        set_config('active_terms', $term_string, 'local_ucla');
        set_config('terms', $term_string, 'tool_uclacourserequestor');
        set_config('terms', $term_string, 'tool_uclacoursecreator');
    }
    
    
   /**
    * Sets the current_week_display config variable based on the given date
    * 
    * @param date string that starts with the format YYYY-MM-DD that is the
    * date associated with the desired display string.
    */     
    public static function set_current_week_display($date) {
        global $CFG;

        //Include registrar files.
        ucla_require_registrar();

        //If the current term is not valid, heuristically initialize it.      
        if(empty($CFG->currentterm) || !ucla_validator('term', $CFG->currentterm)) {
            self::init_currentterm(date('c'));
        }

        $current_term = $CFG->currentterm;

        try {
            $query_result = registrar_query::run_registrar_query('ucla_getterms', 
                    array($current_term), true);    

            $session = new ucla_session($query_result);
            $session->update();
            
        } catch(Exception $e) {
            // mostly likely couldn't connect to registrar
            mtrace($e->getMessage());
        }
    }

    /**
     *  Do not allow block to be added anywhere
     */
    function applicable_formats() {
        return array(
            'site-index' => false,
            'course-view' => false,
            'my' => false,
            'not-really-applicable' => true
        );
    }    
    
   /**
    * Sets currentterm to the current term based on the input date.
    * This function heuristically sets the term, and may not be 
    * accurate 100% of the time.
    * 
    * @param date string of the format YYYY-MM-DD
    */
    public static function init_currentterm($date) {
        
        $year = substr($date, 2, 2); 

        //Guess the quarter based on the month.
        $month = intval(substr($date, 5, 2));
        
        if ($month <= 0 || $month > 12) {
            debugging('Invalid system date month: '.$month);
        } else if ($month < 3) {
            set_config('currentterm', $year.'W');
        } else if ($month < 6) {
            set_config('currentterm', $year.'S');   
        } else if ($month < 9) {
            set_config('currentterm', $year.'1');
        } else {//if($month <= 12) 
            set_config('currentterm', $year.'F');
        }        
    }
        
}

