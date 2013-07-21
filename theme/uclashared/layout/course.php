<?php

// Process and simplify all the options
$hasheading = ($PAGE->heading);
$hasnavbar = (empty($PAGE->layout_options['nonavbar']) 
    && $PAGE->has_navbar());
$hasfooter = (empty($PAGE->layout_options['nofooter']));
$hassidepre = (empty($PAGE->layout_options['noblocks']) 
    && $PAGE->blocks->region_has_content('side-pre', $OUTPUT));
$hassidepost = (empty($PAGE->layout_options['noblocks']) 
    && $PAGE->blocks->region_has_content('side-post', $OUTPUT));
$haslogininfo = (empty($PAGE->layout_options['nologininfo']));
$hasintrobanner = (!empty($PAGE->layout_options['introbanner']));

// START UCLA MODIFICATION CCLE-2452
// Hide Control Panel button if user is not logged in
$showcontrolpanel = (!empty($PAGE->layout_options['controlpanel']) && isloggedin() && !isguestuser());

$showsidepre = ($hassidepre 
    && !$PAGE->blocks->region_completely_docked('side-pre', $OUTPUT));
$showsidepost = ($hassidepost 
    && !$PAGE->blocks->region_completely_docked('side-post', $OUTPUT));
    
//$PAGE->requires->yui_module('yui2-animation');

$custommenu = $OUTPUT->custom_menu();
$hascustommenu = (empty($PAGE->layout_options['nocustommenu']) 
    && !empty($custommenu));

$bodyclasses = array();
if ($showsidepre && !$showsidepost) {
    $bodyclasses[] = 'side-pre-only';
} else if ($showsidepost && !$showsidepre) {
    $bodyclasses[] = 'side-post-only';
} else if (!$showsidepost && !$showsidepre) {
    $bodyclasses[] = 'content-only';
}

if ($hasintrobanner) {
    $bodyclasses[] = 'front-page';
}

if ($hascustommenu) {
    $bodyclasses[] = 'has_custom_menu';
}

$envflag = $OUTPUT->get_environment();

// Attach login check
// This prevents forms from being submitted when the user is not logged into site
$PAGE->requires->yui_module('moodle-local_ucla-logincheck', 
        'M.local_ucla.logincheck.init', 
        array(array('userid' => $USER->id)));
$PAGE->requires->strings_for_js(
        array(
            'logincheck_success', 
            'longincheck_login', 
            'logincheck_idfail', 
            'logincheck_networkfail'), 
        'local_ucla');

// Detect OS via user agent
$agent = $_SERVER['HTTP_USER_AGENT'];
$windowsos = strpos($agent, 'Windows') ? true : false;

// Do all drawing

echo $OUTPUT->doctype() ?>
<html <?php echo $OUTPUT->htmlattributes() ?>>
<head>
    <title><?php echo $PAGE->title ?></title>
    <link rel="shortcut icon" href="<?php echo $OUTPUT->pix_url('favicon', 'theme')?>" />
    <link rel="apple-touch-icon" href="<?php echo $OUTPUT->pix_url('apple-touch-icon', 'theme')?>" />
    <?php 
    // Do not load font on Windows OS
    // Chrome and Firefox don't have proper font-smoothing
    // IE does have font-smoothing, so load font for IE 8 and above
    if(!$windowsos) { ?>
        <link href='https://fonts.googleapis.com/css?family=Lato:400,400italic,700,900' rel='stylesheet' type='text/css'>
    <?php } ?>
    <?php echo $OUTPUT->standard_head_html() ?>
    
    <!--[if gt IE 7]>
        <link href='https://fonts.googleapis.com/css?family=Lato:400,400italic,700,900' rel='stylesheet' type='text/css'>   
    <![endif]-->
    
</head>
<body id="<?php echo $PAGE->bodyid ?>" class="<?php echo $PAGE->bodyclasses.' '.join(' ', $bodyclasses) ?>">
<?php echo $OUTPUT->standard_top_of_body_html() ?>
<div id="page">
<?php if ($hasheading || $hasnavbar) { ?>
    <div id="page-header" class="env-<?php echo $envflag ?>">
        <div class="header-logo" >
            <div class="ucla-logo" >
                 <?php echo $OUTPUT->logo('ucla-logo', 'theme') ?>
            </div>
            <a class ="ccle-logo" href="<?php echo $CFG->wwwroot ?>">CCLE</a>
            <div class="ccle-logo-text">
                common collaboration <br/>& learning environment
            </div>
        </div>
        
        <div class="header-login" >
            <?php echo $OUTPUT->help_feedback_link() ?>
            <a class="login" href="<?php echo get_login_url() ?>">Login</a>
        </div>
        <div class="header-links" >
        <?php
            if ($haslogininfo) {
                echo $OUTPUT->login_info();
            }
        ?>
        </div>
        <div class="weeks-display" >
            <?php echo $OUTPUT->weeks_display() ?>
        </div>
    </div>
 
<?php } ?>

<?php if ($hasnavbar && !$hasintrobanner) { ?>
<div class="navbar clearfix">
    <div class="breadcrumb"><?php echo $OUTPUT->navbar(); ?></div>
    <?php if ($showcontrolpanel) { ?>
        <div class="control-panel">
            <?php echo $OUTPUT->control_panel_button() ?>
        </div>
    <?php } ?>
    <div class="navbutton"> <?php echo $PAGE->button; ?></div>
    
</div>
<?php } ?>
<!-- END OF HEADER -->

    <div id="page-content">
        <?php
            // Determine if we need to display banner
            // @todo: right now it only works for 'red' alerts
            if(!during_initial_install() && get_config('block_ucla_alert', 'alert_sitewide')) {

                // If config is set, then alert-block exists... 
                // There might be some pages that don't load the block however..
                if(!class_exists('ucla_alert_banner_site')) {
                    $file = $CFG->dirroot . '/blocks/ucla_alert/locallib.php';
                    require_once($file);
                }
                
                // Display banner
                $banner = new ucla_alert_banner(SITEID);
                echo $banner->render();
            }
        ?>
        <div id="region-main-box">
            <div id="region-post-box">
            
                <div id="region-main-wrap" >
                    <div id="region-main">
                        <div class="region-content">
                            <?php
                                // Alert banner display for courses
                                // @todo: finish implementing
//                                if($banner = ucla_alert_banner::load($COURSE->id)) {
//                                    echo $banner->alert();
//                                }
                            ?>
                            <?php echo core_renderer::MAIN_CONTENT_TOKEN ?>
                        </div>
                    </div>
                </div>
                
                <?php if ($hassidepre) { ?>
                <div id="region-pre" class="block-region">
                    <div class="region-content">
                        <?php echo $OUTPUT->blocks_for_region('side-pre') ?>
                    </div>
                </div>
                <?php } ?>
                
                <?php if ($hassidepost) { ?>
                <div id="region-post" class="block-region">
                    <div class="region-content">
                        <?php echo $OUTPUT->blocks_for_region('side-post') ?>
                    </div>
                </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

    <!-- START OF FOOTER -->
    <?php if ($hasfooter) { ?>
    <div id="page-footer" >
    <!--
        <p class="helplink"><?php echo page_doc_link(get_string('moodledocslink')) ?></p>
    -->
        <span id="copyright-info">
        <?php echo $OUTPUT->copyright_info() ?>
        </span>

        <span id="footer-links">
        <?php echo $OUTPUT->footer_links() ?>
        </span>

        <?php echo $OUTPUT->standard_footer_html() ?>
    </div>
    <?php } ?>

<?php echo $OUTPUT->standard_end_of_body_html() ?>
</body>
</html>
