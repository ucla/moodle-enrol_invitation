<?php

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('uclacoursecreator');

echo $OUTPUT->header();

echo $OUTPUT->box_start('generalbox boxwidthwide boxaligncenter centerpara');
echo $OUTPUT->heading(get_string('uclacoursecreator', 'report_uclacoursecreator'));

// @todo Add a button to launch course creator
// Stolen from admin/report/courseoverview/index.php
echo '<form action="." method="post" id="settingsform">' . "\n";

echo '<p>';
echo '<input type="submit" value="' . get_string('create') . ' ' 
    . get_string('courses') . '" />';
echo '</p>';

echo '</form>' . "\n";

echo $OUTPUT->box_end();

echo $OUTPUT->footer();
