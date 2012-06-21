<?php
/**
 *  Adds to the giant settings tree, this report.
 **/
$ADMIN->add('reports', new admin_externalpage(
        'reportsupportconsole', 
        get_string('uclasupportconsole', 'tool_uclasupportconsole'), 
        "$CFG->wwwroot/$CFG->admin/tool/uclasupportconsole/index.php",
        'moodle/site:viewreports'
    ));
