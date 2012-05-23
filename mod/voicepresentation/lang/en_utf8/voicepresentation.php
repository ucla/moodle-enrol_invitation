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
 * Author: Hugues Pisapia                                                     *
 *                                                                            *
 * Date: 15th April 2006                                                      *
 *                                                                            *
 ******************************************************************************/

/* $Id: voicetools.php 56777 2007-12-03 21:38:57Z thomasr $ */

$string['modulename'] = 'Voice Presentation';
$string['modulenameplural'] = 'Voice Presentations';
$string['modulename_help'] = '<p>Blackboard Collaborate Voice Tools facilitate and promote vocal instruction, collaboration, coaching, and assessment -- perfect for language learning, and creating vocal exercises in any subject.</p><p>Four Voice Tools are available:</p><p>Voice Boards craft threaded voice discussions, public or private, lending a warm vocal component beyond traditional message boards.</p><p>Voice Presentations combine vocal and text-based commentary alongside a website or any other web-based content</p><p>Blackboard Collaborate Podcaster simplifies the popular medium of podcasting with an easy-to-use recording interface, automatic feed creation, and 1-click subscription.</p><p>Voice Recorder embeds an audio announcement, recorded by instructors, directly in a course (Note: this tool is only available within a separate Voice Recorder Block)</p>';
$string['emptyAdminUsername'] = 'Admin user name is empty';
$string['emptyAdminPassword'] = 'Admin user password is empty';
$string['pluginadministration'] = 'Voice Presentation administration';
$string['pluginname'] = 'Voice Presentation';


$string['wrongAdminPassword'] = 'Wrong password';
$string['wrongadminpass'] = 'Invalid Authentication, please check your admin name or password';
$string['trailingSlash'] = 'The Voice Tools Server Name ends with a trailing \'/\'. Please remove it and submit the configuration again.';
$string['trailingHttp'] = 'The Voice Tools Server Name doesn\'t start with \'http://\'. Please add it and submit the configuration again.';
$string['resourcereq'] = 'You must select a resource to associate with this Voice Presentation';


// configuration stuff
$string['serverconfiguration'] = 'Voice Tools Server Configuration';
$string['explainserverconfiguration'] = 'This is the Voice Tools Server Configuration.';
$string['servername'] = 'Voice Tools Server URL';
$string['configservername'] = 'Example: http://myschool.bbbb.net/myschool';
$string['adminusername'] = 'Admin User Name';
$string['configadminusername'] = 'Enter the admin username';
$string['adminpassword'] = 'Admin Password';
$string['configadminpassword'] = 'Enter the admin password';
$string['alert.submit'] = 'Are you sure these settings are correct?';
$string['is_allowed.false'] = 'The configuration is not correct. The url of this server is not allowed in your Voice Tools Server. Please, click on Continue to go back to the configuration page.';
$string['Invalid account/secret'] = 'The account/password you entered is not acknowledged by the server. Please, click Continue and verify the values you entered match with the values in the Voice Tools Server.';
$string['integrationversion'] = 'Integration Version';
$string['vtversion'] = 'Voice Tools Version';
$string['loglevel'] = 'Set Log Level:';
$string['viewlogs'] = 'View Logs...';

$string["launch_calendar"]="Launch the Voice ";     
$string["voicepresentationtype"]="Associated Voice Presentation:";    
$string["activity_name"]="Activity Name:";      
$string["topicformat"]="Topic:";
$string["weeksformat"]="Week:";  
$string["activity_tools_not_available"]="The Voice Presentation linked to this activity is currently unavailable.<br>Please contact your instructor";
$string["activity_manageTools"]="Manage Voice Tools";

$string['addActivity'] = 'Add an Activity';
$string['updateActivity'] = 'Edit an Activity';
$string['or'] = "or";
$string['visibletostudents'] = "Visible to students:";
$string['in'] = "in";

