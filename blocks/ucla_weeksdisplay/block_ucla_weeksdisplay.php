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
require_once($CFG->dirroot . '/local/ucla/lib.php');

class block_ucla_weeksdisplay extends block_base {
    
    /*
     * Session code for a regular fall/winter/spring session.
     */
    const RG = 'RG'; 
     /*
     * Session codes for the various regular summer sessions.
     */   
    const S1 = '6A'; 
    const S2 = '8A';
    const S3 = '6C';
    
    function init() {
        $this->title = get_string('pluginname', 'block_ucla_weeksdisplay');
       // $this->content_type = BLOCK_TYPE_TEXT;
    }
    
    //Displays the current quarter
    function get_raw_content(){
        global $CFG;
        return ucla_term_to_text($CFG->currentterm);
    }
    
    function cron(){
        
        //Include registrar files.
        ucla_require_registrar();
        global $CFG;
        //If the current term is not valid, heuristically initialize it.
        if(ucla_validator('term', $CFG->currentterm) == false) {
            init_currentterm();
        }
        
        //Run the query and parse out the regular sessions.
        $query_result = registrar_query::run_registrar_query(
                'ucla_getterms', array($CFG->currentterm));
        $regular_sessions = find_regular_sessions($query_result);
        
        //Compare the session start date with the system date
        $system_date = date('c');
        if(isset($regular_sessions[RG])) {
            $is_date_in_session = 
                    is_date_in_session($system_date,$regular_sessions[RG]);
            if($is_date_in_session == 0){
                //get_current_week_display($system_date, $regular_sessions[RG])
            }
        }
        //else if();
        
    }

   /**
    * Takes in a session and a date belonging to that session, and returns the
    * current_week_display string associated with the date
    * 
    * @param date string that starts with the format YYYY-MM-DD that has to be 
    * either within the session start/end dates, or before the sessions start
    * date and after the previous session's end date.
    * @param session a session object returned by ucla_getterms registrar query
    * @return the current_week_display string with format:
    * <Quarter> <Year> - Week <Week number> on a normal week.
    * <Quarter> <Year> - Finals Week for week 11.
    * <Quarter> <Year> - Week 0 if instruction_start > session_start
    * <Quarter> <Year> for all other days that don't fit the stuff above.
    * Summer <Year> - Session A, Week <Week number>
    * Summer <Year> - Session A, Week <Week number> / Session C, Week <Week number>
    * Summer <Year> - Session C, Week <Week number> 
    * for the various summer sessions.
    */      
    function get_current_week_display($date, $session){
        //Array whose index represents the month (1 = January, etc) and value
        //has the number of days in that month. Februrary is defined as 28 days.
        $days_per_month[1] = 31;
        // <editor-fold defaultstate="collapsed" desc="Rest of days_per_month declaration">        
        $days_per_month[2] = 28;
        $days_per_month[3] = 31;
        $days_per_month[4] = 30;
        $days_per_month[5] = 31;
        $days_per_month[6] = 30;
        $days_per_month[7] = 31;
        $days_per_month[8] = 31;
        $days_per_month[9] = 31;
        $days_per_month[10] = 31;
        $days_per_month[11] = 30;
        $days_per_month[12] = 31; // </editor-fold>

        $session_start_date = $session[5];
        $session_end_date = $session[6];
        $instruction_start_date = $session[7];
 
        $date_vs_session_start = cmpdate($date, $session_start_date);
        $date_vs_end = cmpdate($date, $session_end_date);       
        $date_vs_instruction_start = cmpdate($date, $instruction_start_date);
        
  
        $parsed_date = parse_date($date);
        $parsed_session_start_date = parse_date($session[5]);
        $parsed_session_end_date = parse_date($session[6]);
        $parsed_instruction_start_date = parse_date($session[7]);        
        //If the date is in Week 0.
        if($date_vs_session_start >= 0 && $date_vs_instruction_start < 0){
            return ucla_term_to_text($session[0])." - Week 0";
        }
        else if($date_vs_instruction_start >= 0 && $date_vs_session_end >= 0){
            //$parsed_instruction_start_date
            
        }
        //else if()
        
    }
    
