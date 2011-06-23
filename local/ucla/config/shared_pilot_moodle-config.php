<?php  // Moodle configuration file

unset($CFG);
global $CFG;
$CFG = new stdClass();

$CFG->dbtype    = 'mysqli';
$CFG->dblibrary = 'native';
$CFG->dbhost    = 'db2.ccle.ucla.edu';
$CFG->dbname    = 'moodle';
$CFG->dbuser    = 'moodleuser';
$CFG->dbpass    = 'db4moodle';
$CFG->prefix    = 'mdl_';
$CFG->dboptions = array (
  'dbpersist' => 0,
  'dbsocket' => 1,
);

$CFG->wwwroot   = 'https://pilot.ccle.ucla.edu';
$CFG->dataroot  = '/moodle/moodle_data';
$CFG->admin     = 'admin';

$CFG->directorypermissions = 0777;
$CFG->dbsessions = '/usr/local/moodle/sessions';

$CFG->passwordsaltmain = 'PYcU1vr<Ork>;(Vkc;i5=)tA@';

require_once(dirname(__FILE__) . '/lib/setup.php');

// There is no php closing tag in this file,
// it is intentional because it prevents trailing whitespace problems!
