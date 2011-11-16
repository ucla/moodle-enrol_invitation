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
$string['settings_send_to_description'] = 'Where to send help form messags.';
$string['settings_send_to_email_option'] = 'Email';
$string['settings_send_to_jira_option'] = 'JIRA';

$string['settings_email_header'] = 'Email settings';
$string['settings_email'] = 'Email';
$string['settings_email_description'] = 'If "Sent To" is set to "Email", then help form will send email to this address';

$string['settings_jira_header'] = 'JIRA settings';
$string['settings_jira_description'] = 'If "Sent To" is set to "JIRA", then help form will use these values to create a ticket in JIRA.';
$string['settings_jira_endpoint'] = 'JIRA endpoint';
$string['settings_jira_user'] = 'JIRA User';
$string['settings_jira_password'] = 'JIRA password';
$string['settings_jira_pid'] = 'JIRA PID';
$string['settings_jira_default_assignee'] = 'JIRA default assignee';

$string['settings_support_contacts_header'] = 'Support contacts';
$string['settings_support_contacts_description'] = 'For a given context, admins can define a point of contact. For example, if an admin ' . 
        'defines a point of contact for the subject area "English", then help requests for "English" courses will go to that ' . 
        'contact. A point of contact can be either an email address or JIRA user, but the type must match what is choose for ' . 
        'the "Sent To" option. A context can be a category or specific class.';
$string['settings_support_contacts'] = 'Support contacts';
$string['settings_support_contacts_table_context'] = 'Context';
$string['settings_support_contacts_table_contact'] = 'Contact';

// error messages
$string['error_empty_send_to'] = 'Please select a "Send To" option';
$string['error_sending_message'] = 'Sorry, there was a problem with sending your feedback. Please try again later.';

// success messages
$string['success_sending_message'] = 'Thank you for your feedback. If you included your email address someone will respond to your message soon.';
