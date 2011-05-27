<?php  // $Id: settings.php,v 1.1.2.2 2008/11/26 20:58:04 skodak Exp $
$ADMIN->add('courses', new admin_externalpage('courserequestor', get_string('courserequestor', 'report_courserequestor'), "$CFG->wwwroot/$CFG->admin/report/courserequestor/index.php",'report/courserequestor:view'));

?>