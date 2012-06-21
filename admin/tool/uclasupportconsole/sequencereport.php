<?php
require("../../../config.php");
require_once($CFG->libdir.'/adminlib.php');

admin_externalpage_setup('reportsupportconsole');
admin_externalpage_print_header();

require_login();
require_capability('moodle/site:viewreports', get_context_instance(CONTEXT_SYSTEM));

$badsections = array();
    //look up all course sections
    $sql = "SELECT id, sequence FROM {$CFG->prefix}course_sections WHERE CHAR_LENGTH(sequence)>0";
    $result = get_records_sql($sql);

    //use the first query's result to create (mod_id, section_id) pairs, 
    //then query the module table to find ones that don't match
    $sql2 = "SELECT * FROM {$CFG->prefix}course_modules WHERE section = CASE ";
    foreach ($result as $section){
        foreach(explode(",",$section->sequence) as $mod_id){
            $sql2.="WHEN id = $mod_id THEN NOT $section->id ";
					}
				}
    $sql2.= "END";
    $badsections = get_records_sql($sql2);
          
    
	//display the report
    print "<h1>Courses with bad sections:<br/>";
    if(!empty($badsections)){
        foreach($badsections as $badsection){
                print "<br/>courseid:".$badsection->course."<br>";
                print "sectionid:".$badsection->section."<br>";
	}
    }
?>