//tab name
$string['board_info'] = 'Info';
$string['media'] = 'Media';
$string['features'] = 'Features';
$string['access'] = 'Access';

// toolbar
$string['launch'] = 'Launch';
$string['toolbar_activity'] = 'Add Activity';
$string['new'] = 'New';
$string['settings'] = 'Settings';
$string['delete'] = 'Delete';

//Choice Panel
$string['new_tool'] = 'New Blackboard Collaborate Voice Tool';
$string['new_board'] = 'New Voice Board';
$string['new_presentation'] = 'New Voice Presentation';  
$string['new_podcaster'] = 'New Blackboard Collaborate Podcaster';   
//Validation Bar
$string['required_fields'] = 'Required Fields';
$string['cancel'] = 'Cancel';
$string['save_all'] = 'Save All';
$string['save'] = 'Save';
$string['update'] = 'Update';
              
$string['studentview'] = 'Student View';
$string['instructorview'] = 'Instructor View';

// common VT items
$string['title'] = 'Title :';
$string['name'] = 'Name :';
$string['description'] = 'Description :';    
$string['type'] = 'Type :';
$string['public']='Public';
$string['public_comment']='Student can view all discussion threads';
$string['start_thread']='Student can start a new thread';
$string['private']='Private';
$string['private_comment']='Student cannot view other student\'s discussion threads';
$string['comment_slide']= "Student can comment on the slides";
$string['private_slide']= "Make slide comments private";     
$string['private_slide_comment']= "Students cannot view other student's comments";     

$string['view_other_thread']="Student can view other student\'s discussion threads" ;
$string['post_delay']= "Podcast auto-published after : ";  
$string['note_post_delay']= "Note : Teacher can edit his last post before it is published";  
                            
$string['add_calendar']='Add a calendar event';
$string['duration_calendar']='Duration :';  
$string['description_calendar']='Description :';  

$string['basicquality'] = 'Basic Quality (Telephone quality) - 8 kbit/s - Modem usage';
$string['standardquality'] = 'Standard Quality - 12.8 kbit/s - Modem usage';
$string['goodquality'] = 'Good Quality (FM Radio quality) - 20.8 kbit/s - Broadband usage';
$string['superiorquality'] ="Superior Quality - 29.6 kbit/s - Broadband usage";
$string['audioquality'] = 'Audio Quality';
    

$string['post_podcast']= "Allow users to post to podcast";  

$string['message_length'] = 'Max message length';
$string['short_message'] = 'Display short message titles';
$string['chrono_order'] ="Display messages in chronological order";
$string['show_forward'] ="Allow students to forward messages";
$string['available'] ="Available";
$string['available_comment']='  Note : Subscription to the podcast are always available';
$string['start_date'] ="Start date";
$string['end_date'] ="End date";


//Error messages
$string['notoolsavailable'] = "No voice tool available. You must create a tool, then add it to an activity.";


$string['VoiceBoardDescription'] = "Creates a new Blackboard Collaborate Voice Board";
$string['VoicePresentationDescription'] = "Creates a new Blackboard Collaborate Voice Presentation";
$string['PodcasterDescription'] = "Creates a new Blackboard Collaborate Podcaster";


// Voice Tools tags
$string['voicedirect'] = 'Voice Direct';
$string['voicedirects'] = 'Voice Directs';
$string['voiceboard'] = 'Voice Board';  
$string['voiceboards'] = 'Voice Boards';      
$string['board'] = 'Voice Board - ';
$string['voicepresentation'] = 'Voice Presentation';  
$string['presentation'] = 'Voice Presentation - ';         
$string['voicepresentations'] = 'Voice Presentations';
$string['podcaster'] = 'Blackboard Collaborate Podcaster';   
$string['podcasters'] = 'Podcasters';
$string['pc'] = 'Podcaster - ';
$string['settings']="Settings";
$string['archivesession'] = 'Archive Sessions';
$string['new']="New";
$string['starts']="Starts";
$string['ends']="Ends";
$string['unavailablePopup']="Unavailable to the students.<br>Click Settings to change";
$string['availablePopup']="Available to the students.<br>Click Settings to change";
$string['clickSettings']="Available to the students.<br>Click Settings to change";
//day
$string['day1'] = 'Monday';
$string['day2'] = 'Tuesday';
$string['day3'] = 'Wednesday';
$string['day4'] = 'Thursday';
$string['day5'] = 'Friday';
$string['day6'] = 'Saturday';
$string['day0'] = 'Sunday'; 

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
  
