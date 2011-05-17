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
 *
 * @copyright UCLA 2011 - Stolen from: &copy; 2006 The Open University 
 * @author N.D.Freear@open.ac.uk, and others.
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ucla
 * @subpackage format
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/filelib.php');
require_once($CFG->libdir.'/completionlib.php');

// Course preferences
$course_prefs = new ucla_course_prefs($course->id);

// Default to section 0 (course info) if there are no preferences
$landing_page = $course_prefs->get_preference('landing_page', false);

/**
 *  Landing page and determining which section to display
 **/
$topic = optional_param('topic', UCLA_FORMAT_DISPLAY_PREVIOUS, PARAM_INT);

/**
 *  New landing page and topic view control.
 *  We want to make sure that if a user is coming from a different course
 *  that they goto the landing page.
 *
 *  This code uses the fact that the $USER global is cached and carried through
 *  the session. 
 *  Also uses the fact that course_get_display() will clear $USER->display 
 *  whenever we traverse to a new course.
 **/

$displaysection = null;
$to_topic = null;

if ($topic >= UCLA_FORMAT_DISPLAY_ALL) {
    // This means that a topic was explicitly declared
    $to_topic = $topic;
} else {
    if ($topic == UCLA_FORMAT_DISPLAY_LANDING 
      || !isset($USER->display['course'])) {
        debugging('UCLA Format: Landing page');

        // This means that we have come from a different course
        if ($landing_page === false) {
            $to_topic = $marker;
        } else {
            $to_topic = $landing_page;
        }
    } else {
        debugging('UCLA Format: Previously viewed page');

        // This should show the previously viewed page
        // This defaults to '0'
        $displaysection = course_get_display($course->id);
    }
}

if ($displaysection == null && $to_topic !== null) {
    $displaysection = course_set_display($course->id, $to_topic);
}

$USER->display['course'] = $course->id;

// Leave in marker functionality, this isn't really used except visually
// TODO maybe use it for other stuff
if (($marker >= 0) 
  && has_capability('moodle/course:setcurrentsection', $context) 
  && confirm_sesskey()) {
    $course->marker = $marker;
    $DB->set_field("course", "marker", $marker, array("id" => $course->id));
}

/**
 *  Required forums for the UCLA format.
 **/
// Build our required forums
$forum_new = forum_get_course_forum($course->id, 'news');
$forum_gen = forum_get_course_forum($course->id, 'general');

/**
 *  Important Non-Variants.
 **/
$context = get_context_instance(CONTEXT_COURSE, $course->id);

$has_capability_viewhidden = 
    has_capability('moodle/course:viewhiddensections', $context);

$has_capability_update = has_capability('moodle/course:update', $context);
$get_accesshide = get_accesshide(get_string('currenttopic', 'access'));

// Cache all these get_string(), because you know, they're cached already...
$streditsummary   = get_string('editsummary');
$stradd           = get_string('add');
$stractivities    = get_string('activities');
$strshowalltopics = get_string('showalltopics');
$strtopic         = get_string('topic');
$strgroups        = get_string('groups');
$strgroupmy       = get_string('groupmy');
$editing          = $PAGE->user_is_editing();

// If editing... just felt like a comment belongs here
if ($editing) {
    $strtopichide       = get_string('hidetopicfromothers');
    $strtopicshow       = get_string('showtopicfromothers');
    $strmarkthistopic   = get_string('markthistopic');
    $strmarkedthistopic = get_string('markedthistopic');
    $strmoveup          = get_string('moveup');
    $strmovedown        = get_string('movedown');
}

/**
 *  Get instructor information.
 **/
// TODO see if there is an API call for this query
// TODO if not, move outside to library
$params = array();
$params[] = $course->id;

// Instructor configuration, move out to config file 
$instructor_types = array(
    'Instructor' => array(
        'editingteacher',
        'teacher'
    ),
    'Teaching Assistant' => array(),
);

// map-reduce-able
$roles = array();
foreach ($instructor_types as $instructor) {
    foreach ($instructor as $role) {
        $roles[$role] = $role;
    }
}

// Get the people with designated roles
try {
    if (!isset($roles) || empty($roles)) {
        // Hardcoded defaults
        $roles = array(
            'editingteacher',
            'teacher'
        );
    }

    list($in_roles, $new_params) = $DB->get_in_or_equal($roles);

    $additional_sql = ' AND r.shortname '.$in_roles;

    $params = array_merge($params, $new_params);
} catch (coding_exception $e) {
    // Coding exception...
    $additional_sql = '';
}

// This is going to be changed to JOIN on to office hours
$sql = "
    SELECT 
        CONCAT(u.id, '-', r.id) as recordset_id,
        u.id,
        u.firstname,
        u.lastname,
        u.email,
        r.shortname
    FROM {course} c
    JOIN {context} ct
        ON (ct.instanceid = c.id)
    JOIN {role_assignments} ra
        ON (ra.contextid = ct.id)
    JOIN {role} r
        ON (ra.roleid = r.id)
    JOIN {user} u
        ON (u.id = ra.userid)
    WHERE 
        c.id = ?
        $additional_sql
    ";

