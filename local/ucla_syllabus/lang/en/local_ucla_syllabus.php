<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * English strings for UCLA syllabus plugin
 *
 * @package    local
 * @subpackage ucla_syllabus
 * @copyright  2012 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginadministration'] = 'UCLA syllabus administration';
$string['pluginname'] = 'UCLA syllabus';

// strings for uploading syllabus form
$string['syllabus_manager'] = 'Syllabus Manager';
$string['public_syllabus'] = 'Public syllabus';
$string['public_syllabus_help'] = 'A public syllabus is viewable by viewers who are not associated with the course.';
$string['public_syllabus_none_uploaded'] = 'Please upload a file';
$string['pdf_only'] = 'PDF only';
$string['access'] = 'Access';
$string['accesss_public_info'] = 'Public: Anyone can view (incuding non-logged in users)';
$string['accesss_loggedin_info'] = 'UCLA community: Only logged in users can view';
$string['access_none_selected'] = 'Please select an access type';
$string['access_invalid'] = 'Invalid access type selected';
$string['preview_info'] = 'Is this a "preview syllabus"? Which i a summarized version of the course syllabus intended for public consumption.';
$string['display_name'] = 'Display name';
$string['display_name_default'] = 'Syllabus';
$string['display_name_none_entered'] = 'Please enter a display name';
$string['cannnot_make_db_entry'] = 'Cannot insert entry into syllabus table.';
$string['successful_upload'] = 'Successfully uploaded a syllabus';
$string['invalid_public_syllabus'] = 'Can only have one public syllabus for course';

// strings for displaying syllabus
$string['no_syllabus_uploaded'] = 'No syllabus has been uploaded yet.';
$string['no_syllabus_uploaded_help'] = 'To upload a syllabus file, please "Turn on editing".';
$string['clicktodownload'] = 'Download: {$a}';
$string['syllabus_needs_setup'] = 'Syllabus (needs setup)';

// error general strings
$string['err_missing_courseid'] = 'Missing required courseid';
$string['err_syllabus_mismatch'] = 'Selected syllabus does not belong to course';
$string['err_syllabus_not_allowed'] = 'Sorry, you must be logged in or assciated with the course to view this syllabus';

// web service
$string['ws_header'] = 'Add web service subscription';
$string['subject_area'] = 'Subject area';
$string['subject_area_help'] = 'Subject area to monitor';
$string['leading_srs'] = 'Leading SRS';
$string['leading_srs_rule'] = 'Numeric values only';
$string['post_url'] = 'POST URL';
$string['post_url_required'] = 'You must provide a POST url.';
$string['contact_email'] = 'Contact email';
$string['contact_email_help'] = 'This email will be used to report any problems 
    encountered while attempting to access the provided url.';
$string['contact_email_required'] = 'You must provide a valid contact email.';
$string['token'] = 'Token';
$string['token_help'] = 'Use a token to verify the authenticity of the 
    messages you receive on the POST url.';
$string['select_action'] = 'Service action';
$string['action_alert'] = 'Alert';
$string['action_transfer'] = 'Transfer';

$string['heading'] = 'UCLA syllabus web service';
$string['status'] = 'Status';
$string['enable'] = 'Enable';
$string['disable'] = 'Disable';
$string['delete'] = 'Delete';

$string['email_subject'] = 'UCLA syllabus service error';
$string['email_msg'] = 'The following URL returned errors when attempting
to nofiy of syllaubs update.:
    {$a->url}
    
Please update this URL.';