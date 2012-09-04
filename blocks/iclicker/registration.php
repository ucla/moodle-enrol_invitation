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
/* $Id: registration.php 165 2012-08-23 01:12:09Z azeckoski@gmail.com $ */

/**
 * Handles rendering the form for creating new pages and the submission of the form as well
 */

require_once ('../../config.php');
global $CFG, $USER, $COURSE, $OUTPUT, $PAGE;
require_once ('iclicker_service.php');
require_once ('controller.php');

$site = get_site();
require_login($site);

// activate the controller
$cntlr = new iclicker_controller();
$cntlr->processRegistration();
extract($cntlr->results);

// begin rendering
$PAGE->set_title( strip_tags($site->fullname).':'.iclicker_service::msg('app.iclicker').':'.iclicker_service::msg('reg.title') );
$PAGE->set_heading( iclicker_service::msg('app.iclicker').' '.iclicker_service::msg('reg.title') );
$PAGE->navbar->add(iclicker_service::msg('reg.title'));
$PAGE->set_focuscontrol('');
$PAGE->set_cacheable(false);
// NOTE: switching over to locally hosted JS and CSS files
$PAGE->requires->js(iclicker_service::BLOCK_PATH.'/js/jquery-1.5.2.min.js', true);
$PAGE->requires->js(iclicker_service::BLOCK_PATH.'/js/jquery-ui-1.8.min.js', true);
//$PAGE->requires->js( new moodle_url('https://ajax.googleapis.com/ajax/libs/jquery/1.5.2/jquery.min.js'), true);
//$PAGE->requires->js( new moodle_url('http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/jquery-ui.min.js'), true);
$PAGE->requires->css(iclicker_service::BLOCK_PATH.'/css/jquery-ui-1.8.css');
//$PAGE->requires->css( new moodle_url('http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css'), true);
$PAGE->requires->css(iclicker_service::BLOCK_PATH.'/css/iclicker.css');
$PAGE->set_url(iclicker_service::BLOCK_PATH.'/registration.php');
//$PAGE->requires->js('mod/mymod/styles.css');
echo $OUTPUT->header();
?>
<div class="iclicker">

    <?php
    // show messages if there are any to show
    require ('user_messages.php');
    ?>

    <div class="main_content">
        <div class="columns_container">
            <div class="left_column">
                <p><?php echo iclicker_service::msg('reg.remote.instructions') ?></p>

                <form method="post" id="registerForm" style="display: inline;">
                    <input type="hidden" name="register" value="true" />
                    <p class="highlighted">
                        <strong><?php echo iclicker_service::msg('reg.remote.id.enter') ?>:</strong>
                        <input name="clickerId" type="text" size="10" maxlength="8" value="<?php echo $clicker_id_val ?>" />
                        <input type="submit" class="registerButton" value="<?php echo iclicker_service::msg('app.register') ?>"
                               alt="<?php echo iclicker_service::msg('reg.register.submit.alt') ?>" />
                    </p>
                </form>

                <?php if (!empty($regs)) { ?>
                <table class="remotes" summary="<?php echo iclicker_service::msg('reg.registration.table.summary') ?>">
                    <colgroup>
                        <col width="40%" />
                        <col width="40%" />
                        <col />
                    </colgroup>
                    <tr>
                        <th><?php echo iclicker_service::msg('reg.remote.id.header') ?></th>
                        <th><?php echo iclicker_service::msg('reg.registered.date.header') ?></th>
                        <th>&nbsp;</th>
                    </tr>
                    <?php foreach($regs as $reg) { ?>
                    <tr>
                        <td><?php echo $reg->clicker_id ?></td>
                        <td><?php echo iclicker_service::df($reg->timecreated) ?></td>
                        <td>
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="remove" value="remove" />
                                <input type="hidden" name="registrationId" value="<?php echo $reg->id ?>" />
                                <input type="submit" class="small" value="<?php echo iclicker_service::msg('app.remove') ?>" alt="<?php echo iclicker_service::msg('reg.remove.submit.alt') ?>" />
                            </form>
                        </td>
                    </tr>
                    <?php } ?>
                </table>
                <?php } ?>

                <?php if ($below_messages) { ?>
                <!-- registration below messages area -->
                <div class="registration_below_messages_holder style5" style="margin-top: 1em;">
                    <div class="registration_below_messages">
                        <?php foreach ($below_messages as $message) { ?>
                        <p class="registration_below_message"><?php echo $message ?></p>
                        <?php } ?>
                    </div>
                </div>
                <?php } ?>
            </div>

            <div class="right_column">
                <h3><?php echo iclicker_service::msg('reg.remote.faqs') ?></h3>

                <div id="accordion">
                    <h3><a href="#"><?php echo iclicker_service::msg('reg.remote.faq1.question') ?></a></h3>
                    <div>
                        <?php echo iclicker_service::msg('reg.remote.faq1.answer') ?>
                        <img src="img/clickers.png" alt="<?php echo iclicker_service::msg('reg.iclicker.image.alt') ?>" />
                    </div>

                    <h3><a href="#"><?php echo iclicker_service::msg('reg.remote.faq2.question') ?></a></h3>
                    <div><?php echo iclicker_service::msg('reg.remote.faq2.answer') ?></div>

                    <h3><a href="#"><?php echo iclicker_service::msg('reg.remote.faq3.question') ?></a></h3>
                    <div><?php echo iclicker_service::msg('reg.remote.faq3.answer') ?></div>

                    <h3><a href="#"><?php echo iclicker_service::msg('reg.remote.faq4.question') ?></a></h3>
                    <div><?php echo iclicker_service::msg('reg.remote.faq4.answer') ?></div>

                    <h3><a href="#"><?php echo iclicker_service::msg('reg.remote.faq5.question') ?></a></h3>
                    <div><?php echo iclicker_service::msg('reg.remote.faq5.answer') ?></div>

                    <h3><a href="#"><?php echo iclicker_service::msg('reg.remote.faq6.question') ?></a></h3>
                    <div><?php echo iclicker_service::msg('reg.remote.faq6.answer') ?></div>

                    <h3><a href="#"><?php echo iclicker_service::msg('reg.remote.faq7.question') ?></a></h3>
                    <div><?php echo iclicker_service::msg('reg.remote.faq7.answer') ?></div>

                    <h3><a href="#"><?php echo iclicker_service::msg('reg.remote.faq8.question') ?></a></h3>
                    <div><?php echo iclicker_service::msg('reg.remote.faq8.answer') ?></div>

                </div>

            </div>
        </div>
    </div>

    <div class="nav_links">
        <?php
        $reg_link = '<a class="nav_link current_nav_link" href="'.iclicker_service::block_url('registration.php').'">'.iclicker_service::msg('reg.title').'</a>';
        $nav_links = $reg_link.PHP_EOL;
        // the other links
        if (iclicker_service::is_admin()) {
            $nav_links .= ' | <a class="nav_link" href="'.iclicker_service::block_url('admin.php').'">'.iclicker_service::msg('admin.title').'</a>'.PHP_EOL;
        } else if (iclicker_service::is_instructor()) {
            $nav_links .= ' | <a class="nav_link" href="'.iclicker_service::block_url('instructor.php').'">'.iclicker_service::msg('inst.title').'</a>'.PHP_EOL;
            if (iclicker_service::$block_iclicker_sso_enabled) {
                $nav_links .= ' | <a class="nav_link" href="'.iclicker_service::block_url('instructor_sso.php').'">'.iclicker_service::msg('inst.sso.title').'</a>'.PHP_EOL;
            }
        }
        echo $nav_links;
        ?>
    </div>

    <div class="iclicker_version">Version <?php echo iclicker_service::VERSION ?> (<?php echo iclicker_service::BLOCK_VERSION ?>)</div>

</div>

<script type="text/javascript">
    $(document).ready(function() {
        $("#accordion").accordion({ active: 0,
            alwaysOpen: false,
            animated: false,
            autoHeight: false,
            collapsible: true });
    });
</script>

<?php echo $OUTPUT->footer(); ?>
