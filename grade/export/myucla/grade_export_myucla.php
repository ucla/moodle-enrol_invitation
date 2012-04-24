<?php 

require_once($CFG->dirroot.'/grade/export/lib.php');
require_once($CFG->dirroot.'/local/ucla/lib.php'); //Uses the ucla_validator function in order to determine whether or not a UID is valid

class grade_export_myucla extends grade_export {

    public $plugin = 'myucla';
    public $delim = ",";

    /**
     * Constructor should set up all the private variables ready to be pulled
     * @param object $course
     * @param int $groupid id of selected group, 0 means all
     * @param string $itemlist comma separated list of item ids, empty means all
     * @param boolean $export_feedback
     * @param boolean $export_letters
     * @param string $filetype
     * @note Exporting as letters will lead to data loss if that exported set it re-imported.
     * @note If no grade items selected, only UID and name appear- different than parent
     */
    function grade_export_myucla($course, $groupid=0, $itemlist='', $export_feedback=false, $updatedgradesonly = false, $displaytype = GRADE_DISPLAY_TYPE_REAL, $decimalpoints = 2, $filetype = 1) {
        parent::grade_export($course, $groupid, $itemlist, $export_feedback, $updatedgradesonly, $displaytype, $decimalpoints);
        if (empty($itemlist)) {
            $this->columns = array();
        }
        
        if ($filetype === "txt") {
            $this->set_delimiter("\t");
        }
        else {
            $this->set_delimiter(',');
        }
    }
    
    /**
     * Sends the course total (final grades) as a text (tab-delimited) or CSV file
	 *
	 * @return none
     */
    function print_grades() {
        global $CFG;

        $export_tracking = $this->track_exports();

        $strgrades = get_string('grades');
        /// Print header to force download
        @header('Cache-Control: private, must-revalidate, pre-check=0, post-check=0, max-age=0');
        @header('Expires: '. gmdate('D, d M Y H:i:s', 0) .' GMT');
        @header('Pragma: no-cache');
        header("Content-Type: application/download\n");
        $downloadfilename = clean_filename("{$this->course->shortname} $strgrades");
        
        if ($this->get_delimiter() === ',') {
            $ext = 'csv';
        }
        else {
            $ext = 'txt';
        }
        header("Content-Disposition: attachment; filename=\"$downloadfilename.$ext\"");

        $coursetotal = get_string("coursetotal", "grades");       
        $gui = new graded_users_iterator($this->course, $this->columns, $this->groupid);
        $gui->init();
        $lines = array();
        while ($userdata = $gui->next_user()) {
            $user = $userdata->user;
            // if (empty($user->idnumber)) {   // Not sure why this was here, ccommented out for MDL-13722
            //     continue;
            // }
            
            $gradeupdated = false; // if no grade is update at all for this user, do not display this row
            $rowstr = '';
            $delim = $this->delim;  //shorthand
            foreach ($this->columns as $itemid=>$grade_item) {
                $gradetxt = $this->format_grade($userdata->grades[$itemid]);
                
                // get the status of this grade, and put it through track to get the status
                $g = new grade_export_update_buffer();
                $grade_grade = new grade_grade(array('itemid'=>$itemid, 'userid'=>$user->id));
                $status = $g->track($grade_grade);

                if ($this->updatedgradesonly && ($status == 'nochange' || $status == 'unknown')) {
                    $rowstr .= $delim.$this->fix_delims(get_string('unchangedgrade', 'grades'));
                } else {
                    $rowstr .= $delim.$this->fix_delims($gradetxt);
                    $gradeupdated = true;
                }
                
                //Remark- Leave it blank, since can't be added in Moodle
                if ($grade_item->get_name() === $coursetotal) {
                    $rowstr .= $delim;
                }
                
                //Feedback is optional, delimiter is required
                $rowstr .= $delim;
                if ($this->export_feedback) {
                    $feedback_str = $this->fix_delims($this->format_feedback($userdata->feedbacks[$itemid])); 
                    //Replace all newlines and carriage returns in the string with whitespace 
                    //to prevent weird formatting issues. Replace \r\n with a single whitespace.
                    $feedback_str = str_replace(array("\r\n", "\n", "\r"), ' ', $feedback_str);
                    $rowstr .= $feedback_str;
                }
            }

            // if we are requesting updated grades only, we are not interested in this user at all            
            if (!$gradeupdated && $this->updatedgradesonly) {
                continue; 
            }

            //Don't use lang file for lastname, firstname.  This is tied to MyUCLA, not Moodle
            $output = ''; 
            $output .= $user->idnumber.$delim.$this->fix_delims("$user->lastname, $user->firstname");           
            $output .= $rowstr;
            $output .= "\n";

            //Check for valid UID's
            if (isset($user->idnumber) && isset($lines[$user->idnumber])) {
                //Duplicate ID number
                //Delete the first entry too, it's not unique either
                unset($lines[$user->idnumber]);
            }
            else if (isset($user->idnumber) && $user->idnumber != '') { 
                $lines[$user->idnumber] = $output;
            }
            //else missing ID number
        }

		ob_start();
		ksort($lines, SORT_NUMERIC);
		foreach ($lines as $line) {
			echo $line;
		}
		ob_end_flush();
			
		exit;
    }
    	 
