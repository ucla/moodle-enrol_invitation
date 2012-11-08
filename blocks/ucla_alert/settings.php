<?php

defined('MOODLE_INTERNAL') || die;

$ADMIN->add('server', new admin_externalpage('ucla_alert', 'UCLA alert block', "$CFG->wwwroot/blocks/ucla_alert/edit.php?id=1", 'moodle/course:update'));
