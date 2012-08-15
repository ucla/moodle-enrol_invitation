<?php

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$query = required_param('q', PARAM_TEXT);

// Ripped right out of the default search...
$search = trim(strip_tags($query)); // trim & clean raw searched string
if ($search) {
    $searchterms = explode(" ", $search);    // Search for words independently
    foreach ($searchterms as $key => $searchterm) {
        if (strlen($searchterm) < 2) {
            unset($searchterms[$key]);
        }
    }
    $search = trim(implode(" ", $searchterms));
}

$page = 0;
$perpage = 11;

$courses = get_courses_search($searchterms, "fullname ASC", $page, $perpage, $totalcount);

// Format results
$results = array();

foreach($courses as $r) {
    $obj = new stdClass();
    $obj->shortname = $r->shortname;
    $obj->fullname = $r->fullname;
    
    $su = strip_tags($r->summary);
    $su = substr($su, 0, 75);
    $obj->summary = $su . '...';
    
    // for highlit results
    $obj->text = $r->fullname;
    // to determine click..
    $obj->id = $r->id;
    $results[] = $obj;
}

if($totalcount > 10) {
    $results[10]->shortname = '';
    $results[10]->text = 'More results...';
    $results[10]->id = '0';
    $results[10]->summary = '';
}

// Format output
$out = new stdClass();

$out->query = $search;
$out->results = $results;
$out->numresults = $totalcount;

//print_object($out);
// Return as JSON text
echo json_encode($out);