//message
$string["boardCreated"]="Your Voice Board has been successfully created.";
$string["boardUpdated"]="Your Voice Board has been successfully updated.";
$string["deleteVoiceBoard"] = "Your Voice Board has been successfully deleted.";
$string["presentationCreated"]="Your Voice Presentation has been successfully created.";
$string["presentationUpdated"]="Your Voice Presentation has been successfully updated.";
$string["deleteVoicePresentation"] = "Your Voice Presentation has been successfully deleted."; 
$string["podcasterCreated"]="Your Blackboard Collaborate Podcaster has been successfully created.";
$string["podcasterUpdated"]="Your Blackboard Collaborate Podcaster has been successfully updated.";
$string["deletePodcaster"] = "Your Blackboard Collaborate Podcaster has been successfully deleted."; 

$string['vtpopupshouldappear.1'] = 'The Voice tools applet should now appear.<br/> If it does not, please click ';
$string['vtpopupshouldappear.2'] = 'this link';
$string['vtpopupshouldappear.3'] = ' to open it. ';


//error
$string['problem_vt'] = "Moodle cannot connect to the Voice Board server. Please reload the page or contact your administrator for more information. ";
$string['problem_bd'] = "Moodle cannot connect to the Voice Board database. Please reload the page or contact your administrator for more information. ";
$string['problem_vt_recorder'] = "Voice Tools problem";
$string['problem_bd_recorder'] = "Database problem";
$string['session'] = "The session has timed out due to inactivity. Please reload the page to reconnect.";
$string['signature'] = "Invalid connection. Please contact your administrator for more information. ";

$string['updatePodcaster'] = "Invalid connection to Voice Board. Please contact your administrator for more information. ";
$string['launchstudent'] = "You are going to launch this Blackboard Collaborate Voice feature as a student. You will not have instructor privileges.";
$string['date_error'] = "the date selected is previous than today";

$string["activity_no_associated_tools"]="There are no Voice Tools associated with this course.<br> Click Ok to create one.";
$string["activity_welcome_to_wimba"]="Welcome to Blackboard Collaborate Voice Tools!";
$string["activity"]="activity";
$string["close"] = "Close";

$string['filterbar_all'] = "All";
$string['filterbar_boards'] = "Boards";
$string['filterbar_discussions'] = "Discussions";
$string['filterbar_lectures'] = "Lectures";
$string['filterbar_podcasters'] = "Podcasters";
$string['filterbar_voicepresentation'] = "Presentation";
$string['headerbar_instructor_view'] = "Instructor View";
$string['headerbar_student_view'] = "Student View";
$string['list_title_board'] = "Voice Board";
$string['list_title_Discussion'] = "Discussions Rooms";
$string['list_title_MainLecture'] = "Main Lectures Rooms";
$string['list_title_pc'] = "Blackboard Collaborate Podcaster";
$string['list_title_presentation'] = "Voice Presentation";
$string['toolbar_content'] = "Content";
$string['toolbar_schedule'] = 'Schedule' ;
$string['toolbar_delete'] = 'Delete' ;
$string['toolbar_launch'] = "Launch";
$string['toolbar_new'] = "New";
$string['toolbar_reports'] = "Reports";

