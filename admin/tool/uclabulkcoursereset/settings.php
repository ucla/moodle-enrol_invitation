<?php

defined('MOODLE_INTERNAL') || die;

$ADMIN->add('courses', new admin_externalpage('uclabulkcoursereset', 
    get_string('pluginname', 'tool_uclabulkcoursereset'), 
    "$CFG->wwwroot/$CFG->admin/tool/uclabulkcoursereset/index.php")
           );

