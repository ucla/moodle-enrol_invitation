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

/**
 * Evaluation topics format for course display - NO layout tables, for 
 * accessibility, etc.
 *
 * A duplicate course format to enable the Moodle development team to evaluate
 * CSS for the multi-column layout in place of layout tables.
 * Less risk for the Moodle 1.6 beta release.
 *   1. Straight copy of topics/format.php
 *   2. Replace <table> and <td> with DIVs; inline styles.
 *   3. Reorder columns so that in linear view content is first then blocks;
 * styles to maintain original graphical (side by side) view.
 *
 * @copyright UCLA 2011 - Stolen from: &copy; 2006 The Open University 
 * @author N.D.Freear@open.ac.uk, and others.
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ucla
 * @subpackage format
 * @todo use html_writer
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/filelib.php');
require_once($CFG->libdir.'/completionlib.php');

$topic = optional_param('topic', -1, PARAM_INT);

// Determine which section to dipslay ( this maintains user history in the DB )
if ($topic != -1) {
    $displaysection = course_set_display($course->id, $topic);
} else {
    $displaysection = course_get_display($course->id);
}

$context = get_context_instance(CONTEXT_COURSE, $course->id);

// Figure out which section we are highlighting. 
if (($marker >=0) 
        && has_capability('moodle/course:setcurrentsection', $context) 
        && confirm_sesskey()) {
    $course->marker = $marker;
    $DB->set_field("course", "marker", $marker, array("id"=>$course->id));
}

// Cache all these get_string(), because you know, they're cached already...
$streditsummary   = get_string('editsummary');
$stradd           = get_string('add');
$stractivities    = get_string('activities');
$strshowalltopics = get_string('showalltopics');
$strtopic         = get_string('topic');
$strgroups        = get_string('groups');
$strgroupmy       = get_string('groupmy');
$editing          = $PAGE->user_is_editing();

if ($editing) {
    $strtopichide       = get_string('hidetopicfromothers');
    $strtopicshow       = get_string('showtopicfromothers');
    $strmarkthistopic   = get_string('markthistopic');
    $strmarkedthistopic = get_string('markedthistopic');
    $strmoveup          = get_string('moveup');
    $strmovedown        = get_string('movedown');
}

// Print the Your progress icon if the track completion is enabled
$completioninfo = new completion_info($course);
echo $completioninfo->display_help_icon();

// Display the top of the inside of the middle
echo $OUTPUT->heading($course->fullname, 2, 'headingblock header outline');

// Note, an ordered list would confuse - "1" could be the clipboard or summary.
echo html_writer::start_tag('ul', array('class' => 'topics'))."\n";

/// If currently moving a file then show the current clipboard
if (ismoving($course->id)) {
    $stractivityclipboard = 
        strip_tags(get_string(
            'activityclipboard', '', $USER->activitycopyname
        ));

    $strcancel = get_string('cancel');

    $modurl = new moodle_url('mod.php', array(
            'cancelcopy' => 'true',
            'sesskey' => sesskey()
        ));

    $modlink = html_writer::link($modurl, $strcancel);
    
    echo html_writer::start_tag('li', array('class' => 'clipboard'));
    echo $stractivityclipboard.'&nbsp;&nbsp;('.$modlink.')';
    echo html_writer::end_tag('li')."\n";
}

/// Print Section 0 with general activities
$section = 0;
$thissection = $sections[$section];
// This is done so that we can permute throught $sections later without
// repeating the first section
unset($sections[0]);

if ($thissection->summary or $thissection->sequence 
    or $PAGE->user_is_editing()) {

    // Note, no need for a 'left side' cell or DIV.
    // Note, 'right side' is BEFORE content.
    echo html_writer::start_tag('li', array(
        'id' => 'section-0',
        'class' => 'section main clearfix'
    ));

    echo html_writer::tag('div', '&nbsp;', array('class' => 'left side'));
    echo html_writer::tag('div', '&nbsp;', array('class' => 'right side'));

    echo '<div class="content">';
    if (!is_null($thissection->name)) {
        echo $OUTPUT->heading($thissection->name, 3, 'sectionname');
    }

    echo '<div class="summary">';
    $coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);

    // @todo see what thell this function does
    $summarytext = file_rewrite_pluginfile_urls($thissection->summary, 
        'pluginfile.php', $coursecontext->id, 'course', 'section', 
        $thissection->id);

    $summaryformatoptions = new stdClass();
    $summaryformatoptions->noclean = true;
    $summaryformatoptions->overflowdiv = true;
    echo format_text($summarytext, $thissection->summaryformat, 
        $summaryformatoptions);

    if ($PAGE->user_is_editing() 
            && has_capability('moodle/course:update', $coursecontext)) {
        echo '<a title="'.$streditsummary.'" '.' href="editsection.php?id='.
             $thissection->id.'"><img src="'.$OUTPUT->pix_url('t/edit') . '" '.
             ' class="icon edit" alt="'.$streditsummary.'" /></a>';
    }
    // End class="summary"
    echo '</div>';

    // Print contents
    print_section($course, $thissection, $mods, $modnamesused);

    if ($PAGE->user_is_editing()) {
        print_section_add_menus($course, $section, $modnames);
    }

    // End class="content"
    echo '</div>';
    echo "</li>\n";
}


/// Now all the normal modules by topic
/// Everything below uses "section" terminology - each "section" is a topic.

$timenow = time();
$section = 1;
$sectionmenu = array();

while ($section <= $course->numsections) {

    if (!empty($sections[$section])) {
        $thissection = $sections[$section];

    } else {
        // Create a new section
        $thissection = new stdClass;
        $thissection->course  = $course->id;   
        $thissection->section = $section;
        $thissection->name = null;
        $thissection->summary = '';
        $thissection->summaryformat = FORMAT_HTML;
        $thissection->visible  = 1;
        $thissection->id = $DB->insert_record('course_sections', $thissection);
    }

    $showsection = has_capability('moodle/course:viewhiddensections', $context) 
        or $thissection->visible or !$course->hiddensections;

    if (!empty($displaysection) and $displaysection != $section) {  
        // Check this topic is visible
        if ($showsection) {
            $sectionmenu[$section] = get_section_name($course, $thissection);
        }
        $section++;
        continue;
    }

    if ($showsection) {

        $currenttopic = ($course->marker == $section);

        $currenttext = '';
        if (!$thissection->visible) {
            $sectionstyle = ' hidden';
        } else if ($currenttopic) {
            $sectionstyle = ' current';
            $currenttext = get_accesshide(get_string('currenttopic','access'));
        } else {
            $sectionstyle = '';
        }

        echo '<li id="section-'.$section.'" class="section main clearfix'.
             $sectionstyle.'" >'; 

        echo '<div class="left side">'.$currenttext.$section.'</div>';
        // Note, 'right side' is BEFORE content.
        echo '<div class="right side">';

        if ($displaysection == $section) {    // Show the zoom boxes
            echo '<a href="view.php?id='.$course->id.'&amp;topic=0#section-'.
                 $section.'" title="'.$strshowalltopics.'">'.
                 '<img src="'.$OUTPUT->pix_url('i/all').'" class="icon" alt="'.
                 $strshowalltopics.'" /></a><br />';
        } else {
            $strshowonlytopic = get_string("showonlytopic", "", $section);
            echo '<a href="view.php?id='.$course->id.'&amp;topic='.$section.
                 '" title="'.$strshowonlytopic.'">'.
                 '<img src="'.$OUTPUT->pix_url('i/one').'" class="icon" alt="'.
                 $strshowonlytopic.'" /></a><br />';
        }

        if ($PAGE->user_is_editing() && has_capability('moodle/course:update', 
                get_context_instance(CONTEXT_COURSE, $course->id))) {

            if ($course->marker == $section) {  // Show the "light globe" on/off
                echo '<a href="view.php?id='.$course->id.
                     '&amp;marker=0&amp;sesskey='.sesskey().'#section-'.
                     $section.'" title="'.$strmarkedthistopic.'">'.
                     '<img src="'.$OUTPUT->pix_url('i/marked').'" alt="'.
                     $strmarkedthistopic.'" /></a><br />';
            } else {
                echo '<a href="view.php?id='.$course->id.'&amp;marker='.
                     $section.'&amp;sesskey='.sesskey().'#section-'.
                     $section.'" title="'.$strmarkthistopic.'">'.'<img src="'.
                     $OUTPUT->pix_url('i/marker').'" alt="'.$strmarkthistopic.
                     '" /></a><br />';
            }

            if ($thissection->visible) {        // Show the hide/show eye
                echo '<a href="view.php?id='.$course->id.'&amp;hide='.$section.
                     '&amp;sesskey='.sesskey().'#section-'.$section.'" title="'.
                     $strtopichide.'">'.'<img src="'.$OUTPUT->pix_url('i/hide').
                     '" class="icon hide" alt="'.$strtopichide.'" /></a><br />';
            } else {
                echo '<a href="view.php?id='.$course->id.'&amp;show='.$section.
                     '&amp;sesskey='.sesskey().'#section-'.$section.'" title="'.
                     $strtopicshow.'">'.'<img src="'.$OUTPUT->pix_url('i/show').
                     '" class="icon hide" alt="'.$strtopicshow.'" /></a><br />';
            }

            if ($section > 1) {                // Add a arrow to move section up
                echo '<a href="view.php?id='.$course->id.'&amp;random='.
                     rand(1,10000).'&amp;section='.$section.
                     '&amp;move=-1&amp;sesskey='.sesskey().'#section-'.
                     ($section-1).'" title="'.$strmoveup.'">'.
                     '<img src="'.$OUTPUT->pix_url('t/up').
                     '" class="icon up" alt="'.$strmoveup.'" /></a><br />';
            }

            if ($section < $course->numsections) {
                // Add a arrow to move section down
                echo '<a href="view.php?id='.$course->id.'&amp;random='.
                     rand(1,10000).'&amp;section='.$section.
                     '&amp;move=1&amp;sesskey='.sesskey().'#section-'.
                     ($section+1).'" title="'.$strmovedown.'">'.
                     '<img src="'.$OUTPUT->pix_url('t/down').
                     '" class="icon down" alt="'.$strmovedown.'" /></a><br />';
            }
        }
        echo '</div>';

        echo '<div class="content">';
        if (!has_capability('moodle/course:viewhiddensections', $context) 
                and !$thissection->visible) {   // Hidden for students
            echo get_string('notavailable');
        } else {
            if (!is_null($thissection->name)) {
                echo $OUTPUT->heading($thissection->name, 3, 'sectionname');
            }
            echo '<div class="summary">';
            if ($thissection->summary) {
                $coursecontext = 
                    get_context_instance(CONTEXT_COURSE, $course->id);
                $summarytext = 
                    file_rewrite_pluginfile_urls($thissection->summary, 
                        'pluginfile.php', $coursecontext->id, 'course', 
                        'section', $thissection->id);
                $summaryformatoptions = new stdClass();
                $summaryformatoptions->noclean = true;
                $summaryformatoptions->overflowdiv = true;
                echo format_text($summarytext, $thissection->summaryformat, 
                    $summaryformatoptions);
            } else {
               echo '&nbsp;';
            }

            if ($PAGE->user_is_editing() 
                && has_capability('moodle/course:update', 
                    get_context_instance(CONTEXT_COURSE, $course->id))) {

                echo ' <a title="'.$streditsummary.
                     '" href="editsection.php?id='.$thissection->id.'">'.
                     '<img src="'.$OUTPUT->pix_url('t/edit').
                     '" class="icon edit" alt="'.$streditsummary.
                     '" /></a><br /><br />';
            }

            echo '</div>';

            print_section($course, $thissection, $mods, $modnamesused);
            echo '<br />';
            if ($PAGE->user_is_editing()) {
                print_section_add_menus($course, $section, $modnames);
            }
        }

        echo '</div>';
        echo "</li>\n";
    }

    unset($sections[$section]);
    $section++;
}

// print stealth sections if present
if (!$displaysection and $PAGE->user_is_editing() 
    and has_capability('moodle/course:update', 
        get_context_instance(CONTEXT_COURSE, $course->id))) {
    $modinfo = get_fast_modinfo($course);
    foreach ($sections as $section=>$thissection) {
        if (empty($modinfo->sections[$section])) {
            continue;
        }

        echo '<li id="section-'.$section.
            '" class="section main clearfix orphaned hidden">'; 
        //'<div class="left side">&nbsp;</div>';

        echo '<div class="left side">';
        echo '</div>';
        // Note, 'right side' is BEFORE content.
        echo '<div class="right side">';
        echo '</div>';
        echo '<div class="content">';
        echo $OUTPUT->heading(get_string('orphanedactivities'), 3, 
            'sectionname');
        print_section($course, $thissection, $mods, $modnamesused);
        echo '</div>';
        echo "</li>\n";
    }
}


echo html_writer::end_tag('ul')."\n";

if (!empty($sectionmenu)) {
    $select = new single_select(new moodle_url('/course/view.php', 
        array('id'=>$course->id)), 'topic', $sectionmenu);
    $select->label = get_string('jumpto');
    $select->class = 'jumpmenu';
    $select->formid = 'sectionmenu';
    echo $OUTPUT->render($select);
}
