# Enrollment Invitation Moodle Plugin

The Site Invitation plug-in for Moodle allows instructor to invite students to their site and grant necessary access and role to them.

## Download

Visit the [GitHub page for the Syllabus plug-in](https://github.com/ucla/moodle-enrol_invitation) to download a package.

To clone the package into your organization's own repository from the command
line, clone the plug-in repository into your own Moodle install folder by 
doing the following at your Moodle root directory:

    $ git clone https://github.com/ucla/moodle-enrol_invitation enrol/invitation

## Installation

1. Add the plugin into /enrol folder.
2. Log into Moodle as administrator.  Go to Site Administration->Notifications to install the plugin.

## Features

With this enrollment plugin, instructor can invite and grant access to users to their course and site.  The invitation is sent via email that contains a link with an unique invitation token. 
When the user clicks on the link and login to the site, (s)he is automatically enrolled into the course. If the user dose not have an account, (s)he will need to create one.  The user can be assigned with the roles in the the system during the process of creating the invitation.

Only a limited number of invitations can be sent per course/day. However you can change the limitation. Moreover used invitations are not count.

## How to use

1. Instructor login to his course.  Go to Control Panel->invite users
2. On the Invite user page, instructor can enter the role that he likes to assign to the user and the email address for the invitation to send out to.
3. User will receive the inviation email that contains detail instruction.  The user will need to create a Shibboleth login account if he does not have one.  
4. Once user has the account, he will log into Moodle by clicking the link in the email.  He can now access the course and site he invited to.










