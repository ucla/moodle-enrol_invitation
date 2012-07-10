<?php
/**
 *  Adds to the giant settings tree, this report.
 **/
$ADMIN->add('reports', new admin_externalpage(
        'reportsupportconsole', 
        get_string('pluginname', 'tool_uclasupportconsole'), 
        "$CFG->wwwroot/$CFG->admin/tool/uclasupportconsole/index.php",
        'tool/uclasupportconsole:view'
    ));
