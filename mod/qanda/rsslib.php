<?php

/**
 * This file adds support to rss feeds generation
 *
 * @package mod_qanda
 * @category rss
 */

/**
 * Returns the path to the cached rss feed contents. Creates/updates the cache if necessary.
 *
 * @param stdClass $context the context
 * @param array    $args    the arguments received in the url
 * @return string the full path to the cached RSS feed directory. Null if there is a problem.
 */
    function qanda_rss_get_feed($context, $args) {
        global $CFG, $DB, $COURSE, $USER;

        $status = true;

        if (empty($CFG->qanda_enablerssfeeds)) {
            debugging("DISABLED (module configuration)");
            return null;
        }

        $qandaid  = clean_param($args[3], PARAM_INT);
        $cm = get_coursemodule_from_instance('qanda', $qandaid, 0, false, MUST_EXIST);
        $modcontext = context_module::instance($cm->id);

        if ($COURSE->id == $cm->course) {
            $course = $COURSE;
        } else {
            $course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);
        }
        //context id from db should match the submitted one
        if ($context->id != $modcontext->id || !has_capability('mod/qanda:view', $modcontext)) {
            return null;
        }

        $qanda = $DB->get_record('qanda', array('id' => $qandaid), '*', MUST_EXIST);
        if (!rss_enabled_for_mod('qanda', $qanda)) {
            return null;
        }

        $sql = qanda_rss_get_sql($qanda);

        //get the cache file info
        $filename = rss_get_file_name($qanda, $sql);
        $cachedfilepath = rss_get_file_full_name('mod_qanda', $filename);

        //Is the cache out of date?
        $cachedfilelastmodified = 0;
        if (file_exists($cachedfilepath)) {
            $cachedfilelastmodified = filemtime($cachedfilepath);
        }
        //if the cache is more than 60 seconds old and there's new stuff
        $dontrecheckcutoff = time()-60;
        if ( $dontrecheckcutoff > $cachedfilelastmodified && qanda_rss_newstuff($qanda, $cachedfilelastmodified)) {
            if (!$recs = $DB->get_records_sql($sql, array(), 0, $qanda->rssarticles)) {
                return null;
            }

            $items = array();

            $formatoptions = new stdClass();
            $formatoptions->trusttext = true;

            foreach ($recs as $rec) {
                $item = new stdClass();
                $user = new stdClass();
                $item->title = $rec->entryquestion;

                if ($qanda->rsstype == 1) {//With author
                    $user->firstname = $rec->userfirstname;
                    $user->lastname = $rec->userlastname;

                    $item->author = fullname($user);
                }

                $item->pubdate = $rec->entrytimecreated;
                $item->link = $CFG->wwwroot."/mod/qanda/showentry.php?courseid=".$qanda->course."&eid=".$rec->entryid;

                $answer = file_rewrite_pluginfile_urls($rec->entryanswer, 'pluginfile.php',
                    $modcontext->id, 'mod_qanda', 'answer', $rec->entryid);
                $item->description = format_text($answer, $rec->entryformat, $formatoptions, $qanda->course);
                $items[] = $item;
            }

            //First all rss feeds common headers
            $header = rss_standard_header(format_string($qanda->name,true),
                                          $CFG->wwwroot."/mod/qanda/view.php?g=".$qanda->id,
                                          format_string($qanda->intro,true));
            //Now all the rss items
            if (!empty($header)) {
                $articles = rss_add_items($items);
            }
            //Now all rss feeds common footers
            if (!empty($header) && !empty($articles)) {
                $footer = rss_standard_footer();
            }
            //Now, if everything is ok, concatenate it
            if (!empty($header) && !empty($articles) && !empty($footer)) {
                $rss = $header.$articles.$footer;

                //Save the XML contents to file.
                $status = rss_save_file('mod_qanda', $filename, $rss);
            }
        }

        if (!$status) {
            $cachedfilepath = null;
        }

        return $cachedfilepath;
    }

    /**
     * The appropriate SQL query for the qanda items to go into the RSS feed
     *
     * @param stdClass $qanda the qanda object
     * @param int      $time     check for items since this epoch timestamp
     * @return string the SQL query to be used to get the entried from the qanda table of the database
     */
    function qanda_rss_get_sql($qanda, $time=0) {
        //do we only want new items?
        if ($time) {
            $time = "AND e.timecreated > $time";
        } else {
            $time = "";
        }

        if ($qanda->rsstype == 1) {//With author
            $sql = "SELECT e.id AS entryid,
                      e.question AS entryquestion,
                      e.answer AS entryanswer,
                      e.answerformat AS entryformat,
                      e.answertrust AS entrytrust,
                      e.timecreated AS entrytimecreated,
                      u.id AS userid,
                      u.firstname AS userfirstname,
                      u.lastname AS userlastname
                 FROM {qanda_entries} e,
                      {user} u
                WHERE e.qandaid = {$qanda->id} AND
                      u.id = e.userid AND
                      e.approved = 1 $time
             ORDER BY e.timecreated desc";
        } else {//Without author
            $sql = "SELECT e.id AS entryid,
                      e.question AS entryquestion,
                      e.answer AS entryanswer,
                      e.answerformat AS entryformat,
                      e.answertrust AS entrytrust,
                      e.timecreated AS entrytimecreated,
                      u.id AS userid
                 FROM {qanda_entries} e,
                      {user} u
                WHERE e.qandaid = {$qanda->id} AND
                      u.id = e.userid AND
                      e.approved = 1 $time
             ORDER BY e.timecreated desc";
        }

        return $sql;
    }

    /**
     * If there is new stuff in since $time this returns true
     * Otherwise it returns false.
     *
     * @param stdClass $qanda the qanda activity object
     * @param int      $time     epoch timestamp to compare new items against, 0 for everyting
     * @return bool true if there are new items
     */
    function qanda_rss_newstuff($qanda, $time) {
        global $DB;

        $sql = qanda_rss_get_sql($qanda, $time);

        $recs = $DB->get_records_sql($sql, null, 0, 1);//limit of 1. If we get even 1 back we have new stuff
        return ($recs && !empty($recs));
    }


