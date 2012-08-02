<?php
/**
 * Copyright (c) 2012 i>clicker (R) <http://www.iclicker.com/dnn/>
 *
 * This file is part of i>clicker Moodle integrate.
 *
 * i>clicker Moodle integrate is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * i>clicker Moodle integrate is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with i>clicker Moodle integrate.  If not, see <http://www.gnu.org/licenses/>.
 */
/* $Id: block_iclicker.php 150 2012-06-19 00:34:38Z azeckoski@gmail.com $ */

// AZ test and force UTF-8 - 吞下玻璃 - Iñtërnâtiônàlizætiøn

// i18n file
$string['app.iclicker']     = 'i>clicker';
$string['app.title']        = 'i>clicker Moodle integrate';
$string['pluginname']       = 'i>clicker Moodle integrate';

// form controls
$string['app.register']     = 'Register';
$string['app.activate']     = 'Activate';
$string['app.disable']      = 'Disable';
$string['app.remove']       = 'Remove';

// for the main registration page
$string['reg.title'] = 'Remote Registration';
$string['reg.remote.instructions'] = 'Enter the 8-character remote ID below to register your i>clicker remote. You may register multiple remotes or remove a remote at any time.';
$string['reg.remote.id.enter'] = 'Enter Your i>clicker Remote ID';
$string['reg.remote.id.header'] = 'i>clicker Remote ID';
$string['reg.registered.date.header'] = 'Registered';
$string['reg.register.clickers'] = 'Register additional clickers';
$string['reg.registration.table.summary'] = 'Lists the registered clickers for the current user; clickerId, date registered, and controls';
$string['reg.iclicker.image.alt'] = 'I-clicker Sample Remote showing the back of the remote with the location of the ID';
$string['reg.register.submit.alt'] = 'Register the clickerId';
$string['reg.disable.submit.alt'] = 'Disable this registration';
$string['reg.remove.submit.alt'] = 'Remove this registration';
$string['reg.reactivate.submit.alt'] = 'Reactivate this registration';
$string['reg.registration.instructions'] = 'To locate your clicker ID, see the back of your remote and enter the series of numbers (and perhaps letters) on the white sticker on the bottom of your clicker.';
$string['reg.registered.instructions'] = 'You have successfully registered your i>clicker remote ID with the system. If you lose or need to tie a second clicker to your student/user ID, you can do so here by adding another clicker ID to your registration. As with your other registration, to locate your clicker ID, see the back of your remote and enter the series of numbers (and perhaps letters) on the white sticker on the bottom of your clicker.';
$string['reg.registered.success'] = 'Registered a Clicker ID ({$a})';
$string['reg.registered.below.success'] = 'Congratulations; you\'ve successfully registered your i>clicker! All of your voting data (previously recorded and future votes) will now be tied to your ID.';
$string['reg.registered.clickerId.empty'] = 'The clicker ID cannot be empty, please fill in the box and try again';
$string['reg.registered.clickerId.duplicate'] = 'Clicker ID ({$a}) is already registered';
$string['reg.registered.below.duplicate'] = 'You have already successfully registered this clicker ({$a}) and it is tied to your user ID.';
$string['reg.registered.clickerId.duplicate.notowned'] = 'Your clicker ({$a}) has already been registered, but to another student. This could be a result of two possibilities: 1) You are sharing a clicker remote with another student in the same course. You may share your i>clicker remote with another student on campus as long as s/he is not in the same course. You cannot share i>clicker remotes with students in the same course/section. 2) You simply mis-entered your remote ID. Please try again. If you receive this message a second time, contact support@iclicker.com for additional help.';
$string['reg.registered.clickerId.invalid'] = 'The clicker ID ({$a}) is invalid, please correct the entry and try again';
$string['reg.activate.success.true'] = 'Reactivated clicker ({$a}) registration';
$string['reg.activate.success.false'] = 'Disabled clicker ({$a}) registration';
$string['reg.remove.success'] = 'Removed clicker ({$a}) registration';
$string['reg.activate.registrationId.empty'] = 'The registrationId cannot be empty, internal error in the form';
$string['reg.remote.faqs'] = 'Remote Registration FAQs';
$string['reg.remote.faq1.question'] = 'Where do I find my remote ID?';
$string['reg.remote.faq1.answer'] = 'Your i>clicker remote ID is printed on a sticker located on the back of your remote. The ID is the 8-character code below the barcode.';
$string['reg.remote.faq2.question'] = 'What do I do if I cannot read the ID printed on my remote?';
$string['reg.remote.faq2.answer'] = 'If your remote ID has rubbed off or is illegible, go to <a href="http://www.iclicker.com/support/findclickerID/">http://www.iclicker.com/support/findclickerID/</a> for instructions on how to recover your remote ID.';
$string['reg.remote.faq3.question'] = 'Are there zeros or the letter O in my clicker ID?';
$string['reg.remote.faq3.answer'] = 'Your clicker ID may contain the number zero; it will never contain the letter O.';
$string['reg.remote.faq4.question'] = 'I\'ve already used my i>clicker remote in class before registering it. Will I still get credit for my votes?';
$string['reg.remote.faq4.answer'] = 'Yes, your votes still count. Your in-class votes were recorded by i>clicker and once you register your remote, these votes will be associated with you the next time your instructor syncs their gradebook or roster.';
$string['reg.remote.faq5.question'] = 'I\'ve lost/broken my remote. Can I register another remote?';
$string['reg.remote.faq5.answer'] = 'Yes, you may register multiple remotes for any reason. Do not delete your old registration. I>clicker will link both remotes to your name so that you receive credit for votes you made with either remote.';
$string['reg.remote.faq6.question'] = 'I use my i>clicker remote for multiple classes. Do I need to register my remote for each class?';
$string['reg.remote.faq6.answer'] = 'No, you only need to register once. Once you register, your registration information will automatically apply to all of the classes in which you are enrolled and using i>clicker.';
$string['reg.remote.faq7.question'] = 'What do I do if I accidentally registered the wrong remote ID?';
$string['reg.remote.faq7.answer'] = 'If you register a wrong remote ID, simply remove the incorrect entry and register again with the correct information.';

