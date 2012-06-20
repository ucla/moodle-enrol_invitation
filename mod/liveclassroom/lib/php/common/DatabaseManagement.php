<?php

function storeResource($rid,$course_id, $params)
{  
    $voicetools = new Object();
    $voicetools->rid = $rid;
    $voicetools->course = $course_id;
    $voicetools->gradeid = "-1";
   // if(isset($params["name"]))
    //{
      //  $voicetools->name = $params["name"];
    //}
   // $voicetools->type = $params["type"];

    if ($params != null && $params["default"]=="false") 
    {
        $voicetools->availability = $params["accessAvailable"];
        if ($params["start_date"] == "true")
        {
            $start_hr = intval($params["start_hr"]);
            $start_min = intval($params["start_min"]);
            $start_month = intval($params["start_month"]);
            $start_day = intval($params["start_day"]);
            $start_year = intval($params["start_year"]);
            $voicetools->start_date = mktime($start_hr, $start_min, 0, $start_month, $start_day, $start_year);
        } 
        else 
        {
            $voicetools->start_date = -1;
        } 

        if ($params["end_date"] == "true") {
            $end_hr = intval($params["end_hr"]);
            $end_min = intval($params["end_min"]);
            $end_month = intval($params["end_month"]);
            $end_day = intval($params["end_day"]);
            $end_year = intval($params["end_year"]);
            $voicetools->end_date = mktime($end_hr, $end_min, 0, $end_month, $end_day, $end_year);
        } 
        else 
        {
            $voicetools->end_date = -1;
        } 
    }
    else 
    {//defualt value
        $voicetools->availability = 1;
        $voicetools->start_date = -1;
        $voicetools->end_date = -1;
    }
    return liveclassroom_store_new_element($voicetools);
} 

function storeRecorderResource($rid,$course_id, $mid)
{

    $voicetools = new Object();
    $voicetools->rid = $rid;
    $voicetools->course = $course_id;
    $voicetools->mid = $mid;
 
    
    return liveclassroom_store_new_element($voicetools);
} 

function storeVmailResource($rid,$course_id, $block_id='0')
{

    $voicetools = new Object();
    $voicetools->rid = $rid;
    $voicetools->course = $course_id;
    $voicetools->block = $block_id;
 
    
    return liveclassroom_store_new_element($voicetools);
} 

function updateRecorderResource($rid,$course_id, $mid)
{

    $voicetools = new Object();
    $voicetools->rid = $rid;
    $voicetools->course = $course_id;
    $voicetools->mid = $mid;

    
    return liveclassroom_update_element($voicetools);
}

function updateVmailResource($rid,$course_id, $block_id='')
{

    $voicetools = new Object();
    $voicetools->rid = $rid;
    $voicetools->course = $course_id;
    $voicetools->block = $block_id;

    
    return liveclassroom_update_element($voicetools);
}


function updateResource($rid,$course_id, $params)
{
    $voicetools = new Object();
    $voicetools->rid = $rid;
    $voicetools->course = $course_id;
    $voicetools->type = $params["type"];
    $voicetools->availability = $params["accessAvailable"];
    $gradeId="-1";  
    if(isset($params["gradeid"]))
    {
      $gradeId=$params["gradeid"];
    }
    $voicetools->gradeid = $gradeId;  
    if ($params["start_date"] == "true") 
    {
        $start_hr = intval($params["start_hr"]);
        $start_min = intval($params["start_min"]);
        $start_month = intval($params["start_month"]);
        $start_day = intval($params["start_day"]);
        $start_year = intval($params["start_year"]);
        $voicetools->start_date = mktime($start_hr, $start_min, 0, $start_month, $start_day, $start_year);
    } 
    else 
    {
        $voicetools->start_date = -1;
    } 
    
    if ($params["end_date"] == "true") {
        $end_hr = intval($params["end_hr"]);
        $end_min = intval($params["end_min"]);
        $end_month = intval($params["end_month"]);
        $end_day = intval($params["end_day"]);
        $end_year = intval($params["end_year"]);
        $voicetools->end_date = mktime($end_hr, $end_min, 0, $end_month, $end_day, $end_year);
    }
    else 
    {
        $voicetools->end_date = -1;
    } 
    return liveclassroom_update_element($voicetools);
} 

function isVtAvailable($rid)
{
    $preview = false;
    $vt_informations = liveclassroom_get_voicetool_informations($rid);

    if ($vt_informations != null && $vt_informations->availability == 0) 
    {
        $preview = false;
    } 
    elseif ($vt_informations != null && $vt_informations->start_date == -1 && $vt_informations->end_date == -1) 
    {
        $preview = true;
    } 
    elseif ($vt_informations != null && $vt_informations->start_date == -1 && time() <= $vt_informations->end_date) 
    {
        $preview = true;
    } 
    elseif ($vt_informations != null && $vt_informations->start_date < time() && $vt_informations->end_date == -1) 
    {
        $preview = true;
    } 
    elseif ($vt_informations != null && $vt_informations->start_date < time() && time() < $vt_informations->end_date) 
    {
        $preview = true;
    } 
    else {
        $preview = false;
    } 

    return $preview;
} 






?>