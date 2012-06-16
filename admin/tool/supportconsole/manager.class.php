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
        // This code feels really 80s
        $this->consolegroups = $this->breadth_sort($this->consolegroups,
            $sorting, $unspecified_append);
    }

    function breadth_sort($oldsort, $newsort, $appendremainders=true) {
        // Make a new array, changing the internal ordering
        // by appending data to the array in the order specified by
        // new sort name
        $newsorted = array();
        foreach ($newsort as $newsortname => $newsortdata) {
            if (!isset($oldsort[$newsortname])) {
                continue;
            }

            if (is_array($newsortdata) && !empty($newsortdata)) {
                $newsortdata = $this->breadth_sort(
                        $oldsort[$newsortname], $newsortdata
                    );
            } else {
                $newsortdata = $oldsort[$newsortname];
            }

            $newsorted[$newsortname] = $newsortdata;
        }

        // Make a new array, changing the internal ordering by
        // removing data of that which is specified in the new ordering.
        // The ones that are not specified will remain in the same order.
        $oldsorted = array();
        foreach ($oldsort as $oldsortname => $oldsortdata) {
            if (!isset($newsort[$oldsortname])) {
                $oldsorted[$oldsortname] = $oldsortdata;
            }
        }

        if ($appendremainders) {
            $sorted = array_merge($newsorted, $oldsorted);
        } else {
            $sorted = array_merge($oldsorted, $newsorted);
        }

        return $sorted;
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
