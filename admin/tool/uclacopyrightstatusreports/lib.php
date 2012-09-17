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
require_once($CFG->dirroot . '/local/ucla/registrar/registrar_query.base.php');


/**
* get list of terms
*/

function get_terms(){
    global $DB;
	$term_list = array();
    $sql = 'SELECT DISTINCT term FROM {ucla_request_classes}';
    $result = $DB->get_records_sql($sql);
	foreach ($result as $item){
		$term_list[$item->term] = $item->term;
	}
	return $term_list;

}

/**
* get classes for current term
*/

function get_copyright_list_by_class($term){
    global $DB;
    $sql = 'SELECT rc.*, bc.*
    FROM {ucla_request_classes} rc
    INNER JOIN {ucla_browseall_classinfo} bc ON bc.term = rc.term and bc.srs = rc.srs
    WHERE rc.term = :term
    ORDER BY bc.subjarea, bc.course, bc.section';
    $params['term'] = $term;
    return $DB->get_recordset_sql($sql,$params);
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
* get class by subj area
**/
function get_copyright_list_by_course_subj(&$param){
    global $DB;
    $course_subj_list = array();
    $sql = 'SELECT rc.*, bc.*
    FROM {ucla_request_classes} rc
    INNER JOIN {ucla_browseall_classinfo} bc ON bc.term = rc.term and bc.srs = rc.srs';
    if ($param['term']&&!$param['subj']){
        $sql .=' WHERE rc.term = \''. $param['term'] . '\'';
    }
    else if ($param['subj']&&!$param['term']){
        $sql .=' WHERE bc.subjarea = \''. $param['subj'] . '\'';
    }
    else{
        $sql .= ' WHERE rc.term = \''. $param['term'] . '\' and bc.subjarea = \''. $param['subj'] . '\'';
    }
    $sql .= '   ORDER BY bc.subjarea, bc.course, bc.section';
    $result = $DB->get_records_sql($sql);
    foreach ($result as $row){
        $course_subj_list['s'.$row->courseid] = $row;
    }
    return $course_subj_list;
}

/**
* get class by division
**/
function get_copyright_list_by_division(&$param){
    global $DB;
	$course_list_div = array();
    $sql = 'SELECT rc.*, bc.*, reg.division, reg.subj_area, di.fullname, subj.subj_area_full
    FROM {ucla_request_classes} rc
    INNER JOIN {ucla_browseall_classinfo} bc ON bc.term = rc.term and bc.srs = rc.srs
	INNER JOIN {ucla_reg_classinfo} reg on reg.srs=bc.srs and reg.term=bc.term
	INNER JOIN {ucla_reg_division} di on di.code=reg.division
	INNER JOIN {ucla_reg_subjectarea} subj on subj.subjarea=reg.subj_area';
    if ($param['term']&&!$param['subj']){
        $sql .=' WHERE rc.term = \''. $param['term'] . '\'';
    }
    else if ($param['subj']&&!$param['term']){
        $sql .=' WHERE bc.subjarea = \''. $param['subj'] . '\'';
    }
    else{
        $sql .= ' WHERE rc.term = \''. $param['term'] . '\' and bc.subjarea = \''. $param['subj'] . '\'';
    }
    $sql .= '   ORDER BY bc.subjarea, bc.course, bc.section';
    $result = $DB->get_records_sql($sql);
	foreach ($result as $row){
		$course_list_div[$row->division][$row->subj_area][]=$row;
	}
	return $course_list_div;
}

function get_classes_by_term($term){
    global $DB;
    return $DB->get_records('ucla_browseall_classinfo', 
            array('term' => $term), '');
}
    /**
 * get instructor for current term
 */

function get_browser_instructor_by_term($term){
    global $DB;
    return $DB->get_recordset('ucla_browseall_instructor', 
            array('term' => $term), '');
}

function get_instructors_by_term($term){
    global $DB;
    $sql = 'SELECT rc.*, bi.*
    FROM {ucla_request_classes} rc
    INNER JOIN {ucla_browseall_instrinfo} bi ON bi.term = rc.term and bi.srs = rc.srs
    WHERE rc.term = :term
    ORDER BY bi.lastname, bi.firstname, rc.department, rc.course';
    $params['term'] = $term;
    return $DB->get_recordset_sql($sql,$params);
}

function get_instructors_list_by_term($term){
    global $DB;
    $inst_list = array();
    $sql = 'select * from {ucla_browseall_instrinfo} group by uid';
    if ($term){
        $sql .= ' having term = \''.$term.'\'';
    }
    $sql .= ' order by term, lastname, firstname'; 
    $result = $DB->get_records_sql($sql);
    foreach ($result as $inst){
        $inst_list['i'.$inst->uid] = $inst->lastname . ', ' . $inst->firstname;
    }
    return $inst_list;
}

function get_instructor_by_uid($uid){
    global $DB;
    return $DB->get_records('ucla_browseall_instrinfo', 
    array('uid' => $uid), '');
}


function get_copyright_list_by_instructor(&$param){
    global $DB;
    $inst_list = array();
    $sql = 'SELECT rc.*, bi.*
    FROM {ucla_request_classes} rc
    INNER JOIN {ucla_browseall_instrinfo} bi ON bi.term = rc.term and bi.srs = rc.srs';
    if ($param['term']&&!$param['uid']){
        $sql .=' WHERE rc.term = \''. $param['term'] . '\'';
    }
    else if ($param['uid']&&!$param['term']){
        $sql .=' WHERE bi.uid = \''. $param['uid'] . '\'';
    }
    else{
        $sql .= ' WHERE rc.term = \''. $param['term'] . '\' and bi.uid = \''. $param['uid'] . '\'';
    }
    $sql .= ' ORDER BY bi.lastname, bi.firstname, rc.department, rc.course';
    $result = $DB->get_records_sql($sql);
    foreach ($result as $inst){
        $inst_list['i'.$inst->uid] = $inst;
    }
    return $inst_list;
}


function get_all_copyright($term){
    global $DB;
    $query = "SELECT f.id, f.filename, f.author, f.license, f.timemodified, f.contenthash, cm.id as cmid, r.name as rname
     FROM {files} f
     INNER JOIN {context} c
     ON c.id = f.contextid
     INNER JOIN {course_modules} cm
     ON cm.id = c.instanceid
     INNER JOIN {resource} r
     ON cm.instance = r.id
     WHERE f.filename <> '.'
     ORDER BY r.course";
    return $DB->get_records_sql($sql);
}

function get_all_copyright_stat(){

}
