i>clicker integrate for Moodle 2
================================
The >clicker integrate for Moodle 2 plug-in allows instructors to easily synchronize their i>clicker and i>grader data with
their campus Moodle server. Learn more about i>clicker products and features from the i>clicker website. (http://www.iclicker.com)

Features
--------
Students
   * Register and manage their remotes from within Moodle
Instructors
   * View reports within Moodle showing the status of student registrations in their classes
   * Download their Moodle roster and registrations directly into i>grader
   * Upload i>clicker scores from i>grader directly into Moodle's Gradebook
Administrators
   * Configurable for SSO
   * View and manage remote registrations

Moodle Compatibility
--------------------
This version of the plug-in works with Moodle 2.1 or newer and supports single sign-on. Users running Moodle v1.8-1.9
without SSO may use the previous version of the plug-in. Installations of Moodle older than version 1.8 are not supported by the i>clicker integrate plug-in.

This plugin will work with Moodle 2.1 or newer. It is developed as a Moodle plugin/block.

Download Binary
---------------
The plugin can also be downloaded from the project site::

    http://code.google.com/p/iclicker-moodle-integrate/

Source
------
The source code for this plugin is located at::

    - trunk (unstable): http://iclicker-moodle-integrate.googlecode.com/svn/trunk/moodle2x
    - tags (stable): http://iclicker-moodle-integrate.googlecode.com/svn/tags/moodle2x

Install
-------
To install this plugin just extract the contents into your server dir MOODLE_HOME/blocks (so you have MOODLE_HOME/blocks/iclicker).

Once the plugin is installed, you can place the block into your instance.
This is the recommended way to setup the block::

    1. Login to your Moodle instance as an admin
    2. Click on Site Administration > Notifications
    3. Confirm the installation of the iclicker block (continue confirmation until complete)

See the Moodle docs for help installing plugins/blocks::

    http://docs.moodle.org/en/Installing_contributed_modules_or_plugins

Unit Tests
----------
If you are interested you can run the unit tests for the plugin to verify that it is compatible with your installation.
If all the tests pass then you can be confident that the plugin will work correctly.

For Moodle 2.3 or older:
NOTE: You need to have at least 1 user (other than the admin) in your moodle instance to run the tests successfully.
Go to the following URL in your moodle instance when logged in as an admin::

    /admin/report/unittest/index.php?path=blocks%2Ficlicker

For Moodle 2.4 or newer:
To run the tests, please see the instructions here:
http://docs.moodle.org/dev/PHPUnit

To run only the tests for the iclicker plugin:
vendor/bin/phpunit --group block_iclicker


Configuration
-------------
The configuration of the block is handled in the typical Moodle way. You must login as an administrator and then go to::

    Site Administration > Modules > Blocks > Manage blocks > i>clicker > Settings

Usage
-----
Once the installation is complete the i>clicker block should appear in the block lists and can be added anywhere
that a standard block can. It will determine permissions automatically so you can place it anywhere in your Moodle
installation that you see fit. The instructions below cover the recommended setup method but you are welcome
to place the block anywhere you like.

Adding the plugin/block to My Moodle for all users::

    # Login to your Moodle instance as an admin
    # Click on Site Administration > Modules > Blocks > Sticky blocks
    # Select My Moodle from the pulldown
        - NOTE: You should have My Moodle enabled under Site Administration > Appearance > My Moodle > mymoodleredirect
    # Select i>clicker from the Blocks pulldown

Adding the plugin/block to a specific user home::

    # Login to your Moodle instance
    # Click your site name in the upper left to go back to the site root
    # Click on the Turn editing on button in the upper right
    # Select i>clicker from the Blocks pulldown
    # Click on the Turn editing off button in the upper right

Configuring the system settings for the plugin/block::

    # Login to your Moodle instance as an admin
    # Click on Site Administration > Modules > Blocks > Manage blocks
    # Click on Settings to the right of the i>clicker listing
    # Adjust the block system settings according to your needs
    # Block setup is complete

REST data feeds
---------------
The REST data feeds for the block are documented and located at::

    /blocks/iclicker/rest.php

Release Process
---------------
Create a new tag of the code to release, then create a new binary and place it on the site::

    svn export http://iclicker-moodle-integrate.googlecode.com/svn/tags/TAG iclicker
    zip -r iclicker-VERSION.zip iclicker

Help
----
Send questions or comments to:
Chad Moeller (chad.moeller@macmillan.com), i>clicker Sales Engineer

This document is in `reST (reStructuredText) <http://docutils.sourceforge.net/rst.html>`_ format
and can be converted to html using the `online converter <http://www.tele3.cz/jbar/rest/rest.html>`_
or the `rst2a converter api <http://rst2a.com/api/>`_ or a command line tool (rst2html.py README README.html)

-Aaron Zeckoski (azeckoski @ vt.edu)
