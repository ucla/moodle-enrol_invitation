<?php
/**
 *  Adds to the giant settings tree, this report.
 **/
$ADMIN->add('reports', new admin_externalpage(
        'reportsupportconsole', 
        get_string('supportconsole', 'tool_supportconsole'), 
        "$CFG->wwwroot/$CFG->admin/tool/supportconsole/index.php",
        'moodle/site:viewreports'
    ));
