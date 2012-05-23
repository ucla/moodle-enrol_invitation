<?PHP
/******************************************************************************
 *                                                                            *
 * Copyright (c) 1999-2007 Horizon Wimba, All Rights Reserved.                *
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
 * Author: Hugues Pisapia                                                     *
 *                                                                            *
 * Date: 15th April 2006                                                      *
 *                                                                            *
 ******************************************************************************/
/* $Id: liveclassroom.php 80757 2010-12-08 21:48:45Z bdrust $ */

//General
$string['modulename'] = 'Blackboard Collaborate Classroom';
$string['modulenameplural'] = 'Blackboard Collaborate Classrooms';
$string['pluginname'] = 'Blackboard Collaborate Classroom';
$string['pluginadministration'] = 'Blackboard Collaborate Classroom administration';
$string['modulename_help'] = '<p>Live Classroom allows students and instructors to meet synchronously online, using multi-way audio and video, application sharing, and content display, enabling instructors to add vitally important elements of interaction that simply cannot be provided in a text-based course. Sessions within Live Classroom may be recorded for future review.</p><p>Rooms are either instructor-led (only instructors have presenter rights), or presentation rights may be shared by both students and instructors (designed for student collaboration in the absence of an instructor).</p>';

//configuration module
$string['serverconfiguration'] = 'Blackboard Collaborate Classroom Server Configuration';
$string['servername'] = 'Blackboard Collaborate Classroom Server URL';
$string['configservername'] = "Example: http://myserver.bbbb.net";
$string['adminusername'] = 'Account Name';
$string['adminpassword'] = 'Account Password';
$string['lcversion'] = 'Blackboard Collaborate Classroom Version';
$string['integrationversion'] = 'Integration Version';
$string['expirationdate'] = "Expiration date";
$string["viewlogs"]="View the logs";
$string["serverlogs"]="Server Logs";
$string["logback"]="Back to ".$string['modulename']." Configuration";
$string["wc_logs"]="Blackboard Collaborate Classroom Logs";
$string["general_logs"]="General Logs";
$string["loglevel"]="Set Log Level:";
$string['no_logs'] = 'There are no logs yet.';
$string['wellconfigured'] = 'Configuration settings are valid.';
// capabilities
$string["liveclassroom:presenter"] = "Has presenter access to Blackboard Collaborate Classrooms";
//error configuration
$string['emptyAdminUsername'] = 'Admin user name is empty';
$string['emptyAdminPassword'] = 'Admin user password is empty';
$string['wrongconfigurationURLunavailable'] = 'Wrong server configuration, URL unavailable, please see the logs to fix the error';
$string['wrongconfigurationURLincorrect'] = 'Wrong server configuration, URL, Account Name, or Account Password incorrect, please see the logs to fix the error';
$string['wrongAdminPassword'] = 'Wrong password';
$string['wrongadminpass'] = 'Invalid Authentication, please check your admin name or password';
$string['trailingSlash'] = 'The Blackboard Collaborate Classroom Server Name ends with a trailing \'/\'. Please remove it and submit the configuration again.';
$string['trailingHttp'] = 'The Blackboard Collaborate Classroom Server Name doesn\'t start with \'http://\'. Please add it and submit the configuration again.';
$string['domxml'] = "The php extension Domxml has to be installed to have the Blackboard Collaborate integration working";
$string['httpsnotenabled'] = "https connections are not enabled for this Blackboard Collaborate Classroom server.";
$string['httpsrequired'] = "https connections are required for this Blackboard Collaborate Classroom server.";
$string['contactadmin'] = "Please contact your system administrator.";
$string['configfailed'] = "Configuration Failed";
$string['configerror'] = "Configuration Error";

//ToolBar
$string['toolbar_content'] = "Content";
$string['toolbar_schedule'] = 'Schedule' ;
$string['toolbar_delete'] = 'Delete' ;
$string['toolbar_activity'] = "Add Activity";
$string['toolbar_launch'] = "Launch";
$string['toolbar_new'] = "New";
$string['toolbar_reports'] = "Reports";
$string['toolbar_settings'] = "Settings";

//Main Component
$string['list_no_liveclassrooms'] = "There are no Blackboard Collaborate Classrooms available at this time";