$string['toolbar_grade'] = "Grade";
$string['toolbar_settings'] = "Settings";
$string['headerbar_instructorView'] = "Instructor View";
$string['headerbar_studentView'] = "Student View";
$string['error_notfind'] = "Invalid Live Classroom parameters. Please contact your administrator for more information.";
$string['error_room'] = "Invalid Live Classroom parameters. Please contact your administrator for more information.";
$string['error_connection'] = "Moodle cannot connect to the database. Please reload the page or contact your administrator for more information.";
$string['error_bd'] = "Moodle cannot connect to the database. Please reload the page or contact your administrator for more information.";
$string['error_session'] = "The session has timed out due to inactivity. Please reload the page to reconnect.";
$string['error_signature'] = "Invalid connection. Please contact your administrator for more information.";
$string['error_board'] = "Invalid Voice Board parameters. Please contact your administrator for more information.";
$string['error_connection_vt'] = "Moodle cannot connect to the Voice Board server. Please reload the page or contact your administrator for more information.";
$string['error_bdvt'] = "Moodle cannot connect to the Voice Board database. Please reload the page or contact your administrator for more information.";
$string['error_roomNotFound'] = "The room has been deleted and is no longer available.\nPlease contact your instructor for more information";
$string['error_boardNotFound'] = "The ressource has been deleted and is no longer available.\nPlease contact your instructor for more information";
$string['error_xml'] = "Problem to display the component. Please contact your administrator for more information";
$string['choiceElement_description_board'] = "Creates a new Voice Board";
$string['choiceElement_description_podcaster'] = "Creates a new Podcaster";
$string['choiceElement_description_presentation'] = "Creates a new Voice Presentation";
$string['choiceElement_description_room'] = "Creates a new Live Classroom";
$string['choiceElement_new_board'] = "New Board";
$string['choiceElement_new_podcaster'] = "New Podcaster";
$string['choiceElement_new_presentation'] = "New Presentation";
$string['choiceElement_new_room'] = "New Room";
$string['contextbar_new_voicetools'] = "New Blackboard Collaborate tools";
$string['contextbar_settings'] = ": Settings";
$string['validationElement_cancel'] = "Cancel";
$string['validationElement_saveAll'] = "Save All";
$string['validationElement_saveAllActivity'] = "Save All and Display";
$string['validationElement_saveAllAndBack'] = "Save All and Return to Course";
$string['validationElement_createActivity'] = "Create and Display";
$string['validationElement_createAndBack'] = "Create and Return to Course";

$string['general_liveclassroom'] = "Live Classroom";
$string['general_pc'] = "Blackboard Collaborate Podcaster";
$string['general_board'] = "Voice Board";
$string['general_presentation'] = "Voice Presentation";
$string['settings_available'] = "Available";
$string['settings_chat_enabled'] = "Enable students to use text chat ";
$string['settings_description'] = "Description :";
$string['settings_discussion'] = "Discussion room";
$string['settings_discussion_comment'] = "Students and intructors have the same rights";
$string['settings_discussion_rooms'] = "Discussion rooms:";
$string['settings_enable_student_video_on_startup'] = "Enable students to show their video by default";
$string['settings_enabled_appshare'] = "Enable Appshare ";
$string['settings_enabled_archiving'] = "Enable Archiving ";
$string['settings_enabled_breakoutrooms'] = "Enable Breakout Rooms ";
$string['settings_enabled_guest'] = "Enable guest access ";
$string['settings_enabled_guest_comment'] = "Note: This setting only has effect when guest access is enabled on the Live Classroom server.  
 Contact your administrator for more information. ";
