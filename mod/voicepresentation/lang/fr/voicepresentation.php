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
/* $Id: liveclassroom.php 51390 2007-07-16 21:10:08Z thomasr $ */
// Configuration
$string['modulename'] = 'Voice Presentation';
$string['modulenameplural'] = 'Voice Presentations';
$string['serverconfiguration'] = 'Configuration du Serveur Voice Tools';
$string['explainserverconfiguration'] = 'Voici la configuration du serveur Voice Tools.';
$string['servername'] = 'URL du Serveur';
$string['configservername'] = 'Exemple: http:///myschool.bbbb.net/wimba';
$string['adminusername'] = 'Nom d\'Utilisateur Administrateur';
$string['configadminusername'] = 'Entrez le nom d\'utilisateur de l\'administrateur';
$string['adminpassword'] = 'Mot de Passe Administrateur';
$string['configadminpassword'] = 'Entrez le mot de passe de l\'administrateur';
$string['settinguniqueid'] = 'Unique Prefix ID';
$string['configsettinguniqueid'] = 'Un prefix unique destin&eacute; &agrave; permettre plusieurs instance de Moodle de partager un Serveur Voice Tools sans probl&egrave;me.';
$string['vtversion'] = 'Version du Serveur Voice Tools';
$string['integrationversion'] = 'Version du module d\'Integration';
$string['emptyAdminUsername'] = 'Nom d\'Utilisateur Administrateur vide';
$string['emptyAdminPassword'] = 'Mot de Passe Administrateur vide';
$string['unlimited'] = 'Unlimited';        
$string['wrongconfigurationURLunavailable'] = 'Configuration incorrecte, l\'URL n\'est pas disponible. Plus d\'information est disponible  dans les journaux d\'erreurs';
$string['wrongconfigurationURLincorrect'] = 'Configuration incorrecte, l\'URL est incorrecte. Plus d\'information est disponible  dans les journaux d\'erreurs';
$string['wrongAdminPassword'] = 'Mot de Passe incorrect';
$string['wrongadminpass'] = 'Authentification invalide. Merci de v&eacute;rifier vos identifiants.';
$string['trailingSlash'] = 'Le Nom du Serveur se termine par le caract&egrave;re \'/\'. Veuillez le supprimer et soumettre le formulaire &agrave; nouveau.';

//Add activity form
$string['name'] = 'Nom';
$string['voicetoolstype'] = 'Voice Tools Associ&eacute;';
$string['topicformat'] = 'Sujet de l\'activit&eacute;';
$string['weeksformat'] = 'Semaine de l\'activit&eacute;';
$string['required_fields'] = 'Champs n&eacute;cessaires';
$string['topicdisplay'] = 'Sujet';
$string['firstsection'] = 'Section 0';                  
//Add activity form
$string["activity_name"]="Nom de l'activit&eacute;:";   
$string["duration_calendar"]="Dur&eacute;e:";   
$string['name'] = "Nom de l'activit&eacute;:";

$string['topicformat'] = 'Sujet:';
$string['weeksformat'] = 'Semaine:';
$string['required_fields'] = 'Champs requis';
$string['topicdisplay'] = 'Sujet';

$string['visibletostudents']='Visible pour les &eacute;tudiants';

$string['vtpopupshouldappear.1'] = 'La Voice Tools devrait maintenant appara&icirc;tre.<br/> Dans le cas contraire, cliquez sur ';
$string['vtpopupshouldappear.2'] = 'ce lien';
$string['vtpopupshouldappear.3'] = ' pour l\'ouvrir. ';

$string['addactivity'] = 'Ajouter une activit&eacute;';
$string['in'] = 'dans';
$string['or'] = 'ou';
$string['new'] = 'Nouveau...';
$string["activity_manageTools"]="G&eacute;rer les Voice tools";
$string["activity_tools_not_available"]="Le Voice Tools li&eacute;e &agrave; cette activit&eacute; est actuellement indisponible.<br>Merci de contacter votre professeur";
$string["activity_no_associated_tools"]="Il n'y pas de voice tools associ&eacute;e &agrave; ce cour.<br>Cliquer sur Ok pour en cr&eacute;e une.";
$string["activity_wevtome_to_wimba"]="Bienvenue dans la Voice Tools!";

