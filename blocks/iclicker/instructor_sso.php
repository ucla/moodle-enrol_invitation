<?php
/**
 * Copyright (c) 2012 i>clicker (R) <http://www.iclicker.com/dnn/>
 *
 * This file is part of i>clicker Moodle integrate.
 *
 * i>clicker Moodle integrate is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * i>clicker Moodle integrate is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with i>clicker Moodle integrate.  If not, see <http://www.gnu.org/licenses/>.
 */
/* $Id: instructor.php 119 2012-04-15 22:16:23Z azeckoski@gmail.com $ */

/**
 * Handles rendering the form for creating new pages and the submission of the form as well
 * NOTE: table is named iclicker
 */

require_once ('../../config.php');
global $CFG, $USER, $COURSE, $OUTPUT, $PAGE;
require_once ('iclicker_service.php');
require_once ('controller.php');

$site = get_site();
require_login($site);

// activate the controller
$cntlr = new iclicker_controller();
$cntlr->processInstructorSSO();
extract($cntlr->results);

// begin rendering
$PAGE->set_title( strip_tags($site->fullname).':'.iclicker_service::msg('app.iclicker').':'.iclicker_service::msg('inst.sso.title') );
$PAGE->set_heading( iclicker_service::msg('app.iclicker').' '.iclicker_service::msg('inst.sso.title') );
$PAGE->navbar->add(iclicker_service::msg('inst.sso.title'));
$PAGE->set_focuscontrol('');
$PAGE->set_cacheable(false);
$PAGE->requires->css(iclicker_service::BLOCK_PATH.'/css/iclicker.css');
$PAGE->set_url(iclicker_service::BLOCK_PATH.'/instructor_sso.php');
//$PAGE->requires->js('mod/mymod/styles.css');
echo $OUTPUT->header();
?>
<div class="iclicker">

    <?php
    // show messages if there are any to show
    require ('user_messages.php');
    ?>

    <div class="main_content">
        <?php if ($sso_enabled) { ?>
        <div class="inst_sso_instructions">
            <?php echo iclicker_service::msg('inst.sso.instructions') ?>
        </div>
        <div class="inst_sso_controls">
            <span class="sso_control_message"><?php echo iclicker_service::msg('inst.sso.key.message') ?>: </span>
            <span class="sso_control_key"><?php echo $sso_user_key ?></span>
            <form class="sso_control_form" method="post" style="display:inline;">
                <input type="submit" class="generate_button" name="generateKey" value="<?php echo iclicker_service::msg('inst.sso.generate.key') ?>" />
            </form>
        </div>
        <?php } else { ?>
        <div class="error"><?php echo iclicker_service::msg('inst.sso.disabled') ?></div>
        <?php } ?>
    </div>

    <div class="nav_links">
        <?php
        $reg_link = '<a class="nav_link" href="'.iclicker_service::block_url('registration.php').'">'.iclicker_service::msg('reg.title').'</a>';
        $nav_links = $reg_link.PHP_EOL;
        // the other links
        $nav_links .= ' | <a class="nav_link" href="'.iclicker_service::block_url('instructor.php').'">'.iclicker_service::msg('inst.title').'</a>'.PHP_EOL;
        if (iclicker_service::$block_iclicker_sso_enabled) {
            $nav_links .= ' | <a class="nav_link current_nav_link" href="'.iclicker_service::block_url('instructor_sso.php').'">'.iclicker_service::msg('inst.sso.title').'</a>'.PHP_EOL;
        }
        if (iclicker_service::is_admin()) {
            $nav_links .= ' | <a class="nav_link" href="'.iclicker_service::block_url('admin.php').'">'.iclicker_service::msg('admin.title').'</a>'.PHP_EOL;
        }
        echo $nav_links;
        ?>
    </div>

    <div class="iclicker_version">Version <?php echo iclicker_service::VERSION ?> (<?php echo iclicker_service::BLOCK_VERSION ?>)</div>

</div>

<?php echo $OUTPUT->footer(); ?>
