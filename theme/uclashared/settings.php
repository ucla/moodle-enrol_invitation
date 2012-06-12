<?php

/**
 *  Settings for UCLA Shared System theme.
 **/

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    // Footer links
    $theme_name = 'theme_uclashared';

    $options = array(
        'prod' => get_string('env_prod', $theme_name),
        'stage' => get_string('env_stage', $theme_name),
        'test' => get_string('env_test', $theme_name),
        'dev' => get_string('env_dev', $theme_name)
    );

    $the_setting = 'running_environment';
    $name = $theme_name . '/' . $the_setting;
    $title = get_string('setting_title_' . $the_setting, $theme_name);
    $description = get_string('setting_desc_' . $the_setting, $theme_name);
    $default = get_string('setting_default_' . $the_setting, $theme_name);
    $setting = new admin_setting_configselect($name, $title, $description, 
            $default, $options);
    $settings->add($setting);

//    $the_setting = 'footer_links';
//    $name = $theme_name . '/' . $the_setting;
//    $title = get_string('setting_title_' . $the_setting, $theme_name);
//    $description = get_string('setting_desc_' . $the_setting, $theme_name);
//    $default = get_string('setting_default_' . $the_setting, $theme_name);
//    $setting = new admin_setting_configtextarea($name, $title, $description, 
//            $default, PARAM_RAW);
//    $settings->add($setting);

    // The sub text
    $the_setting = 'logo_sub_text';
    $name = $theme_name . '/' . $the_setting;
    $title = get_string('setting_title_' . $the_setting, $theme_name);
    $description = get_string('setting_desc_' . $the_setting, $theme_name);
    $default = get_string('setting_default_' . $the_setting, $theme_name);
    $setting = new admin_setting_configtextarea($name, $title, $description, 
            $default, PARAM_RAW);
    $settings->add($setting);

    $the_setting = 'logo_sub_dropdown';
    $name = $theme_name . '/' . $the_setting;
    $title = get_string('setting_title_' . $the_setting, $theme_name);
    $description = get_string('setting_desc_' . $the_setting, $theme_name);
    $default = false; 
    $setting = new admin_setting_configcheckbox($name, $title, $description, 
            $default);
    $settings->add($setting);

    $the_setting = 'disable_post_blocks';
    $name = $theme_name . '/' . $the_setting;
    $title = get_string('setting_title_' . $the_setting, $theme_name);
    $description = get_string('setting_desc_' . $the_setting, $theme_name);
    $default = false; 
    $setting = new admin_setting_configcheckbox($name, $title, $description, 
            $default);
    $settings->add($setting);
} 

// EoF
