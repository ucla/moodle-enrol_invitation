<?php
/**
 * Configuration for UCLA's Shared Server theme.
 *
 * For full information about creating Moodle themes, see:
 *  http://docs.moodle.org/en/Development:Themes_2.0
 *
 * @copyright 2010 UC Regents
 */

$THEME->name = 'uclashared';
$tn = 'theme_' . $THEME->name;

$THEME->parents = array('base');

$THEME->sheets = array(
    'admin',    // custom admin style changes
    'base',
    'core',     // custom core stlye changes
    'general',
);

$tf_general     = 'course.php';
$tf_course      = 'course.php';
$tf_embedded    = 'embedded.php';
$tf_frontpage   = 'course.php';
$tf_report      = 'course.php';

$disablepostblocks = get_config($tn, 'disable_post_blocks');

$defaultregion = 'side-post';
$enabledregions = array('side-pre');

if ($disablepostblocks) {
    $defaultregion = 'side-pre';
} else {
    $enabledregions[] = 'side-post';
}

/**
 * CCLE-2882 - Control panel missing for some course pages
 * 
 * Going to use global $COURSE object and if it isn't the SITEID, then will
 * enable the control panel for certain layouts that can exist in or out of 
 * courses
 */
global $COURSE;
$control_panel_option = array('controlpanel' => true);
if (SITEID == $COURSE->id) {   // for most cases, user will usually be on course
    $control_panel_option['controlpanel'] =  false;
}

$THEME->layouts = array(
    // Most backwards compatible layout without the blocks 
    // - this is the layout used by default
    'base' => array(
        'file' => $tf_general,
        'regions' => array(),
        'options' => $control_panel_option
    ),
    // Standard layout with blocks, this is recommended for most 
    // pages with general information
    'standard' => array(
        'file' => $tf_general,
        'regions' => $enabledregions,
        'defaultregion' => $defaultregion,
        'options' => $control_panel_option
    ),
    // Main course page
    'course' => array(
        'file' => $tf_course,
        'regions' => $enabledregions,
        'defaultregion' => $defaultregion,
        'options' => array(
            'controlpanel' => true
        )
    ),
    'coursecategory' => array(
        'file' => $tf_general,
        'regions' => $enabledregions,
        'defaultregion' => $defaultregion,
    ),
    // part of course, typical for modules - default page layout if 
    // $cm specified in require_login()
    'incourse' => array(
        'file' => $tf_course,
        'regions' => $enabledregions,
        'defaultregion' => $defaultregion,
    ),
    // The site home page.
    'frontpage' => array(
        'file' => $tf_frontpage,
        'regions' => array('side-pre', 'side-post'),
        'defaultregion' => $defaultregion,
    ),
    // Server administration scripts.
    'admin' => array(
        'file' => $tf_general,
        'regions' => array('side-pre'),
        'defaultregion' => 'side-pre',
        'options' => $control_panel_option
    ),
    // My dashboard page
    'mydashboard' => array(
        'file' => $tf_general,
        'regions' => $enabledregions,
        'defaultregion' => $defaultregion,
        'options' => array(),
    ),
    // My public page
    'mypublic' => array(
        'file' => $tf_general,
        'regions' => $enabledregions,
        'defaultregion' => $defaultregion,
    ),
    'login' => array(
        'file' => $tf_general,
        'regions' => array(),
        'options' => array(),
    ),

    // Pages that appear in pop-up windows - no navigation, 
    // no blocks, no header.
    'popup' => array(
        'file' => $tf_general,
        'regions' => array(),
        'options' => array( 
            'nofooter' => true, 
            'nonavbar' => true, 
            'nocustommenu' => true, 
            'nologininfo' => true
        ),
    ),
    // No blocks and minimal footer - used for legacy frame layouts only!
    'frametop' => array(
        'file' => $tf_general,
        'regions' => array(),
        'options' => array(
            'nofooter' => true
        ),
    ),
    // Embeded pages, like iframe/object embeded in moodleform 
    // - it needs as much space as possible
    'embedded' => array(
        'file' => $tf_embedded,
        'regions' => array(),
        'options' => array(
            'nofooter' => true, 
            'nonavbar' => true, 
            'nocustommenu' => true
        ),
    ),
    // Used during upgrade and install, and for the 'This site is 
    // undergoing maintenance' message.
    // This must not have any blocks, and it is good idea if it does not 
    // have links to
    // other places - for example there should not be a home link 
    // in the footer...
    'maintenance' => array(
        'file' => $tf_general,
        'regions' => array(),
        'options' => array(
            'noblocks' => true, 
            'nofooter' => true, 
            'nonavbar' => true, 
            'nocustommenu' => true
        ),
    ),
    // Should display the content and basic headers only.
    'print' => array(
        'file' => $tf_general,
        'regions' => array(),
        'options' => array(
            'noblocks' => true, 
            'nofooter' => true, 
            'nonavbar' => false, 
            'nocustommenu' => true
        ),
    ),
    // The pagelayout used when a redirection is occuring.
    'redirect' => array(
        'file' => $tf_embedded,
        'regions' => array(),
        'options' => array(
            'nofooter' => true, 
            'nonavbar' => true, 
            'nocustommenu' => true
        ),
    ),
    // The pagelayout used for reports
    'report' => array(
        'file' => $tf_report,
        'regions' => array('side-pre'),
        'defaultregion' => 'side-pre',
        'options' => $control_panel_option
    ),
);

$THEME->rendererfactory = 'theme_overridden_renderer_factory';
$THEME->enable_dock = true;

$dropdown_contents = get_config('theme_' . $THEME->name, 'logo_sub_dropdown');
if ($dropdown_contents) {
    $THEME->javascripts[] = 'jquery-1.5.2.min'; 
    $THEME->javascripts[] = 'shared_server_dropdown';
}