// for the instructor page
$string['inst.title'] = 'Instructor Report';
$string['inst.all.courses'] = 'All Courses';
$string['inst.no.courses'] = 'No courses';
$string['inst.courses.header'] = 'Courses Listing';
$string['inst.courses.table.summary'] = 'Lists the courses taught by this instructor; title, link to students listing';
$string['inst.course'] = 'Course';
$string['inst.students'] = 'Students';
$string['inst.students.table.summary'] = 'Lists the students in the selected course; name, email, registration status';
$string['inst.student.name.header'] = 'Name';
$string['inst.student.email.header'] = 'Email';
$string['inst.student.status.header'] = 'Status';
$string['inst.student.registered.true'] = 'Registered';
$string['inst.student.registered.false'] = 'Not registered';
$string['inst.course.view.students'] = 'View Students';

// inst SSO page
$string['inst.sso.title'] = 'Single Sign-On Security Key';
$string['inst.sso.instructions'] = 'This installation of Moodle is configured to use Single Sign-On. When synchronizing with i>clicker, you must enter the security key shown here instead of your password.';
$string['inst.sso.link'] = 'SSO Security Key';
$string['inst.sso.message'] = 'Single Sign On is enabled for this Moodle installation. You will need to use a Security Key with i>clicker instead of your password.';
$string['inst.sso.generate.key'] = 'Generate New Key';
$string['inst.sso.key.message'] = 'Your Security Key';
$string['inst.sso.generated.new.key'] = 'New Security Key generated';
$string['inst.sso.disabled'] = 'SSO is not enabled, you cannot access or generate a Security Key';

