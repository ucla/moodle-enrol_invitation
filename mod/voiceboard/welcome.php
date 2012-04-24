<?PHP

/******************************************************************************
 *                                                                            *
 * Copyright (c) 1999-2006 Horizon Wimba, All Rights Reserved.                *
 *                                                                            *
 * COPYRIGHT:                                                                 *
 *      This software is the property of Horizon Wimba.                       *
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
 *      along with the Horizon Wimba Moodle Integration;                      *
 *      if not, write to the Free Software Foundation, Inc.,                  *
 *      51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA                *
 *                                                                            *
    				  *
 *                                                                            *
 * Date: September 2006                                                       *
 *                                                                            *
 ******************************************************************************/

/* $Id: welcome.php 70214 2008-11-12 00:13:01Z thomasr $ */

/// This page creates a link between the CMS and the component
error_reporting(E_ERROR);
require_once("../../config.php");
require_once("lib.php");

 $firstPage= "generateXmlMainPanel.php";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd" >
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
    <title>Voice Board</title>
    <link rel="STYLESHEET" href="css/StyleSheet.css" type="text/css" />
    <!--[if lt IE 7]>
            <script type="text/javascript" src="lib/web/js/lib/iepngfix/iepngfix_tilebg.js"></script>
    <![endif]-->
    <script type="text/javascript" src="lib/web/js/lib/prototype/prototype.js"></script> 
    
    <script type="text/javascript" src="lib/web/js/wimba_commons.js"></script>
    <script type="text/javascript" src="lib/web/js/wimba_ajax.js"></script> 
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
        
        function doOpenAddActivity(url,param)
        {	
        	if(currentId!="") 
        	{                   
        	    var complete_url=url+'?rid='+currentId+'&id='+session["courseId"]+'&'+param;
                window.open(complete_url,"_top");
        	}
        }
        currentProduct="voiceboard";
        initPath('lib/web/js/xsl/wimba.xsl','lib/web/pictures');
        addLoadEvent(display);
    </script>
<style>
*{
    margin:0; 
    padding:0;
}
</style>   
</head>
<body id="body">    
	<div id="all" class="general_font" style="display:block;background-color:white;overflow:hidden;border:solid 1px #808080;width:700px;height:400px;"></div>  
	<div id="loading" class="general_font" style="display:none;background-color:white;border:solid 1px #808080;width:700px;height:400px;overflow:hidden">
        <div class="headerBar">
            <div class="headerBarLeft" >
                <span>Blackboard Collaborate</span>
            </div>
        </div>
        <div style="height: 300px; width: 700px; padding-top: 150px; padding-left: 300px;text-indent:10">
            <img src="lib/web/pictures/items/wheel-24.gif"><br>
            <span style="font-color:#666666">Loading...</span>  
        </div>
	</div>
</body>
</html>
