<?php

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); /// It must be included from a Moodle page.
}

class admin_setting_liveclassroom_classroomversion extends admin_setting {
/**
 * not a setting, just text
 * @param string $name unique ascii name, either 'mysetting' for settings that in config, or 'myplugin/mysetting' for ones in config_plugins.
 * @param string $heading heading
 * @param string $information text in box
 */
    public function __construct($name, $heading, $information) {
        $this->nosave = true;
        parent::__construct($name, $heading, $information, '');
    }

    /**
     * Always returns true
     * @return bool Always returns true
     */
    public function get_setting() {
        global $CFG;
        require_once("lib.php");
        require_once("lib/php/lc/LCAction.php");
        require_once("lib/php/lc/lcapi.php");
        if (!isset($CFG->liveclassroom_servername) ||
            !isset($CFG->liveclassroom_adminusername) ||
            !isset($CFG->liveclassroom_adminpassword) ||
            $CFG->liveclassroom_servername == '')
            return 'Unknown';
        $lcApi=new LCAction(null,$CFG->liveclassroom_servername, 
                   $CFG->liveclassroom_adminusername, 
                   $CFG->liveclassroom_adminpassword, $CFG->dataroot);
        $version = $lcApi->getVersion();

        return $version;
    }

    /**
     * Always returns true
     * @return bool Always returns true
     */
    public function get_defaultsetting() {
        return true;
    }

    /**
     * Never write settings
     * @return string Always returns an empty string
     */
    public function write_setting($data) {
    // do not write any setting
        return '';
    }

    /**
     * Returns an HTML string
     * @return string Returns an HTML string
     */
    public function output_html($data, $query='') {
        $return = '<div class="form-item clearfix">';
        $return .= '<div class="form-label"><label>'.$this->visiblename.'</label></div>';
        $return .= '<div class="form-setting">'.$data.'</div>';
        $return .= '</div>';
        return $return;
    }
}

class admin_setting_liveclassroom_integrationversion extends admin_setting {
/**
 * not a setting, just text
 * @param string $name unique ascii name, either 'mysetting' for settings that in config, or 'myplugin/mysetting' for ones in config_plugins.
 * @param string $heading heading
 * @param string $information text in box
 */
    public function __construct($name, $heading, $information) {
        $this->nosave = true;
        parent::__construct($name, $heading, $information, '');
    }

    /**
     * Always returns true
     * @return bool Always returns true
     */
    public function get_setting() {
        return LIVECLASSROOM_MODULE_VERSION;
    }

    /**
     * Always returns true
     * @return bool Always returns true
     */
    public function get_defaultsetting() {
        return true;
    }

    /**
     * Never write settings
     * @return string Always returns an empty string
     */
    public function write_setting($data) {
    // do not write any setting
        return '';
    }

    /**
     * Returns an HTML string
     * @return string Returns an HTML string
     */
    public function output_html($data, $query='') {
        $return = '<div class="form-item clearfix">';
        $return .= '<div class="form-label"><label>'.$this->visiblename.'</label></div>';
        $return .= '<div class="form-setting">'.$data.'</div>';
        $return .= '</div>';
        return $return;
    }
}

class admin_setting_liveclassroom_loglevel extends admin_setting_configselect {
    public function __construct($name, $heading, $information, $defaultsetting, $choices) {
        parent::__construct($name, $heading, $information, $defaultsetting, $choices);
    }

    /**
     * Return the setting
     *
     * @return mixed returns config if successful else null
     */
    public function get_setting() {
        $setting = $this->config_read($this->name);
        if ($setting == '')
            $setting = 2;
        return $setting;
    }

    /**
     * Returns XHTML select field and wrapping div(s)
     *
     * @see output_select_html()
     *
     * @param string $data the option to show as selected
     * @param string $query
     * @return string XHTML field and wrapping div
     */
    public function output_html($data, $query='') {
        global $CFG;
        $default = $this->get_defaultsetting();
        $current = $this->get_setting();

        list($selecthtml, $warning) = $this->output_select_html($data, $current, $default);
        if (!$selecthtml) {
            return '';
        }

        $return = '<div class="form-select defaultsnext">' . $selecthtml . '</div>';
        $return .= '<a href="'.$CFG->wwwroot.'/mod/liveclassroom/logs.php?action=list">';
        $return .= get_string('viewlogs', 'liveclassroom').'</a>';

        return format_admin_setting($this, $this->visiblename, $return, $this->description, true, $warning, null, $query);
    }
}

class admin_setting_liveclassroom_configtest extends admin_setting {
/**
 * not a setting, just text
 * @param string $name unique ascii name, either 'mysetting' for settings that in config, or 'myplugin/mysetting' for ones in config_plugins.
 * @param string $heading heading
 * @param string $information text in box
 */
    public function __construct($name) {
        $this->nosave = true;
        parent::__construct($name, '', '', '');
    }

    /**
     * Always returns true
     * @return bool Always returns true
     */
    public function get_setting() {
        return VOICETOOLS_MODULE_VERSION;
    }

    /**
     * Always returns true
     * @return bool Always returns true
     */
    public function get_defaultsetting() {
        return true;
    }

    /**
     * Never write settings
     * @return string Always returns an empty string
     */
    public function write_setting($data) {
    // do not write any setting
        return '';
    }

    /**
     * Returns an HTML string
     * @return string Returns an HTML string
     */
    public function output_html($data, $query='') {
        $return = '<div class="form-item clearfix">';
        $return .= '<div class="form-label"></div>';
        $return .= '<div class="form-setting"><input type="button" onclick="lc_CheckConfiguration();" value="Check Configuration" /></div>';
        $return .= '</div>';
        $return .= '<div id="hiddenDiv" class="opac" style="display:none">
     <!--[if lte IE 6.5]><iframe></iframe><![endif]-->
</div>';
        return $return;
    }
}