$string['settings_enabled_onfly_ppt'] = "Enable On-The-Fly PowerPoint Import ";
$string['settings_enabled_status'] = "Enable User Status Indicators ";
$string['settings_enabled_student_eboard'] = "Enable students to use the eBoard by default ";
$string['settings_enabled_students_breakoutrooms'] = "Students can see content created in other Breakout Rooms ";
$string['settings_enabled_students_mainrooms'] = "Students in Breakout Rooms can see Main Room folders ";
$string['settings_hms_simulcast_restricted'] = "Enable students to use the phone ";
$string['settings_hms_two_way_enabled'] = "Enable students to speak by default ";
$string['settings_lectures_rooms'] = "Lecture rooms:";
$string['settings_mainLecture'] = "Lecture room";
$string['settings_mainLecture_comment'] = "Instructors lead the presentation";
$string['settings_max_user'] = "Maximum Users :";
$string['settings_max_user_limited'] = "Limited:";
$string['settings_max_user_unlimited'] = "Unlimited ";
$string['settings_private_chat_enabled'] = "Enable private text chat among students ";
$string['settings_private_chat_enabled_comment'] = "Note: Students are always able to chat with instructors";
$string['settings_status_appear'] = "User Status updates appear in chat ";
$string['settings_status_indicators'] = "Status Indicators:";
$string['settings_student_privileges'] = "Student Privileges:";
$string['settings_title'] = "Title :";
$string['settings_type'] = "Type :";
$string['settings_video_bandwidth'] = "Video Bandwidth: ";
$string['settings_video_bandwidth_large'] = "Fast - most users have a T1/LAN connection ";
$string['settings_video_bandwidth_medium'] = "Medium - most users have a DSL/cable modem";
$string['settings_video_bandwidth_small'] = "Slow - most users have a dial-up modem ";
$string['tab_title_Info'] = "Info";

