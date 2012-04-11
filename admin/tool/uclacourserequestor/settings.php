<?php  

defined('MOODLE_INTERNAL') || die;

$ADMIN->add('courses', new admin_externalpage('uclacourserequestor', 
    get_string('pluginname', 'tool_uclacourserequestor'), 
    "$CFG->wwwroot/$CFG->admin/tool/uclacourserequestor/index.php",
    'tool/uclacourserequestor:edit'));

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configtext(
        'tool_uclacourserequest/customfilters',
        get_string('customfilters', 'tool_uclacourserequestor'),
        get_string('customfilters_desc', 'tool_uclacourserequestor'),
        ''
    ));
}