//calendar
$string['add_calendar']='Ajouter un &eacute;v&egrave;nement au calendrier';  
$string["launch_calendar"]="Lancer Voice Tools";      
$string["description_calendar"] = "Description: ";
//day
$string['day1'] = 'Lundi';
$string['day2'] = 'Mardi';
$string['day3'] = 'Mercredi';
$string['day4'] = 'Jeudi';
$string['day5'] = 'Vendredi';
$string['day6'] = 'Samedi';
$string['day0'] = 'Dimanche';
$string['cannotretreivelistofrooms'] = "Impossible de recup&eacute;rer la liste de classes;";
$string['month1'] = 'Janvier';
$string['month2'] = 'F&eacute;vrier';
$string['month3'] = 'Mars';
$string['month4'] = 'Avril';
$string['month5'] = 'Mai';
$string['month6'] = 'Juin';
$string['month7'] = 'Juillet';
$string['month8'] = 'Aout';
$string['month9'] = 'Septembre';
$string['month10'] = 'Octobre';
$string['month11'] = 'Novembre';
$string['month12'] = 'D&eacute;cembre';
//First Time Use - not use yet
$string['firstRoom'] = 'Main classroom';
$string['secondRoom'] = 'Group 1';
$string['thirdRoom'] = 'Group 2';
$string['fourthRoom'] = 'Group 3';
$string['filterbar_all'] = "Tous";
$string['filterbar_boards'] = "Boards";
$string['filterbar_discussions'] = "Discussions";
$string['filterbar_lectures'] = "Lectures";
$string['filterbar_podcasters'] = "Podcasters";
$string['filterbar_voicepresentation'] = "Presentations";
$string['headerbar_instructor_view'] = "Vue Instructeur";
$string['headerbar_student_view'] = "Vue &Eacute;tudiant";
$string['list_title_board'] = "Voice Board";
$string['list_title_Discussion'] = "Discussions Rooms";
$string['list_title_MainLecture'] = "Main Lectures Rooms";
$string['list_title_pc'] = "Blackboard Collaborate Podcaster";
$string['list_title_presentation'] = "Voice Presentation";
$string['toolbar_content'] = "Contenu";
$string['toolbar_schedule'] = 'Schedule' ;
$string['toolbar_delete'] = 'Suppr.' ;
$string['toolbar_activity'] = "Ajouter une Activit&eacute;";
$string['toolbar_launch'] = "Lancer";
$string['toolbar_new'] = "Nouv.";
$string['toolbar_reports'] = "Rapports";
$string['toolbar_settings'] = "Param&egrave;tres";

$string['error_notfind'] = "Param&egrave;tres de Voice Tools invalides. Merci de contacter votre administrateur pour plus d'information.";
$string['error_room'] = "Param&egrave;tres de Voice Tools invalides. Merci de contacter votre administrateur pour plus d'information.";
$string['error_connection'] = "Moodle ne peut se connecter � la base de donn&eacute;es. Merci de contacter votre administrateur pour plus d'information.";
$string['error_bd'] =  "Moodle ne peut se connecter � la base de donn&eacute;es. Merci de contacter votre administrateur pour plus d'information.";
$string['error_session'] = "En raison d'une inactivitn&eacute;, votre session a expir&eacute.Veuillez recharger la page..";
$string['error_signature'] = "Connexion invalide. Merci de contacter votre administrateur pour plus d'information.";
$string['error_board'] = "Param&egrave;tres des Voice Tools invalides. Merci de contacter votre administrateur pour plus d'information.";
$string['error_connection_vt'] = "Moodle ne peut se connecter au serveur de Voice Tools.. Merci de contacter votre administrateur pour plus d'information.";
$string['error_bdvt'] = "Moodle ne peut se connecter � la base de donn&eacute;es. Merci de contacter votre administrateur pour plus d'information.";
$string['error_roomNotFound'] = "La classe a &eacute;t&eacute; supprim&eacute; ou n'est pas disponible. Merci de contacter votre administrateur pour plus d'information.";
$string['error_boardNotFound'] = "La ressource a &eacute;t&eacute; supprim&eacute; ou n'est pas disponible. Merci de contacter votre administrateur pour plus d'information.";
$string['error_xml'] = "Probl�me pour afficher le composant. Merci de contacter votre administrateur pour plus d'information.";

