<?php

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/' . $CFG->admin . '/tool/uclasupportconsole/manager.class.php');

/**
 * Returns all available registrar queries
 * 
 * @return array        Array of registrar queries avialble 
 */
function get_all_available_registrar_queries() {
    global $CFG;
    
    $dirname = $CFG->dirroot .'/local/ucla/registrar';
    $qfs = glob($dirname . '/*.class.php');

    $queries = array();
    foreach ($qfs as $query) {
        if ($query == __FILE__) {
            continue;
        }

        $query = str_replace($dirname . '/registrar_', '', $query);
        $query = str_replace('.class.php', '', $query);
        $queries[] = $query;
    }

    return $queries;    
}

/**
 * Generates appropiate browseby link
 * 
 * @param string $type      Type of browseby link to generate: subjarea or
 *                          division
 * @param array $params     Needed params to generate link. Different depending
 *                          on type of link
 * 
 * @return string           Returns html link text
 */
function get_browseby_link($type, $params) {
    $ret_val = '';
    if ($type == 'subjarea') {
        /* Expects params to have: subjarea, term, display_string
         */
        $ret_val = html_writer::link(new moodle_url('/blocks/ucla_browseby/view.php',
                array('subjarea' => $params['subjarea'],
                      'term' => $params['term'],
                      'type' => 'course')), $params['display_string'],
                array('target' => '_blank'));
    } else if ($type == 'division') {
        /* Expects params to have: division, term, display_string
         */
        $ret_val = html_writer::link(new moodle_url('/blocks/ucla_browseby/view.php',
                array('division' => $params['division'],
                      'term' => $params['term'],
                      'type' => 'subjarea')), $params['display_string'],
                array('target' => '_blank'));
    }
    return $ret_val;
}

/**
 * Generates input field for SRS number
 * 
 * @param string $id        Id to use for label
 * 
 * @return string           Returns HTML to render SRS input 
 */
function get_srs_input($id) {
    $ret_val = html_writer::label(get_string('srs', 
            'tool_uclasupportconsole'), $id.'_srs');
    $ret_val .= html_writer::empty_tag('input', 
            array('type' => 'text', 'name' => 'srs', 'id' => $id.'_srs'));        
    return $ret_val;
}

/**
 * Either creates or returns a subject area selector dropdown.
 * 
 * @global object $DB
 * @staticvar string $term_selector
 * 
 * @param string $id        Id to use for label
 * @param string $selected_subject_area If passed, will be the default subject area selected
 * 
 * @return string           Returns HTML to render subject area dropdown 
 */
function get_subject_area_selector($id, $selected_subject_area = null) {
    global $DB;  
    static $_subject_area_selector_subjects;  // to store cached copy of db record
    $ret_val = '';
  
    if (!isset($_subject_area_selector_subjects)) {
        // generate associative array: subject area => subject area
        $sql = 'SELECT DISTINCT subjarea
                FROM            {reg_subjectarea}
                WHERE           1
                ORDER BY        subjarea';
        $_subject_area_selector_subjects = $DB->get_records_menu('ucla_reg_subjectarea', 
                null, 'subjarea', 'subjarea, subjarea AS subject_area');
        if (empty($_subject_area_selector_subjects)) {
            return '';
        }        
    }

    $ret_val .= html_writer::label(get_string('subject_area', 
            'tool_uclasupportconsole'), $id.'_subject_area_selector');
    $ret_val .= html_writer::select($_subject_area_selector_subjects, 
            'subjarea', $selected_subject_area, 
            get_string('choose_subject_area', 'tool_uclasupportconsole'), 
            array('id' => $id.'_subject_area_selector'));
        
    return $ret_val;
}

/**
 * Either creates or returns a term selector dropdown.
 * 
 * @global object $DB
 * @staticvar array $_term_selector_terms
 * 
 * @param string $id        Id to use for label
 * @param string $selected_term If passed, will be the default term selected
 * 
 * @return string           Returns HTML to render term dropdown 
 */
function get_term_selector($id, $selected_term = null) {
    global $CFG, $DB;  
    static $_term_selector_terms;  // to store cached copy of db record
    $ret_val = '';
    
    if (!ucla_validator('term', $selected_term)) {
        $selected_term = $CFG->currentterm;
    }
  
    if (!isset($_term_selector_terms)) {
        // generate associative array: term => term
        $sql = 'SELECT DISTINCT term AS term_index,
                                term AS term_value
                FROM            {ucla_request_classes}
                WHERE           1';
        $terms = $DB->get_records_sql_menu($sql);
        if (empty($terms)) {
            return '';
        }
        
        // sort array in decending order
        uksort($terms, 'term_cmp_fn');    
        $_term_selector_terms = array_reverse($terms, true);
        
    }

    $ret_val .= html_writer::label(get_string('term', 'tool_uclasupportconsole'), $id.'_term_selector');
    $ret_val .= html_writer::select($_term_selector_terms, 'term', $selected_term, 
            get_string('choose_term', 'tool_uclasupportconsole'), 
            array('id' => $id.'_term_selector'));
    
    
    return $ret_val;
}

/**
 * Generates input field for UID number
 * 
 * @param string $id        Id to use for label
 * 
 * @return string           Returns HTML to render UID input 
 */
function get_uid_input($id) {
    $ret_val = html_writer::label(get_string('uid', 
            'tool_uclasupportconsole'), $id.'_uid');
    $ret_val .= html_writer::empty_tag('input', 
            array('type' => 'text', 'name' => 'uid', 'id' => $id.'_uid'));        
    return $ret_val;
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
                                                $inputs=array(), $moreinfo=NULL) {
    global $OUTPUT;
    $size = 0;
    if (!empty($data)) {
        $size = count($data);        
    }
    
    if ($size == 0) { 
       $pretext = 'There are no results';
    } else if ($size == 1) {
        $pretext = 'There is 1 result';
    } else {
        $pretext = 'There are ' . $size . ' results';
    }

    if (!empty($inputs)) {
        if (!is_array($inputs)) {
            $inputs = (array) $inputs;
        }
        
        // not every support console tool as input
        $pretext .= ' for input [' . implode(', ', $inputs) . '].';
    }
    
    // only display table if there is data to display
    if (empty($data)) {
        return $OUTPUT->box($pretext);                
    } else if ($moreinfo != NULL) {
        return $OUTPUT->box($moreinfo) . $OUTPUT->box($pretext) . 
                supportconsole_render_table_shortcut($data, $inputs);        
    } else {
        return $OUTPUT->box($pretext) . 
                supportconsole_render_table_shortcut($data, $inputs);        
    }
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