//tabs
$string['tab_title_roomInfo'] = "Room Info";
$string['tab_title_archives'] = "Archives";
$string['tab_title_access'] = "Access";
$string['tab_title_features'] = "Features";
$string['tab_title_chat'] = "Chat";
$string['tab_title_media'] = "Media";
$string["tab_title_archive_settings"] =  "MP3 & MP4";
$string['tab_title_advanced'] = "Advanced";

//headerBAr
$string['headerbar_instructor_view'] = "Instructor View";
$string['headerbar_student_view'] = "Student View";

//contextBar
$string['contextbar_settings'] = ": Settings";
$string['contextbar_new_liveclassroom'] = "New Room";

//Settings
$string['general_liveclassroom'] = "Live Classroom";
$string['general_pc'] = "Blackboard Collaborate Podcaster";
$string['general_board'] = "Voice Board";
$string['general_presentation'] = "Voice Presentation";
$string['settings_available'] = "Available";
$string['settings_chat_enabled'] = "Enable students to use text chat ";
$string['settings_description'] = "Description :";
$string['settings_discussion'] = "Discussion room";
$string['settings_discussion_comment'] = "Presentation tools are available to both students and instructors.";
$string['settings_discussion_rooms'] = "Discussion rooms :";
$string['settings_enable_student_video_on_startup'] = "Enable students to show their video by default";
$string['settings_enabled_appshare'] = "Enable Appshare ";
$string['settings_enabled_archiving'] = "Enable Archiving ";
$string['settings_enabled_breakoutrooms'] = "Enable Breakout Rooms ";
$string['settings_enabled_guest'] = "Enable guest access ";

$string['settings_enable_archives'] = "Enable Archives :";
$string['settings_display_archive_reminder'] = "Display Archive Reminder :";

