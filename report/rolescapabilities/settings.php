<?php
$ADMIN->add('reports',
            new admin_externalpage('reportrolescapabilities',
                                   get_string('rolescapabilities', 'report_rolescapabilities'),
                                   "{$CFG->wwwroot}/report/rolescapabilities/index.php",
                                   'report/rolescapabilities:view'));

$records = $DB->get_records('role',  array(), 'sortorder ASC', 'id,name');
$roles = array();
foreach ($records as $r) {
    $roles[$r->id] = $r->name;
}
$temp = new admin_settingpage('rolescapabilities', get_string('rolescapabilities', 'report_rolescapabilities'));
$temp->add(new admin_setting_configmultiselect('report_rolescapabilities_available_roles', 
                                               get_string('config_available_roles', 'report_rolescapabilities'),
                                               get_string('desc_available_roles', 'report_rolescapabilities'),
                                               null, $roles));
$settings = $temp;
