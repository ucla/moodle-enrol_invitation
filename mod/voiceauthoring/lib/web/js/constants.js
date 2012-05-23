/******************************************************************************
 *                                                                            *
 * Copyright (c) 1999-2007 Wimba, All Rights Reserved.                        *
 *                                                                            *
 * COPYRIGHT:                                                                 *
 *      This software is the property of Wimba.                               *
 *      It cannot be copied, used, or modified without obtaining an           *
 *      authorization from the authors or a mandated member of Wimba.         *
 *      If such an authorization is provided, any modified version            *
 *      or copy of the software has to contain this header.                   *
 *                                                                            *
 * WARRANTIES:                                                                *
 *      This software is made available by the authors in the hope            *
 *      that it will be useful, but without any warranty.                     *
 *      Wimba is not liable for any consequence related to                    *
 *      the use of the provided software.                                     *
 *                                                                            *
 * Class:  constants.js                                                       *
 *                                                                            *
 * Author:  Thomas Rollinger                                                  *
 *                                                                            *
 * Date: May 2007                                                             *
 *                                                                            *
 ******************************************************************************/

/* $Id: constants.js 200 2008-01-09 11:37:50Z trollinger $ */
//Constants
var LC_PRODUCT= "liveclassroom";
var LC_MAINLECTURE = "MainLecture";
var LC_DISCUSSION = "Discussion";
var VT_PRODUCT = "voicetools";
var VB_PRODUCT = "board";
var VP_PRODUCT = "presentation";
 var PC_PRODUCT = "pc"; 

var session=new Array();
var timeOfLoad="";
var currentFilter="filter_all";
var currentId="";
var currentgradeId="-1";
var lineCurrentId="";
var currentCourseTab=""
var currentProduct="";
var currentTool="filter_all";
 var currentIdtab="Info";
var currentType=LC_MAINLECTURE;
var message=false;
var currentDiv=0;
var studentView=false;
var advancedSettings=false;
// xml
var objDom;
var objDomTree;
var xsl="";
var pathPictures="";

var myDOM;
var xmlDoc;
var xslData=null;
var lcPopup=null;
var expandState=0;
var gradeSettingUnchecked=false;

var audioStatus = new Array();
audioStatus["error"] = 'An MP4 could not be generated from this archive.';
audioStatus["exists"] = '';
audioStatus["error_server"] = "There is a problem to get the audio file. Please retry later.";
audioStatus["does_not_exist"] = '';
audioStatus["generating"] = 'The MP4 you requested is being generated. This process will take several minutes to complete.<br> Please close this dialog and try again in a few minutes.';
audioStatus["generating_previous_error"] = 'The MP4 you requested is being generated. This process will take several minutes to complete.<br><br>Note: The last attempt to generate an MP4 for this archive did not complete. If this message persists, please contact your system administrator.';