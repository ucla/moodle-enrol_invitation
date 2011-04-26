<?php

/**
 *  Settings for UCLA Shared System theme.
 **/

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    // Footer links
    $the_setting = 'footer_links';
    $theme_name = 'theme_uclashared';

    $name = $theme_name . '/' . $the_setting;
    $title = get_string('setting_title_' . $the_setting, $theme_name);
    $description = get_string('setting_desc_' . $the_setting, $theme_name);
    $default = get_string('setting_default_' . $the_setting, $theme_name);
    $setting = new admin_setting_configtextarea($name, $title, $description, $default, PARAM_RAW);
    $settings->add($setting);

    // The sub text
    $the_setting = 'logo_sub_text';
    $name = $theme_name . '/' . $the_setting;
    $title = get_string('setting_title_' . $the_setting, $theme_name);
    $description = get_string('setting_desc_' . $the_setting, $theme_name);
    $default = get_string('setting_default_' . $the_setting, $theme_name);
    $setting = new admin_setting_configtextarea($name, $title, $description, $default, PARAM_RAW);
    $settings->add($setting);

    $the_setting = 'logo_sub_dropdown';
    $name = $theme_name . '/' . $the_setting;
    $title = get_string('setting_title_' . $the_setting, $theme_name);
    $description = get_string('setting_desc_' . $the_setting, $theme_name);
    $default = false; 
    $setting = new admin_setting_configcheckbox($name, $title, $description, $default);
    $settings->add($setting);
} 
