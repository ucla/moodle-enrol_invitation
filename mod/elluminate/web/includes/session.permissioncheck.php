<?php
require_course_login($cm->course, true);

//If an error happens, we need the full page setup and output here
if (!$permissions->doesUserHaveViewPermissionsForSession()){
   print_error(get_string($permissions->permissionFailureKey,'elluminate'));
}