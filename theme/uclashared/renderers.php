<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
class theme_uclashared_core_renderer extends core_renderer {
    private $sep = NULL;
    private $theme = 'theme_uclashared';

    function separator() {
        if ($this->sep == NULL) {
            $this->sep = get_string('separator__', $this->theme);
        }

        return $this->sep;
    }

    /** 
     *  Displays what user you are logged in as, and if needed, along with the 
     *  user you are logged-in-as.
     **/
    function login_info() {
        global $CFG, $DB, $USER;

        $course = $this->page->course;

        // This will have login informations
        // [0] == Login information 
        // - Format  [REALLOGIN] (as \(ROLE\))|(DISPLAYLOGIN) (from MNET)
        // [1] == H&Fb link
        // [2] == Logout/Login button
        $login_info = array();

        $loginurl = get_login_url();
        $add_loginurl = ($this->page->url != $loginurl);

        $add_logouturl = false;

        $loginstr = '';

        if (isloggedin()) {
            $add_logouturl = true;
            $add_loginurl = false;

            $usermurl = new moodle_url('/user/profile.php', array(
                'id' => $USER->id
            ));


            // In case of mnet login
            $mnetfrom = '';
            if (is_mnet_remote_user($USER)) {
                $idprovider = $DB->get_record('mnet_host', array(
                    'id' => $USER->mnethostid
                ));

                if ($idprovider) {
                    $mnetfrom = html_writer::link($idprovider->wwwroot,
                        $idprovider->name);
                }
            }

            $realuserinfo = '';
            if (session_is_loggedinas()) {
                $realuser = session_get_realuser();
                $realfullname = fullname($realuser, true);
                $dest = new moodle_url('/course/loginas.php', array(
                    'id' => $course->id,
                    'sesskey' => sesskey()
                ));

                $realuserinfo = '[' . html_writer::link($dest, $realfullname) . ']'
                    . get_string('loginas_as', 'theme_uclashared');
            } 

            $fullname = fullname($USER, true);
            $userlink = html_writer::link($usermurl, $fullname);

            $rolename = '';
            // I guess only guests cannot switch roles
            if (isguestuser()) {
                $userlink = get_string('loggedinasguest');
                $add_loginurl = true;
            } else if (is_role_switched($course->id)) {
                $context = get_context_instance(CONTEXT_COURSE, $course->id);

                $role = $DB->get_record('role', array(
                    'id' => $USER->access['rsw'][$context->path]
                ));

                if ($role) {
                    $rolename = ' (' . format_string($role->name) . ') ';
                }
            } 

            $loginstr = $realuserinfo . $rolename . $userlink;

        } else {
            $loginstr = get_string('loggedinnot', 'moodle');
        }

        if (isset($SESSION->justloggedin)) {
            unset($SESSION->justloggedin);
            if (!empty($CFG->displayloginfailures) && !isguestuser()) {
                if ($count = count_login_failures($CFG->displayloginfailures, 
                        $USER->username, $USER->lastlogin)) {

                    $loginstr .= '&nbsp;<div class="loginfailures">';

                    if (empty($count->accounts)) {
                        $loginstr .= get_string('failedloginattempts', '', 
                                $count);
                    } else {
                        $loginstr .= get_string('failedloginattemptsall', '', 
                                $count);
                    }

                    if (has_capability('coursereport/log:view', 
                            get_context_instance(CONTEXT_SYSTEM))) {
                        $loginstr .= ' (' . html_writer::link(new moodle_url(
                            '/course/report/log/index.php', array(
                                'chooselog' => 1,
                                'id' => 1,
                                'modid' => 'site_errors'
                            )), get_string('logs')) . ')';
                    }

                    $loginstr .= '</div>';
                }
            }
        }

        $login_info[] = $loginstr;

        // The help and feedback link
        $fbl = $this->help_feedback_link();
        if ($fbl) {
            $login_info[] = $fbl;
        }
       
        // The actual login link
        if ($add_loginurl) {
            $login_info[] = html_writer::link($loginurl, 
                get_string('login'));
        } else if ($add_logouturl) {
            $login_info[] = html_writer::link(
                new moodle_url('/login/logout.php',
                    array('sesskey' => sesskey())), 
                get_string('logout')
            );
        }

        $separator = $this->separator(); 
        $login_string = implode($separator, $login_info);

        return $login_string;
    }

    /**
     *  Returns the HTML link for the help and feedback.
     **/
    function help_feedback_link() {
        $help_locale = $this->call_separate_block_function(
                'ucla_help', 'get_action_link'
            );

        if (!$help_locale) {
            return false;
        }
        
        $hf_link = get_string('help_n_feedback', $this->theme);

        return html_writer::link($help_locale, $hf_link);
    }

