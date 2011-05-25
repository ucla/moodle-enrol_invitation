<?php

/** Renderer for stuff. **/

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
                    || preg_match('/' . preg_quote($login_url, '/') . '/', $login_a)) {
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
    
        $login_info[$pr_haf] = $this->help_feedback_link();

        ksort($login_info);
        $separator = $this->separator(); 
        $login_string = implode($separator, $login_info);

        return $login_string;
    }

    function help_feedback_link() {
        // TODO use html_writer
        $hf_link = get_string('help_n_feedback', $this->theme);

        return $hf_link;
    }

    function weeks_display() {
        return 'Weeks Section'; 
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
     *      Will reach into the settings to see if the hover over should be displayed.
     **/
    function sublogo() {
        $display_text   = get_config($this->theme, 'logo_sub_text');

        return $display_text;
    }
    
    // This function will be called only in class sites 
    function control_panel_button() {
        global $CFG;

        $course = $this->page->course;

        // Use html_writer to render the actual link
        // html_writer::tag(tagname, contents, attributes[])
        $cp_text = get_string('control_panel', $this->theme);

        $cp_block = 'block_ucla_control_panel';

        // Make the link
        if (!class_exists($cp_block)) {
            $cp_path = $CFG->dirroot
                . '/blocks/ucla_control_panel/' . $cp_block . '.php';

            if (file_exists($cp_path)) {
                require($cp_path);
            } else {
                debugging('Control Panel Block not found.');
                return '';
            }
        } 

        $cp_link = $cp_block::create_control_panel_link($course);

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

                $link_a = html_writer::tag('a', $link_display, array('href' => $link_href));

                $footer_string .= '&nbsp;' . $link_a;
            }
        }

        return $footer_string;
    }

    function copyright_info() {
        return get_string('copyright_information', $this->theme);
    }
}
