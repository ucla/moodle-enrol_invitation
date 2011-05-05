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

if ($thissection->summary or $thissection->sequence or $editing()) {

    // Note: no need for a 'left side' cell or DIV.
    // Note: 'right side' is BEFORE content.
    echo html_writer::start_tag('li', array(
        'id' => 'section-0',
        'class' => 'section main clearfix'
    ));

    echo html_writer::tag('div', '&nbsp;', array('class' => 'left side'));
    echo html_writer::tag('div', '&nbsp;', array('class' => 'right side'));

    echo html_writer::start_tag('div', array('class' => 'content'));

    if (!is_null($thissection->name)) {
        echo $OUTPUT->heading($thissection->name, 3, 'sectionname');
    }

    echo html_writer::start_tag('div', array('class' => 'summary'));

    // @todo see what thell this function does
    $summarytext = file_rewrite_pluginfile_urls($thissection->summary, 
        'pluginfile.php', $context->id, 'course', 'section', 
        $thissection->id);

    $summaryformatoptions = new stdClass();
    $summaryformatoptions->noclean = true;
    $summaryformatoptions->overflowdiv = true;
    echo format_text($summarytext, $thissection->summaryformat, 
        $summaryformatoptions);

    if ($PAGE->user_is_editing() 
            && has_capability('moodle/course:update', $context)) {
        echo '<a title="'.$streditsummary.'" '.' href="editsection.php?id='.
             $thissection->id.'"><img src="'.$OUTPUT->pix_url('t/edit') . '" '.
             ' class="icon edit" alt="'.$streditsummary.'" /></a>';
    }
    // End class="summary"
    echo html_writer::end_tag('div');

    // Print contents
    print_section($course, $thissection, $mods, $modnamesused);

    if ($PAGE->user_is_editing()) {
        print_section_add_menus($course, $section, $modnames);
    }

    // End class="content"
    echo html_writer::end_tag('div');
    echo html_writer::end_tag('li') . "\n";
}

/// Now all the normal modules by topic
/// Everything below uses "section" terminology - each "section" is a topic.

$timenow = time();
$section = 1;
$sectionmenu = array();

// Non-Variants
$has_capability_viewhidden = 
    has_capability('moodle/course:viewhiddensections', $context);

$has_capability_update = has_capability('moodle/course:update', 
    $context);

