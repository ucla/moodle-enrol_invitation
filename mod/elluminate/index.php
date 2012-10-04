<?php // $Id: index.php,v 1.1.2.2 2009/03/18 16:45:54 mchurch Exp $


    require_once dirname(dirname(dirname(__FILE__))) . '/config.php';
    require_once dirname(__FILE__) . '/lib.php';

    $id = optional_param('id', 0, PARAM_INT);                   // Course id

    if ($id) {
        if (! $course = $DB->get_record('course', array('id'=>$id))) {
            print_error("Course ID is incorrect");
        }
    } else {
        if (! $course = get_site()) {
            print_error("Could not find a top-level course!");
        }
    }

    require_course_login($course);

    // START UCLA MOD: CCLE-2966 - Replace Elluminate with Blackboard Web Conferencing
    $PAGE->set_url('/mod/elluminate/index.php', array('id'=>$id));
    // END UCLA MOD: CCLE-2966
    
    add_to_log($course->id, "elluminate", "view all", "index.php?id=$course->id", "");


/// Get all required strings

    $strelluminates = get_string("modulenameplural", "elluminate");
    $strelluminate  = get_string("modulename", "elluminate");


/// Print the header

    if ($course->category) {
        $navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->";
    }

/// Print header.
    $navigation = build_navigation($strelluminates);
    // START UCLA MOD: CCLE-2966 - Replace Elluminate with Blackboard Web Conferencing
    print_header_simple($strelluminates, $COURSE->fullname, $navigation, "", "", true, '');
    // END UCLA MOD: CCLE-2966
    

/// Get all the appropriate data

    if (! $elluminates = get_all_instances_in_course("elluminate", $course)) {
        notice("There are no Blackboard Collaborate meetings ", "../../course/view.php?id=$course->id");
        die;
    }

/// Print the list of instances (your module will probably extend this)

    $timenow = time();
    $strname  = get_string("name");
    $strweek  = get_string("week");
    $strtopic  = get_string("topic");
    $strsection  = get_string("section");
    
    $table = new html_table();
    if ($course->format == "weeks") {
        $table->head  = array ($strweek, $strname);
        $table->align = array ("center", "left");
    } else if ($course->format == "topics") {
        $table->head  = array ($strtopic, $strname);
        $table->align = array ("center", "left", "left", "left");
    } else {
        $table->head  = array ($strsection, $strname);
        $table->align = array ("center", "left", "left", "left");
    }

	$search =  array("@", "#", "$", "%", "^", "?", "&", "/", "\\", "'", ";", "\"", ",", ".", "<", ">","*");
	$replace = '';

    foreach ($elluminates as $elluminate) {
    	$name = $elluminate->name;
	    //$name = str_replace($search, $replace, stripslashes($elluminate->name));
	    $elluminate->name = stripslashes($elluminate->name);
		//if(($elluminate->groupmode == 0) || ($elluminate->creator == $USER->id) || groups_is_member($elluminate->groupid, $USER->id)) {
	        if (!$elluminate->visible) {
	            //Show dimmed if the mod is hidden
	            //$link = "<a class=\"dimmed\" href=\"view.php?id=$elluminate->coursemodule\">$elluminate->name</a>";
	            $link = "<a class=\"dimmed\" href=\"view.php?id=$elluminate->coursemodule\">$name</a>";
	        } else {
	            //Show normal if the mod is visible
	            //$link = "<a href=\"view.php?id=$elluminate->coursemodule\">$elluminate->name</a>";
	            $link = "<a href=\"view.php?id=$elluminate->coursemodule\">$name</a>";
	        }
	
                // START UCLA MOD: CCLE-2966 - Replace Elluminate with Blackboard Web Conferencing
//	        if ($course->format == "weeks" or $course->format == "topics") {
//	            $table->data[] = array ($elluminate->section, $link);
//	        } else {
//	            $table->data[] = array ($link);
//	        }
	        $table->data[] = array ($elluminate->section, $link);
                // END UCLA MOD: CCLE-2966
		//}
    }

    echo "<br />";

    // START UCLA MOD: CCLE-2966 - Replace Elluminate with Blackboard Web Conferencing
    //print_table($table);
    echo html_writer::table($table);
    // END UCLA MOD: CCLE-2966 
    
/// Finish the page

    // START UCLA MOD: CCLE-2966 - Replace Elluminate with Blackboard Web Conferencing
    //print_footer($course);
    $OUTPUT->footer();
    // END UCLA MOD: CCLE-2966 


