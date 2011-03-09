<?php

/** Renderer for stuff. **/

class theme_uclashared_core_renderer extends core_renderer {
    // Stealing functionality from core_renderer->login_info()
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
        $login_string = implode(' | ', $login_info);

        return $login_string;
    }

    function help_feedback_link() {
        return "Help & Feedback";
    }
    
    // This function will be called only in class sites 
    function control_panel_button() {
        // This is awesome.
        $course = $this->page->course;

        // Text to control panel
        $cp_dest = '';

        // Use html_writer to render the actual link
        // html_writer::tag(tagname, contents, attributes[])
        $cp_button = get_string('control-panel', 'theme_uclashared');

        return $cp_button;
    }
}
