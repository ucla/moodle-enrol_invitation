<?php


require_once(dirname(__FILE__).'/../../config.php');
global $CFG, $PAGE;

require_once($CFG->dirroot . '/blocks/ucla_alert/block_ucla_alert.php');
require_once($CFG->dirroot . '/blocks/ucla_alert/ucla_alert_form.php');

require_login();

$PAGE->set_url('/blocks/ucla_alert/edit.php');

$PAGE->set_course($SITE);

$PAGE->set_pagetype('site-index');
$PAGE->set_pagelayout('coursecategory');

$PAGE->set_title('Editing the alerts block');
$PAGE->navbar->add('Editing the alerts block');

// I have no idea when this is used...
$PAGE->set_heading($SITE->fullname);

// Load YUI script
$PAGE->requires->js('/blocks/ucla_alert/alert.js');
$PAGE->requires->js_init_call('M.alert_block.init', array());

echo $OUTPUT->header();

// Alert add message form
//$alert_add_form = new ucla_alert_add_form();
//$alert_add_form->display();
//
//if($data = $alert_add_form->get_data()) {
//
//    $data->type = '';
//    
//    $record = new stdClass();
//    $record->module = 'body';
//    $record->type = 'default';
//    $record->visible = 0;
//    $record->sortorder = 1000;
//    $record->content = json_encode($data);
//    
//    $DB->insert_record('ucla_alert', $record);
//}

// Alert edit (Y)UI
//echo block_ucla_alert::write_alert_edit_ui();

?>
<style type="text/css" media="screen">
    .yui3-dd-proxy {
        text-align: left;
    }
    #ucla-alert-edit {
        border: 1px solid black;
        padding: 10px;
        margin: 10px;
        zoom: 1;
    }
    #ucla-alert-edit:after { display: block; clear: both; visibility: hidden; content: '.'; height: 0;}
    #ucla-alert-edit ul {
        border: 1px solid #CDCDCD;
        margin: 5px;
        width: 200px;
        min-height: 100px;
        float: left;
        padding: 0;
        zoom: 1;
        position: relative;
        padding-top: 30px;
        border-radius: 4px 4px 4px 4px;
    }
    #ucla-alert-edit ul li {
        background-image: none;
        list-style-type: none;
        padding-left: 20px;
        padding: 5px;
        margin: 5px;
        cursor: move;
        zoom: 1;
        position: relative;
    }
    
    .alert-edit-section ul li.list1,
    #ucla-alert-edit ul li.list1 {
        background-color: #8DD5E7;
        border:1px solid #004C6D;
    }
    #ucla-alert-edit ul li.list2 {
        background-color: #EDFF9F;
        border:1px solid #CDCDCD;
    }
    
    /* ALERT EDIT STYLES */
    .alert-edit-header,
    .alert-edit-section {
        border: 1px solid #CDCDCD;
        display: block;
        float: left;
        width: auto;
        margin: 10px;
    }
    .alert-edit-header .header-box {
        margin: 5px;
    }
    #ucla-alert-edit .block-ucla-alert .box-section-title {
        border-top: 0;
        padding: 4px 8px 0 8px;
    }

    .alert-edit-header ul li.alert-edit-item,
    .alert-edit-section ul li.alert-edit-item {
        background-color: #FAFAF8;
        border:1px dashed gray;
        margin: 5px;
    }
    
    #ucla-alert-edit ul:after {
        background-color: #F5F5F5;
        border: 1px solid #DDDDDD;
        border-radius: 4px 0 4px 0;
        content: "Section content";
        left: -1px;
        padding: 3px 7px;
        position: absolute;
        top: -1px;
        font-weight: bold;
        font-size: .9em;
        color: #9DA0A4;
    }
    
    /* ITEM EDIT TEXT BOX */
    .alert-edit-text-box {
        border: 1px solid #cdcdcd;
        padding: 5px;
        background-color: white;
        display: none;
    }
    .alert-edit-textarea {
        border: 0;
        width: 100%;
        height: 100%;
    }
    .alert-edit-button-box {
        border-top: 1px solid #cdcdcd;
        padding: 5px;
        margin-top: 5px;
        margin-bottom: -2px;
        background-color: white;
        text-align: right;
    }
    
    /* HEADER EDIT TEXT BOX */
    .alert-edit-header .alert-edit-header-wrapper .alert-edit-text-box {
        margin: 5px;
        border: 5px solid #88B851;
    }
    .alert-edit-header .alert-edit-text-box.alert-header-yellow {
        border-color: #EDB83D;
    }
    .alert-edit-header .alert-edit-text-box.alert-header-red {
        border-color: #E17D68;
    }
    .alert-edit-header .alert-edit-text-box.alert-header-blue {
        border-color: #47BCBB;
    }
    
    /* HEADER COLOR OVERRIDES */
    .block-ucla-alert .header-box.alert-header-yellow {
        background-color: #EDB83D;
        text-shadow: 1px 1px 3px #9F730F;
    }
    .block-ucla-alert .header-box.alert-header-red {
        background-color: #E17D68;
        text-shadow: 1px 1px 3px #802B19;
    }
    .block-ucla-alert .header-box.alert-header-blue {
        background-color: #47BCBB;
        text-shadow: 1px 1px 3px #196E80;
    }
    
    /* HEADER SELECT HIGHTLIGHT */
    .block-ucla-alert .header-box.header-selected {
        border: 10px solid white;
    }
    .block-ucla-alert .header-box.header-selected:before {
        color: white;
        content: '✔';
        font-size: 1em;
        font-weight: bold;
        left: -1px;
        padding: 3px 7px;
        position: relative;
        top: -1px;
    }
    
    
</style>


    <?php 
    $s = new ucla_alert_block_editable(1);
    echo $s->render();
    ?>


<script type="text/javascript" >

    
</script>
<?php

echo $OUTPUT->footer();
