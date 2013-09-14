<?php 

require_once dirname(dirname(dirname(__FILE__))) . '/config.php';
require_once dirname(__FILE__) . '/lib.php';

$id = optional_param('id', 0, PARAM_INT);               

$PAGE->set_url('/mod/elluminate/index.php', array('id'=>$id));

if ($id) {
   if (! $course = $DB->get_record('course', array('id'=>$id))) {
      print_error(get_string('courseidincorrect', 'elluminate'));
   }
} else {
   if (! $course = get_site()) {
      print_error(get_string('toplevelnotfound', 'elluminate'));
   }
}

require_course_login($course);

/// Get all required strings
$strelluminates = get_string("modulenameplural", "elluminate");
$strelluminate  = get_string("modulename", "elluminate");

/// Print the header
if ($course->category) {
   $navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->";
}
$navigation = build_navigation($strelluminates);
print_header_simple($strelluminates, "", $navigation, "", "", true, '');

/// Get all the appropriate data
try {
    if (!$collaborateSessions = get_all_instances_in_course("elluminate", $course)) {
      notice(get_string('nomeetings', 'elluminate') , "../../course/view.php?id=$course->id");
      die;	
    }
} catch (Elluminate_Exception $e) {
	print_error(get_string($e->getUserMessage(),'elluminate'));
} catch (Exception $e) {
	print_error(get_string('user_error_processing','elluminate'));
}

$strname  = get_string("name");
$strweek  = get_string("week");
$strtopic  = get_string("topic");

$table = new html_table();
$attribs['style'] ="margin-left:auto; margin-right:auto; width:90%";
$table->attributes = $attribs;

if ($course->format == "weeks") {
   $table->head  = array ($strweek, $strname);
   $table->align = array ("center", "center");
} else if ($course->format == "topics") {
   $table->head  = array ($strtopic, $strname);
   $table->align = array ("center", "left", "left", "left");
} else {
   $table->head  = array ($strname);
   $table->align = array ("left", "left", "left");
}

foreach ($collaborateSessions as $session) {

    if (!$session->visible) {
      $link = "<a class=\"dimmed\" href=\"view.php?id=$session->coursemodule\">" . format_string($session->name) . "</a>";
   } else {
      $link = "<a href=\"view.php?id=$session->coursemodule\">" . format_string($session->name) . "</a>";
   }

   if ($course->format == "weeks" or $course->format == "topics") {
      $table->data[] = array ($session->section, $link);
   } else {
      $table->data[] = array ($link);
   }
}

echo $OUTPUT->box_start('generalbox', '');
echo html_writer::table($table);
echo $OUTPUT->box_end();

$OUTPUT->footer($course);

$pageUrl = "index.php?id=" . $course->id;
Elluminate_Audit_Log::log(Elluminate_Audit_Constants::SESSION_VIEW_ALL, $pageUrl);