   /**
    * Compare
    * @param date string that starts with the format YYYY-MM-DD
    * @param session a session object returned by ucla_getterms registrar query
    * @return 1 if date comes after the session's session_end date.
    *         0 if date is between the instruction start and session end dates.
    *         -1 if date1 comes before the instruction start date.
    */  
    function is_date_in_session($date, $session){
        //TODO: Check if this is a valid session?
        $session_start_date = $session[5];
        $session_end_date = $session[6];
        
        $date_vs_start = cmpdate($date, $session_start_date);
        $date_vs_end = cmpdate($date, $session_end_date);
        
        //If the date comes after start of session and before end of session
        //("after" and "before" include if date is the same as session dates)        
        if($date_vs_start >= 0 && $date_vs_end <= 0) {
            return 0;
        } else if($date_vs_start <= -1) {
            //If the date comes before the start of session
            return -1;
        } else { //if($date_vs_end == 1){
            //If the date comes after the end of session
            return 1;
        }
    }
    
   /**
    * Sets $CFG->currentterm to the current term based on the system date.
    * Note that this function heuristically sets the term, and may not be
    * accurate 100% of the time.
    */
    function init_currentterm(){
        global $CFG;
        $date = date('c'); //returns string of format 2004-02-12T15:19:21+00:00
        $year = substr($date, 2, 2); 
        
        //Figure out what quarter it is based on the month.
        $month = intval(substr($date, 5, 2));
        if($month <= 0 || $month > 12) {
            debugging("Invalid system date month: ".$month);
        } else if($month <= 3){
            $CFG->currentterm = $year."W";
        } else if($month <= 6) {
            $CFG->currentterm = $year."S";   
        } else if($month <= 9) {
            $CFG->currentterm = $year."1";
        } else {//if($month <= 12) 
            $CFG->currentterm = $year."F";
        }        
    }
    
   /**
    * Parses the object returned by a ucla_getterms registrar query 
    * (which is an array of session objects for the term),
    * and returns an array of the regular sessions (it's session variable is 
    * either RG, 6A, 6C, or 8A, which are the only ones we care about)
    * within the object.
    * 
    * @param query object $query_obj the object returned by the query.
    * @return an array of "regular" session objects.
    * 
    * Notes: a session object contains the following values:
    * (the only relevant values are 0,3,5,6,7).
    *  format: [Index] => Example value (what the value represents)
    *         [0] => 09W (term) 
    *         [1] => 2009-01-05 00:00:00.000 (term_start) 
    *         [2] => 2009-03-28 00:00:00.000  (term_end) 
    *         [3] => RG (session) 
    *         [4] =>  (session_name, currently blank) 
    *         [5] => 2009-01-05 00:00:00.000 (session_start) 
    *         [6] => 2009-03-28 00:00:00.000 (session_end) 
    *         [7] => 2009-01-12 00:00:00.000 (instruction start) 
    */
    function find_regular_sessions($query_obj){
        
        $regular_sessions;
        
        foreach($query_obj as $session){
            
            //If the session is a "regular" session, add it to the list.
            if($session[3] == RG || $session[3] == S1 
                    || $session[3] == S2 || $session[3] == S3){
                $regular_sessions[$session[3]] = $session;
            }
        }
        
        return $regular_sessions;
    }

   /**
    * @param date string that starts with the format YYYY-MM-DD
    * @return a date object with indices year,month,day.
    * Date['year'] = YYYY
    * Date['month'] = MM
    * Date['day'] = DD
    */
    function parse_date($date){
        
        $date_obj['year'] = intval(substr($date, 0, 4));
        $date_obj['month'] = intval(substr($date, 5, 2));        
        $date_obj['day'] = intval(substr($date, 8, 2));    
        return $date_obj;
    }    

   /**
    * Compares 2 dates and returns which one is earlier.
    * @param date1, date2  strings that start with the format YYYY-MM-DD
    * @return 1 if date1 comes after date2.
    *         0 if date1 is the same as date2
    *         -1 if date1 comes before date2.
    */   
    function cmp_dates($date1, $date2){
        $date1_obj = parse_date($date1);
        $date2_obj = parse_date($date2);
        
        if($date1_obj['year'] > $date2_obj['year']) { 
            return 1; 
        } else if($date1_obj['year'] < $date2_obj['year']) { 
            return -1; 
        } else { //$date1 year == $date2 year            
            
            if($date1_obj['month'] > $date2_obj['month']){
                return 1;
            } else if($date1_obj['month'] < $date2_obj['month']){
                return -1;
            } else { //$date1_month == $date2_month
                               
                if($date1_obj['day'] > $date2_obj['day']){
                    return 1;
                } else if($date1_obj['day'] < $date2_obj['day']){
                    return -1;
                } else{
                    return 0;
                }
            }     
        }   
    }    
    
}

//EOF

