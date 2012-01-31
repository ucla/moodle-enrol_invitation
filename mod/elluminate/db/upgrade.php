<?php // $Id: upgrade.php,v 1.6 2009-06-05 20:12:38 jfilip Exp $

/**
 * Database upgrade code.
 *
 * @version $Id: upgrade.php,v 1.6 2009-06-05 20:12:38 jfilip Exp $
 * @author Justin Filip <jfilip@remote-learner.ca>
 * @author Remote Learner - http://www.remote-learner.net/
 */

    function xmldb_elluminate_upgrade($oldversion = 0) {
        global $CFG, $THEME, $DB;

        $result = true;		
        if ($oldversion < 2006062102) {
        /// This should not be necessary but it's included just in case.
            $result = install_from_xmldb_file($CFG->dirroot . '/mod/elluminate/db/install.xml');
        }

        if ($result && $oldversion < 2009090801) {                                              
            //updates to the elluminate table
            $elluminate_table = new XMLDBTable('elluminate');
	        $field = new XMLDBField('meetinginit');
            $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NULL, false, false, false, '0', 'meetingid');
            $result = $result && add_field($elluminate_table, $field);
            
            $field = new XMLDBField('groupmode');
            $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NULL, false, false, false, '0', 'meetinginit');
            $result = $result && add_field($elluminate_table, $field);
            
			$field = new XMLDBField('groupid');
            $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NULL, false, false, false, '0', 'groupmode');
            $result = $result && add_field($elluminate_table, $field);                
            
            $field = new XMLDBField('groupparentid');
            $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NULL, false, false, false, '0', 'groupid');
            $result = $result && add_field($elluminate_table, $field);        
            
            $field = new XMLDBField('sessionname');
            $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, false, false, false, '0', 'groupparentid');
            $result = $result && add_field($elluminate_table, $field);        
            
            $field = new XMLDBField('customname');
            $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, false, false, false, '0', 'sessionname');
            $result = $result && add_field($elluminate_table, $field);
            
            $field = new XMLDBField('customdescription');
            $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, false, false, false, '0', 'customname');
            $result = $result && add_field($elluminate_table, $field);
            
            $field = new XMLDBField('timestart');
            $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NULL, false, false, false, '0', 'customdescription');
            $result = $result && add_field($elluminate_table, $field);
            
            $field = new XMLDBField('timeend');
            $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NULL, false, false, false, '0', 'timestart');
            $result = $result && add_field($elluminate_table, $field);
            
            $field = new XMLDBField('recordingmode');
            $field->setAttributes(XMLDB_TYPE_CHAR, '10', XMLDB_UNSIGNED, XMLDB_NULL, false, false, false, '0', 'timeend');
            $result = $result && add_field($elluminate_table, $field);
            
            $field = new XMLDBField('boundarytime');
            $field->setAttributes(XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NULL, false, false, false, '0', 'recordingmode');
            $result = $result && add_field($elluminate_table, $field);
	        
	        $field = new XMLDBField('boundarytimedisplay');
            $field->setAttributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NULL, false, false, false, '0', 'boundarytime');
            $result = $result && add_field($elluminate_table, $field);
            
            $field = new XMLDBField('chairlist');
            $field->setAttributes(XMLDB_TYPE_TEXT, 'medium', XMLDB_UNSIGNED, null, false, false, false, null, 'boundarytimedisplay');
            $result = $result && add_field($elluminate_table, $field);
            
            $field = new XMLDBField('nonchairlist');
            $field->setAttributes(XMLDB_TYPE_TEXT, 'big', XMLDB_UNSIGNED, null, false, false, false, null, 'chairlist');
            $result = $result && add_field($elluminate_table, $field);                      
	        
	        //Updates to the recordings table
	        $recordings_table = new XMLDBTable('elluminate_recordings');	       
			$field = new XMLDBField('description');
            $field->setAttributes(XMLDB_TYPE_CHAR, '255', XMLDB_UNSIGNED, null, false, false, false, '0', 'recordingid');
            $result = $result && add_field($recordings_table, $field);
            
            $field = new XMLDBField('visible');
            $field->setAttributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NULL, false, false, false, '0', 'description');
            $result = $result && add_field($recordings_table, $field);
            
            $field = new XMLDBField('groupvisible');
            $field->setAttributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NULL, false, false, false, '0', 'visible');
            $result = $result && add_field($recordings_table, $field);    
            	        	        
	        $table = new XMLDBTable('elluminate_session');
	        if (table_exists($table)) {
	            $status = drop_table($table, true, false);
	        }
	        
	        $table = new XMLDBTable('elluminate_users');
	        if (table_exists($table)) {
	            $status = drop_table($table, true, false);
	        }   

			$table = new XMLDBTable('elluminate_preloads');
	        if (table_exists($table)) {
	            $status = drop_table($table, true, false);
	        }
	        
			install_from_xmldb_file($CFG->dirroot . '/mod/elluminate/db/upgrade.xml');         
            
            $meetings = $DB->get_records('elluminate');
                        
            /// Modify all of the existing meetings, if any.
            if ($result && !empty($meetings)) {
                $timenow = time();
				
                foreach ($meetings as $meeting) {
                /// Update the meeting by storing values from the ELM server in the local DB.
                    if (!$elmmeeting = elluminate_get_meeting_full_response($meeting->meetingid)) {
                        continue;
                    }					
					
                    $meeting->meetinginit = 2;
                    $meeting->groupmode = 0;
                    $meeting->groupid = 0;
                    $meeting->groupparentid = 0;                    
                    $meeting->sessionname = addslashes($meeting->name);
                    $meeting->timestart   = substr($elmmeeting->startTime, 0, -3);
                    $meeting->timeend     = substr($elmmeeting->endTime, 0, -3);
                    $meeting->chairlist     = $elmmeeting->chairList;
                    $meeting->nonchairlist  = $elmmeeting->nonChairList;                    
					$meeting->recordingmode = $elmmeeting->recordingModeType;					
					$meeting->boundarytime = $elmmeeting->boundaryTime;
					$meeting->boundarytimedisplay = 1;
					$meeting->customname = 0;
					$meeting->customdescription = 0;					
								                  
                    $DB->update_record('elluminate', $meeting);                                                                             
                }                      
        	}
        	                                            
	        $recordings = $DB->get_records('elluminate_recordings');    
	        if (!empty($recordings)) {		
	            foreach ($recordings as $recording) {               
	            	$urecording = new stdClass;
	            	$recording->description = '';
	                $recording->visible = '1';
	                $recording->groupvisible = '0';
					$DB->update_record('elluminate_recordings', $urecording);
	            }
	        }
        }

		if ($oldversion < 2010062500) {
			
			/*
			 * This is put in place to account for Elluminate Sessions that were created using
			 * the 1.0 and 1.1 bridge which do not contain group sessions, however if the course
			 * has either seperate or visible groups set as it's default the 1.6 adapter will attempt
			 * to convert it to a group session which is bad.  We have to force the
			 * group mode of the course_module to be zero which means no groups.
			 */
			if($oldversion <= 2009020501) {
				$module = $DB->get_record('modules', array('name'=>'elluminate'));    
    			$course_modules = $DB->get_records('course_modules', 'module', $module->id);

				foreach ($course_modules as $course_module) {
					$course_module->groupmode = 0;
					$DB->update_record('course_modules',$course_module);
				}
			}
						
			$table = new XMLDBTable('elluminate');
			
			$field = new XMLDBField('sessiontype');
            $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, false, false, false, '0', 'creator');
            $result = $result && add_field($table, $field);
            
            $field = new XMLDBField('groupingid');
            $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, false, false, false, '0', 'sessiontype');
            $result = $result && add_field($table, $field);
            
            $meetings = $DB->get_records('elluminate');
            
            foreach ($meetings as $meeting) {
            	$meeting->groupingid = 0;            	
            	if($meeting->private == true) {
            		$meeting->sessiontype = 1;
            	}            	
            	if($meeting->groupmode > 0) {
            		$meeting->sessiontype = 2;
            	}   
            	
            	$DB->update_record('elluminate', $meeting);      		
            }
            
            $field = new XMLDBField('private');
            drop_field($table, $field);         
            
            $recordings_table = new XMLDBTable('elluminate_recordings');
			$size_field = new XMLDBField('recordingsize');
            $size_field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NULL, false, false, false, '0', 'description');
            $result = $result && add_field($recordings_table, $size_field);               
            
			$recordings = $DB->get_records('elluminate_recordings');    		    
		    foreach($recordings as $recording) {
		    	$full_recordings = elluminate_list_recordings($recording->meetingid);
		    	foreach($full_recordings as $full_recording) {
		    		if($full_recording->recordingid == $recording->recordingid) {
		    			$recording->recordingsize = $full_recording->size;
		    			$DB->update_record('elluminate_recordings', $recording);	
		    		}
		    	}
		    }
            
		}
		
        return $result;
    }

?>
