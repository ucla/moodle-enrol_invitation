<?php

defined('MOODLE_INTERNAL') || die();

/**
 *  Helps keep track of display-grouping and
 *  ordering of the support console.
 **/
class tool_supportconsole_manager {
    var $consolegroups = array();

    function push_console_html($group, $consolekey, $html) {
        $consolegroups = $this->consolegroups;

        if (!isset($consolegroups[$group])) {
            $consolegroups[$group] = array();
        }

        $consolegroups[$group][$consolekey] = $html;

        $this->consolegroups = $consolegroups;

        return true;
    }

    /**
     *  Applies the specified sorting to a copy of the console
     *  elements.
     **/
    function sort($sorting, $unspecified_append=true) {
        // Do stuff
    }

    /**
     *  Renders in form mode...
     **/
    function render_forms() {
        global $OUTPUT;

        $render = '';
        $sm = get_string_manager();
        foreach ($this->consolegroups as $groupname => $group) {
            $innercontent = '';
            foreach ($group as $titleid => $contenthtml) {
                if ($sm->string_exists($titleid, 'tool_supportconsole')) {
                    $titlestr = get_string($titleid, 'tool_supportconsole');
                } else {
                    $titlestr = $titleid . ' ' 
                        . get_string('notitledesc', 'tool_supportconsole');
                }

                $sectioncontent = $OUTPUT->heading($titlestr, 3)
                    . html_writer::tag('div', $contenthtml, array(
                            'class' => 'uclaconsole'
                        ));
                $innercontent .= $OUTPUT->box($sectioncontent, 
                    'generalbox supportconsole');

            }

            $render .= $OUTPUT->heading(get_string($groupname,
                    'tool_supportconsole'), 1) . $innercontent;
        }

        return $render;

    }

    /**
     *  Renders in result mode...
     **/
    function render_results() {
        global $OUTPUT;
        $sm = get_string_manager();

        $prerender = '';
        foreach ($this->consolegroups as $groupname => $group) {
            $sectionrender = '';
            foreach ($group as $titleid => $contenthtml) {
                if (empty($contenthtml)) {
                    continue;
                }

                if ($sm->string_exists($titleid, 'tool_supportconsole')) {
                    $titlestr = get_string($titleid, 'tool_supportconsole');
                } else {
                    $titlestr = $titleid . ' ' 
                        . get_string('notitledesc', 'tool_supportconsole');
                }

                $prerender .= 
                    $OUTPUT->box($OUTPUT->heading($titlestr, 2))
                        . $contenthtml;
            }
        }

        return $prerender;
    }
}