$string['choiceElement_description_board'] = "Cr&eacute;er un nouveau Voice Board";
$string['choiceElement_description_podcaster'] = "Cr&eacute;er une nouveau Podcaster";
$string['choiceElement_description_presentation'] = "Cr&eacute;er un nouveau Voice Presentation";
$string['choiceElement_description_room'] = "Cr&eacute;er un nouveau Voice Tools";
$string['choiceElement_new_board'] = "Nouveau Board";
$string['choiceElement_new_podcaster'] = "Nouveau Podcaster";
$string['choiceElement_new_presentation'] = "Nouveau Presentation";
$string['choiceElement_new_room'] = "Nouveau Room";
$string['contextbar_new_voicetools'] = "Nouveau Blackboard Collaborate tools";
$string['contextbar_settings'] = ": Param&egrave;tres";
$string['validationElement_cancel'] = "Annuler";
$string['validationElement_create'] = "Cr&eacute;e";
$string['validationElement_saveAll'] = "Sauver";
$string['general_liveclassroom'] = "Voice Tools";
$string['general_pc'] = "Blackboard Collaborate Podcaster";
$string['general_presentation'] = "Voice Presentation";
$string['settings_available'] = "Disponible";
$string['settings_chat_enabled'] = "Autoriser les &eacute;tudiants &agrave; utiliser le chat textuel ";
$string['settings_description'] = "Description :";
$string['settings_discussion'] = "Discussion room";
$string['settings_discussion_comment'] = "Les outils de pr&eacute;sentation sont disponibles aux &eacute;tudiants et aux professeurs.";
$string['settings_discussion_rooms'] = "Discussion rooms:";
$string['settings_enable_student_video_on_startup'] = "Autoriser les &eacute;tudiants &agrave; montrer leur vid&eacute;o par d&eacute;faut ";
$string['settings_enabled_appshare'] = "Activer le partage d'&eacute;cran";
$string['settings_enabled_archiving'] = " Activer l'archivage";
$string['settings_enabled_breakoutrooms'] = "Activer les groupes de travail (Breakout Rooms)";
$string['settings_enabled_guest'] = "Autoriser l'acc&egrave;s public ";
$string['settings_enabled_guest_comment'] = "Note : Ce param&egrave;tre n'est effectif que lorsque l'acc&egrave;s public est activ&eacute; sur le serveur de Voice Tools .
Veuillez contacter votre administrateur pour plus d'informations. ";
$string['settings_enabled_onfly_ppt'] = "Activer l'import &agrave; la vol&eacute;e de pr&eacute;sentations Powerpoint";
$string['settings_enabled_status'] = "Activer les icones de statut";
$string['settings_presenter_console'] = "Console du pr&eacute;senteur:";
$string['settings_eboard'] = "eBoard:";
$string['settings_breakout'] = "Groupes de travail :";
$string['settings_enabled_student_eboard'] = "Autoriser les &eacute;tudiants &agrave; utiliser le eBoard par d&eacute;faut";
$string['settings_enabled_students_breakoutrooms'] = "Les &eacute;tudiants peuvent voir le contenu cr&eacute;&eacute; par les autres groupes";
$string['settings_enabled_students_mainrooms'] = "Les &eacute;tudiants travaillant en groupe peuvent voir le contenu de la salle principale";
$string['settings_hms_simuvtast_restricted'] = "Autoriser les &eacute;tudiants &agrave; utiliser le t&eacute;l&eacute;phone";
$string['settings_hms_two_way_enabled'] = "Autoriser les &eacute;tudiants &agrave; parler par d&eacute;faut ";
$string['settings_lectures_rooms'] = "Lecture rooms:";
$string['settings_mainLecture'] = "Lecture room";
$string['settings_mainLecture_comment'] = "Les outils de pr&eacute;sentation sont disponibles seulement aux professeurs.";
$string['settings_max_user'] = "Nombre d'utilisateurs maximal:";
$string['settings_max_user_limited'] = "Limit&eacute;:";
$string['settings_max_user_unlimited'] = "Illimit&eacute; ";
$string['settings_private_chat_enabled'] = "Autoriser les &eacute;tudiants &agrave; discuter entre eux en chat priv&eacute;";
$string['settings_private_chat_enabled_comment'] = "Note : Les &eacute;tudiants peuvent toujours discuter avec leurs professeurs";
$string['settings_status_appear'] = "Les changements de statut apparaissent dans la fen&ecirc;tre de chat";
$string['settings_status_indicators'] = "Indicateurs de status:";
$string['settings_student_privileges'] = "Privil&eacute;ges des &eacute;tudiants:";
$string['settings_title'] = "Titre :";
$string['settings_type'] = "Type :";
$string['settings_video_bandwidth'] = "Bande passante vid&eacute;o: ";
$string['settings_video_bandwidth_large'] = " Rapide - la plupart utilisent une connexion T1/LAN";
$string['settings_video_bandwidth_medium'] = "Moyenne - la plupart utilisent un modem ADSL/cable";
$string['settings_video_bandwidth_small'] = "Lente - la plupart utilisent un modem dial-up";
$string['tab_tite_roomInfo'] = "Informations";
$string['tab_title_chat'] = "Chat";
$string['tab_title_media'] = "M&eacute;dia";
$string['delay_0'] = "0 s";
$string['delay_1'] = "1 min";
$string['delay_10'] = "10 min";
$string['delay_2'] = "2 min";
$string['delay_20'] = "20 min";
$string['delay_3'] = "3 min";
$string['delay_30'] = "30 min";
$string['delay_30'] = "60 min";
$string['delay_5'] = "5 min";
$string['filterbar_vt'] = "Voice tools";
$string['filterbar_vt'] = "Voice Tools";
$string['general_board'] = "Voice Board";
$string['settings_audio'] = "Audio Quality :";
$string['settings_audio_format_basic'] = "Basic Quality (Telephone quality) - 8 kbit/s - Modem usage";
$string['settings_audio_format_good'] = "Good Quality (FM Radio quality) - 20.8 kbit/s - Broadband";
$string['settings_audio_format_standart'] = "Qualit Quality - 12.8 kbit/s - Modem usage";
$string['settings_audio_format_superior'] = "Superior Quality - 29.6 kbit/s - Broadband usage";
$string['settings_auto_publish_podcast'] = "Podcast auto-published after:";
$string['settings_chrono_order'] = "Autoriser les &eacute; &agrave; r&eacute;pondre aux messages";
$string['settings_comment_slide'] = "Autoriser les &eacute; &agrave; r&eacute;pondre aux messages";
$string['settings_dial_in_informations'] = "Param&egrave;tres de Conf&eacute;rence";
$string['settings_end_date'] = "Date de fin:";
$string['settings_guest_access_comment'] = "Note : Ce param&egrave;tre n'est effectif que lorsque l'acc&egrave;s public est activ&eacute; sur le serveur de Voice Tools. Veuillez contacter votre administrateur pour plus d'informations.";
$string['settings_max_message'] = "Dur&eacute;e max du message audio:";
$string['settings_max_message_120'] = "2 min";
$string['settings_max_message_1200'] = "20 min";
$string['settings_max_message_15'] = "15 s";
$string['settings_max_message_30'] = "30 s";
$string['settings_max_message_300'] = "5 min";
$string['settings_max_message_60'] = "1 min";
$string['settings_max_message_600'] = "10 min";
$string['settings_phone_informations'] = "Show phone information to students";
$string['settings_post_podcast'] = "Autoriser les utilisateurs &agrave; poster dans le Podcast";
$string['settings_private_board'] = "Priv&eacute;e";
$string['settings_private_board_comment'] = "Les &eacute;tudiants ne peuvent pas voir les fils de discussion des autres &eacute;tudiants";
$string['settings_public_board'] = "Publique";

