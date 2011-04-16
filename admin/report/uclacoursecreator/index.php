<?php

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('uclacoursecreator');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('uclacoursecreator', 'report_uclacoursecreator'));

// @todo Add stuff here ... but what?

echo $OUTPUT->footer();