   /**
     * Returns an html table with it's column header row filled out in the format
     * specified for displaying a preview.
     * Meant to be used with the display_preview function.
     * @return html_table
     */    
    function init_preview_table_with_headers(){
        //Initialize the table
        $preview_rows_table = new html_table();
        $preview_rows_table->attributes = array('class' => 'gradeexportpreview');
        
        //Generate the table's column headers based on the number of grade items.
        $preview_rows_table->head = array( get_string("idnumber"),  get_string("name"));
        foreach ($this->columns as $grade_item) {

            $grade_header = new html_table_cell($this->format_column_name($grade_item));
            $grade_header->attributes['class'] = 'grade';
            $preview_rows_table->head[] = $grade_header;

            //Add a remark about final grade
            if ($grade_item->get_name() === get_string("coursetotal", "grades")) {
            $preview_rows_table->head[] = 'Remark';
            }
            /// add a column_feedback column
            if ($this->export_feedback) {
                $preview_rows_table->head[] = $this->format_column_name($grade_item, true);
            }
        }
        return $preview_rows_table;
    }
    
    /**
     * Prints preview of exported grades on screen as a feedback mechanism
     * @return none
     */	
    function display_preview() {
        //Override parent function in grade_export (see /moodle/grade/export/lib.php  
        global $OUTPUT; 
        echo $OUTPUT->heading(get_string('previewrows', 'grades'));   
        
        //Initialize the table

        $preview_rows_table = $this->init_preview_table_with_headers();
        
        //Parse all user data and sort them into the appropriate preview table accordingly.
        $valid_rows = array();
        $invalidids = array();
        $gui = new graded_users_iterator($this->course, $this->columns, $this->groupid);
        $gui->init();        
       //For each user
        while ($userdata = $gui->next_user()) {
            // Since we need to sort later, keep all rows (don't break early)
                        
            $user = $userdata->user;
            // if (empty($user->idnumber)) {   // Not sure why this was here, ccommented out for MDL-13722
            //     continue;
            // }
            
            $gradeupdated = false; // if no grade is update at all for this user, do not display this row
            //
            //Initialize the table row that represents this user's data.
            $table_row = new html_table_row();
            $table_row->cells[] = new html_table_cell($user->idnumber);
            $table_row->cells[] = new html_table_cell($user->lastname.", ".$user->firstname);    
            
            //Fill in the row's columns.
            foreach ($this->columns as $itemid=>$grade_item) {
                $gradetxt = $this->format_grade($userdata->grades[$itemid]);
                
                // get the status of this grade, and put it through track to get the status
                $g = new grade_export_update_buffer();
                $grade_grade = new grade_grade(array('itemid'=>$itemid, 'userid'=>$user->id));
                $status = $g->track($grade_grade);

                 //Set the grade cell  
                $table_grade_cell = new html_table_cell();
                if ($this->updatedgradesonly && ($status == 'nochange' || $status == 'unknown')) {
                    $table_grade_cell->text = get_string('unchangedgrade', 'grades');
                } else {
                    $table_grade_cell->text = $gradetxt;
                    $gradeupdated = true;
                }                
                
                //Real/percent should align-right, Letter grade should align left
                if ($this->displaytype != GRADE_DISPLAY_TYPE_LETTER) {
                    $table_grade_cell->attributes['class'] = 'grade';
                }
                //Table cell classes are initialized to '', so this else statement is implicitly executed.
                //Comment is here for reference.
                /*else {
                    $table_grade_cell->attributes['class'] = '';
                }*/
                
                $table_row->cells[] = $table_grade_cell; //Add the grade cell to the table row.
                
                //Add an empty cell for coursetotal.
                if ($grade_item->get_name() === get_string("coursetotal", "grades")) {
                     $table_row->cells[] = new html_table_cell();
                }
                
                //Feedback cell
                if ($this->export_feedback) {
                    $table_row->cells[] = new html_table_cell($this->format_feedback($userdata->feedbacks[$itemid]));
                }
            }

            // if we are requesting updated grades only, we are not interested in this user at all            
            if (!$gradeupdated && $this->updatedgradesonly) {
                continue; 
            }

            //If a user's UID is invalid, add it to the array of invalid ids.
            if ( !(ucla_validator('uid', $user->idnumber)) ) {
                $invalidids[] = $table_row;
            }
            //If a user's UID is already in the valid rows array, remove that user from the array.
            else if (isset($valid_rows[$user->idnumber])) {
                $invalidids[] = $table_row;

                //Delete the first entry, it's not unique either
                $invalidids[] = $valid_rows[$user->idnumber];
                unset($valid_rows[$user->idnumber]);
            }
            //If a user's UID is valid, add the user to the list of valid rows.
            else {
                $valid_rows[$user->idnumber] = $table_row;
            }
        }
              
        //Sort the list of valid table rows by user ID instead of name.
        ksort($valid_rows, SORT_NUMERIC);
        
        //Add the valid rows to the table, stopping once you've reached the limit of preview rows allowed.
        $i = 0;
        foreach ($valid_rows as $row) {
            //Use !== false, because 0 evaluates to false too
            if ($this->previewrows !== false and $this->previewrows <= $i) {
                //Limit to the number of preview rows
                break;
            }
            $preview_rows_table->data[] = $row;
            $i++;
        }
        echo html_writer::table($preview_rows_table); //Print out the entire preview rows table.
        
        $gui->close();

        //Check for duplicate ID numbers
        if (!empty($invalidids)) {
            $numduplicates = count($invalidids);

            echo $OUTPUT->heading(get_string('invalidids', 'gradeexport_myucla'));

            // Print out the number of students not listed due to not enough previewrows
            $OUTPUT->box_start('generalbox', 'notice');
            echo html_writer::start_tag('p').get_string('invalididsexplanation', 'gradeexport_myucla').html_writer::end_tag('p');
            //Use !== false, because 0 evaluates to false too
            if ($this->previewrows !== false) {
                //Sanity check- negatives mess up calculations!
                $previewrows = $this->previewrows >= 0 ? $this->previewrows : 0;

                $numskipped = ($numduplicates - $previewrows);
                if ($numskipped >= 1) {
                    echo html_writer::start_tag('p').$numskipped.' additional student';
                    if ($numskipped > 1) {
                        echo 's';   //Make it plural
                    }
                    echo ' not listed.';
                }
            }
            $OUTPUT->box_end();

            //Initialize the table
            $invalid_rows_table = $this->init_preview_table_with_headers();
            
            //Add the invalid rows to the table, stopping once you've reached the limit of preview rows allowed.
            ksort($invalidids, SORT_NUMERIC);
            for ($j = 0; $j < $numduplicates; ++$j) {
                if ($this->previewrows !== false and $this->previewrows <= $j) {
                    //Limit to the number of preview rows
                    break;
                }
                $invalid_rows_table->data[] = $invalidids[$j];
            }
            echo html_writer::table($invalid_rows_table); //Print students which were skipped
        }
    }
    
