<?php
$ADMIN->add('server', new admin_externalpage('configmanagement', 
        get_string('pluginname', 'tool_configmanagement'), 
        "$CFG->wwwroot/$CFG->admin/tool/configmanagement/index.php"));