$string['settings_public_board_comment'] = "Les &eacute;tudiants peuvent voir toutes les fils de discussion";
$string['settings_required'] = "Champs requis";
$string['settings_roomId_guest'] = "Id de la classe:";
$string['settings_short_title'] = "Afficher des titres courts";
$string['settings_show_forward'] = "Autoriser les utilisateurs &agrave; faire suivre un message";
$string['settings_slide_private'] = "Autoriser les &eacute;tudiants &agrave; r&eacute;pondre aux messages";
$string['settings_slide_private_comment'] = "Les &eacute;tudiants ne peuvent pas voir les commentaires des autres &eacute;tudiants";
$string['settings_start_date'] = "Date de d&eacute;but:";
$string['settings_start_thread'] = "Autoriser les &eacute;tudiants &agrave; commencer un nouveau sujet";
$string['tab_tite_Info'] = "Information";
$string['tab_title_roomInfo'] = "Information";
$string['tab_tite_podcasterInfo'] = "Information";
$string['tab_tite_presentationInfo'] = "Information";
$string['access'] = "Acc&egrave;s";
$string['tab_title_features'] = "Param&egrave;tres";
$string['list_title_liveclassroom'] = "Voice tools";
$string['contextbar_new_liveclassroom'] = "Nouvelle Room";
$string['contextbar_new_pc'] = "Nouveau Podcaster";
$string['contextbar_new_board'] = "Nouveau Board";
$string['contextbar_new_presentation'] = "Nouvelle Presentation";
$string['configuration_account_name'] = "Nom d\'Utilisateur Administrateur:";
$string['configuration_account_password'] = "Mot de passe Administrateur:";

