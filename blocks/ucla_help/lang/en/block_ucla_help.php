<?php
$string['pluginname'] = 'Help & Feedback';

// help form text
$string['name_field'] = 'Name';
$string['email_field'] = 'Email';
$string['description_field'] = 'Details';

$string['submit_button'] = 'Submit';

$string['error_empty_description'] = 'A description is required to send feedback';

$string['helpbox_header'] = 'Help/Feedback';
$string['helpbox_text_default'] = 'Please use the settings option of the help block to set what displays in the Help Box';

$string['helpform_header'] = 'Report a problem';
$string['helpform_text'] = 'Use the form below to report a problem or error. Include your email address so we can get back to you.';

// used by message being sent
$string['message_header'] = 'Moodle feedback from {$a}';

// settings page text
$string['settings_set_in_config'] = 'WARNING: Value set in config.php. Any value set here will be ignored by the system. Check block\'s config.php to edit or view true value.';

$string['settings_boxtext'] = 'Help Box Text';
$string['settings_boxtext_description'] = 'Text that will appear next to the feedback form.';

$string['settings_send_to'] = 'Send To';
$string['settings_send_to_description'] = 'Where to send feedback form messages.';
$string['settings_send_to_email_option'] = 'Email';
$string['settings_send_to_jira_option'] = 'JIRA';

$string['settings_email_header'] = 'Email settings';
$string['settings_email'] = 'Email';
$string['settings_email_description'] = 'If the "Send To" config option is set to "Email", then all completed feedback forms will be sent this e-mail address.';

$string['settings_jira_header'] = 'JIRA settings';
$string['settings_jira_description'] = 'If the "Send To" config option is set to "JIRA", then all completed feedback forms will automatically create a ticket in JIRA based on these settings.';
$string['settings_jira_endpoint'] = 'JIRA endpoint';
$string['settings_jira_user'] = 'JIRA user';
$string['settings_jira_password'] = 'JIRA password';
$string['settings_jira_pid'] = 'JIRA PID';
$string['settings_jira_default_assignee'] = 'JIRA default assignee';

$string['settings_support_contacts_header'] = 'Support contacts';
$string['settings_support_contacts_description'] = '<p>If a user clicks on a "Help & Feedback" link while in a course, admins ' . 
        'can define a support contact based on context levels.</p><p>For example, if a user is in English 1 (shortname=eng1, category=English) ' . 
        'and submits a feedback form, then if an admin setup a support contact for the context "eng1", then that person will ' . 
        'be contacted. Else if an admin setup a support contact for the context "English", then that person will be contacted. ' . 
        'Else the contact specified at the "System" context will be contacted' . 
        '</p><p>A point of contact can be either an email address or JIRA user, but the type must match what is choose for ' . 
        'the "Sent To" option. A context can be a category or shortname.</p>';
$string['settings_support_contacts'] = 'Support contacts';
$string['settings_support_contacts_table_context'] = 'Context';
$string['settings_support_contacts_table_contact'] = 'Contact';

// error messages
$string['error_empty_send_to'] = 'Please select a "Send To" option';
$string['error_sending_message'] = 'Sorry, there was a problem with sending your feedback. Please try again later.';

// success messages
$string['success_sending_message'] = 'Thank you for your feedback. If you included your email address someone will respond to your message soon.';