    /**
     *  Calls the hook function that will return the current week we are on.
     **/
    function weeks_display() {

        $weeks_text = $this->call_separate_block_function(
                'ucla_weeksdisplay', 'get_raw_content'
            );

        if (!$weeks_text) {
            return false;
        }

        return $weeks_text;
    }

    /**
     *  This a wrapper around pix().
     *  
     *  It will make a picture of the logo, and turn it into a link.
     *
     *  @param string $pix Passed to pix().
     *  @param string $pix_loc Passed to pix().
     *  @param moodle_url $address Destination of anchor.
     *  @return string The logo HTML element.
     **/
    function logo($pix, $pix_loc, $address=null) {
        global $CFG, $OUTPUT;

        if ($address == null) {
            $address = new moodle_url($CFG->wwwroot);
        } 

        $pix_url = $this->pix_url($pix, $pix_loc);
        //UCLA MOD BEGIN: CCLE-2862-Main_site_logo_image_needs_alt_attribute
        $logo_alt = get_string('UCLA_CCLE_text', 'theme_uclashared');
        $logo_img = html_writer::empty_tag('img', array('src' => $pix_url, 'alt' => $logo_alt));
        //UCLA MOD END: CCLE-2862
        $link = html_writer::link($address, $logo_img);

        return $link;
    }

    /**
     *      Displays the text underneath the UCLA | CCLE logo.
     *
     *      Will reach into the settings to see if the hover over should be 
     *      displayed.
     **/
    function sublogo() {
        $display_text   = $this->get_config($this->theme, 'logo_sub_text');

        return $display_text;
    }
    
    // This function will be called only in class sites 
    function control_panel_button() {
        global $CFG, $OUTPUT;

        // Hack since contexts and pagelayouts are different things
        // Hack to fix: display control panel link when updating a plugin
        if ($this->page->context == get_context_instance(CONTEXT_SYSTEM)) {
            return '';
        }

        // Use html_writer to render the control panel button
        $cp_text = html_writer::empty_tag('img', 
            array('src' => $OUTPUT->pix_url('cp_button', 'block_ucla_control_panel'),
                  'alt' => get_string('control_panel', $this->theme)));
        
        $cp_link = $this->call_separate_block_function(
                'ucla_control_panel', 'get_action_link'
            );

        if (!$cp_link) {
            return false;
        }
       
        $cp_button = html_writer::link($cp_link, $cp_text, 
            array('class' => 'control-panel-button'));

        return $cp_button;
    }

    function footer_links() {
        global $CFG;

        $links = array(
            'contact_ccle', 
            'about_ccle',
            'privacy',
            'copyright',
            'separator',            
            'school',
            'registrar',
            'myucla'
        );

        $footer_string = '';
        
        $custom_text = $this->get_config($this->theme, 'footer_links');

        if ($custom_text != '') {
            $footer_string = $custom_text; 

            array_unshift($links, 'separator');
        }

        foreach ($links as $link) {
            if ($link == 'separator') {
                $footer_string .= '&nbsp;';
                $footer_string .= $this->separator();
            } else {
                $link_display = get_string('foodis_' . $link, $this->theme);
                $link_href = get_string('foolin_' . $link, $this->theme);

                $link_a = html_writer::tag('a', $link_display, 
                    array('href' => $link_href, 'target' => '_blank'));

                $footer_string .= '&nbsp;' . $link_a;
            }
        }

        return $footer_string;
    }

    function copyright_info() {
        $curr_year = date('Y');
        return get_string('copyright_information', $this->theme, $curr_year);
    }

    /**
     *  Attempts to get a feature of another block to generate special text or
     *  link to put into the theme.
     *
     *  TODO This function should not belong to this specific block
     **/
    function call_separate_block_function($blockname, $functionname) {
        if (during_initial_install()) {
            return '';
        }

        return block_method_result($blockname, $functionname, 
            $this->page->course);
    }

    function get_environment() {
        $c = $this->get_config($this->theme, 'running_environment');

        if (!$c) {
            return 'prod';
        } 

        return $c;
    }

    /**
     *  Overwriting pix icon renderers to not use icons for action buttons.
     **/
    function render_action_link($action) {
        $noeditingicons = get_user_preferences('noeditingicons', 1);
        if (!empty($noeditingicons)) {
            if ($action->text instanceof pix_icon) {
                $icon = $action->text;

                $attr = $icon->attributes;
                $displaytext = $attr['alt'];

                unset($attr['alt']);
                unset($attr['title']);

                $action->text = $displaytext;
            }
        }

        return parent::render_action_link($action);
    }

    /**
     *  Wrapper function to prevent initial install.
     **/
    function get_config($plugin, $var) {
        if (!during_initial_install()) {
            return get_config($plugin, $var);
        } 

        return false;
    }
}