$string['configuration_button_save'] = "Sauver";

$string['configuration_expiration_date'] = "Date d'expiration:";
$string['configuration_vt'] = "Configuration du Serveur Live Classroom";
$string['configuration_vt_server_url'] = "URl du serveur Live Classroom:";
$string['configuration_vt_version'] = "Version du serveur Live Classroom:";
$string['configuration_test_failed'] = "Configuration incorrecte";
$string['configuration_test_failed_noAccountName'] = "Nom d\'Utilisateur Administrateur vide. Veuillez le remplir et soumettre le formulaire &agrave; nouveau.";
$string['configuration_test_failed_noAccountPassword'] = "Mot de Passe Administrateur vide. Veuillez le remplir et soumettre le formulaire &agrave; nouveau.";
$string['configuration_test_failed_nohttp'] = "L'url du Serveur ne commence pas par \'http://\'. Veuillez l\'ajouter et soumettre le formulaire &agrave; nouveau.";
$string['configuration_test_failed_noServerURl'] = "L'url du Serveur est vide. Veuillez l\'ajouter et soumettre le formulaire &agrave; nouveau.";
$string['configuration_test_failed_sentence_end'] = "Si le probl�me persiste, merci de contacter votre administrateur.";
$string['configuration_test_failed_traillingSlash'] = "Le Nom du Serveur se termine par le caract&egrave;re \'/\'. Veuillez le supprimer et soumettre le formulaire &agrave; nouveau.";
$string['configuration_test_vtfailedConnection'] = "Please check the Live Classroom parameters and retry.";
$string['configuration_test_vt_failed_sentence_start'] = "The test of your Live Classroom server configuration settings was unsuccessful.";
$string['configuration_test_vt_successful_sentence'] = "The test of your Live Classroom server configuration settings was successful.
The configuration settings have been saved.";
$string['configuration_test_successful'] = "Configuration Test Successful";
$string['configuration_version'] = "Version de l'int&eacute;gration:";
$string['configuration_vt'] = "Voice Tools Server Configuration";
$string['configuration_vt_server_url'] = "Voice Tools Server URL:";
$string['tab_title_advanced'] = "Avanc&eacute;";
$string['validationElement_create'] = "Cr&eacute;er";
$string['advancedPopup_sentence'] = "Vous allez acc&eacute;der aux param&egrave;tres avanc&eacute;s, tous les param&egrave;tres de cette Live Classroom vont &ecirc;tre sauv&eacute;s ";
$string['advancedPopup_title'] = "Param&egrave;tres avanc&eacute;s";
$string['error_license'] = "Access denied. You don't have a valid license to use the Voice Tools. Please speak with your System Administrator or contact Blackboard Collaborate (http://www.blackboard.com/Contact-Us/Contact-Form.aspx) to inquire about subscribing to a license.";
$string['list_no_boards'] = "Il n'y a pas de Voice Board disponible en ce moment";
$string['list_no_liveclassrooms'] = "Il n'y a pas de Live Classroom disponible en ce moment";
$string['list_no_pcs'] = "Il n'y a pas de Podcaster disponible en ce moment";
$string['list_no_presentations'] = "Il n'y a pas de Voice Presentation disponible en ce moment";
$string['message_board_created_start'] = "Le board";
$string['message_created_end'] = "a &eacute;t&eacute; cr&eacute;&eacute; avec succ&egrave;s";
$string['message_deleted_end'] = "a &eacute;t&eacute; supprim&eacute; avec succ&egrave;s";
$string['message_podcaster_start'] = "Le podcaster";
$string['message_presentation_start'] = "La presentation";
$string['message_room_start'] = "La room";
$string['message_updated_end'] = "a &eacute;t&eacute; upgrad&eacute; avec succ&egrave;s";
$string['popup_dial_numbers'] = "Num:";
$string['popup_dial_pin'] = "Pin code permanent:";
$string['popup_dial_title'] = "Param&egrave;tres de conf&eacute;rence";
$string['recorder_edit'] = "Editer";
$string['recorder_save'] = "Sauver";
$string['recorder_title'] = "Annonce";
$string['tooltip_dial'] = "Cliquer pour voir des informations suppl&eacute;mentaires sur la room.";
$string['tooltipvt__student'] = "Disponible pour les &eacute;tudiants";
$string['tooltipvt_1_student'] = "Indisponible pour les &eacute;tudiants";
$string['validationElement_ok'] = "Ok";
$string['settings_title_comment1'] = "Best practice: Use 'course_name - podcast_name";
$string['settings_title_comment2'] = "eg. 'Biology 101 - Extra Help Podcast''";
$string['tooltipVT_False_student'] = "Indisponible pour les &eacute;tudiants";
$string['tooltipVT_True_student'] = "Disponible pour les &eacute;tudiants";
$string['configuration_test_vt_failed_sentence_start1'] = "The test of your Voice Tools server configuration settings was unsuccessful.";
$string['configuration_test_vt_successful_sentence'] = "The test of your Voice Tools server configuration settings was successful.

