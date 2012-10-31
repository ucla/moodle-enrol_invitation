<?php

require_once('locallib.php');

function change_class(&$obj, $class_type) {
    if (class_exists($class_type, true)) {
        $obj = unserialize(preg_replace("/^O:[0-9]+:\"[^\"]+\":/i", "O:" . strlen($class_type) . ":\"" . $class_type . "\":", serialize($obj)));
        return $obj;
    }
}

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

