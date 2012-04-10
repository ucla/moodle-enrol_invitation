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

class block_ucla_weeksdisplay extends block_base {
    
    /** Session code for a regular fall/winter/spring session. */
    const RG = 'RG'; 
     /** Session codes for the various regular summer sessions. */   
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
                get_current_week_display($system_date, $regular_sessions[RG]);
            }
            //
            else if($is_date_in_session < 0){
                $prev_term = get_prev_term($CFG->currentterm);
                while(1){
                    $query_result = registrar_query::run_registrar_query(
                            'ucla_getterms', array($prev_term));
                    $regular_sessions = find_regular_sessions($query_result);  
                    
                    if(isset($regular_sessions[RG])) {
                        $is_date_in_session = 
                                is_date_in_session($system_date,$regular_sessions[RG]);
                        if($is_date_in_session == 0){
                            get_current_week_display($system_date, $regular_sessions[RG]);
                            break;
                        } 
                        if($is_date_in_session > 0){
                            $query_result = registrar_query::run_registrar_query(
                                    'ucla_getterms', array(get_next_term($prev_term)));
                            $regular_sessions = find_regular_sessions($query_result);                              
                            get_current_week_display($system_date, $regular_sessions[RG]);
                            break;                            
                        }
                    }
                    $prev_term = get_prev_term($prev_term);
                }                
            }
            //If the date is in a future session
            else{ // $is_date_in_session > 0
                
                $next_term = get_next_term($CFG->currentterm);
                while(1){
                    $query_result = registrar_query::run_registrar_query(
                            'ucla_getterms', array($next_term));
                    $regular_sessions = find_regular_sessions($query_result);  
                    
                    if(isset($regular_sessions[RG])) {
                        $is_date_in_session = 
                                is_date_in_session($system_date,$regular_sessions[RG]);
                        if($is_date_in_session >= 0){
                            get_current_week_display($system_date, $regular_sessions[RG]);
                            break;
                        } 
                    }
                    $next_term = get_next_term($next_term);
                }
            }
        }

        
    }

   /**
    * Takes in a UCLA term (Ex: 11F) and returns the term after it.
    */       
    function get_next_term($current_term){
        $year = intval(substr($current_term,0 , 2));
        $quarter = $current_term[3];
        switch($quarter){
            case 'F':
                return $year."W";
            case 'W':
                $next_year = ($year == 99) ? "00" : sprintf("%02d", intval($year)+1);
                return $next_year."S";
            case 'S':
                return $year."1";
            case '1':
                return $year."F";
        }
    }

   /**
    * Takes in a UCLA term (Ex: 11F) and returns the term before it.
    */       
    function get_prev_term($current_term){
        $year = intval(substr($current_term,0 , 2));
        $quarter = $current_term[3];
        switch($quarter){
            case 'F':
                return $year."1";
            case 'W':                
                return $year."F";
            case 'S':
                $prev_year = ($year == 0) ? "99" : sprintf("%02d", intval($year)-1);
                return $prev_year."W";
            case '1':
                return $year."S";
        }
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
        $ses_start_date = $session[5];
        $ses_end_date = $session[6];
        $instr_start_date = $session[7];
        
        $unix_date = strtotime($date);
        $unix_ses_start_date = strtotime($session[5]);
        $unix_instr_start_date = strtotime($session[7]); 
        
        $date_vs_ses_start = find_earlier_date($date, $ses_start_date);
        $date_vs_ses_end = find_earlier_date($date, $ses_end_date);       
        $date_vs_instr_start 
            = find_earlier_date($date, $instruction_start_date);
        $ses_start_vs_instr_start  
            = find_earlier_date($session_start_date, $instruction_start_date);
         
        //If the date is in Week 0.
        if($date_vs_ses_start >= 0 && $date_vs_instr_start < 0){
            return ucla_term_to_text($session[0])." - Week 0";
        } else if($date_vs_instr_start >= 0 && $date_vs_ses_end <= 0){
            // If the date is in Week 1- Finals Week    
            //TODO: Summer sessions: ses start == instr start?
            
            //Week 1 always starts the first monday after session start date.
            //TODO: overflow stuff
            $first_day_of_first_week = date("z", $unix_ses_start_date);
            // <editor-fold defaultstate="collapsed" desc="Find first day of first week">
            switch (get_dayofweek($ses_start_date)) {
                case "Mon":
                    $first_day_of_first_week += 7;
                    break;
                case "Tue":
                    $first_day_of_first_week += 6;
                    break;
                case "Wed":
                    $first_day_of_first_week += 5;
                    break;
                case "Thu":
                    $first_day_of_first_week += 4;
                    break;
                case "Fri":
                    $first_day_of_first_week += 3;
                    break;
                case "Sat":
                    $first_day_of_first_week += 2;
                    break;
                case "Sun":
                    $first_day_of_first_week += 1;
                    break;
            }// </editor-fold>
            
            //Find the number of weeks elapsed from the first day to the current day.
            $weeks_from_first_day 
                = ((date("z", $unix_date) - $first_day_of_first_week) % 7) + 1;
            
            return ucla_term_to_text($session[0])." - Week ". $weeks_from_first_day;            
            
        } else{
            return ucla_term_to_text($session[0]);
        }
        
    }

   /**
    * Returns whether or not the date is within the dession.
    * @param date string that starts with the format YYYY-MM-DD
    * @return a string that represents the day of the week associated with date.
    *  (Monday, Tuesday, etc.)
    */     
    function get_dayofweek($date){
        $unix_date = strtotime($date);
        return date($unix_date, 'D');
    }
    
   /**
    * Returns whether or not the date is within the dession.
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
        
        $date_vs_start = find_earlier_date($date, $session_start_date);
        $date_vs_end = find_earlier_date($date, $session_end_date);
        
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
        //TODO: Test local scope stuff
        $regular_sessions = NULL;
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
    * Compares 2 dates and returns which one is earlier and by how many days.
    * @param date1, date2  strings that start with the format YYYY-MM-DD
    * @return a positive number denoting how many days date1 comes after date2.
    *         0 if date1 is the same as date2
    *         a negative number denoting how many days date1 comes before date2.
    */   
    function cmp_dates($date1, $date2){
        $unix_date1 = strtotime($date1);
        $unix_date2 = strtotime($date2);  
        
        $days_difference = 0; //The amount of days between date1 and date2. 
                            //Positive = date1 comes after date2
                            //Negative = date1 comes before date1.
        
        $earlier_date_result = find_earlier_date($date1, $date2);
        if($earlier_date_result == 0){
            return 0;
        } else if($earlier_date_result == -1){
            $earlier_date = $unix_date1;
            $later_date = $unix_date2;
        } else { // $earlier_date_result == 1
            $earlier_date = $unix_date2;
            $later_date = $unix_date1;            
        }
        
        $earlier_date_year = date('Y',$unix_date1);
        $later_date_year = date('Y',$unix_date2);        
        //Traverse years until the two years are equal.
        if($earlier_date_year < $later_date_year) { 
            //Round the earlier date to beginning of the next year to make calculations easier.
            $earlier_date_day_of_year = date('z',$unix_date1);
            $days_in_year = (date('L',$unix_date1)) ? 366 : 365;
            //+1 because the unix date day of year starts at 0.
            $days_difference += ($days_in_year - $earlier_date_day_of_year + 1);
            $earlier_date_year++;
            
            //Traverse whole years until you reach date2's year.
            while($earlier_date_year < $later_date_year){
                $days_in_year = (is_leap_year($earlier_date_year)) ? 366 : 365;
                $days_difference += ($days_in_year + 1);
                $earlier_date_year++;
            }
        } 
        //Add the number of days from the beginning of the year to the later date.
        $$later_date_year_day_of_year = date('z',$unix_date2);
        $days_in_year = (is_leap_year($earlier_date_year)) ? 366 : 365;
        $days_difference += ($days_in_year - $later_date_year + 1);
        
        //Return a negative number if date1 comes before date2.
        if($earlier_date_result == -1){ $days_difference *= -1;}
        
        return $days_difference;
    }    

    function is_leap_year($year){
        return ($year % 4) ? 1 : 0;
    }
    
   /**
    * Compares 2 dates and returns which one is earlier.
    * @param date1, date2  strings that start with the format YYYY-MM-DD
    * @return 1 if date1 comes after date2.
    *         0 if date1 is the same as date2
    *         -1 if date1 comes before date2.
    */      
    function find_earlier_date($date1, $date2){   
        $unix_date1 = strtotime($date1);
        $unix_date2 = strtotime($date2); 
        $unix_date1_year = date("Y", $unix_date1);
        $unix_date2_year = date("Y", $unix_date2);
        
        
        if($unix_date1_year > $unix_date2_year) { 
            return 1; 
        } else if($unix_date1_year < $unix_date2_year) { 
            return -1; 
        } else { //$date1 year == $date2 year            
            $unix_date1_day_in_year = date("z", $unix_date1);
            $unix_date2_day_in_year = date("z", $unix_date2);            
            if($unix_date1_day_in_year > $unix_date2_day_in_year){
                return 1;
            } else if($unix_date1_day_in_year < $unix_date2_day_in_year){
                return -1;
            } else { //$unix_date1_day_in_year == $unix_date2_day_in_year
                return 0;
            }
        }     
     }
    
    
}

//EOF

