<?php

$string['pluginname'] = 'UCLA browse-by';
$string['displayname'] = 'Browse by:';

// Links displayed in the block
$string['link_subjarea'] = 'Subject area';
$string['link_division'] = 'Division';
$string['link_instructor'] = 'Instructor';
$string['link_collab'] = 'Collaboration sites';
$string['link_mycourses'] = 'My sites';

// This is for errors
$string['illegaltype'] = 'Browse by "{$a}" does not exist.';

// Subject area
$string['subjarea_title'] = 'Subject areas in {$a}';
$string['all_subjareas'] = 'Subject areas'; 

// Divisions
$string['division_title'] = 'Divisions';
$string['division_none'] = 'No divisions were found on this server';
$string['division_noterm'] = 'No divisions were found for this term, please try another term.';

// Instructors
$string['instructorsall'] = 'Instructors';
$string['instructorswith'] = 'Instructors with a last name starting with "{$a}"';
$string['instructosallterm'] = 'All instructors for {$a}';
$string['noinstructorsterm'] = 'There are no instructors teaching on this server for {$a}, please try another term.';
$string['noinstructors'] = 'There were no instructors found.';
$string['selectinstructorletter'] = "Please select a letter to view instructors.";

// Instructors -> courses
$string['coursesbyinstr'] = 'Courses taught by {$a}';
$string['coursesinsubjarea'] = 'Courses in {$a}';

// Collaborations
$string['collab_notfound'] = 'No collaboration sites found.';
$string['collab_notcollab'] = 'This category is not considered a category for collaboration sites.';
$string['collab_coursesincat'] = 'Sites in: ';
$string['collab_catsincat'] = 'Categories in: ';
$string['collab_nocatsincat'] = 'Available collaboration site categories';
$string['collab_viewall'] = 'Collaboration sites';
$string['collab_allcatsincat'] = 'Available collaboration site categories';
$string['collab_viewin'] = 'Collaboration sites in {$a}';
$string['collab_nocoursesincat'] = 'No sites were found in this category';

$string['sitename'] = 'Site name';
$string['projectlead'] = 'Project lead';
$string['coursecreators'] = 'Course owner';

// Options
$string['title_division'] = 'Disable browse-by division';
$string['title_subjarea'] = 'Disable browse-by subject areas';
$string['title_instructor'] = 'Disable browse-by instructors';
$string['title_collab'] = 'Disable browse collaboration sites';
$string['title_ignore_coursenum'] = 'Course numbers to hide';
$string['title_allow_acttypes'] = 'Activity types to allow';

$string['desc_division'] = 'Check box to disable the ability to use divisions to narrow down subject areas to look at.';
$string['desc_subjarea'] = 'Check box to disable the ability to use subject areas to narrow down courses to look at.';
$string['desc_instructor'] = 'Check box to disable the ability to see the courses an instructor is teaching.';
$string['desc_collab'] = 'Check box to disable the ability for guests to browse collaboration sites.';
$string['desc_ignore_coursenum'] = 'Courses whose course number is equivalent to one of these values will NOT be displayed if they do NOT have an associated course site. Please leave this as a comma-separated field.';
$string['desc_allow_acttypes'] = 'Comma-delimited list. Courses with the activity type (i.e. "LEC,SEM") specified in this list will be visible in the browse-by results. If you want all courses to be visible, leave this blank.';

$string['title_syncallterms'] = 'Sync for all terms';
$string['desc_syncallterms'] = 'Check box to enable synchronization of all terms that is available on this server. It will uncheck itself after it runs.';

$string['title_use_local_courses'] = 'Use local courses';
$string['desc_use_local_courses'] = 'Check box to allow for local courses to override the URL that has been provided by the Registrar. Otherwise, the data that the Registrar has provided will be considered infallible.';

// Courses view
$string['nousersinrole'] = 'N / A';
$string['session_break'] = 'Summer session {$a}';
$string['registrar_link'] = 'Registrar';
$string['coursesnotfound'] = 'No courses found for given subject area and term';

// Headers in courses view
$string['course'] = 'Course';
$string['instructors'] = 'Instructors';
$string['coursetitle'] = 'Course title';

// CCLE-3141 - Prepare for post M2 deployment
$string['spring2012'] = 'If you cannot find the course for which ' . 
        'you\'re looking, please visit our archive server ' . 
        '(<a href="https://archive.ccle.ucla.edu">https://archive.ccle.ucla.edu</a>).';
$string['prespring2012'] = 'You are currently on the CCLE production server ' . 
        '(<a href="https://ccle.ucla.edu">https://ccle.ucla.edu</a>). For ' . 
        'courses from Winter 2012 or earlier, please visit our archive server ' . 
        '(<a href="https://archive.ccle.ucla.edu">https://archive.ccle.ucla.edu</a>).';