    /**
     * Init object based using data from form
     * @param object $formdata
     */
    function process_form($formdata) {
        //Overrides parent function
        parent::process_form($formdata);
        $this->filetype = $formdata->filetype;
    }

    /**
     * Returns array of parameters used by dump.php and export.php.
     * @return array
     */
    function get_export_params() {
        //Overrride parent function
        
        $params = parent::get_export_params();
        $params['filetype'] = $this->filetype;
        return $params;
    }
    
    /*
     * Returns the delimiter being used
     *
     * @return string delimiter
     */
    function get_delimiter() {
        return $this->delim;
    }
    
    /*
     * Sets the delimiter being used
     * The delimiter is only changed if the new delimiter is valid
     *
     * @return string The new value of the delimiter
     */
    function set_delimiter($newdelim) {
        if ($newdelim === "\t" || $newdelim === ',') {
            $this->delim = $newdelim;
        }
        return $this->delim;
    }
    
    /*
     * Changes instances of the delimiter in field text with appropriate values
     *   Replaces tabs with 4 spaces
     *   Encloses strings with commas inside quotes
     */
    function fix_delims($string) {
        $findtext = $this->delim;
        $replacetext = $this->delim;
        if ($this->delim === "\t") {
            $replacetext = '    ';  //4 spaces
        }
        else if ($this->delim === ',') {
            if (strpos($string, $this->delim) === FALSE) {
                return $string;
            }
            else {
                $findtext =  '"';   //double quote
                $replacetext = "'"; //single quote
                return '"'.str_replace($findtext, $replacetext, $string).'"';
            }
        }
        
        //Missing case!
        return str_replace($findtext, $replacetext, $string);
    }
}