$string['settings_enabled_onfly_ppt'] = "Enable On-The-Fly PowerPoint Import ";
$string['settings_enabled_status'] = "Enable User Status Indicators ";
$string['settings_presenter_console'] = "Presenter Console :";
$string['settings_eboard'] = "eBoard :";
$string['settings_breakout'] = "Breakout Room :";
$string['settings_enabled_student_eboard'] = "Enable students to use the eBoard by default ";
$string['settings_enabled_students_breakoutrooms'] = "Students can see content created in other Breakout Rooms ";
$string['settings_enabled_students_mainrooms'] = "Students in Breakout Rooms can see Main Room folders ";
$string['settings_hms_simulcast_restricted'] = "Enable students to use the phone ";
$string['settings_hms_two_way_enabled'] = "Enable students to speak by default ";
$string['settings_lectures_rooms'] = "Lecture rooms :";
$string['settings_mainLecture'] = "Lecture room";
$string['settings_mainLecture_comment'] = "Presentation tools are available only to instructors.";
$string['settings_max_user'] = "Maximum Users :";
$string['settings_max_user_limited'] = "Limited :";
$string['settings_max_user_unlimited'] = "Unlimited ";
$string['settings_private_chat_enabled'] = "Enable private text chat among students ";
$string['settings_private_chat_enabled_comment'] = "Note: Students are always able to chat with instructors";
$string['settings_status_appear'] = "User Status updates appear in chat ";
$string['settings_status_indicators'] = "Status Indicators :";
$string['settings_student_privileges'] = "Student Privileges :";
$string['settings_title'] = "Title :";
$string['settings_type'] = "Type :";
$string['settings_video_bandwidth'] = "Video Bandwidth :";
$string['settings_video_bandwidth_fastest'] = "Fastest Connection (512kbps)";
$string['settings_video_bandwidth_fast'] = "Fast Connection (256kbps)";
$string['settings_video_bandwidth_medium'] = "Medium Connection (128kbps)";
$string['settings_video_bandwidth_slow'] = "Slow Connection (32kbps)";
$string['settings_video_bandwidth_large'] = "Fast - most users have a T1/LAN connection ";
//$string['settings_video_bandwidth_medium'] = "Medium - most users have a DSL/cable modem";
$string['settings_video_bandwidth_small'] = "Slow - most users have a dial-up modem ";
$string['settings_video_bandwidth_custom'] = "Custom (Advanced)";
$string['settings_video_bitrate'] = "Video Bitrate: ";
$string['settings_video_bandwidth_cap_set'] = 'Note: Your system administrator has set the maximum video bandwidth to {$a} kbps.';
$string['settings_video_popup_size'] = "Window Size :";
$string['settings_video_resolution'] = "Resolution :";
$string['settings_audio'] = "Audio Quality :";
$string['settings_audio_format_basic'] = "Basic Quality (Telephone quality) - 8 kbit/s - Modem usage";
$string['settings_audio_format_good'] = "Good Quality (FM Radio quality) - 20.8 kbit/s - Broadband";
$string['settings_audio_format_standart'] = "Standard Quality - 12.8 kbit/s - Modem usage";
$string['settings_audio_format_superior'] = "Superior Quality - 29.6 kbit/s - Broadband usage";
$string['settings_auto_publish_podcast'] = "Podcast auto-published after :";
$string['settings_chrono_order'] = "Display messages in chronological order";
$string['settings_comment_slide'] = "Students can comment the slides";
$string['settings_dial_in_informations'] = "Dial-in Information";
$string['settings_end_date'] = "End date :";
$string['settings_guest_access_comment1'] = "Note: This setting only has effect when guest access is enabled on the Blackboard Collaborate Classroom server. ";
$string['settings_guest_access_comment2'] = "Contact your administrator for more information. ";
$string['settings_phone_informations'] = "Show phone information to students";
$string['settings_required'] = "Required";
$string['settings_roomId_guest'] = "Room Id :";
$string['settings_short_title'] = "Display short message titles";
$string['settings_show_forward'] = "Allow students to forward messages";
$string['settings_slide_private'] = "Make slide comments private";
$string['settings_slide_private_comment'] = "Student cannot view other student's comments";
$string['settings_start_date'] = "Start date :";
$string['settings_start_thread'] = "Student can start a new thread";
$string["settings_auto_open_archive"] = "Automatically Open New Archives";
$string["settings_archive_access"] = "Archive Access :";
$string["archive_settings"] = "Portable Media Settings";
$string["settings_download_media"] = "Download Media :";
$string["settings_archive_availaibility"] = "Availability :";
$string["settings_auto_open_archive"] = "Automatically Open New Archives";
$string["settings_allow_download_mp3_room"] = "Allow Students to Download Archives as MP3";
$string["settings_allow_download_mp4_room"] = "Allow Students to Download Archives as MP4";
$string["settings_allow_download_mp3_archive"] = "Allow Students to Download Archive as MP3";
$string["settings_allow_download_mp4_archive"] = "Allow Students to Download Archive as MP4";
$string["settings_mp4Settings"] = "MP4 Settings :";
$string["settings_mp4SettingsSetence_room"] = "Select default settings for this room's MP4 archives.";
$string["settings_mp4SettingsSetence_archive"] = "Select the creation settings for this MP4 archive. You can change your settings and recreate the MP4 at any time.";
$string["settings_mp4SettingsComment"] = "What content is most important in your MP4?";
$string["settings_mp4Settings_content_focus_no_video"] = "AppShare, slide and eBoard content";
$string["settings_mp4Settings_doNotIncludeVideo"] = "Do not include video camera content";
$string["settings_mp4Settings_video_focus"] = "Video camera content";
$string["settings_mp4Settings_eboard_only"] = "Slides and eBoard Only";
$string["settings_mp4Settings_appshare_only"] = "AppShare Only";
$string["settings_mp4Settings_video_only"] = "Video Broadcast Only";
$string["settings_mp4encodingOptions"] = "Encoding Quality";
$string["settings_mp4encodingOptions_standard"] = "Standard (Optimized for portable media players)";
$string["settings_mp4encodingOptions_streaming"] = "Low (Optimized for quicker download and real time online viewing)";
$string["settings_mp4encodingOptions_highQuality"] = "High (Optimized for content preservation; Not iPod compatible)";
$string["settings_oldArchive"] = "Portable media files cannot be generated from this archive. Archives created in Blackboard Collaborate Classroom prior to version 5.0 are not supported.";
$string['settings_advanced_comment_1'] = "For advanced users only: Click on the buttons below to open Blackboard Collaborate Classroom 
Advanced Settings pages in a new window.";
$string['settings_advanced_comment_2'] = "You must click the 'Save' button in the new window to save your changes.";
$string['settings_advanced_media_settings_button'] = "Advanced Media Settings...";
$string['settings_advanced_room_settings_button'] = "Advanced Room Settings...";
//advanced settings popup
$string['advancedPopup_sentence'] = "To access to the advanced settings, all the current settings for this Blackboard Collaborate Classroom will be saved";
$string['advancedPopup_title'] = "Advanced Settings Popup";

