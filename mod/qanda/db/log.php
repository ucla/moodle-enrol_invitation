<?php


/**
 * Definition of log events
 *
 * @package    mod_qanda
 * @copyright 2013 UC Regents
 */

defined('MOODLE_INTERNAL') || die();

$logs = array(
    array('module'=>'qanda', 'action'=>'add', 'mtable'=>'qanda', 'field'=>'name'),
    array('module'=>'qanda', 'action'=>'update', 'mtable'=>'qanda', 'field'=>'name'),
    array('module'=>'qanda', 'action'=>'view', 'mtable'=>'qanda', 'field'=>'name'),
    array('module'=>'qanda', 'action'=>'view all', 'mtable'=>'qanda', 'field'=>'name'),
    array('module'=>'qanda', 'action'=>'add entry', 'mtable'=>'qanda', 'field'=>'name'),
    array('module'=>'qanda', 'action'=>'update entry', 'mtable'=>'qanda', 'field'=>'name'),
    array('module'=>'qanda', 'action'=>'add category', 'mtable'=>'qanda', 'field'=>'name'),
    array('module'=>'qanda', 'action'=>'update category', 'mtable'=>'qanda', 'field'=>'name'),
    array('module'=>'qanda', 'action'=>'delete category', 'mtable'=>'qanda', 'field'=>'name'),
    array('module'=>'qanda', 'action'=>'approve entry', 'mtable'=>'qanda', 'field'=>'name'),
    array('module'=>'qanda', 'action'=>'view entry', 'mtable'=>'qanda_entries', 'field'=>'question'),
);