// for the admin page
$string['admin.title'] = 'Admin Control';
$string['admin.process.header'] = 'Running process status:';
$string['admin.process.add'] = 'Add i>clicker to all workspaces';
$string['admin.process.remove'] = 'Remove i>clicker from all workspaces';
$string['admin.process.sync'] = 'Sync with Webservices';
$string['admin.process.type.add'] = 'Adding to workspaces';
$string['admin.process.type.remove'] = 'Removing from workspaces';
$string['admin.process.type.sync'] = 'Syncing with Webservices';
$string['admin.process.message.add'] = 'Adding i>clicker tool to all workspaces';
$string['admin.process.message.remove'] = 'Removing i>clicker tool from all workspaces';
$string['admin.process.message.sync'] = 'Syncing all clicker registrations with Webservices';
$string['admin.process.message.locked'] = 'Cannot start long running process ({$a}) because there is already one running on another server';
$string['admin.process.message.inprogress'] = 'Cannot start long running process ({$a}) because there is already one running';
$string['admin.paging'] = 'Paging:';
$string['admin.no.regs'] = 'No registrations';
$string['admin.regs.table.summary'] = 'Lists the registered clickers for all users for the admin; user name, clickerId, date registered, and controls';
$string['admin.remove.submit.alt'] = 'Remove this registration permanently';
$string['admin.username.header'] = 'User name';
$string['admin.controls.header'] = 'Controls';
$string['admin.activate.success.true'] = 'Reactivated clicker ({$a->cid}) registration for {$a->user}';
$string['admin.activate.success.false'] = 'Disabled clicker ({$a->cid}) registration for {$a->user}';
$string['admin.delete.success'] = 'Deleted clicker ({$a->cid}) registration ({$a->rid}) for {$a->user}';
$string['admin.config.header'] = 'i>clicker plugin configuration';
$string['admin.config.usewebservices'] = 'Use National Webservices';
$string['admin.config.domainurl'] = 'Domain URL';
$string['admin.config.workspacepagetitle'] = 'Workspace Tool Title';
$string['admin.config.syncenabled'] = 'National sync enabled';
$string['admin.config.synchour'] = 'Sync runs at hour';
$string['admin.errors.header'] = 'Most Recent Failures';
$string['admin.config.ssoenabled'] = 'Single Sign On Enabled';
$string['admin.config.ssosharedkey'] = 'Shared Key';

// Config
$string['config_general'] = 'General';
$string['config_notify_emails'] = 'Failure notification email';
$string['config_notify_emails_desc'] = 'The email addresses to send notifications to on failures. Blank indicates disabled.';
$string['config_notify_emails_enabled'] = 'Emails will be sent to {$a}';
$string['config_notify_emails_disabled'] = 'DISABLED';
$string['config_disable_alternateid'] = 'Alternate Id disabled';
$string['config_disable_alternateid_desc'] = 'Whether to disable the use of alternate clicker IDs, DEFAULT: false (enable alternate clicker IDs)';
$string['config_webservices'] = 'WebServices';
$string['config_use_national_ws'] = 'Enable WebServices';
$string['config_use_national_ws_desc'] = 'Whether to use the webservices to store clicker registrations';
$string['config_domain_url'] = 'Domain URL';
$string['config_domain_url_desc'] = 'The i>clicker domain URL, leave blank for DEFAULT: the Moodle server URL (e.g. http://your.server.edu)';
$string['config_webservices_url'] = 'WebServices URL';
$string['config_webservices_url_desc'] = 'The webservices URL, leave blank for DEFAULT: the URL for the national i>clicker webservices server';
$string['config_webservices_username'] = 'WebServices Username';
$string['config_webservices_username_desc'] = 'The webservices username, leave blank for the DEFAULT national webservices username';
$string['config_webservices_password'] = 'WebServices Password';
$string['config_webservices_password_desc'] = 'The webservices password, leave blank for the DEFAULT national webservices password';
$string['config_disable_sync'] = 'Disable WebServices Sync';
$string['config_disable_sync_desc'] = 'Disable the national webservices automatic synchronization, DEFAULT: false (sync enabled)';
$string['config_sso'] = 'Single Sign On';
$string['config_sso_enabled'] = 'Single Sign On support is enabled.';
$string['config_sso_disabled'] = 'Single Sign On support is currently disabled. Enter a key below to enable SSO support.';
$string['config_shared_key'] = 'SSO Shared Key';
$string['config_shared_key_desc'] = 'Enable Single Sign On support by setting a shared key (must be at least 10 chars long). Set this to blank to disable SSO support';

// Main block
$string['leaveblanktohide'] = 'leave blank to hide';
$string['invalidcourse']    = 'Invalid CourseID: ';
$string['addpage']          = 'Add a Page';
$string['confirmdelete']    = 'Confirm Delete';
$string['deletepage']       = 'Do you really want to delete \'{$a}\'';
$string['inserterror']      = 'Insert failed';
$string['updateerror']      = 'Update failed';