//validation bar
$string['validationElement_cancel'] = "Cancel";
$string['validationElement_saveAll'] = "Save All";
$string['validationElement_saveAllActivity'] = "Save All and Display";
$string['validationElement_saveAllAndBack'] = "Save All and Return to Course";
$string['validationElement_create'] = "Create";
$string['validationElement_createActivity'] = "Create and Display";
$string['validationElement_createAndBack'] = "Create and Return to Course";
$string['validationElement_ok'] = "Ok";
//Add activity form
$string["activity_name"]="Activity Name:";  
$string['required_fields'] = 'Required Fields';
$string['topicformat'] = 'Topic:';
$string['weeksformat'] = 'Week:';
$string['liveclassroomtype'] = 'Associated Room:';
$string["duration_calendar"]="Duration:";   
$string['name'] = 'Activity Name:';
$string['description_calendar'] = "Description: ";
$string['visibletostudents'] = "Visible to students:";
$string['start_date'] = 'Start Date:';
//calendar
$string['add_calendar'] = 'Add a calendar event';
$string["launch_calendar"] = "Launch the room ";
//day
$string['day1'] = 'Monday';
$string['day2'] = 'Tuesday';
$string['day3'] = 'Wednesday';
$string['day4'] = 'Thursday';
$string['day5'] = 'Friday';
$string['day6'] = 'Saturday';
$string['day0'] = 'Sunday';
$string['cannotretreivelistofrooms'] = "Cannot retreive list of rooms";
$string['month1'] = 'January';
$string['month2'] = 'February';
$string['month3'] = 'March';
$string['month4'] = 'April';
$string['month5'] = 'May';
$string['month6'] = 'June';
$string['month7'] = 'July';
$string['month8'] = 'August';
$string['month9'] = 'September';
$string['month10'] = 'October';
$string['month11'] = 'November';
$string['month12'] = 'December';


//error
$string['error_notfind'] = "Invalid Blackboard Collaborate Classroom parameters. Please contact your administrator for more information.";
$string['error_room'] = "Invalid Blackboard Collaborate Classroom parameters. Please contact your administrator for more information.";
$string['error_connection'] = "Moodle cannot connect to the database. Please reload the page or contact your administrator for more information.";
$string['error_bd'] = "Moodle cannot connect to the database. Please reload the page or contact your administrator for more information.";
$string['error_session'] = "The session has timed out due to inactivity. Please reload the page to reconnect.";
$string['error_signature'] = "Invalid connection. Please contact your administrator for more information.";
$string['error_board'] = "Invalid Voice Board parameters. Please contact your administrator for more information.";
$string['error_connection_lc'] = "Moodle cannot connect to the Blackboard Collaborate Classroom server. Please reload the page or contact your administrator for more information.";
$string['error_bdvt'] = "Moodle cannot connect to the Voice Board database. Please reload the page or contact your administrator for more information.";
$string['error_roomNotFound'] = "The room has been deleted and is no longer available.\nPlease contact your instructor for more information";
$string['error_boardNotFound'] = "The ressource has been deleted and is no longer available.\nPlease contact your instructor for more information";
$string['error_xml'] = "Problem to display the component. Please contact your administrator for more information";

//message Bar
$string['message_created_end'] = "has been successfully created.";
$string['message_deleted_end'] = "has been successfully deleted";
$string['message_updated_end'] = "has been successfully updated.";

$string['message_room_start'] = "The room";
$string['message_room#start'] = "The room";

$string['popup_dial_numbers'] = "Dial-in numbers:";
$string['popup_dial_pin'] = "Permanent Pin code:";
$string['popup_dial_title'] = "Dial-in Information";

//tooltip 
$string['tooltip_dial'] = "Click to see information about this room.";
$string['tooltipLC__student'] = "Available to students";
$string['tooltipLC_1_student'] = "Unavailable to students";


//view panel
$string['lcpopupshouldappear.1'] = 'The Blackboard Collaborate Classroom should now appear.<br> If it does not, please click ';
$string['lcpopupshouldappear.2'] = 'this link';
$string['lcpopupshouldappear.3'] = ' to open it. ';
$string['activity'] = 'activity';
$string['close'] = 'Close';
$string["activity_tools_not_available"]="The Blackboard Collaborate Classroom linked to this activity is currently unavailable.<br>Please contact your instructor";

?>
