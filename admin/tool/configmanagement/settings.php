<?php  // $Id: settings.php,v 1.1.2.2 2008/11/26 20:58:04 skodak Exp $
$ADMIN->add('server', new admin_externalpage('configmanagement', get_string('pluginname', 'tool_configmanagement'), "$CFG->wwwroot/$CFG->admin/tool/configmanagement/index.php"));

?>