$get_accesshide = get_accesshide(get_string('currenttopic', 'access'));

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

    // Check viewing capabilities of this section
    $showsection = $has_capability_viewhidden or $thissection->visible 
        or !$course->hiddensections;

    // If we are only displaying one section, save this section for the 
    // pull down menu later
    if (!empty($displaysection) and $displaysection != $section) {  
        // Show the section in the pull down only if we would've shown it
        // otherwise
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
            $currenttext = $get_accesshide;
        } else {
            $sectionstyle = '';
        }

       
        $section_id = 'section-'.$section;
        $class_text = 'section main clearfix '.$sectionstyle;
        echo html_writer::start_tag('li', array(
                'id' => $section_id,
                'class' => $class_text
            ));

        echo html_writer::tag('div', $currenttext.$section, array(
                'class' => 'left side'
            ));

        // Note, 'right side' is BEFORE content.
        echo html_writer::start_tag('div', array('class' => 'right side'));

        $additional_controls = array();

        // Constants for the link HREF
        $url_options = array('id' => $course->id);

        // Options for the link HREF
        $add_url_options = array();

        // This is additional option for the link (usually anchor)
        $link_str = '';

        // These are the options for the anchor tag
        $link_options = array();

        // These are the options for the img tag
        $img_options = array();

        // Draw the boxes to display this or all sections
        if ($displaysection == $section) {    
            // Show the zoom boxes
            $add_url_options['topic'] = '0';

            $link_str = '#section-'.$section;

            $link_options = array(
                'title' => $strshowalltopics
            );

            $img_options = array(
                'src' => $OUTPUT->pix_url('i/all'),
                'class' => 'icon',
                'alt' => $strshowalltopics
            );

        } else {
            $strshowonlytopic = get_string("showonlytopic", "", $section);

            $add_url_options['topic'] = $section;

            $link_options = array(
                'title' => $strshowonlytopic
            );

            $img_options = array(
                'src' => $OUTPUT->pix_url('i/one'),
                'class' => 'icon',
                'alt' => $strshowonlytopic
            );
        }

        // Create the URL link
        $moodle_url = new moodle_url('view.php' . $link_str, 
            array_merge($url_options, $add_url_options));

        $additional_controls[] = 
            html_writer::link($moodle_url,
                html_writer::empty_tag('img', $img_options),
                $link_options);

        // This section is for editors
        if ($editing && $has_capability_update) {
            $url_options['sesskey'] = sesskey();

            $add_url_options = array();
            $img_options = array();
            $link_options = array();

            // Highlight section
            if ($course->marker == $section) {
                $img_options['src'] = $OUTPUT->pix_url('i/marked');
                $img_options['alt'] = $strmarkedthistopic;

                $link_options['title'] = $strmarkedthistopic;

                $add_url_options['marker'] = '0';
                $url_str = '#section-'.$section;
            } else {
                $img_options['src'] = $OUTPUT->pix_url('i/marker');
                $img_options['alt'] = $strmarkthistopic;
                
                $link_options['title'] = $strmarkthistopic;

                $add_url_options['marker'] = $section;
                $url_str = '';
            }

            $moodle_url = new moodle_url('view.php' . $url_str,
                array_merge($url_options, $add_url_options));

            $additional_controls[] = 
                html_writer::link($moodle_url,
                    html_writer::empty_tag('img', $img_options),
                    $link_options);

            // // // // // // // // // // // // // // // // //
            
            $add_url_options = array();
            $link_options = array();
            $img_options = array();
            $url_str = '';
       
            // Hide or show the section
            if ($thissection->visible) {
                $add_url_options['hide'] = $section;
                $url_str = '#section-'.$section;

                $link_options['title'] = $strtopichide;

                $img_options['src'] = $OUTPUT->pix_url('i/hide');
                $img_options['class'] = 'icon hide';
                $img_options['alt'] = $strtopichide;
            } else {
                $add_url_options['show'] = $section;
                $url_str = '#section-'.$section;
                
                $link_options['title'] = $strtopicshow;

                $img_options['src'] = $OUTPUT->pix_url('i/show');
                $img_options['class'] = 'icon hide';
                $img_options['alt'] = $strtopicshow;
            }

            $moodle_url = new moodle_url('view.php' . $url_str,
                array_merge($url_options, $add_url_options));

            $additional_controls[] =
                html_writer::link($moodle_url,
                    html_writer::empty_tag('img', $img_options),
                    $link_options);

            // // // // // // // // // // // // // // // // // //

            $add_url_options = array();
            $link_options = array();
            $img_options = array();
            $url_str = '';

            // Arrow to move section UP
            if ($section > 1) {
                $add_url_options['random'] = rand(1, 10000);
                $add_url_options['section'] = $section;
                $add_url_options['move'] = '-1';

                $url_str = '#section-'.($section - 1);

                $link_options['title'] = $strmoveup;

                $img_options['src'] = $OUTPUT->pix_url('t/up');
                $img_options['class'] = 'icon up';
                $img_options['alt'] = $strmoveup;
    
                $moodle_url = new moodle_url('view.php' . $url_str,
                    array_merge($url_options, $add_url_options));

                
                $additional_controls[] = 
                    html_writer::link($moodle_url,
                        html_writer::empty_tag('img', $img_options),
                        $link_options);
            }

            // // // // // // // // // // // // // // // // // //

            $add_url_options = array();
            $link_options = array();
            $img_options = array();
            $url_str = '';

            // Add a arrow to move section down
            if ($section < $course->numsections) {
                $add_url_options['random'] = rand(1, 10000);
                $add_url_options['section'] = $section;
                $add_url_options['move'] = '1';

                $url_str = '#section-'.($section + 1);

                $link_options['title'] = $strmovedown;

                $img_options['src'] = $OUTPUT->pix_url('t/down');
                $img_options['class'] = 'icon down';
                $img_options['alt'] = $strmovedown;

                $moodle_url = new moodle_url('view.php' . $url_str,
                    array_merge($url_options, $add_url_options));
                
                $additional_controls[] = 
                    html_writer::link($moodle_url,
                        html_writer::empty_tag('img', $img_options),
                        $link_options);
            }
        }

        // Display all the additional controls
        foreach ($additional_controls as $control) {
            echo $control.html_writer::empty_tag('br')."\n";
        }

        echo html_writer::end_tag('div');

        echo html_writer::start_tag('div', array('class' => 'content'));

        // Do not display hidden sections to students
        if (!$has_capability_viewhidden and !$thissection->visible) {
            echo get_string('notavailable');
        } else {
            if (!is_null($thissection->name)) {
                echo $OUTPUT->heading($thissection->name, 3, 'sectionname');
            }

            // Display the section
            echo html_writer::start_tag('div', array('class' => 'summary'));

            if ($thissection->summary) {
                $summarytext = 
                    file_rewrite_pluginfile_urls($thissection->summary, 
                        'pluginfile.php', $context->id, 'course', 
                        'section', $thissection->id);
                $summaryformatoptions = new stdClass();
                $summaryformatoptions->noclean = true;
                $summaryformatoptions->overflowdiv = true;

                echo format_text($summarytext, $thissection->summaryformat, 
                    $summaryformatoptions);
            } else {
               echo '&nbsp;';
            }

            // Display the editing button
            if ($PAGE->user_is_editing() && $has_capability_update) {
                $url_options = array(
                        'id' => $thissection->id,
                    );

                $link_options = array('title' => $streditsummary);

                $moodle_url = new moodle_url('edisection.php', $url_options);

                $img_options = array(
                        'src' => $OUTPUT->pix_url('t/edit'),
                        'class' => 'icon edit',
                        'alt' => $streditsummary
                    );

                echo html_writer::link($moodle_url,
                    html_writer::empty_tag('img', $img_options), 
                    $link_options);

                echo html_writer::empty_tag('br');
                echo html_writer::empty_tag('br');
            }

            echo html_writer::end_tag('div');

            print_section($course, $thissection, $mods, $modnamesused);

            echo html_writer::empty_tag('br');

            if ($PAGE->user_is_editing()) {
                print_section_add_menus($course, $section, $modnames);
            }
        }

        echo html_writer::end_tag('div');
        echo html_writer::end_tag('li') . "\n";
    }

    unset($sections[$section]);
    $section++;
}

// print stealth sections if present
if (!$displaysection and $PAGE->user_is_editing() 
  and $has_capability_update) {

    $modinfo = get_fast_modinfo($course);

    foreach ($sections as $section=>$thissection) {
        if (empty($modinfo->sections[$section])) {
            continue;
        }

        echo html_writer::start_tag('li',
            array(
                    'id' => 'section-'.$section,
                    'class' => 'section main clearfix orphaned hidden'
                ));

        echo html_writer::start_tag('div',
            array(
                'class' => 'left side'
            ));
        echo html_writer::end_tag('div');
           
        // Note: 'right side' is BEFORE content.

        echo html_writer::start_tag('div', array(
                'class' => 'right side'
            ));
        echo html_writer::end_tag('div');

        echo html_writer::start_tag('div', array(
                'class' => 'content'
            ));

        echo $OUTPUT->heading(get_string('orphanedactivities'), 3, 
            'sectionname');

        print_section($course, $thissection, $mods, $modnamesused);

        echo html_writer::end_tag('div');
        echo html_writer::end_tag('li') . "\n";
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
