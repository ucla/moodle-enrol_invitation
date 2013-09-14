<?php

/// This page lists all the instances of qanda in a particular course
/// Replace qanda with the name of your module

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once("$CFG->libdir/rsslib.php");
require_once("$CFG->dirroot/course/lib.php");

$id = required_param('id', PARAM_INT);   // course

$PAGE->set_url('/mod/qanda/index.php', array('id' => $id));

if (!$course = $DB->get_record('course', array('id' => $id))) {
    print_error('invalidcourseid');
}

require_course_login($course);
$PAGE->set_pagelayout('incourse');
$context = context_course::instance($course->id);

add_to_log($course->id, "qanda", "view all", "index.php?id=$course->id", "");


/// Get all required strings

$strqandas = get_string("modulenameplural", "qanda");
$strqanda = get_string("modulename", "qanda");
$strrss = get_string("rss");


/// Print the header
$PAGE->navbar->add($strqandas, "index.php?id=$course->id");
$PAGE->set_title($strqandas);
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();

/// Get all the appropriate data

if (!$qandas = get_all_instances_in_course("qanda", $course)) {
    notice(get_string('thereareno', 'moodle', $strqandas), "../../course/view.php?id=$course->id");
    die;
}

$usesections = course_format_uses_sections($course->format);

/// Print the list of instances (your module will probably extend this)

$timenow = time();
$strsectionname = get_string('sectionname', 'format_' . $course->format);
$strname = get_string("name");
$strentries = get_string("entries", "qanda");

$table = new html_table();

if ($usesections) {
    $table->head = array($strsectionname, $strname, $strentries);
    $table->align = array("CENTER", "LEFT", "CENTER");
} else {
    $table->head = array($strname, $strentries);
    $table->align = array("LEFT", "CENTER");
}

if ($show_rss = (isset($CFG->enablerssfeeds) && isset($CFG->qanda_enablerssfeeds) &&
        $CFG->enablerssfeeds && $CFG->qanda_enablerssfeeds)) {
    $table->head[] = $strrss;
    $table->align[] = "CENTER";
}

$currentsection = "";

foreach ($qandas as $qanda) {
    if (!$qanda->visible && has_capability('moodle/course:viewhiddenactivities', $context)) {
        // Show dimmed if the mod is hidden.
        $link = "<a class=\"dimmed\" href=\"view.php?id=$qanda->coursemodule\">" . format_string($qanda->name, true) . "</a>";
    } else if ($qanda->visible) {
        // Show normal if the mod is visible.
        $link = "<a href=\"view.php?id=$qanda->coursemodule\">" . format_string($qanda->name, true) . "</a>";
    } else {
        // Don't show the qanda.
        continue;
    }
    $printsection = "";
    if ($usesections) {
        if ($qanda->section !== $currentsection) {
            if ($qanda->section) {
                $printsection = get_section_name($course, $qanda->section);
            }
            if ($currentsection !== "") {
                $table->data[] = 'hr';
            }
            $currentsection = $qanda->section;
        }
    }

    // TODO: count only approved if not allowed to see them

    $count = $DB->count_records_sql("SELECT COUNT(*) FROM {qanda_entries} WHERE (qandaid = ? OR sourceqandaid = ?)", array($qanda->id, $qanda->id));

    //If this qanda has RSS activated, calculate it
    if ($show_rss) {
        $rsslink = '';
        if ($qanda->rsstype and $qanda->rssarticles) {
            //Calculate the tolltip text
            $tooltiptext = get_string("rsssubscriberss", "qanda", format_string($qanda->name));
            if (!isloggedin()) {
                $userid = 0;
            } else {
                $userid = $USER->id;
            }
            //Get html code for RSS link
            $rsslink = rss_get_link($context->id, $userid, 'mod_qanda', $qanda->id, $tooltiptext);
        }
    }

    if ($usesections) {
        $linedata = array($printsection, $link, $count);
    } else {
        $linedata = array($link, $count);
    }

    if ($show_rss) {
        $linedata[] = $rsslink;
    }

    $table->data[] = $linedata;
}

echo "<br />";

echo html_writer::table($table);

/// Finish the page

echo $OUTPUT->footer();

