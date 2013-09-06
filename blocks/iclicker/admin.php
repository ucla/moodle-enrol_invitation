<?php
/**
 * Copyright (c) 2009 i>clicker (R) <http://www.iclicker.com/dnn/>
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
/* $Id: admin.php 177 2012-11-05 23:25:58Z azeckoski@gmail.com $ */

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
$cntlr->processAdmin();
extract($cntlr->results);

// begin rendering
$PAGE->set_title( strip_tags($site->fullname).':'.iclicker_service::msg('app.iclicker').':'.iclicker_service::msg('admin.title') );
$PAGE->set_heading( iclicker_service::msg('app.iclicker').' '.iclicker_service::msg('admin.title') );
$PAGE->navbar->add(iclicker_service::msg('admin.title'));
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
$PAGE->set_url(iclicker_service::BLOCK_PATH.'/admin.php');
echo $OUTPUT->header();
?>
<div class="iclicker">

    <?php
    // show messages if there are any to show
    require ('user_messages.php');
    ?>

    <div class="main_content">

        <div class="main_content_header">
            <!-- pager control -->
            <div class="paging_bar" style="float:left;">
                <?php echo iclicker_service::msg('admin.paging') ?>
                <?php if ($total_count > 0) {
                    echo $pagerHTML;
                } else {
                    echo '<i>'.iclicker_service::msg('admin.no.regs').'</i>';
                } ?>
            </div>
            <div class="search_filters" style="float:right;">
                <form method="get" action="<?php echo iclicker_service::block_url('admin.php') ?>" style="margin:0;">
                    <input type="hidden" name="page" value="<?php echo $page ?>" />
                    <input type="hidden" name="sort" value="<?php echo $sort ?>" />

                    <span><?php echo iclicker_service::msg('admin.search.id') ?></span>
                    <input name="search" value="<?php echo $search ?>" class="clicker_id_filter" type="text" size="8" maxlength="12" />

                    <span><?php echo iclicker_service::msg('admin.search.start') ?></span>
                    <input name="start_date" value="<?php echo $startDate ?>" class="datepicker startdate date_picker_marker" type="text" size="8" maxlength="12" title="yyyy-mm-dd" />
                    <span><?php echo iclicker_service::msg('admin.search.end') ?></span>
                    <input name="end_date" value="<?php echo $endDate ?>" class="datepicker enddate date_picker_marker" type="text" size="8" maxlength="12" title="yyyy-mm-dd" />

                    <input type="submit" class="small" value="<?php echo iclicker_service::msg('admin.search.search') ?>" alt="<?php echo iclicker_service::msg('admin.search.search') ?>" />
                    <input name="purge" id="purgeFormSubmit" type="submit" class="small" value="<?php echo iclicker_service::msg('admin.search.purge') ?>" alt="<?php echo iclicker_service::msg('admin.search.purge') ?>" />
                </form>
                <form method="get" style="margin:0;">
                    <input type="submit" class="small" value="<?php echo iclicker_service::msg('admin.search.reset') ?>" alt="<?php echo iclicker_service::msg('admin.search.reset') ?>" />
                </form>
            </div>
        </div>

        <!-- clicker registration listing -->
        <table width="90%" border="1" cellspacing="0" cellpadding="0"
               summary="<?php echo iclicker_service::msg('admin.regs.table.summary') ?>">
            <thead>
            <tr class="registration_row header_row">
                <th width="30%" scope="col" height="25" valign="middle" bgcolor="#e8e8e8" class="style5">
                    <?php echo iclicker_service::msg('admin.username.header') ?>
                </th>
                <th width="20%" scope="col" height="25" valign="middle" bgcolor="#e8e8e8" class="style5">
                    <a href="<?php echo $adminPath.'&sort=clicker_id&page='.$page ?>"><?php echo iclicker_service::msg('reg.remote.id.header') ?></a>
                </th>
                <th width="20%" scope="col" height="25" valign="middle" bgcolor="#e8e8e8" class="style5">
                    <a href="<?php echo $adminPath.'&sort=timecreated&page='.$page ?>"><?php echo iclicker_service::msg('reg.registered.date.header') ?></a>
                </th>
                <th width="30%" scope="col" height="25" valign="middle" bgcolor="#e8e8e8" class="style5" nowrap="nowrap">
                    <a href="<?php echo $adminPath.'&sort=activated&page='.$page ?>"><?php echo iclicker_service::msg('admin.controls.header') ?></a>
                </th>
            </tr>
            </thead>
            <tbody>
            <?php foreach($registrations as $registration) { ?>
            <tr class="registration_row data_row style1 <?php echo $registration->activated ? '' : 'disabled' ?>">
                <td class="user_name" align="center"><?php echo $registration->user_display_name ?></td>
                <td class="clicker_id" align="center"><?php echo $registration->clicker_id ?></td>
                <td class="date" align="center"><?php echo iclicker_service::df($registration->timecreated) ?></td>
                <td class="controls" align="center">
                    <form method="post">
                        <input type="hidden" name="page" value="<?php echo $page ?>" />
                        <input type="hidden" name="sort" value="<?php echo $sort ?>" />
                        <input type="hidden" name="search" value="<?php echo $search ?>" />
                        <input type="hidden" name="start_date" value="<?php echo $startDate ?>" />
                        <input type="hidden" name="end_date" value="<?php echo $endDate ?>" />
                        <input type="hidden" name="registrationId" value="<?php echo $registration->id ?>" />
                        <?php if ($registration->activated) { ?>
                        <input type="button" class="small" value="<?php echo iclicker_service::msg('app.activate') ?>" disabled="disabled" />
                        <input type="submit" class="small" value="<?php echo iclicker_service::msg('app.disable') ?>" alt="<?php echo iclicker_service::msg('reg.disable.submit.alt') ?>" />
                        <input type="hidden" name="activate" value="0" />
                        <?php } else { ?>
                        <input type="submit" class="small" value="<?php echo iclicker_service::msg('app.activate') ?>" alt="<?php echo iclicker_service::msg('reg.reactivate.submit.alt') ?>" />
                        <input type="button" class="small" value="<?php echo iclicker_service::msg('app.disable') ?>" disabled="disabled" />
                        <input type="hidden" name="activate" value="1" />
                        <?php } ?>
                    </form>
                    <form method="post">
                        <input type="hidden" name="page" value="<?php echo $page ?>" />
                        <input type="hidden" name="sort" value="<?php echo $sort ?>" />
                        <input type="hidden" name="registrationId" value="<?php echo $registration->id ?>" />
                        <input type="hidden" name="remove" value="0" />
                        <input type="submit" class="small" value="<?php echo iclicker_service::msg('app.remove') ?>" alt="<?php echo iclicker_service::msg('admin.remove.submit.alt') ?>" />
                    </form>
                </td>
            </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <?php if (count($recent_failures) > 0) { ?>
    <div class="admin_errors">
        <fieldset class="visibleFS">
            <legend class="admin_errors_header">
                <?php echo iclicker_service::msg('admin.errors.header') ?>
            </legend>
            <ul class="tight admin_errors_list">
                <?php foreach($recent_failures as $message) { ?>
                <li class="admin_errors_list_item"><?php echo $message ?></li>
                <?php } ?>
            </ul>
        </fieldset>
    </div>
    <?php } ?>

    <div class="download_link"><a href="<?php echo iclicker_service::block_url('adminCSV.php') ?>"><?php echo iclicker_service::msg('admin.csv.download') ?></a></div>

    <div class="admin_config">
        <fieldset class="visibleFS">
            <legend class="admin_config_header">
                <?php echo iclicker_service::msg('admin.config.header') ?>
            </legend>
            <ul class="tight admin_config_list">
                <?php if ($sso_enabled) { ?>
                <li class="admin_config_list_item">
                    <span class="sso_enabled"><?php echo iclicker_service::msg('admin.config.ssoenabled') ?></span>:
                    <?php echo iclicker_service::msg('admin.config.ssosharedkey') ?>: <span class="sso_shared_key"><?php echo $sso_shared_key; ?></span>
                </li>
                <?php } ?>
                <li class="admin_config_list_item">
                    <?php echo iclicker_service::msg('config_notify_emails') ?>:
                    <?php echo (!empty($adminEmailAddress) ? iclicker_service::msg('config_notify_emails_enabled', $adminEmailAddress) : iclicker_service::msg('config_notify_emails_disabled')) ?>
                </li>
                <?php /* forced to sharing on for now
                <li class="admin_config_list_item">
                    <?php echo iclicker_service::msg('config_allow_sharing') ?>:
                    <?php echo (iclicker_service::$allow_remote_sharing) ? iclicker_service::msg('app.allowed') : iclicker_service::msg('config_notify_emails_disabled') ?>
                </li> */ ?>
            </ul>
        </fieldset>
    </div>

    <div class="nav_links">
        <?php
        $reg_link = '<a class="nav_link" href="'.iclicker_service::block_url('registration.php').'">'.iclicker_service::msg('reg.title').'</a>';
        $nav_links = $reg_link.PHP_EOL;
        // the other links
        if (iclicker_service::is_instructor()) {
            $nav_links .= ' | <a class="nav_link" href="'.iclicker_service::block_url('instructor.php').'">'.iclicker_service::msg('inst.title').'</a>'.PHP_EOL;
            if (iclicker_service::$block_iclicker_sso_enabled) {
                $nav_links .= ' | <a class="nav_link" href="'.iclicker_service::block_url('instructor_sso.php').'">'.iclicker_service::msg('inst.sso.title').'</a>'.PHP_EOL;
            }
        }
        $nav_links .= ' | <a class="nav_link current_nav_link" href="'.iclicker_service::block_url('admin.php').'">'.iclicker_service::msg('admin.title').'</a>'.PHP_EOL;
        echo $nav_links;
        ?>
    </div>

    <div class="iclicker_version">Version <?php echo iclicker_service::VERSION ?> (<?php echo iclicker_service::BLOCK_VERSION ?>)</div>

</div>

<script type="text/javascript">
    jQuery(document).ready(function() {
        // date pickers
        jQuery( ".iclicker .date_picker_marker" ).datepicker({
            dateFormat: "yy-mm-dd",
            changeMonth: true,
            changeYear: true
        });
        // purge confirmation
        jQuery("#purgeFormSubmit").click(function(e) {
            if (!confirm("<?php echo iclicker_service::msg('admin.search.purge.confirm', $total_count) ?>")) {
                e.preventDefault();
                return false;
            } else {
                // switch the form to POST for purge submission
                $(this).closest("form").attr("method", "POST");
            }
            return true;
        });
    });
</script>

<?php echo $OUTPUT->footer(); ?>
