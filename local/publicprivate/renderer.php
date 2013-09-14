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
 * Public/private course renderer.
 *
 * @package   local_publicprivate
 * @copyright 2013 UC Regents
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . '/local/publicprivate/lib/course.class.php');
require_once($CFG->dirroot . '/local/publicprivate/lib/module.class.php');
require_once($CFG->dirroot . '/local/publicprivate/lib.php');
require_once($CFG->dirroot . '/course/renderer.php');

/**
 * Public/private course renderer.
 *
 * Used to override the core course renderer, so that we can inject the
 * public/private editing icon and rearrange the delete icon to be last.
 *
 * Also, we add the grouping label for labels.
 *
 * @package   local_publicprivate
 * @copyright 2013 UC Regents
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_publicprivate_renderer extends core_course_renderer {
    protected $ppcourse;

    /**
     * Public private needs to override the display of course module links. To 
     * achieve this with minimal core edits, it's necessary to write the
     * core_course_renderer and modify $mods
     * 
     * @param moodle_page $page
     * @param string $target
     */
    public function __construct(moodle_page $page, $target) {
        parent::__construct($page, $target);

        // Public private course?
        $this->ppcourse = new PublicPrivate_Course($page->course->id);
    }

    /**
     * Need to override this or else constructor will try to add another
     * modchoosertoggle instance.
     */
    public function add_modchoosertoggle() {
    }

    /**
     * Override the visibility of a module link to display if 'public'
     * 
     * @param type $course
     * @param type $completioninfo
     * @param cm_info $mod
     * @param type $sectionreturn
     * @param type $displayoptions
     * @return type
     */
    public function course_section_cm($course, &$completioninfo, cm_info $mod, $sectionreturn, $displayoptions = array()) {

        if ($this->ppcourse->is_activated()) {
            // Check if mod is public.
            $ppmod = new PublicPrivate_Module($mod->id);
            if ($ppmod->is_public()) {
                $mod->uservisible = true;
            }
        }

        $output = '';
        // We return empty string (because course module will not be displayed at all)
        // if:
        // 1) The activity is not visible to users
        // and
        // 2a) The 'showavailability' option is not set (if that is set,
        //     we need to display the activity so we can show
        //     availability info)
        // or
        // 2b) The 'availableinfo' is empty, i.e. the activity was
        //     hidden in a way that leaves no info, such as using the
        //     eye icon.
        if (!$mod->uservisible &&
                (empty($mod->showavailability) || empty($mod->availableinfo))) {
            return $output;
        }

        $indentclasses = 'mod-indent';
        if (!empty($mod->indent)) {
            $indentclasses .= ' mod-indent-' . $mod->indent;
            if ($mod->indent > 15) {
                $indentclasses .= ' mod-indent-huge';
            }
        }
        $output .= html_writer::start_tag('div',
                        array('class' => $indentclasses));

        // Start the div for the activity title, excluding the edit icons.
        $output .= html_writer::start_tag('div',
                        array('class' => 'activityinstance'));

        // Display the link to the module (or do nothing if module has no url).
        $output .= $this->course_section_cm_name($mod, $displayoptions);

        // Module can put text after the link (e.g. forum unread).
        $output .= $mod->get_after_link();

        // Closing the tag which contains everything but edit icons. Content
        // part of the module should not be part of this.
        $output .= html_writer::end_tag('div'); // .activityinstance
        // If there is content but NO link (eg label), then display the
        // content here (BEFORE any icons). In this case cons must be
        // displayed after the content so that it makes more sense visually
        // and for accessibility reasons, e.g. if you have a one-line label
        // it should work similarly (at least in terms of ordering) to an
        // activity.
        $contentpart = $this->course_section_cm_text($mod, $displayoptions);
        $url = $mod->get_url();
        if (empty($url)) {
            $output .= $contentpart;
        }

        // If editing, then add the edit links.
        if ($this->page->user_is_editing()) {

            $editactions = course_get_cm_edit_actions($mod, $mod->indent,
                    $sectionreturn);

            // Add public private.
            $ppeditaction = get_private_public($mod, $sectionreturn);
            if (!empty($ppeditaction)) {
                $editactions = array_merge($editactions, $ppeditaction);
            }

            //  Move delete to the end.
            if (isset($editactions['delete'])) {
                $deledtionaction = $editactions['delete'];
                unset($editactions['delete']);
                $editactions = array_merge($editactions, array($deledtionaction));
            }

            $output .= ' ' . $this->course_section_cm_edit_actions($editactions);
            $output .= $mod->get_after_edit_icons();
        }

        $output .= $this->course_section_cm_completion($course, $completioninfo,
                $mod, $displayoptions);

        // If there is content AND a link, then display the content here
        // (AFTER any icons). Otherwise it was displayed before.
        if (!empty($url)) {
            $output .= $contentpart;
        }

        // Show availability info (if module is not available).
        $output .= $this->course_section_cm_availability($mod, $displayoptions);

        $output .= html_writer::end_tag('div'); // Close $indentclasses.
        return $output;
    }

    /**
     * If a module is public, do not show the 'Private Course Material' label.
     * 
     * @param cm_info $mod
     * @param type $displayoptions
     * @return type
     */
    public function course_section_cm_name(cm_info $mod, $displayoptions = array()) {

        if ($this->ppcourse->is_activated()) {

            $ppmod = new PublicPrivate_Module($mod->id);
            if ($ppmod->is_public()) {
                $mod->groupingid = null;
            }

            // Labels resources are not printed, so add the grouping name manually.
            if (strtolower($mod->modfullname) === 'label' && $ppmod->is_private()) {
                $pptext = html_writer::span('(' . get_string('publicprivategroupingname',
                                        'local_publicprivate') . ')',
                                'groupinglabel');
                $mod->set_after_link($pptext);
            }
        }

        return parent::course_section_cm_name($mod, $displayoptions);
    }

}