The configuration settings have been saved.";
$string['configuration_test_vtfailedAccount'] = "Please check the account name and the password s and retry.";
$string['configuration_test_vtfailedConnection'] = "Merci de v&eacute;rifier Please check the Voice Tools Server Url parameters and retry.";
$string['contextbar_schedule'] = " : Calendrier ";
$string['message_calendar_created_stamessage_board_created_start'] = "L'&eacutevenement";
$string['schedule_date'] = "Date:";
$string['schedule_EndTime'] = "Date de fin:";
$string['schedule_Required'] = "Champs n&eacute;cessaires";
$string['schedule_StartTime'] = "Date de d&eacute;but:";
$string['schedule_Summary'] = "R&eacute;sum&eacute;:";
$string['settings_max_message_180'] = "3 min";
$string['error_connection_vt'] = "Moodle cannot connect to the Live Classroom server. Please reload the page or contact your administrator for more information.";
$string['configuration_available_students'] = "Disponible pour les &eacute;tudiants";
$string['settings_advanced_comment_1'] = "Utilisateurs avanc&eacute;s : Cliquer sur le bouton suivant pour ouvrir les options avanc&eacute;es de la Live ClassroomClassroom dans une nouvelle fen&ecirc;tre.";
$string['settings_advanced_comment_2'] = "Vous devez cliquer sur le boutton 'Sauver' de la nouvelle fen&circ;re pour sauver les changements.";
$string['settings_advanced_media_settings_button'] = "Param&eacute;tres m&eacute;dia avanc&eacute;s...";
$string['settings_advanced_room_settings_button'] = "Param&eacute;tres avanc&eacute;s de la classe...";

