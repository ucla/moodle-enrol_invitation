<?php  

defined('MOODLE_INTERNAL') || die;

$ADMIN->add('courses', new admin_externalpage('uclacourserequestor', 
    get_string('pluginname', 'tool_uclacourserequestor'), 
    "$CFG->wwwroot/$CFG->admin/tool/uclacourserequestor/index.php",
    'tool/uclacourserequestor:edit'));