// Use this whenever you need to display instructors
$instructors = $DB->get_records_sql($sql, $params);

/**
 *  Registrar information Line
 **/
// Registrar information TODO
// Pretty version of term
$course_reg_infos = false;

$course_term = 'No Term'; 

$course_subj = '';
$course_coursenum = 'No Registrar Information';

// Display the top of the inside of the middle (the heading)
$imploder = array();
foreach ($instructors as $instructor) {
    if (in_array($instructor->shortname, $instructor_types['Instructor'])) {
        $imploder[$instructor->id] = $instructor->lastname;
    }
}

if (empty($imploder)) {
    $inst_text = 'N/A';
} else {
    $inst_text = implode(' / ', $imploder);
}

$heading_text = $course_term.' - '.$course_subj.' '.$course_coursenum.' - '.
    $inst_text;

$heading_text .= html_writer::empty_tag('br');
$heading_text .= $OUTPUT->heading($course->fullname, 2);

echo html_writer::tag('div', $heading_text, array(
        'class' => ''
    ));

/**
 *  Progress icon for track completion!
 **/
// Print the Your progress icon if the track completion is enabled
$completioninfo = new completion_info($course);
echo $completioninfo->display_help_icon();

/**
 *  Start printing the sections.
 **/
// Note, an ordered list would confuse - "1" could be the clipboard or summary.
echo html_writer::start_tag('ul', array('class' => 'topics'))."\n";

/**
 *  The non-AJAX clipboard for moving resources.
 **/
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

/**
 *  This is where we start to draw the actual sections.
 **/
$timenow = time();
$section = 0;
$sectionmenu = array();