// common VT items
$string['title'] = "Titre: ";
$string['name'] = 'Nom :';
$string['description'] = "Description: ";
$string['public']='Public';
$string['type'] = 'Type :';        
$string['public_comment']='Les &eacute;tudiants peuvent voir toutes les fils de discussion';
$string['start_thread']='Autoriser les &eacute;tudiants &agrave  commencer un nouveau sujet';
$string['private']='Private';
$string['private_comment']='Les &eacute;tudiants ne peuvent pas voir les fils de discussion des autres &eacute;tudiants';
$string['comment_slide']= "Autoriser les &eacute; &agrave  r&eacute;pondre aux messages";
$string['private_slide']= "Activer les messages priv&eacute;s";     
 $string['private_slide_comment']= "Les &eacute;tudiants ne peuvent pas voir les commentaires des autres &eacute;tudiants";     

$string['view_other_thread']="Autoriser les student &agrave  faire suivre un message" ;
$string['post_delay']= "Podcast auto-publi&eacute; apr&agrave�s : ";  
$string['note_post_delay']= "NB : Le professeur peut &eacute;diter ses dernier poste avant de le publier";  
                            
$string['add_calendar']='Ajouter un &eacute;v&eacute;nement dans le calendrier ';
$string['duration_calendar']='Dur&eacute;e :';  
$string['description_calendar']='Description :';  

$string['basicquality'] = 'Basic Quality (Telephone quality) - 8 kbit/s - Modem usage';
$string['standardquality'] = 'Standard Quality - 12.8 kbit/s - Modem usage';
$string['goodquality'] = 'Good Quality (FM Radio quality) - 20.8 kbit/s - Broadband usage';
$string['superiorquality'] ="Superior Quality - 29.6 kbit/s - Broadband usage";
$string['audioquality'] = 'Qualit&eacute; audio';
    

$string['post_podcast']= "Autoriser les utilisateurs &agrave  poster dans le Podcast";  

$string['message_length'] = 'Dur&eacute;e max du message audio';
$string['short_message'] = 'Afficher des titres courts';
$string['chrono_order'] ="Afficher les messages dans l'ordre chronologique";
$string['show_forward'] ="Autoriser les utilisateurs &agrave  faire suivre un message";
$string['available'] ="Disponible";
$string['available_comment']='  NB : Les abonnements au podcast sont toujours disponible';
$string['start_date'] ="Date de d&eacute;but";
$string['end_date'] ="Date de fin";


//Error messages
$string['notoolsavailable'] = "Aucun Voice tools n'est disponible. Vous devez en cr&eacute;er un avant de pouvoir ajouter une activit&eacute;";

//Choice Panel
$string['new_tool'] = 'Nouveau Blackboard Collaborate Voice Tool';
$string['new_board'] = 'Nouveau Board';
$string['new_presentation'] = 'Nouvelle P&eacute;rsentation';  
$string['new_podcaster'] = 'Nouveau Podcaster';   

$string['VoiceBoardDescription'] = "Cr&eacute;er un nouveau Blackboard Collaborate Voice Board";
$string['VoicePresentationDescription'] = "Cr&eacute;er un nouveau Blackboard Collaborate Voice Presentation";
$string['PodcasterDescription'] = "Cr&eacute;er un nouveau Blackboard Collaborate Podcaster";

$string['studentview'] = 'Vue &Eacute;tudiant';
$string['instructorview'] = 'Vue Instructeur';

?>
