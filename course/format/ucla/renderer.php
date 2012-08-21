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
 * Renderer for outputting the ucla course format. Based off the topic course 
 * format.
 *
 * @package format_ucla
 * @copyright 2012 UCLA Regent
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since Moodle 2.3
 */


defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/course/format/renderer.php');

/**
 * Basic renderer for ucla format. Based off the topic renderer.
 *
 * @copyright 2012 UC Regents
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_ucla_renderer extends format_section_renderer_base {
    // course info, may contain reginfo
    private $courseinfo = array();
    
    // parsed version of $courseinfo, used to display course sections
    private $displayinfo = array();
    
    // instructors for course
    private $instructors = array();
    
    // course object
    private $course = null;
    
    // context object
    private $context = null;
    
    // term for course that is being rendered
    private $term = null;
    
    // is user editign the page?
    private $user_is_editing = false;
    
    // strings to generate jit links
    private $jit_links = array();
    
    // edit icons style preference
    private $noeditingicons;
    
    /**
     * Constructor method, do necessary setup for UCLA format.
     *
     * @param moodle_page $page
     * @param string $target one of rendering target constants
     */    
    function __construct($page, $target) {
        parent::__construct($page, $target);
       
        // Build required forums for the UCLA format
        $forum_new = forum_get_course_forum($page->course->id, 'news');
        $forum_gen = forum_get_course_forum($page->course->id, 'general');       
        
        // get reg info, if any
        $this->courseinfo = ucla_get_course_info($page->course->id);
        
        // parse that reg info
        $this->parse_courseinfo();
        
        // get instructors, if any
        $this->instructors = ucla_format_display_instructors($page->course);
        
        // save course object
        $this->course =& $page->course;
        
        // save context object
        $this->context =& $page->context;       
        
        // is user editing the page?
        $this->user_is_editing = $page->user_is_editing();
        
        // CCLE-2800 - cache strings for JIT links
        $this->jit_links = array('file' => get_string('file', 'format_ucla'),
                                 'link' => get_string('link', 'format_ucla'),
                                 'text' => get_string('text', 'format_ucla'),
                                 'subheading' => get_string('subheading', 'format_ucla'));     
        
        $this->noeditingicons = get_user_preferences('noeditingicons', 1);
   }    
    
    /**
     * Generate the starting container html for a list of sections
     * @return string HTML to output.
     */
    protected function start_section_list() {
        return html_writer::start_tag('ul', array('class' => 'topics'));
    }

    /**
     * Generate the closing container html for a list of sections
     * @return string HTML to output.
     */
    protected function end_section_list() {
        return html_writer::end_tag('ul');
    }

    /**
     * Generate next/previous section links for navigation.
     * 
     * Copied from format_section_renderer_base
     *
     * @param stdClass $course The course entry from DB
     * @param array $sections The course_sections entries from the DB
     * @param int $sectionno The section number in the course which is being dsiplayed
     * @return array associative array with previous and next section link
     */
    protected function get_nav_links($course, $sections, $sectionno) {
        // FIXME: This is really evil and should by using the navigation API.
        $canviewhidden = has_capability('moodle/course:viewhiddensections', context_course::instance($course->id))
            or !$course->hiddensections;

        $links = array('previous' => '', 'next' => '');
        $back = $sectionno - 1;
        while ($back >= 0 and empty($links['previous'])) {
            if ($canviewhidden || $sections[$back]->visible) {
                $params = array();
                if (!$sections[$back]->visible) {
                    $params = array('class' => 'dimmed_text');
                }
                $previouslink = html_writer::tag('span', $this->output->larrow(), array('class' => 'larrow'));
                $previouslink .= get_section_name($course, $sections[$back]);
                $links['previous'] = html_writer::link(course_get_url($course, $back), $previouslink, $params);
            }
            $back--;
        }

        $forward = $sectionno + 1;
        while ($forward <= $course->numsections and empty($links['next'])) {
            if ($canviewhidden || $sections[$forward]->visible) {
                $params = array();
                if (!$sections[$forward]->visible) {
                    $params = array('class' => 'dimmed_text');
                }
                $nextlink = get_section_name($course, $sections[$forward]);
                $nextlink .= html_writer::tag('span', $this->output->rarrow(), array('class' => 'rarrow'));
                
                $links['next'] = html_writer::link(course_get_url($course, $forward), $nextlink, $params);
            }
            $forward++;
        }
        
        return $links;
    }    
    
    /**
     * Generate the title for this section page
     * @return string the page title
     */
    protected function page_title() {
        return get_string('topicoutline');
    }

    /**
     * Output the html for the page header. For SRS courses will display 
     * reginfo content. Also displays public/private message if user is not 
     * logged in.
     */
    public function print_header() {
        global $CFG, $OUTPUT, $PAGE;
        
        // Formatting and determining information to display for these courses
        $regcoursetext = '';
        $termtext = '';
        if (!empty($this->courseinfo)) {
            $regcoursetext = implode(' / ', $this->displayinfo);
            $termtext = ucla_term_to_text($this->term);
        }

        // This is for the sets of instructors in a course
        $imploder = array();
        $inst_text = '';
        if (!empty($this->instructors)) {
            foreach ($this->instructors as $instructor) {
                if (in_array($instructor->shortname, $CFG->instructor_levels_roles['Instructor'])) {
                    $imploder[$instructor->id] = $instructor->lastname;
                }
            }
        }

        if (empty($imploder)) {
            $inst_text = 'N/A';
        } else {
            $inst_text = implode(' / ', $imploder);
        }

        $heading_text = '';
        if (!empty($termtext)) {
            $heading_text = $termtext . ' - ' . $regcoursetext . ' - ' . $inst_text;
            $heading_text = html_writer::tag('div', $heading_text);
        }        
        
        // display page header
        echo $OUTPUT->heading($heading_text . $this->course->fullname, 2, 'headingblock');
        
        // next, display public private notice
        
        /**
         * Alert that displays when a visitor is not logged in, as the course will
         * only show public content (a partial view) in this case.
         *
         * @author ebollens
         * @version 20110719
         */
        include_once($CFG->libdir . '/publicprivate/course.class.php');
        $publicprivate_course = new PublicPrivate_Course($this->course);
        if ($publicprivate_course->is_activated() && isguestuser()) {
            echo $OUTPUT->box_start('noticebox');

            echo get_string('publicprivatenotice');
            $loginbutton = new single_button(new moodle_url($CFG->wwwroot
                                    . '/login/index.php'), get_string('publicprivatelogin'));
            $loginbutton->class = 'continuebutton';

            echo $OUTPUT->render($loginbutton);
            echo $OUTPUT->box_end();
        }        
        
        // Handle cancelled classes
        if (is_course_cancelled($this->courseinfo)) {
            echo $OUTPUT->box(get_string('coursecancelled', 'format_ucla'), 'noticebox coursecancelled');
        }        
    }
    
    /**
     * Include our custom ajax overwriters to convert icons to text. This needs 
     * to be printed after the headers, but before the footers.
     */
    public function print_js() {
        $noeditingicons = $this->noeditingicons;
        if (ajaxenabled() && !empty($this->user_is_editing)) {
            echo html_writer::script(false, new moodle_url('/course/format/ucla/sections.js'));

            if ($noeditingicons) {
                $editingiconsjs = 'true';
            } else {
                $editingiconsjs = 'false';
            }

            $strishidden = '(' . get_string('hidden', 'calendar') . ')';
            $strmovealt = get_string('movealt', 'format_ucla');
            
            echo html_writer::script("
            M.format_ucla.strings['hidden'] = '$strishidden';
            M.format_ucla.strings['movealt'] = '$strmovealt';
            M.format_ucla.no_editing_icons = $noeditingicons;
            ");
        }        
    }
    
    /**
     * Output html for content that belong in section 0, such as course 
     * description, final location, registrar links and the office hours block.
     */
    public function print_section_zero_content() {
        global $CFG, $OUTPUT;
        
        $center_content = '';
        
        // Course Information specific has a different section header
        if (!empty($this->courseinfo)) {
            // We need the stuff...
            $regclassurls = array();
            $regfinalurls = array();
            foreach ($this->courseinfo as $key => $courseinfo) {
                $displayinfo = $this->displayinfo[$key];

                $url = new moodle_url($courseinfo->url);
                $regclassurls[$key] = html_writer::link($url, $displayinfo);

                $regfinalurls[$key] = html_writer::link(
                        build_registrar_finals_url($courseinfo), $displayinfo
                );
            }

            $registrar_info = get_string('reg_listing', 'format_ucla');

            $registrar_info .= implode(', ', $regclassurls);
            $registrar_info .= html_writer::empty_tag('br');

            $registrar_info .= get_string('reg_finalcd', 'format_ucla');
            $registrar_info .= implode(', ', $regfinalurls);

            $center_content .= html_writer::tag('div', $registrar_info, array('class' => 'registrar-info'));
            $center_content .= html_writer::empty_tag('br');
        }

        // Editing button for course summary
        if ($this->user_is_editing) {
            $streditsummary = get_string('editcoursetitle', 'format_ucla');
            $url_options = array(
                'id' => $this->course->id,
            );

            $link_options = array('title' => $streditsummary);

            $moodle_url = new moodle_url('edit.php', $url_options);

            $img_options = array(
                    'class' => 'icon edit iconsmall',
                    'alt' => $streditsummary
                );

            $innards = new pix_icon('t/edit', $link_options['title'], 
                'moodle', $img_options);

            $center_content .= html_writer::tag('span', 
                $OUTPUT->render(new action_link($moodle_url, 
                    $innards, null, $link_options)),
                array('class' => 'editbutton'));

        }

        $center_content .= html_writer::start_tag('div', array('class' => 'summary'));
        $center_content .= format_text($this->course->summary);
        $center_content .= html_writer::end_tag('div');

        // Instructor informations
        if (!empty($this->instructors)) {
            $instr_info = block_ucla_office_hours::render_office_hours_table(
                    $this->instructors, $CFG->instructor_levels_roles, $this->course, $this->context);

            $center_content .= html_writer::tag('div', $instr_info, array('class' => 'instr-info'));
        }        
        
        echo $center_content;
    }
    
    /**
     * Output the html for a single section page.
     *
     * @param stdClass $course The course entry from DB
     * @param array $sections The course_sections entries from the DB
     * @param array $mods used for print_section()
     * @param array $modnames used for print_section()
     * @param array $modnamesused used for print_section()
     * @param int $displaysection The section number in the course which is being displayed
     */
    public function print_single_section_page($course, $sections, $mods, $modnames, $modnamesused, $displaysection) {

        // Can we view the section in question?
        $context = context_course::instance($course->id);
        $canviewhidden = has_capability('moodle/course:viewhiddensections', $context);

        if (!isset($sections[$displaysection])) {
            // This section doesn't exist
            print_error('unknowncoursesection', 'error', null, $course->fullname);
            return;
        }

        if (!$sections[$displaysection]->visible && !$canviewhidden) {
            if (!$course->hiddensections) {
                echo $this->start_section_list();
                echo $this->section_hidden($displaysection);
                echo $this->end_section_list();
            }
            // Can't view this section.
            return;
        }

        // Copy activity clipboard..
        echo $this->course_activity_clipboard($course, $displaysection);

        // Start single-section div
        echo html_writer::start_tag('div', array('class' => 'single-section'));

        // if on section 0, then display some special content
        if ($displaysection == 0) {
            $this->print_section_zero_content();
        }
        
        // Title with section navigation links.
        $sectionnavlinks = $this->get_nav_links($course, $sections, $displaysection);
        $sectiontitle = '';
        $sectiontitle .= html_writer::start_tag('div', array('class' => 'section-navigation header sectionheader'));
        $sectiontitle .= html_writer::tag('span', $sectionnavlinks['previous'], array('class' => 'mdl-left'));
        $sectiontitle .= html_writer::tag('span', $sectionnavlinks['next'], array('class' => 'mdl-right'));
        // Title attributes
        $titleattr = 'mdl-align title';
        if (!$sections[$displaysection]->visible) {
            $titleattr .= ' dimmed_text';
        }
        $sectiontitle .= html_writer::tag('div', get_section_name($course, $sections[$displaysection]), array('class' => $titleattr));
        $sectiontitle .= html_writer::end_tag('div');
        echo $sectiontitle;

        // Now the list of sections..
        echo $this->start_section_list();

        // The requested section page.
        $thissection = $sections[$displaysection];
        echo $this->section_header($thissection, $course, true);
        // Show completion help icon.
        $completioninfo = new completion_info($course);
        echo $completioninfo->display_help_icon();

        print_section($course, $thissection, $mods, $modnamesused, true, '100%', false, true);
        if ($this->user_is_editing) {
            print_section_add_menus($course, $displaysection, $modnames, false, false, true);
        }
        echo $this->section_footer();
        echo $this->end_section_list();

        // Display section bottom navigation.
        $sectionbottomnav = '';
        $sectionbottomnav .= html_writer::start_tag('div', array('class' => 'section-navigation mdl-bottom'));
        $sectionbottomnav .= html_writer::tag('span', $sectionnavlinks['previous'], array('class' => 'mdl-left'));
        $sectionbottomnav .= html_writer::tag('span', $sectionnavlinks['next'], array('class' => 'mdl-right'));
        $sectionbottomnav .= html_writer::end_tag('div');
        echo $sectionbottomnav;

        // close single-section div.
        echo html_writer::end_tag('div');
    }    
    
    /**
     * Generate the edit controls of a section
     *
     * @param stdClass $course The course entry from DB
     * @param stdClass $section The course_section entry from DB
     * @param bool $onsectionpage true if being printed on a section page
     * @return array of links with edit controls
     */
    protected function section_edit_controls($course, $section, $onsectionpage = false) {
        
        if (!$this->user_is_editing) {
            return array();
        }

        if (!has_capability('moodle/course:update', context_course::instance($course->id))) {
            return array();
        }

        if ($onsectionpage) {
            $url = course_get_url($course, $section->section);
        } else {
            $url = course_get_url($course);
        }
        $url->param('sesskey', sesskey());

        $controls = array();
        if ($course->marker == $section->section) {  // Show the "light globe" on/off.
            $url->param('marker', 0);
            $controls[] = html_writer::link($url,
                                html_writer::empty_tag('img', array('src' => $this->output->pix_url('i/marked'),
                                    'class' => 'icon ', 'alt' => get_string('markedthistopic'))),
                                array('title' => get_string('markedthistopic'), 'class' => 'editing_highlight'));
        } else {
            $url->param('marker', $section->section);
            $controls[] = html_writer::link($url,
                            html_writer::empty_tag('img', array('src' => $this->output->pix_url('i/marker'),
                                'class' => 'icon', 'alt' => get_string('markthistopic'))),
                            array('title' => get_string('markthistopic'), 'class' => 'editing_highlight'));
        }

        return array_merge($controls, parent::section_edit_controls($course, $section, $onsectionpage));
    }

    /**
     * Generate the display of the header part of a section before
     * course modules are included
     *
     * @param stdClass $section The course_section entry from DB
     * @param stdClass $course The course entry from DB
     * @param bool $onsectionpage true if being printed on a single-section page
     * @return string HTML to output.
     */
    protected function section_header($section, $course, $onsectionpage) {

        $o = '';
        $currenttext = '';
        $sectionstyle = '';

        if ($section->section != 0) {
            // Only in the non-general sections.
            if (!$section->visible) {
                $sectionstyle = ' hidden';
            } else if ($this->is_section_current($section, $course)) {
                $sectionstyle = ' current';
            }
        }
        
        // Apply section edit style
        if($this->noeditingicons) {
            $sectionstyle .= ' text-icons';
        }

        $o.= html_writer::start_tag('li', array('id' => 'section-'.$section->section,
            'class' => 'section main clearfix'.$sectionstyle));

        $leftcontent = $this->section_left_content($section, $course, $onsectionpage);
        $o.= html_writer::tag('div', $leftcontent, array('class' => 'left side'));

        $rightcontent = $this->section_right_content($section, $course, $onsectionpage);
        $o.= html_writer::tag('div', $rightcontent, array('class' => 'right side'));
        $o.= html_writer::start_tag('div', array('class' => 'content'));

        if (!$onsectionpage) {
            $o.= $this->output->heading($this->section_title($section, $course), 3, 'sectionname');
        }

        $o.= html_writer::start_tag('div', array('class' => 'summary'));
        $o.= $this->format_summary_text($section);

        $context = context_course::instance($course->id);
        if ($this->user_is_editing && has_capability('moodle/course:update', $context)) {
            $url = new moodle_url('/course/editsection.php', array('id'=>$section->id));

            if ($onsectionpage) {
                $url->param('sectionreturn', 1);
            }

            $o.= html_writer::link($url,
                html_writer::empty_tag('img', array('src' => $this->output->pix_url('t/edit'), 'class' => 'iconsmall edit')),
                array('title' => get_string('editsummary')));
        }
        $o.= html_writer::end_tag('div');

        $o .= $this->section_availability_message($section);

        return $o;
    }    
    
    // PRIVATE METHODS \\

    /**
     * Generates JIT links for given section.
     * 
     * @param int $section  Section we are on
     * 
     * @return string       Returns JIT link html
     */
    private function get_jit_links($section) {
        $ret_val = html_writer::start_tag('div',
                array('class' => 'jit_links'));

        foreach ($this->jit_links as $jit_type => $jit_string) {
            $link = new moodle_url('/blocks/ucla_easyupload/upload.php',
                    array('course_id' => $this->course->id,
                          'type' => $jit_type,
                          'section' => $section));
            $ret_val .= html_writer::link($link, $jit_string);
        }

        $ret_val .= html_writer::end_tag('div');        
        return $ret_val;
    }
            
    
    /**
     * If courseinfo is not empty, then will parse its contents into user 
     * displayable strings so that course sections can be printed.
     */
    private function parse_courseinfo() {
        if (empty($this->courseinfo)) {
            return false;
        }
        
        $theterm = false;
        foreach ($this->courseinfo as $key => $courseinfo) {
            $thisterm = $courseinfo->term;
            if (!$theterm) {
                $theterm = $thisterm;
            } else if ($theterm != $thisterm) {
                debugging('Mismatching terms in crosslisted course.'
                        . $theterm . ' vs ' . $thisterm);
            }

            $course_text = $courseinfo->subj_area . $courseinfo->coursenum . '-' .
                    $courseinfo->sectnum;

            // if section is cancelled, then cross it out
            if (enrolstat_is_cancelled($courseinfo->enrolstat)) {
                $course_text = html_writer::tag('span', $course_text, array('class' => 'course_text_cancelled'));
            }

            // save section info
            $this->displayinfo[$key] = $course_text;
        }
        
        $this->term = $theterm; // save term for course being displayed
    }    
}
