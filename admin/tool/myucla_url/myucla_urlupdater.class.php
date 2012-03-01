<?php

class myucla_urlupdater {
    const mname = 'tool_myucla_url';

    // Updating flags
    // Never update the url
    const neverflag = 'neversend';

    // Do not overwrite the URL (standard)
    const nooverwriteflag = 'nooverwrite';

    // Always overwrite the URL
    const alwaysflag = 'alwayssend';

    // Possible MyUCLA server responses
    const expected_success_message  = 'Update Successful';
    const error_connection          = 'Unable to Connect to SQL Servers!';
    const error_denied              = 'Unauthorized Access!';    
    const error_failed              = 'Update Unsuccessful. SQL Update Failed.';
    const error_invalid             = 'Update Unsuccessful. Invalid Course.'; 
    
    // Cache, skip checking the $CFG...
    var $myucla_login = null;

    var $successful = array();
    var $failed = array();
    var $skipped = array();

    /**
     *  Builds the MyUCLA URL update webservice URL.
     *
     *  @param string $term The term to upload.
     *  @param string $srs  The SRS of the course to upload.
     *  @param string $url  Default to false. Otherwise is the url to update to. 
     *                      If parameter is null or an empty string it will 
     *                      clear the URL at MyUCLA.
     *  @return string      The URL to be used in the MyUCLA update.
     */
    function get_MyUCLA_service($term, $srs, $url=false) {
        if ($this->myucla_login == null) {
            $cc_url = get_config(self::mname, 'url_service');

            $cc_name = get_config(self::mname, 'user_name');
            $cc_email = get_config(self::mname, 'user_email');

            $mu_url = $cc_url . '?name=' . urlencode($cc_name) 
                . '&email=' . $cc_email;
            
            $this->myucla_login = $mu_url;
        }
        
        $returner = $this->myucla_login . '&term=' . $term . '&srs=' . $srs;

        if ($url !== false) {
            // if URL is null or empty it will clear the url on MyUCLA
            $returner .= '&url=' . urlencode($url);
        }

        return $returner;
    }

    /**
     * Sends the URLs of the courses to MyUCLA. Either updates those urls or
     * gets the current valies depending on the parameter.
     * 
     *  @param array $sending_urls  Expects array in following format:
     *      Array (
     *          make_idnumber() => Array (
     *              'term' => term,
     *              'srs' => srs,
     *              'url' => url
     *          )
     *      )
     * @param boolean $push     Default false. If true will update urls for 
     *                          given set of courses
     * 
     * @return array    Returns array in following format:
     *  [term-srs] => [response message from server]
     */
    function send_MyUCLA_urls($sending_urls, $push=false) {
        // Figure out what to build as the URL of the course
        $retrieved_info = array();

        // For each requested course, figure out the URL
        foreach ($sending_urls as $idnumber => $sendings) {
            $sender = false;
            if ($push) {
                $sender = $sendings['url'];
            }
            
            // Figure out the URL
            $url_update = $this->get_MyUCLA_service(
                $sendings['term'], $sendings['srs'], $sender
            );

            if ($this->is_debugging()) {
                // debugging is on, so just assume success
                $myucla_curl = self::expected_success_message;
            } else {
                $myucla_curl = $this->contact_MyUCLA($url_update);
            }

            $retrieved_info[$idnumber] = $myucla_curl;
        }
        
        return $retrieved_info;
    }

    /** 
     *  Syncs a set of courses with MyUCLA URLs.
     *  @param  Array(
     *      make_idnumber() => Array(
     *          'term' => term,
     *          'srs' => srs,
     *          'url' => url
     *      )
     *  )
     * 
     * Sets successful and failed arrays with the appropiate courses indexed by 
     * the same course index as given in the $course paramter.
     */
    function sync_MyUCLA_urls($courses) {
        // first get the urls for the given courses
        $fetch_results = $this->send_MyUCLA_urls($courses);

        foreach ($fetch_results as $idnumber => $result) {
            // The hardcoded default
            $flag = self::nooverwriteflag;

            if (isset($courses[$idnumber]['flag'])) {
                $flag = $courses[$idnumber]['flag'];
            }

            if ($flag == self::neverflag) {
                // This is done
                $this->successful[$idnumber] = $result;
            }

            // We got a result but we're not supposed to overwrite it
            if (!empty($result)) {
                if ($flag == self::nooverwriteflag) {
                    $this->successful[$idnumber] = $result;
                }
            }

            // We don't need to push urls that are supposedly done
            if (isset($this->successful[$idnumber])) {
                $this->skipped[$idnumber] = true;
                unset($courses[$idnumber]);
            }
        }

        // now update those urls that need to be processed
        $results = $this->send_MyUCLA_urls($courses, true);

        foreach ($results as $rid => $result) {
            if (strpos($result, self::expected_success_message) === false) {
                $this->failed[$rid] = $result;
            } else {
                $this->successful[$rid] = $result;
            }
        }

        return true;
    }

    /** 
     *  Convenience function to get access the webservice for MyUCLA
     */
    function contact_MyUCLA($url) {
        $content = $this->trim_strip_tags(file_get_contents($url));

        // Give MyUCLA time to breathe (if needed, please uncomment)
        // sleep(1);

        return $content;
    }

    /**
     *  Returns if we should send the actual message or not.
     */
    function is_debugging() {
        if (get_config(self::mname, 'override_debugging')) {
            return false;
        }

        return debugging();
    }

    /**
     *  Quick wrapper for @see strip_tags and @see trim.
     *
     *  @param string The string to trim and strip_tags.
     *  @return string The string, without HTML tags and with leading and 
     *      trailing spaces removed.
     */
    function trim_strip_tags($string) {
        return trim(strip_tags($string), " \r\n\t");
    }
}

// EoF
