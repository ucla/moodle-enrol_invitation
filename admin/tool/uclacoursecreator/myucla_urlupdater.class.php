<?php

class myucla_urlupdater {
    const $mname = 'tool/myucla_url';

    // Updating flags
    // Never update the url
    const $neverflag = 'neversend';

    // Do not overwrite the URL (standard)
    const $nooverwriteflag = 'nooverwrite';

    // Always overwrite the URL
    const $alwaysflag = 'alwayssend';

    // Cache, skip checking the $CFG...
    var $myucla_login = null;

    var $successful = array();
    var $failed = array();

    /**
     *  Builds the MyUCLA URL update webservice URL.
     *
     *  @param $term The term to upload.
     *  @param $srs The SRS of the course to upload.
     *  @param $url The url to update to. Can be null.
     *  @return string The URL for the MyUCLA update.
     **/
    function get_MyUCLA_service($term, $srs, $url=null) {
        if ($this->myucla_login == null) {
            $cc_url = get_config(self::mname, 'url_service');

            $cc_name = get_config(self::mname, 'user_name');
            $cc_email = get_config(self::mname, 'user_email');

            $mu_url = $cc_url . '?name=' . urlencode($cc_name) 
                . '&email=' . $cc_email;
            
            $this->myucla_login = $mu_url;
        }
        
        $returner = $this->myucla_login . '&term=' . $term . '&srs=' . $srs;

        if ($url != null) {
            $returner .= '&url=' . urlencode($url);
        }

        return $returner;
    }

    /**
     *  Sends the URLs of the courses to MyUCLA.
     *  This is relatively slow...
     *  
     *  @param $sending_urls
     *      Array (
     *          make_idnumber() => Array (
     *              'term' => term,
     *              'srs' => srs,
     *              'url' => url
     *          )
     *      )
     **/
    function send_MyUCLA_urls($sending_urls, $push=false) {
        // Figure out what to build as the URL of the course
        $retrieved_info = array();

        // For each requested course, figure out the URL
        foreach ($sending_urls as $idnumber => $sendings) {
            $sender = null;
            if ($push) {
                $sender = $sendings['url'];
            }

            $url_update = $this->get_MyUCLA_service(
                $sendings['term'], $sendings['srs'], $sender
            );

            if ($this->get_debug()) {
                // Just print the statements
                $this->println($url_update);
            } else {
                $myucla_curl = $this->contact_MyUCLA($url_update);
                $retrieved_info[$idnumber] = $myucla_curl;
            }
        }

        return $retrieved_info;
    }

    /** 
     *  Syncs a set of courses with MyUCLA URLs.
     **/
    function sync_MyUCLA_urls($courses) {
        $targets = array();

        foreach ($courses as $course) {
            $idn = make_idnumber($course);
            if (empty($course['nourlupdate'])) {
                // Ignored results are successful...hehe
                $this->successful[$idn] = $course;
                continue;
            }

            $targets[$idn] = $course;
        }

        $results = $this->send_MyUCLA_urls($targets, true);

        foreach ($results as $rid => $result) {
            if (strpos($result, 'Update Successful') === false) {
                $this->failed[$rid] = $result;
            } else {
                $this->successful[$rid] = $result;
            }
        }

        return true;
    }

    /** 
     *  Convenience function to get access the webservice for MyUCLA
     **/
    function contact_MyUCLA($url) {
        $content = $this->trim_strip_tags(file_get_contents($url));

        // Rest?
        sleep(1);

        return $content;
    }

    /**
     *  Quick wrapper for @see strip_tags and @see trim.
     *
     *  @param string The string to trim and strip_tags.
     *  @return string The string, without HTML tags and with leading and 
     *      trailing spaces removed.
     **/
    function trim_strip_tags($string) {
        return trim(strip_tags($string), " \r\n\t");
    }
}