$string['tab_title_chat'] = "Chat";
$string['tab_title_media'] = "Media";
$string['delay_0'] = "0 s";
$string['delay_1'] = "1 min";
$string['delay_10'] = "10 min";
$string['delay_2'] = "2 min";
$string['delay_20'] = "20 min";
$string['delay_3'] = "3 min";
$string['delay_30'] = "30 min";
$string['delay_5'] = "5 min";
$string['filterbar_lc'] = "Live Classrooms";
$string['filterbar_vt'] = "Voice Tools";
$string['general_board'] = "Voice Board";
$string['settings_audio'] = "Audio Quality :";
$string['settings_audio_format_basic'] = "Basic Quality (Telephone quality) - 8 kbit/s - Modem usage";
$string['settings_audio_format_good'] = "Good Quality (FM Radio quality) - 20.8 kbit/s - Broadband";
$string['settings_audio_format_standart'] = "Standard Quality - 12.8 kbit/s - Modem usage";
$string['settings_audio_format_superior'] = "Superior Quality - 29.6 kbit/s - Broadband usage";
$string['settings_auto_publish_podcast'] = "Podcast auto-published after:";
$string['settings_chrono_order'] = "Display messages in chronological order";
$string['settings_comment_slide'] = "Students can comment on the slides";
$string['settings_dial_in_informations'] = "Dial-in Informations";
$string['settings_end_date'] = "End date :";
$string['settings_guest_access_comment'] = "Note: This setting only has effect when guest access is enabled on the Live Classroom server.Contact your administrator for more information.";
$string['settings_max_message'] = "Max message length :";
$string['settings_max_message_120'] = "2 min";
$string['settings_max_message_1200'] = "20 min";
$string['settings_max_message_15'] = "15 s";
$string['settings_max_message_30'] = "30 s";
$string['settings_max_message_300'] = "5 min";
$string['settings_max_message_60'] = "1 min";
$string['settings_max_message_600'] = "10 min";
$string['settings_phone_informations'] = "Show phone information to students";
$string['settings_post_podcast'] = "Allow users to post to podcast";
$string['settings_private_board'] = "Private";
$string['settings_private_board_comment'] = "Student cannot view other student's discussion threads";
$string['settings_public_board'] = "Public";
$string['settings_public_board_comment'] = "Student can view all discussion threads";
$string['settings_show_reply'] = "Allow Students to reply to messages";
$string['settings_required'] = "Required";
$string['settings_roomId_guest'] = "Room Id:";
$string['settings_short_title'] = "Display short message titles";
$string['settings_show_forward'] = "Allow students to forward messages";
$string['settings_slide_private'] = "Make slide comments private";
$string['settings_slide_private_comment'] = "Student cannot view other student's comments";
$string['settings_start_date'] = "Start date :";
$string['settings_start_thread'] = "Student can start a new thread";
$string['tab_tite_boardInfo'] = "Info";
$string['tab_tite_podcasterInfo'] = "Info";
$string['tab_tite_presentationInfo'] = "Info";
$string['tab_title_access'] = "Access";
$string['tab_title_features'] = "Features";
$string['list_title_liveclassroom'] = "Live Classrooms";
$string['contextbar_new_liveclassroom'] = "New Room";
$string['contextbar_new_pc'] = "New Podcaster";
$string['contextbar_new_board'] = "New Board";
$string['contextbar_new_presentation'] = "New Presentation";
$string['configuration_account_name'] = "Account Name:";
$string['configuration_account_password'] = "Account Password:";
$string['configuration_button_back'] = "Back to Administrator Console";
$string['configuration_button_save'] = "Save";
$string['configuration_database_string'] = "Moodle Database String:";
$string['configuration_expiration_date'] = "Expiration Date:";
$string['configuration_lc'] = "Live Classroom Server Configuration";
$string['configuration_lc_server_url'] = "Live Classroom Server URL:";
$string['configuration_lc_version'] = "Live Classroom Version:";
$string['configuration_test_failed'] = "Configuration Test Failed";
$string['configuration_test_failed_noAccountName'] = "The Account Name field is empty. Please enter an account name and retry.";
$string['configuration_test_failed_noAccountPassword'] = "The Password field is empty. Please enter a password and retry.";
$string['configuration_test_failed_nohttp'] = "The Server URL should start with 'http://'. Please add it and retry.";
$string['configuration_test_failed_noServerURl'] = "The Server URL field is empty. Please enter a server name and retry.";
$string['configuration_test_failed_sentence_end'] = "If the problem persists, please contact your Professional Services Manager.";
$string['configuration_test_failed_traillingSlash'] = "The Server URL should not contain a trailing '\/\'. Please remove it and retry.";
$string['configuration_test_lcfailedConnection'] = "Please check the Live Classroom parameters and retry.";
$string['configuration_test_lc_failed_sentence_start'] = "The test of your Live Classroom server configuration settings was unsuccessful.";
$string['configuration_test_lc_successful_sentence'] = "The test of your Live Classroom server configuration settings was successful.
The configuration settings have been saved.";
$string['configuration_test_successful'] = "Configuration Test Successful";
$string['configuration_version'] = "Integration Version:";
$string['configuration_vt'] = "Voice Tools Server Configuration";
$string['configuration_vt_server_url'] = "Voice Tools Server URL:";
$string['tab_title_advanced'] = "Advanced";
$string['validationElement_create'] = "Create";
$string['advancedPopup_sentence'] = "To access to the advanced settings, all the current settings for this Live Classroom will be saved";
$string['advancedPopup_title'] = "Advanced Settings Popup";
$string['error_license'] = "Access denied. You don't have a valid license to use the Voice Tools. Please speak with your System Administrator or contact Blackboard Collaborate (http://www.blackboard.com/Contact-Us/Contact-Form.aspx) to inquire about subscribing to a license.";
$string['list_no_voiceboards'] = "There are no Voice Boards available at this time";
$string['list_no_liveclassrooms'] = "There are no Live Classrooms available at this time";
$string['list_no_pcs'] = "There are no Blackboard Collaborate Podcaster available at this time";
$string['list_no_voicepresentations'] = "There are no Voice Presentations available at this time";
$string['list_no_voicepodcasters'] = "There are no Voice Presentations available at this time";
$string['message_board_start'] = "The board";
$string['message_grades_start'] = "The grades";
$string['message_board#start'] = "The board";
$string['message_created_end'] = "has been successfully created.";
$string['message_deleted_end'] = "has been successfully deleted";
$string['message_pc_start'] = "The podcaster";
$string['message_pc#start'] = "The podcaster";
$string['message_presentation_start'] = "The presentation";
$string['message_presentation#start'] = "The presentation";
$string['message_room_start'] = "The room";
$string['message_updated_end'] = "has been successfully updated.";
$string['message_updated_grades_end'] = "have been successfully updated.";
$string['popup_dial_numbers'] = "Dial-in numbers:";
$string['popup_dial_pin'] = "Permanent Pin code:";
$string['popup_dial_title'] = "Dial-in Information";
$string['recorder_edit'] = "Edit";
$string['recorder_save'] = "Save";
$string['recorder_title'] = "Annoucement";
$string['tooltip_dial'] = "Click to see information about this room.";
$string['tooltipLC_False_student'] = "Available to students";
$string['tooltipLC_True_student'] = "Unavailable to students";
$string['validationElement_ok'] = "Ok";
$string['settings_title_comment1'] = "Best practice: Use 'course_name - podcast_name";
$string['settings_title_comment2'] = "eg. 'Biology 101 - Extra Help Podcast''";
$string['tooltipVT__student'] = "Unavailable to students";
$string['tooltipVT_1_student'] = "Available to students";
$string['configuration_test_vt_failed_sentence_start1'] = "The test of your Voice Tools server configuration settings was unsuccessful.";
$string['configuration_test_vt_successful_sentence'] = "The test of your Voice Tools server configuration settings was successful.
The configuration settings have been saved.";
$string['configuration_test_vtfailedAccount'] = "Please check the account name and the password s and retry.";
$string['configuration_test_vtfailedConnection'] = "Please check the Voice Tools Server Url parameters and retry.";
$string['contextbar_schedule'] = " : Schedule ";
$string['message_calendar_created_stamessage_board_created_start'] = "The calendar event";
$string['schedule_date'] = "Date:";
$string['schedule_EndTime'] = "Duration:";
$string['schedule_Required'] = "Required";
$string['schedule_StartTime'] = "Start time:";
$string['schedule_Summary'] = "Summary:";
$string['settings_max_message_180'] = "3 min";
$string['error_connection_lc'] = "Moodle cannot connect to the Live Classroom server. Please reload the page or contact your administrator for more information.";
$string['configuration_available_students'] = "Available to students";
$string['settings_advanced_comment_1'] = "For advanced users only: Click on the buttons below to open Live Classroom 
Advanced Settings pages in a new window.";
$string['settings_advanced_comment_2'] = "You must click the 'Save' button in the new window to save your changes.";
$string['settings_advanced_media_settings_button'] = "Advanced Media Settings...";
$string['settings_advanced_room_settings_button'] = "Advanced Room Settings...";


$string['voiceboard_reset_only_replies']="Delete only replies (keep top level threads)";
$string['voiceboard_reset_all']="Delete all messages";
$string['voicepresentation_reset_only_replies']="Delete only replies (keep top level threads)";
$string['voicepresentation_reset_all']="Delete all content";

$string['voicepodcaster_reset_all']="Delete all podcasts";

$string["voicepresentation:presenter"] = "Has presenter access to Voice Presentation";

$string["grade_settings"] = "Grade this board";
$string["points_possible"] = "Points Possible:";

$string["grade_vb_name"]="Voice Board Name:";
$string["grade_last_name"]="Last Name";
$string["grade_first_name"]="First Name";
$string["grade_user_name"]="UserName";
$string["grade_posts"]="Posts";
$string["grade_avg_length"]="Avg. Length";
$string["grade_points"]="Grades";
$string["grade_open_board"]="Open Voice Board";
?>
