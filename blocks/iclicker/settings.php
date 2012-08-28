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
/* $Id: settings.php 142 2012-05-08 15:31:27Z azeckoski@gmail.com $ */

defined('MOODLE_INTERNAL') || die;

// control the config settings for this plugin
require_once ('iclicker_service.php');
$block_name = iclicker_service::BLOCK_NAME;
if ($ADMIN->fulltree) {
    // general
    $settings->add(
        new admin_setting_heading('block_iclicker_general_heading',
            get_string('config_general', $block_name),
            null
        )
    );
    $settings->add(
        new admin_setting_configtext(iclicker_service::BLOCK_NAME.'/block_iclicker_notify_emails',
            get_string('config_notify_emails', $block_name),
            get_string('config_notify_emails_desc', $block_name),
            '', //50,200
            PARAM_TEXT,
            50
        )
    );
    $settings->add(
        new admin_setting_configcheckbox(iclicker_service::BLOCK_NAME.'/block_iclicker_disable_alternateid',
            get_string('config_disable_alternateid', $block_name),
            get_string('config_disable_alternateid_desc', $block_name),
            0
        )
    );
    // WS
/** webservices code is currently disabled ********************
    $settings->add(
        new admin_setting_heading('block_iclicker_ws_heading',
            get_string('config_webservices', $block_name),
            null
        )
    );
    $settings->add(
        new admin_setting_configcheckbox('block_iclicker_use_national_ws',
            get_string('config_use_national_ws', $block_name),
            get_string('config_use_national_ws_desc', $block_name),
            0
        )
    );
    $settings->add(
        new admin_setting_configtext('block_iclicker_domain_url',
            get_string('config_domain_url', $block_name),
            get_string('config_domain_url_desc', $block_name),
            '', //50,200
            PARAM_TEXT,
            50
        )
    );
    $settings->add(
        new admin_setting_configtext('block_iclicker_webservices_url',
            get_string('config_webservices_url', $block_name),
            get_string('config_webservices_url_desc', $block_name),
            '', //iclicker_service::NATIONAL_WS_URL, //50,200
            PARAM_TEXT,
            75
        )
    );
    $settings->add(
        new admin_setting_configtext('block_iclicker_webservices_username',
            get_string('config_webservices_username', $block_name),
            get_string('config_webservices_username_desc', $block_name),
            '', //iclicker_service::NATIONAL_WS_URL, //50,200
            PARAM_TEXT,
            30
        )
    );
    $settings->add(
        new admin_setting_configtext('block_iclicker_webservices_password',
            get_string('config_webservices_password', $block_name),
            get_string('config_webservices_password_desc', $block_name),
            '', //iclicker_service::NATIONAL_WS_URL, //50,200
            PARAM_TEXT,
            30
        )
    );
 *******************************************/
    // SSO
    $headerDesc = get_string('config_sso_disabled', $block_name);
    $currentSSOkey = get_config($block_name, 'block_iclicker_sso_shared_key');
    if (!empty($currentSSOkey)) {
        $headerDesc = get_string('config_sso_enabled', $block_name);
        $timestamp = time();
        $headerDesc .= ' [Sample encoded key: '.iclicker_service::makeEncodedKey($timestamp).'|'.$timestamp.']';
    }
    $settings->add(
        new admin_setting_heading('block_iclicker_sso_heading',
            get_string('config_sso', $block_name),
            $headerDesc
        )
    );
    $settings->add(
        new admin_setting_configtext(iclicker_service::BLOCK_NAME.'/block_iclicker_sso_shared_key',
            get_string('config_shared_key', $block_name),
            get_string('config_shared_key_desc', $block_name),
            '', //50,200
            PARAM_TEXT,
            50
        )
    );
}
?>