while ($section <= $course->numsections) {
    // This will auto create sections if we have numsections set < than 
    // the actual number of sections that exist
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
    $showsection = ($has_capability_viewhidden 
        or ($thissection->visible == '1')
        or !$course->hiddensections);

    // If we are only displaying one section, save this section for the 
    // pull down menu later
    if ($displaysection != UCLA_FORMAT_DISPLAY_ALL && $displaysection != $section) {
        // Show the section in the pull down only if we would've shown it
        // otherwise

        if ($showsection) {
            $sectionmenu[$section] = get_section_name($course, $thissection);
        }

        // Don't display sections that we are showing in the menu
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

        /////// The actual Section /////
        echo html_writer::start_tag('li', array(
                'id' => $section_id,
                'class' => $class_text
            ));

        //// (LEFT) State ////
        $left_side = html_writer::tag('div', $currenttext.$section, array(
                'class' => 'left side'
            ));

        //// (RIGHT) Control ////
        // Note, 'right side' is BEFORE content.
        $right_side = html_writer::start_tag('div', 
            array('class' => 'right side'));

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
            $add_url_options['topic'] = UCLA_FORMAT_DISPLAY_ALL;

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

        $innards = html_writer::empty_tag('img', $img_options);

        $additional_controls[] = 
            html_writer::link($moodle_url, $innards, $link_options);

        // This section is for editors
        if ($editing && $has_capability_update) {
            // Making things simpler by making things complex
            $validators = array();

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

            $innards = html_writer::empty_tag('img', $img_options);

            $additional_controls[] = 
                html_writer::link($moodle_url, $innards, $link_options);

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

            $innards = html_writer::empty_tag('img', $img_options);

            $additional_controls[] = 
                html_writer::link($moodle_url, $innards, $link_options);

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

                $innards = html_writer::empty_tag('img', $img_options);

                $additional_controls[] = 
                    html_writer::link($moodle_url, $innards, $link_options);
            }

            // // // // // // // // // // // // // // // // // //

            $add_url_options = array();
            $link_options = array();
            $img_options = array();
            $url_str = '';

            // Add a arrow to move section down
            if ($section > 0 && $section < $course->numsections) {
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
                
                $innards = html_writer::empty_tag('img', $img_options);

                $additional_controls[] = 
                    html_writer::link($moodle_url, $innards, $link_options);
            }
        }

        // Display all the additional controls
        foreach ($additional_controls as $control) {
            $right_side .= $control."\n";
        }

        $right_side .= html_writer::end_tag('div');

        //////////////// Actual Section Content /////////////////////////
        $css_classes = 'content';
        $center_content = html_writer::start_tag('div', 
            array('class' => $css_classes));
            
        // Do not display hidden sections to students
        if (!$has_capability_viewhidden and !$thissection->visible) {
            $center_content .= get_string('notavailable');
        } else {
            $section_title_class = 'headerblock header outline';

            // This is the class info stuff
            if ($section == 0) {
                // Course Information specific has a different section
                // header
                if ($course_reg_infos) {
                    $registrar_info = get_string('reg_listing', 
                        'format_ucla');
                    $registrar_info .= html_writer::empty_tag('br');

                    $registrar_info .= get_string('reg_finalcd', 
                        'format_ucla');
                    $registrar_info .= html_writer::empty_tag('br');
                } else {
                    $registrar_info = get_string('reg_unavail', 
                        'format_ucla');

                    debugging($registrar_info);
                    $registrar_info = '';
                }

                $center_content .= html_writer::tag('div', $registrar_info,
                    array('class' => 'registrar-info'));

                $center_content .= html_writer::tag('div', 
                    format_text($course->summary),
                    array('class' => 'summary'));
                
                // Editing button for course summary
                if ($editing && $has_capability_update) {
                    $url_options = array(
                            'id' => $course->id,
                        );

                    $link_options = array('title' => $streditsummary);

                    $moodle_url = new moodle_url('edit.php', $url_options);

                    $img_options = array(
                            'src' => $OUTPUT->pix_url('t/edit'),
                            'class' => 'icon edit',
                            'alt' => $streditsummary
                        );

                    $innards = html_writer::empty_tag('img', $img_options);

                    $center_content .= html_writer::link($moodle_url, 
                        $innards, $link_options);

                    $center_content .= html_writer::empty_tag('br');
                    $center_content .= html_writer::empty_tag('br');
                }
   
                // Instructor informations
                $instr_info = '';

                if (!empty($instructors)) {
                    foreach ($instructor_types as $title => $rolenames) {
                        $goal_users = array();
                        foreach ($instructors as $user) {
                            if (in_array($user->shortname, $rolenames)) {
                                $goal_users[$user->id] = $user;
                            }
                        }

                        if (empty($goal_users)) {
                            continue;
                        }

                        $table = new html_table();
                        $table->width = '*';

                        // TODO make this more modular
                        $desired_info = array(
                            'fullname' => $title,
                            'office' => 'Office',
                            'phone' => 'Phone',
                            'email' => 'E-Mail Address',
                            'office_hours' => 'Office Hours'
                        );
                
                        $cdi = count($desired_info);
                        $aligns = array();
                        for ($i = 0; $i < $cdi; $i++) {
                            $aligns[] = 'left';
                        }

                        $table->align = $aligns;

                        $table->attributes['class'] = 'boxalignleft';
                        $table->data[] = $desired_info;

                        foreach ($goal_users as $user) {
                            $user_row = array();
                            foreach ($desired_info as $field => $header) {
                                $dest_data = '';
                                if ($field == 'fullname') {
                                    $dest_data = fullname($user);
                                } else if (!isset($user->$field)) {
                                    // Do nothing
                                } else {
                                    $dest_data = $user->$field;
                                }

                                $user_row[$field] = $dest_data;
                            }

                            $table->data[] = $user_row;
                        }
    
                        $instr_info .= html_writer::table($table);
                    }

                    $center_content .= html_writer::tag('div', $instr_info, 
                        array('class' => 'instr-info'));
                }
            } else {
                // Callback to determine the section title displayed
                $section_name = get_section_name($course, $thissection);

                // Print the section name
                $center_content .= $OUTPUT->heading($section_name, 2, 
                    $section_title_class);
            }

            // Display the section
            $center_content .= html_writer::start_tag('div', 
                array('class' => 'summary'));

            if ($thissection->summary) {
                $summarytext = 
                    file_rewrite_pluginfile_urls($thissection->summary, 
                        'pluginfile.php', $context->id, 'course', 
                        'section', $thissection->id);
                $summaryformatoptions = new stdClass();
                $summaryformatoptions->noclean = true;
                $summaryformatoptions->overflowdiv = true;

                $center_content .= format_text($summarytext, 
                    $thissection->summaryformat, 
                    $summaryformatoptions);
            } else {
               $center_content .= '&nbsp;';
            }

            // Display the editing button
            if ($section != 0 && $editing && $has_capability_update) {
                $url_options = array(
                        'id' => $thissection->id,
                    );

                $link_options = array('title' => $streditsummary);

                $moodle_url = new moodle_url('editsection.php', $url_options);

                $img_options = array(
                        'src' => $OUTPUT->pix_url('t/edit'),
                        'class' => 'icon edit',
                        'alt' => $streditsummary
                    );

                $innards = html_writer::empty_tag('img', $img_options);

                $center_content .= html_writer::link($moodle_url, 
                    $innards, $link_options);

                $center_content .= html_writer::empty_tag('br');
                $center_content .= html_writer::empty_tag('br');
            }

            $center_content .= html_writer::end_tag('div');

            ob_start();
            print_section($course, $thissection, $mods, $modnamesused);
            $center_content .= ob_get_clean();

            $center_content .= html_writer::empty_tag('br');

            if ($editing) {
                ob_start();
                print_section_add_menus($course, $section, $modnames);
                $center_content .= ob_get_clean();
            }
        }

        $center_content .= html_writer::end_tag('div');

        echo $left_side;
        echo $right_side;
        echo $center_content;

        // End of the section
        echo html_writer::end_tag('li') . "\n";
    }

    unset($sections[$section]);
    $section++;
}

// Orphaned activities custom written section
if ($displaysection == UCLA_FORMAT_DISPLAY_ALL and $editing and $has_capability_update) {
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
