<?php  
// Private moodle configuration file

// sensitive db details
$CFG->dbhost    = 'localhost';
$CFG->dbname    = 'moodle';
$CFG->dbuser    = 'moodle';
$CFG->dbpass    = 'test';

$CFG->wwwroot   = 'http://localhost:8080/moodle';
$CFG->dataroot  = '/opt/moodledata';

// Default salt to use if you are using the sample database dump at
// https://test.ccle.ucla.edu/vagrant/new_moodle_instance.sql
$CFG->passwordsaltmain = 'a_very_long_salt_string';

// Registrar
$CFG->registrar_dbhost = '';
$CFG->registrar_dbuser = '';
$CFG->registrar_dbpass = '';

// Course Creator
/* To use the CCLE email templates please do the following:
 *  - In your host machine, go to ~/Projects/ccle
 *  - mkdir moodle_configs && cd moodle_configs
 *  - git clone git@github.com:ucla/ccle_email_templates.git
 *  - If not using vagrant, put files in a path that your web server can access
 */
$CFG->forced_plugin_settings['tool_uclacoursecreator']['email_template_dir'] 
        = '/vagrant/moodle_configs/ccle_email_templates/course_creator';

// If you want to allow Moodle to send email, please comment out 
// $CFG->noemailever and set $CFG->divertallemailsto to your email address.
$CFG->noemailever = true;
$CFG->divertallemailsto = '';

$CFG->phpunit_prefix = 'phpu_';
$CFG->phpunit_dataroot = '/opt/phpu_moodledata';
