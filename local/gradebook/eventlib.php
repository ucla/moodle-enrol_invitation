<?php

require_once('locallib.php');

function ucla_grade_grade_updated($data) {
    // Want to use ucla_grade_grade class
    $grade = grade_reporter::change_class($data, 'ucla_grade_grade');
    return $grade->webservice_handler();
}

function ucla_grade_item_updated($data) {
    // Want to use ucla_grade_item
    $item = grade_reporter::change_class($data, 'ucla_grade_item');
    return $item->webservice_handler();
}

