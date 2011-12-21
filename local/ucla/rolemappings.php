<?php
/**
 * Configuration file for role mappings. Will override any existing entries in 
 * the table 'ucla_rolemapping'
 */

// *SYSTEM* defaults
$role['ta']['*SYSTEM*'] = 'ta_admin'; // 02 whenever there is also an 01
$role['ta_instructor']['*SYSTEM*'] = 'ta_instructor'; 
$role['supervising_instructor']['*SYSTEM*'] = 'supervising_instructor'; 
$role['student_instructor']['*SYSTEM*'] = 'editinginstructor';
$role['instructor']['*SYSTEM*'] = 'editinginstructor'; // Always an 01
$role['waitlisted']['*SYSTEM*'] = 'student'; // Student trying to add course
$role['enrolled']['*SYSTEM*'] = 'student'; // Student enrolled in the course

// chemistry roles
$role['ta']['CHEM'] = 'ta'; // 02 whenever there is also an 01
