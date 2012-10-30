<?php

/**
 * UCLA copyright status reports 
 * 
 * @package     ucla
 * @subpackage  uclacopyrightstatusreports
 * @author      Jun Wan
 */

require_once(dirname(__FILE__) . '/../../../config.php');

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot . '/blocks/ucla_copyright_status/lib.php');
require_once($CFG->dirroot. '/admin/tool/uclacourserequestor/lib.php');
require_once($CFG->dirroot . '/local/ucla/lib.php');


/**
* get list of terms
*/

function get_terms(){
    global $DB;
    $term_list = array();
    $sql = 'SELECT DISTINCT term FROM {ucla_request_classes}';
    $result = $DB->get_records_sql($sql);
    foreach ($result as $item){
        $term_text = ucla_term_to_text($item->term);
        $term_list[$item->term] = $term_text;
    }
    return $term_list;
}

/**
* get subject area
**/

function get_subjarea(){
    global $DB;
    $subj_list = array();
    $result=$DB->get_records('ucla_reg_subjectarea', null, 'subjarea');
    foreach ($result as $item){
        $subj_list[$item->subjarea]=$item->subj_area_full;
    }
    return $subj_list;
}

/**
* get division
**/

function get_division(){
    global $DB;
    $div_list = array();
    $result=$DB->get_records('ucla_reg_division', null, 'code');
    foreach ($result as $item){
        $div_list[$item->code]=$item->fullname;
    }
    return $div_list;
}

/**
* get instructor
**/
function get_instructors_list_by_term($term=''){
    global $DB;
    $inst_list = array();
    $sql = 'select * from {ucla_browseall_instrinfo} group by uid';
    if ($term){
        $sql .= ' having term = \''.$term.'\'';
    }
    $sql .= ' order by lastname, firstname'; 
    $result = $DB->get_records_sql($sql);
    foreach ($result as $inst){
        $inst_list['i'.$inst->uid] = $inst->lastname . ', ' . $inst->firstname;
    }
    return $inst_list;
}
/**
* get class list (display list by course)
*/

function get_copyright_list_by_class($term){
    global $DB;
    $list = array();
    $sql = 'SELECT rc.*, reg.*
    FROM {ucla_request_classes} rc
    INNER JOIN {ucla_reg_classinfo} reg ON reg.term = rc.term and reg.srs = rc.srs';
    if($term){
        $sql .=' WHERE rc.term = \''.$term.'\'';
    }
    $sql .= ' ORDER BY reg.subj_area, reg.coursenum, reg.sectnum';
    $result = $DB->get_records_sql($sql);
    foreach($result as $row){
        $list[$row->term][]=$row;
    }
    return $list;
}

/** 
* get all information of class list (display subject area, division and list by course)
**/

function get_all($listby){
    global $DB;
    $list = array();
    $sql = 'SELECT rc.*, reg.*, di.fullname, subj.subj_area_full
    FROM {ucla_request_classes} rc
    INNER JOIN {ucla_reg_classinfo} reg on rc.srs=reg.srs and rc.term=reg.term
    INNER JOIN {ucla_reg_division} di on di.code=reg.division
    INNER JOIN {ucla_reg_subjectarea} subj on subj.subjarea=reg.subj_area
    ORDER BY reg.term, reg.division, reg.subj_area, reg.coursenum, reg.sectnum';
    $result = $DB->get_records_sql($sql);
    foreach ($result as $row){
        if ($listby == 'div'){
            $list[$row->term][$row->division][$row->subj_area][] = $row;
        }
        else if ($listby == 'subj'){
            $list[$row->term][$row->subj_area][] = $row;

        }
    }
    return $list;
}

/**
* get class list (display by instructor)
**/

function get_copyright_list_by_instructor(&$param){
    global $DB;
    $list = array();
    $sql = 'SELECT reg.*, bi.*, rc.courseid, rc.department, rc.course
    FROM {ucla_request_classes} rc
	INNER JOIN {ucla_reg_classinfo} reg ON reg.srs = rc.srs and reg.term=rc.term
    INNER JOIN {ucla_browseall_instrinfo} bi ON bi.term = rc.term and bi.srs = rc.srs';
    if ($param['term']&&!$param['uid']){
        $sql .=' WHERE rc.term = \''. $param['term'] . '\'';
    }
    else if ($param['uid']&&!$param['term']){
        $sql .=' WHERE bi.uid = \''. $param['uid'] . '\'';
    }
    else if ($param['uid']&&$param['term']){
        $sql .= ' WHERE rc.term = \''. $param['term'] . '\' and bi.uid = \''. $param['uid'] . '\'';
    }
    $sql .= ' ORDER BY bi.lastname, bi.firstname, rc.department, rc.course';
    $result = $DB->get_records_sql($sql);
    foreach ($result as $row){
        $list[$row->term][$row->uid][] = $row;
    }
    return $list;
}