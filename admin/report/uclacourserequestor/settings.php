<?php  // $Id: settings.php,v 1.1.2.2 2008/11/26 20:58:04 skodak Exp $
$ADMIN->add('courses', new admin_externalpage('uclacourserequestor', get_string('pluginname', 'report_uclacourserequestor'), "$CFG->wwwroot/$CFG->admin/report/uclacourserequestor/index.php",'report/uclacourserequestor:view'));

?>