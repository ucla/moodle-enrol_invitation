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

    function login_info() {
        $original = parent::login_info();

        preg_match_all('/<a.+?<\/a>/', $original, $matches);
        if (empty($matches)) {
            return $original;
        }

        // There should only be one line
        $login_links = $matches[0];
        if (empty($matches)) {
            return $original;
        }

        $login_url = get_login_url();

        $pr_name  = 0;
        $pr_haf   = 1;
        $pr_login = 2;

        // Try to parse the links
        foreach ($login_links as $login_a) {
            if (preg_match('/user/', $login_a)) {
                $login_info[$pr_name] = $login_a;
            } else if (preg_match('/login.logout/', $login_a) 
                    || preg_match('/' . preg_quote($login_url, '/') 
                        . '/', $login_a)) {
                // Magical capitalization skills
                preg_match('/(.*)(>.*<)(.*)/', $login_a, $anchor);

                $toupper = strtoupper($anchor[2]);
                $login_info[$pr_login] = $anchor[1] . $toupper . $anchor[3];
            }
        }
        
        // Manually handle each link
        if (!isset($login_info[$pr_name])) {
            // Bad, repeated stuff
            $login_info[$pr_name] = get_string('loggedinnot', 'moodle');
        }
   
        $fbl = $this->help_feedback_link();
        if ($fbl) {
            $login_info[$pr_haf] = $fbl;
        }

        ksort($login_info);
        $separator = $this->separator(); 
        $login_string = implode($separator, $login_info);

        return $login_string;
    }

    /**
     *  Returns the HTML link for the help and feedback.
     **/
    function help_feedback_link() {
        $help_locale = $this->call_separate_block_function(
                'ucla_helpblock', 'get_helpblock_link'
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
                'ucla_weeksdisplay', 'get_weeksdisplay_link'
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

        $logo_img = html_writer::empty_tag('img', array('src' => $pix_url));
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
        $display_text   = get_config($this->theme, 'logo_sub_text');

        return $display_text;
    }
    
    // This function will be called only in class sites 
    function control_panel_button() {
        global $CFG;

        // Use html_writer to render the actual link
        // html_writer::tag(tagname, contents, attributes[])
        $cp_text = get_string('control_panel', $this->theme);

        $cp_link = $this->call_separate_block_function(
                'ucla_control_panel', 'get_control_panel_link', true
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
            'school',
            'separator',
            'registrar',
            'myucla'
        );

        $footer_string = '';
        
        $custom_text = get_config($this->theme, 'footer_links');

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
                    array('href' => $link_href));

                $footer_string .= '&nbsp;' . $link_a;
            }
        }

        return $footer_string;
    }

    function copyright_info() {
        return get_string('copyright_information', $this->theme);
    }

    /**
     *  Attempts to get a feature of another block to generate special text or
     *  link to put into the theme.
     *
     *  TODO This function should not belong to this specific block
     **/
    function call_separate_block_function($blockname, $functionname) {
        global $CFG;
        $blockclassname = 'block_' . $blockname;

        $blockfile = $CFG->dirroot . "/blocks/$blockname/$blockclassname.php";
        if (file_exists($blockfile)) {
            require_once($CFG->dirroot . '/block/moodleblock.class.php');
            require_once($blockfile);
        } else {
            debugging('Could not find ' . $blockfile);
            return false;
        }

        $course = $this->page->course;

        if (method_exists($blockclassname, $functionname)) {
            $retval = $blockclassname::$functionname($course);
        } else {
            debugging('Could not find ' . $functionname . ' for ' 
                . $blockclassname);
            return false;
        }

        return $retval;
    }
}
