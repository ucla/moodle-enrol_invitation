<?PHP

/******************************************************************************
 *                                                                            *
 * Copyright (c) 1999-2012  Blackboard Collaborate, All Rights Reserved.      *
 *                                                                            *
 * COPYRIGHT:                                                                 *
 *      This software is the property of Blackboard Collaborate.              *
 *      You can redistribute it and/or modify it under the terms of           *
 *      the GNU General Public License as published by the                    *
 *      Free Software Foundation.                                             *
 *                                                                            *
 * WARRANTIES:                                                                *
 *      This software is distributed in the hope that it will be useful,      *
 *      but WITHOUT ANY WARRANTY; without even the implied warranty of        *
 *      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         *
 *      GNU General Public License for more details.                          *
 *                                                                            *
 *      You should have received a copy of the GNU General Public License     *
 *      along with the Blackboard Collaborate Moodle Integration;             *
 *      if not, write to the Free Software Foundation, Inc.,                  *
 *      51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA                *
 *                                                                            *
 * Author: Hazan Samy                                                         *
 *                                                                            *
 * Date: September 2006                                                       *
 *                                                                            *
 ******************************************************************************/

/* $Id: welcome.php 76290 2009-09-18 14:54:20Z trollinger $ */

require_once("../../config.php");
require_once("lib.php");
global $CFG;

$id = optional_param('id', 0, PARAM_INT);

if (! $course = $DB->get_record("course", array("id" => $id))) {
  print_error("Course ID is incorrect");
}

require_login($course->id);
$firstPage="generateXmlMainPanel.php";
//$PAGE->requires->js('/mod/liveclassroom/lib/web/js/lib/prototype/prototype.js');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd" >
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
    <title>Blackboard Collaborate Classroom</title>

<!--[if lt IE 7]>
        <script type="text/javascript" src="lib/web/js/lib/iepngfix/iepngfix_tilebg.js"></script>
<![endif]-->
<link rel="STYLESHEET" href="css/StyleSheet.css" type="text/css" />
<script type="text/javascript" src="lib/web/js/lib/prototype/prototype.js"></script> 

<script type="text/javascript" src="lib/web/js/wimba_ajax.js"></script> 
<script type="text/javascript" src="lib/web/js/wimba_commons.js"></script>
<script type="text/javascript" src="lib/web/js/verifForm.js"></script> 
<script type="text/javascript" src="lib/web/js/constants.js"></script> 

<script>
    if(navigator.userAgent.indexOf( 'Safari' ) != -1)
    {
        document.write('<script type=\"text/javascript\" src=\"lib/web/js/lib/ajaxslt/xslt.js\"></' + 'script>');
        document.write('<script type=\"text/javascript\" src=\"lib/web/js/lib/ajaxslt/util.js\"></' + 'script>');
        document.write('<script type=\"text/javascript\" src=\"lib/web/js/lib/ajaxslt/xmltoken.js\"></' + 'script>');
        document.write('<script type=\"text/javascript\" src=\"lib/web/js/lib/ajaxslt/dom.js\"></' + 'script>');
        document.write('<script type=\"text/javascript\" src=\"lib/web/js/lib/ajaxslt/xpath.js\"></' + 'script>');
    }
    
    function display()
    {
        DisplayFirstPage('<?php echo $firstPage ?>','all','','');   
    }
    
</script>

<script type="text/javascript">
function doOpenAddActivity(url,param){
  if( !currentId.empty() ) {
    if( currentType == "liveclassroom") {
      var complete_url=url+'?roomId='+currentId+'&id=<?php p($id) ?>&section=0&sesskey=<?php echo sesskey(); ?>&'+param;
      window.open(complete_url,"_top");
    }else{
      alert("You can not link an archive to an activity");  
    }
  }
}

function doOpenReport() {
 if( !currentId.empty() ) {
    var complete_url='reports.php'+'?roomId='+currentId+'&hza='+session['authToken'];
    var w = window.open(complete_url,'lc_popup','scrollbars=yes,resizable=yes,width=800,height=500');
    w.focus();
  }
}
/*
function doDelete(){
  if( !currentId.empty() ) {
    var complete_url='manageRoomAction.php'+'?time='+session["timeOfLoad"]+'&enc_courseId='+session["courseId"]+'&enc_email='+session["email"]+'&enc_firstname='+session["firstName"]+'&enc_lastname='+session["lastName"]+'&enc_role='+session["role"]+'&signature='+session["signature"]+'&id='+currentId+'&action=deleteRoom&hza='+session['authToken'] ;
    location.href = complete_url
  //var w = window.open(complete_url, '_top');
  //   w.focus(); 
  }
}*/

//set the current product
currentProduct="liveclassroom";
initPath('lib/web/js/xsl/wimba.xsl','lib/web/pictures');
addLoadEvent(display);
</script>
  
  <style>
  *{
    padding:0px;
    margin:0px;
  }
  </style>
</head>

<body id="body"> 
    <div id="all" class="general_font" style="border:solid 1px #808080;background-color:white;overflow:hidden;width:700px;height:500px;"></div>  
    <div id="loading" class="general_font" style="display:none;background-color:white;border:solid 1px #808080;width:700px;height:500px">
        <div class="headerBar">
            <div class="headerBarLeft" >
                <span>Blackboard Collaborate</span>
            </div>
        </div>
        <div style="height: 400px; width: 700px; padding-top: 220px; padding-left: 300px;">
            <img src="lib/web/pictures/items/wheel-24.gif"><br>
            <span style="padding-left:-10px;font-color:#666666">Loading...</span>  
        </div>
    </div>
    <div id="hiddenDiv" style="display:none" class="opac">
      <!--[if lte IE 6.5]><iframe width="0px" height="0px"></iframe><![endif]-->
    </div>
    
    <div class="wimba_box" id="downloadPopup"
         style="width:350px;display:none;position:absolute;z-index:155;left: 25%; top: 25%;">
      <div class="wimba_boxTop">
        <div class="wimbaBoxTopDiv">
          <span class="wimba_boxTopTitle" style="width:300px;">Download Mp4
          </span>
          <span title="close" class="wimba_close_box" onclick="closePopup()">Close</span>
    
          <p style="text-align:left;padding-top:15px;height:100px">
            <span id="status"></span>
          </p>
    
          <p style="height:20px;padding-top:10px;padding-left:20px">
            <a class="regular_btn" href="#" onclick="closePopup()" style="margin-left:70px;"><span
                    style="width:110px">Ok</span></a>
          </p>
    
          <div style="clear: both; display:block; height:0px;"><h:outputText value="&#160;"/></div>
        </div>
      </div>
      <div class="wimba_boxBottom">
        <div>
        </div>
      </div>
    </div>
    
</body>
</html>
