<?php

$string['pluginname'] = 'UCLA TA sites';

$string['no_tasites'] = 'There is no elegible {$a} to make a site for.';

$string['tasitefor'] = '{$a->fullname} TA site: {$a->course_fullname}';

// Building
$string['delete_tasites'] = 'Delete existing TA sites';
$string['delete_tadesc'] = 'Check to delete TA site <a href="{$a->course_url}">{$a->course_shortname}</a> for {$a->fullname}.';

$string['build_tasites'] = 'Make TA sites';
$string['build_tadesc'] = 'Check to make a TA site for {$a->fullname}.';

$string['built_tasite'] = 'TA site <a href="{$a->course_url}">{$a->course_shortname}</a> for {$a->fullname} was successfully built.';
$string['deleted_tasite'] = 'Deleting {$a->course_fullname}...<br />{$a->delete_text}<br />TA site for {$a->fullname} was successfully deleted.';

$string['returntocourse'] = 'Return to main course';

// Exceptions
$string['xzibit'] = 'Cannot make TA sites from within a TA site.';
$string['setupenrol'] = 'The enrollment plugin "meta" needs to be enabled system-wide in order to use TA sites.';
$string['setuproles'] = 'Could not find role for {$a}.';
