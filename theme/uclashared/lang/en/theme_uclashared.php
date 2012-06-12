<?php

$string['pluginname'] = 'UCLA Theme';
$string['region-side-post'] = 'Right';
$string['region-side-pre'] = 'Left';
$string['choosereadme'] = 'The theme from University of California, Los Angeles.';

// The footer links
$string['foodis_contact_ccle'] = 'Contact CCLE';
$string['foolin_contact_ccle'] = 'https://pilot.ccle.ucla.edu/course/view.php?id=110&topic=8';

$string['foodis_about_ccle'] = 'About CCLE';
$string['foolin_about_ccle'] = 'https://pilot.ccle.ucla.edu/course/view.php?id=110&topic=0';

$string['foodis_privacy'] = 'Privacy policy';
$string['foolin_privacy'] = $CFG->wwwroot . '/theme/uclashared/view.php?page=privacy';

$string['foodis_copyright'] = 'Copyright information';
$string['foolin_copyright'] = $CFG->wwwroot . '/theme/uclashared/view.php?page=copyright';

$string['foodis_uclalinks'] = 'UCLA links';
$string['foolin_uclalinks'] = 'https://pilot.ccle.ucla.edu/course/view.php?id=110&topic=10';

$string['foodis_school'] = 'UCLA home';
$string['foolin_school'] = 'http://www.ucla.edu/';

$string['foodis_registrar'] = 'Registrar';
$string['foolin_registrar'] = 'http://www.registrar.ucla.edu/';

$string['foodis_myucla'] = 'MyUCLA';
$string['foolin_myucla'] = 'http://my.ucla.edu/';

$string['control_panel'] = 'Control Panel';

$string['help_n_feedback'] = 'Help & Feedback';

$string['copyright_information'] = '&copy; {$a} UC Regents';

$string['separator__'] = ' | ';

$string['loginas_as'] = ' as ';

// Settings titles, descriptions and defaults
$string['setting_title_footer_links'] = 'Footer links';
$string['setting_desc_footer_links'] = 'This text will be displayed to the right of the set of links in the footer. A separator will be automatically added.';
$string['setting_default_footer_links'] = '';

$string['setting_title_logo_sub_text'] = 'Text under logo';
$string['setting_desc_logo_sub_text'] = 'This is the text displayed under the UCLA | CCLE logo. I.E. Social Sciences Computing.';
$string['setting_default_logo_sub_text'] = 
'<div id="dropdown">Shared Server</div>
<div class="dropdownlist" style="display:none">
<ul>
    <li><span>Chemistry & Biochemistry</span></li>
    <li><span>Computer Science</span></li>
    <li><span>Dentistry</span></li>
    <li><span>Education & Information Studies</span></li>
    <li><span>Engineering</span></li>
    <li><span>Human Genetics</span></li>
    <li><span>Humanities</span></li>
    <li><span>Life Sciences</span></li>
    <li><span>Management</span></li>
    <li><span>Nursing</span></li>
    <li><span>Physical Sciences</span></li>
    <li><span>Physics & Astronomy</span></li>
    <li><span>Public Affairs</span></li>
    <li><span>Public Health</span></li>
    <li><span>World Arts & Architecture</span></li>
</ul>
</div>';

$string['setting_title_logo_sub_dropdown'] = 'Dropdown javascript';
$string['setting_desc_logo_sub_dropdown'] = 'Enable the Javascript shared_server_dropdown.js';
$string['setting_default_logo_sub_dropdown'] = '';

$string['setting_title_disable_post_blocks'] = 'Disable blocks on right';
$string['setting_desc_disable_post_blocks'] = 'Disable courses from adding blocks onto the right side of the course page. The site page will still have blocks on the right.';

$string['setting_title_running_environment'] = 'Server environment';
$string['setting_desc_running_environment'] = 'This option will determine the color of the header to make it easier distinguish which server environment you are on. Default should be \'Production\'.';
$string['setting_default_running_environment'] = 'prod';
$string['env_prod'] = 'Production';
$string['env_stage'] = 'Stage';
$string['env_test'] = 'Test';
$string['env_dev'] = 'Development';

// CCLE-3069: Editing icons preference
$string['noeditingicons'] = 'Site editing style';
$string['useeditingicons'] = 'Use icons';
$string['donotuseeditingicons'] = 'Use text';
$string['donotuseeditingicons2'] = 'Use text, except for buttons that move the module';

//BEGIN UCLA MOD: CCLE-2862-Main_site_logo_image_needs_alt_altribute
$string['UCLA_CCLE_text'] = 'UCLA CCLE Common Collaboration and Learning Environment';
//END UCLA MOD: CCLE-2862

// CCLE-2493 - UCLA Links / CCLE-2827 - Copyright Notice in Footer
$string['copyright'] = 'CCLE copyright information';
$string['privacy'] = 'CCLE privacy policy';
$string['links'] = 'Useful links for UCLA class sites';
$string['error'] = 'Error';     
$string['page_notfound'] = 'The page you requested does not exist';
// EoF
