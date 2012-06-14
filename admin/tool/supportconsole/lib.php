<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/' . $CFG->admin . '/tool/supportconsole/manager.class.php');

function setup_js_tablesorter($tableid=null) {
    global $PAGE;

    $PAGE->requires->js('/local/ucla/tablesorter/jquery-latest.js');
    $PAGE->requires->js('/local/ucla/tablesorter/jquery.tablesorter.js');
    $PAGE->requires->css('/local/ucla/tablesorter/themes/blue/style.css');

    if (!$tableid) {
        $tableid = uniqid();
    }

    $PAGE->requires->js_init_code('$(document).ready(function() { $("#' 
        . $tableid . '").addClass("tablesorter").tablesorter('
        . '{widgets: ["zebra"]}); });');

    return $tableid;
}

/**
 *  This function auto-strips 'id' from the data.
 **/
function html_table_auto_headers($data) {
    $fields = array();
    foreach ($data as $datum) {
        foreach ($datum as $f => $v) {
            if ($f == 'id') {
                continue;
            }

            $fields[$f] = $f;
        }
    }
    
    $paddeddata = array();
    foreach ($data as $datum) {
        $paddeddatarow = array();
        foreach ($fields as $field) {
            if (is_object($datum)) {
                $datum = get_object_vars($datum);
            }

            $fieldvalue = '';
            if (isset($datum[$field])) {
                $fieldvalue = $datum[$field];
            }

            $paddeddatarow[$field] = $fieldvalue;
        }

        $paddeddata[] = $paddeddatarow;
    }

    $table = new html_table();
    $table->head = $fields;
    $table->data = $paddeddata;

    return $table;
}

/**
 *  Used when you want to display a title and a table.
 **/
function supportconsole_render_section_shortcut($title, $data, 
                                                $inputs=array()) {
    global $OUTPUT;
    $size = count($data);

    if ($size == 0) {
        $pretext = 'There are no results';
    } else if ($size == 1) {
        $pretext = 'There is 1 result';
    } else {
        $pretext = 'There are ' . $size . ' results';
    }

    $pretext .= ' for inputs [' . implode(', ', $inputs) . '].';
    return $OUTPUT->box($pretext) 
        . supportconsole_render_table_shortcut($data, $inputs);
}

function supportconsole_render_table_shortcut($data, $inputs) {
    $table = html_table_auto_headers($data);
    $table->id = setup_js_tablesorter();

    return html_writer::table($table);

}

function supportconsole_simple_form($title, $contents='') {
    global $PAGE;
    $formhtml = html_writer::start_tag('form', array(
            'method' => 'post',
            'action' => $PAGE->url
        ));

    $formhtml .= $contents;
    $formhtml .= html_writer::empty_tag('input', array(
            'type' => 'hidden',
            'name' => 'console',
            'value' => $title
        ));
    $formhtml .= html_writer::empty_tag('input', array(
            'type' => 'submit',
            'name' => 'submit-button',
            'value' => 'Go'
        ));

    $formhtml .= html_writer::end_tag('form');

    return $formhtml;
}

