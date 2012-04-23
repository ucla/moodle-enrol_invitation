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
    
    public function init() {
        $this->title = get_string('pluginname', 'block_ucla_weeksdisplay');
    }
    
    /*
     * Returns the current week's display string
     */
    public function get_raw_content() {
        self::set_current_week_display(date('c'));
        return get_config('local_ucla', 'current_week_display');
    }
    
    public function cron() {
        self::set_current_week_display(date('c'));
    }
    
   /**
    * Sets the current_week_display config variable based on the given date
    * 
    * @param date string that starts with the format YYYY-MM-DD that is the
    * date associated with the desired display string.
    */     
    public static function set_current_week_display($date) {
        //Include registrar files.
        ucla_require_registrar();
        global $CFG;

        //If the current term is not valid, heuristically initialize it.      
        if(isset($CFG->currentterm) == false 
                || ucla_validator('term', $CFG->currentterm) == false) {
            self::init_currentterm(date('c'));
        }    

        $current_term = $CFG->currentterm;
        $query_result = registrar_query::run_registrar_query(
                'ucla_getterms', array($current_term), true);       
        //Compare valid queries with the system date
        $is_date_in_sessions = 
                self::find_date_in_sessions($date,$query_result);
        
        if($is_date_in_sessions == NULL){
            debugging('Registrar query did not return any valid sessions');
            return;
        } else if($is_date_in_sessions == -1) {
            //If the date is in a previous term, go backwards until a term is found.
            $prev_term = self::get_prev_term($current_term);
            while(1) {
                
                $query_result = registrar_query::run_registrar_query(
                        'ucla_getterms', array($prev_term), true);

                $is_date_in_sessions = 
                        self::find_date_in_sessions($date,$query_result);
                
                if($is_date_in_sessions == NULL) {
                    debugging('Registrar query did not return any valid sessions');
                    return;
                } else if($is_date_in_sessions == 1) {
                //If the date is between terms, return the string for the later term
                    $query_result = registrar_query::run_registrar_query(
                            'ucla_getterms', array(self::get_next_term($prev_term)), true);  
                    //Workaround because of limitations in find date in sessions
                    $regular_sessions = self::find_regular_sessions($query_result);

                    self::set_term_configs(self::get_next_term($prev_term));
                    self::set_current_week_display_config($date, $regular_sessions);
                    break;                            
                } else if($is_date_in_sessions != -1){
                    ////if is_date_in_sessions returned an array 
                    self::set_current_week_display_config($date, $is_date_in_sessions);
                    break;
                }         
                //else if ($is_date_in_sessions == -1)
                $prev_term = self::get_prev_term($prev_term);
            }       
        }  else if($is_date_in_sessions == 1) {
            //If the date is in a future term, keep on going forward until a term is found.
            $next_term = self::get_next_term($current_term);
            while(1){
                //error checking for if registrar query reaches forever?
                $query_result = registrar_query::run_registrar_query(
                        'ucla_getterms', array($next_term), true);
               
                $is_date_in_sessions = 
                        self::find_date_in_sessions($date,$query_result);
                
                if($is_date_in_sessions == NULL) {
                    debugging('Registrar query did not return any valid sessions');
                    return;
                } else if($is_date_in_sessions == -1) {
                    //If the date is between terms, return the string for the later term
                    self::set_term_configs($next_term);
                    //Workaround because of limitations in find date in sessions
                    $regular_sessions = self::find_regular_sessions($query_result);
                    self::set_current_week_display_config($date, $regular_sessions);
                    break;
                } else if($is_date_in_sessions != 1) {
                    //if is_date_in_sessions returned an array 
                    self::set_current_week_display_config($date, $is_date_in_sessions);
                    break;
                }
                
                //else if ($is_date_in_sessions == 1)
                $next_term = self::get_next_term($next_term);
            }
        } else { // is_date_in_session is an array of matching query results       
            self::set_current_week_display_config($date, $is_date_in_sessions);
        }
    }

    /*
     * Sets the current week display config variable associated with the date 
     * and an array of query results that are supposed to be displayed with that date.
     * 
     * @param date string of the format YYYY-MM-DD.
     * @param query_result an array of session objects that the date falls under.
     * 
     */
    public static function set_current_week_display_config($date, $query_result){
        $display_string = self::get_current_week_display_string($date, $query_result);
        set_config('current_week_display', $display_string, 'local_ucla');
    }
    
    /*
     * To be called whenever the current_term needs to be set.
     * Sets the configuration variables associated with the current term,
     * including the ones used to 
     * run course requestor/creator equal to a list of comma delimited terms,
     * including the start_term and the 3 terms after it.
     */
    public static function set_term_configs($current_term){
        //Generate the term list string
        $term_string = NULL;
        $term_string.=$current_term;
        $next_term = self::get_next_term($current_term);
        for($i = 0; $i < 3; $i++){
            $term_string.=', '.$next_term;
            $next_term = self::get_next_term($next_term);
        }
        
        set_config('currentterm', $current_term);
        set_config('active_terms', $term_string, 'local_ucla');
        set_config('terms', $term_string, 'tool_uclacourserequestor');
        set_config('terms', $term_string, 'tool_uclacoursecreator');
     
    }
   /**
    * Returns the current_week_display string associated with the date and sessions.
    * 
    * @param date string that starts with the format YYYY-MM-DD that has to be 
    * either within the session start/end dates. 
    * Exception: A date before a sessions startdate can be passed into this function
    * as well, in which case it will just display <Quarter> <Year>.
    * @param sessions an array of session objects 
    *       returned by ucla_getterms registrar query.
    *       The only sessions that will be parsed are the ones with terms 'RG', '8A', '6C'.
    *       THIS FUNCTION ASSUMES THAT THERE WILL BE EITHER ONLY ONE SESSION IN
    *       THE ARRAY, OR AN ARRAY CONTAINING ONE 8A SESSION AND 1 6C SESSION.
    *       If the array contains both an 8A session and a 6C session, then the
    *       code assumes that the date is within both sessions.
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
    public static function get_current_week_display_string($date, $sessions) {
        //Filter out sessions that aren't of term RG/8A/6C 
        $regular_sessions = self::find_regular_sessions($sessions);          
        usort($regular_sessions, 'self::cmp_sessions');
        
        //Handles special case where the 2 summer sessions overlap with the date.
        if(isset($regular_sessions[1]) 
               && $regular_sessions[0]['session'] == '8A' && $regular_sessions[1]['session'] == '6C') {
            $week_number0 = self::get_week($date, $regular_sessions[0]);          
            $week_number1 = self::get_week($date, $regular_sessions[1]);     
                return ucla_term_to_text($regular_sessions[0]['term'], 'A').', Week '
                       . $week_number0 . ' / Session C, Week ' . $week_number1;                                  
        } else {
            //Handles cases that only have a single session.
            
            $week_number = self::get_week($date, $regular_sessions[0]);
            //Summer sessions
            if($regular_sessions[0]['session'] == '8A' || $regular_sessions[0]['session'] == '6C') {
                if($week_number >= 0 && $week_number < 11) {       
                    //Return the string with the correct session.
                    return ucla_term_to_text($regular_sessions[0]['term'], $regular_sessions[0]['session'][1]).', Week '
                            . $week_number;                      
                } else { 
                    return ucla_term_to_text($regular_sessions[0]['term'], $regular_sessions[0]['session'][1]);
                }                
            } else { //Regular sessions
                 if($week_number >= 0 && $week_number < 11) {       
                    return ucla_term_to_text($regular_sessions[0]['term']).' - Week '
                            . $week_number;                      
                } else if($week_number == 11) {       
                    return ucla_term_to_text($regular_sessions[0]['term']).' - Finals Week';                      
                } else { //If the date is before this term's start date.
                    return ucla_term_to_text($regular_sessions[0]['term']);
                }
            }
        }  
    }

   /**
    * Returns the week of the session that the date is in.
    * @param date string that starts with the format YYYY-MM-DD
    * @param session a session object returned by the get_terms registrar query
    * @return an int representing the week that the date is in.
    * -1 if the date is not within the current week.
    */    
    public static function get_week($date, $session) {
        $ses_start_date = $session['session_start'];
        $ses_end_date = $session['session_end'];
        $instr_start_date = $session['instruction_start'];       
        
        $date_vs_ses_start = self::find_earlier_date($date, $ses_start_date);
        $date_vs_ses_end = self::find_earlier_date($date, $ses_end_date);       
        $date_vs_instr_start 
            = self::find_earlier_date($date, $instr_start_date);
        $ses_start_vs_instr_start = self::find_earlier_date($ses_start_date, $instr_start_date);  
        
        //If the date is in Week 0.
        if($date_vs_ses_start >= 0 && $date_vs_instr_start < 0) {
            return 0;
        } else if($date_vs_instr_start >= 0 && $date_vs_ses_end <= 0) {
            // If the date is in Week 1 - Finals Week    
            
            //Week 1 always starts the first monday including or after session start date.
            $unix_ses_start_date = strtotime($ses_start_date);
            $first_day_of_first_week = date('z', $unix_ses_start_date);
      
            switch (self::get_dayofweek($ses_start_date)) {
                // <editor-fold defaultstate="collapsed" desc="Find the first day of the week">                      
                case 'Mon':
                    //Summer session's week 1 starts at the session_start/
                    //instr_start date, which should be equal if its summer.                    
                    if ($ses_start_vs_instr_start != 0) {
                        $first_day_of_first_week += 7;
                    }
                    break;
                case 'Tue':
                    $first_day_of_first_week += 6;
                    break;
                case 'Wed':
                    $first_day_of_first_week += 5;
                    break;
                case 'Thu':
                    $first_day_of_first_week += 4;
                    break;
                case 'Fri':
                    $first_day_of_first_week += 3;
                    break;
                case 'Sat':
                    $first_day_of_first_week += 2;
                    break;
                case 'Sun':
                    $first_day_of_first_week += 1;
                    break;
                // </editor-fold>                
            }
            
             $unix_date = strtotime($date);
            //Find the number of weeks elapsed from the first day to the current day.
            return floor( (date('z', $unix_date) - $first_day_of_first_week) / 7 ) + 1;                    
        } else { //Date is not within the session dates.
            return -1;
        }                
    }
    
   /**
    * Returns whether or not the date is within the dession.
    * @param date string that starts with the format YYYY-MM-DD
    * @param sessions an array of session objects returned by ucla_getterms
    * registrar query
    * @return 1 if date comes after all session's session_end date.
    *         an array of sessions that the date is within
    *            if date is between the instruction start and session end dates.
    *          if the date is inbetween two sessions, returns the next session
    *           that begins after the date.
    *         -1 if date1 comes before all session's instruction start date.
    */  
    public static function find_date_in_sessions($date, $sessions) {
        $return_sessions = NULL;
        $regular_sessions = self::find_regular_sessions($sessions);
        //Sort the sessions from earliest to latest.
        usort($regular_sessions, 'self::cmp_sessions');     
                
        for($i = 0; $i < count($regular_sessions); $i++) {
            $session = $regular_sessions[$i];

            $date_vs_start = self::find_earlier_date($date, $session["session_start"]);
            $date_vs_end = self::find_earlier_date($date, $session["session_end"]);

            if($date_vs_start <= -1 && $i == 0) {
                //If the date comes before the start of the earliest session
                return -1;
            } else if($date_vs_start <= -1) {
                //If the date comes before the start of a session (this implicitly
                //also means the date comes after the end of the session before this
                //if the date has not been found in a previous session.)
                if(isset($return_sessions) == false){
                    $return_sessions[] = $regular_sessions[$i];
                }
                break; //No need to search rest because dates are chronologically sorted.
            } else if($date_vs_start >= 0 && $date_vs_end <= 0) {
                //If the date comes after start of session and before end of session     
                $return_sessions[] = $regular_sessions[$i];
            } else if($date_vs_end == 1 && $i == count($regular_sessions) - 1) {
                //If the date comes after the end of the last session
                return 1;
            }
        }

        return $return_sessions;
    }

   /**
    * Parses the object returned by a ucla_getterms registrar query 
    * (which is an array of session objects for the term),
    * and returns an array of the regular sessions (it's session variable is 
    * either RG, 6A, 6C, or 8A, which are the only ones we care about)
    * within the object.
    * 
    * @param query object $query_obj the object returned by the query.
    * @return an array of 'regular' session objects.
    * 
    */
    public static function find_regular_sessions($query_obj) {  
        //Add checks to make sure the query object is correct?
        $regular_sessions = NULL;
        foreach($query_obj as $session){

            //If the session is a 'regular' session, add it to the list.
            if($session['session'] == 'RG' || $session['session'] == '8A' 
                    || $session['session'] == '6C') {
                $regular_sessions[] = $session;
            }
        }

        return $regular_sessions;
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
        if($month <= 0 || $month > 12) {
            debugging('Invalid system date month: '.$month);
        } else if($month <= 3) {
            set_config('currentterm', $year.'W');
        } else if($month <= 6) {
            set_config('currentterm', $year.'S');   
        } else if($month <= 9) {
            set_config('currentterm', $year.'1');
        } else {//if($month <= 12) 
            set_config('currentterm', $year.'F');
        }        
    }
    
   /**
    * Takes in a UCLA term (Ex: 11F) and returns the term after it.
    * 
    * @param current_term a valid term string (Ex: '11F')
    * @return the term after the current term.
    */       
    public static function get_next_term($current_term) {
        
        $year = intval(substr($current_term,0 , 2));
        $quarter = $current_term[2];

        switch($quarter) {
            case 'F':
                $next_year = ($year == 99) ? '00' : sprintf('%02d', intval($year)+1);
                return $next_year.'W';
            case 'W':   
                return $year.'S';
            case 'S':
                return $year.'1';
            case '1':
                return $year.'F';
            default:
                debugging("Invalid term:".$current_term);
                return NULL;
        }
    }

   /**
    * Takes in a UCLA term (Ex: 11F) and returns the term before it.
    * 
    * @param current_term a valid term string (Ex: '11F')
    * @return the term after the current term.
    */       
    public static function get_prev_term($current_term) {
        $year = intval(substr($current_term,0 , 2));
        $quarter = $current_term[2];
        switch($quarter){
            case 'F':
                return $year.'1';
            case 'W':             
                $prev_year = ($year == 0) ? '99' : sprintf('%02d', intval($year)-1);
                return $prev_year.'F';
            case 'S': 
                return $year.'W';
            case '1':
                return $year.'S';
            default:
                debugging("Invalid term: ".$current_term);
                return NULL;                
        }
    } 
    
   /**
    * Cmp function for sorting find_regular_sessions. Returns the session with 
    * the earliest session start date.
    * 
    * @param session1, session2  session objects returned by the registrar query
    * @return positive number (number of days) if session1 comes before session2.
    *         0 if date1 is the same as date2
    *        negative number if session1 comes before session2
    */ 
    public static function cmp_sessions($session1, $session2) {                
        return self::cmp_dates($session1['session_end'],$session2['session_end']);
    }      
    
   /**
    * Compares 2 dates and returns which one is earlier and by how many days.
    * 
    * @param date1, date2  strings that start with the format YYYY-MM-DD
    * @return a positive number denoting how many days date1 comes after date2.
    *         0 if date1 is the same as date2
    *         a negative number denoting how many days date1 comes before date2.
    */   
    public static function cmp_dates($date1, $date2) {
        
        $unix_date1 = new DateTime($date1);
        $unix_date2 = new DateTime($date2);
        $interval = $unix_date1->diff($unix_date2);

        if($unix_date1 <  $unix_date2){
            return $interval->days * -1;
        } else {
            return $interval->days;
        }
    }    
    
   /**
    * Determines whether or not the input year is a leap year.
    * 
    * @param date a year of the format YYYY Ex: 2005.
    * @return true if the year is a leap year
    *         false if the year is not a leap year
    */    
    public static function is_leap_year($year) {
        return date('L', strtotime($year.'-01-01')) ? true : false;
    }
    
   /**
    * Compares 2 dates and returns which one is earlier.
    * 
    * @param date1, date2  strings that start with the format YYYY-MM-DD
    * @return 1 if date1 comes after date2.
    *         0 if date1 is the same as date2
    *         -1 if date1 comes before date2.
    */      
    public static function find_earlier_date($date1, $date2) {   
        $unix_date1 = strtotime($date1);
        $unix_date2 = strtotime($date2); 
          
        if($unix_date1 > $unix_date2) { 
            return 1; 
        } else if($unix_date1 < $unix_date2) { 
            return -1; 
        } else { //$date1 year == $date2 year            
                return 0;
        }     
     } 
     
   /**
    * Returns whether or not the date is within the dession.
    * 
    * @param date string that starts with the format YYYY-MM-DD
    * @return a string that represents the day of the week associated with date.
    *  (Monday, Tuesday, etc.)
    */     
    public static function get_dayofweek($date) {
        return date('D', strtotime($date));
    }
}

//EOF
