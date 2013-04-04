<?php

/// This page prints a particular instance of qanda
require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once($CFG->libdir . '/completionlib.php');
require_once("$CFG->libdir/rsslib.php");

$id = optional_param('id', 0, PARAM_INT);           // Course Module ID
$g = optional_param('g', 0, PARAM_INT);            // qanda ID

$tab = optional_param('tab', QANDA_NO_VIEW, PARAM_ALPHA);    // browsing entries by categories?
$displayformat = optional_param('displayformat', -1, PARAM_INT);  // override of the qanda display format

$mode = optional_param('mode', 'date', PARAM_ALPHA);           // term entry cat date letter search author approval
$hook = optional_param('hook', '', PARAM_CLEAN);           // the term, entry, cat, etc... to look for based on mode
$fullsearch = optional_param('fullsearch', 0, PARAM_INT);         // full search (question and answer) when searching?
$sortkey = optional_param('sortkey', 'UPDATE', PARAM_ALPHA); // Sorted view: CREATION | UPDATE | FIRSTNAME | LASTNAME...
$sortorder = optional_param('sortorder', 'DESC', PARAM_ALPHA);   // it defines the order of the sorting (ASC or DESC)
$offset = optional_param('offset', 0, PARAM_INT);             // entries to bypass (for paging purposes)
$page = optional_param('page', 0, PARAM_INT);               // Page to show (for paging purposes)
$show = optional_param('show', '', PARAM_ALPHA);           // [ question | alias ] => mode=term hook=$show

if (!empty($id)) {
    if (!$cm = get_coursemodule_from_id('qanda', $id)) {
        print_error('invalidcoursemodule');
    }
    if (!$course = $DB->get_record("course", array("id" => $cm->course))) {
        print_error('coursemisconf');
    }
    if (!$qanda = $DB->get_record("qanda", array("id" => $cm->instance))) {
        print_error('invalidid', 'qanda');
    }
} else if (!empty($g)) {
    if (!$qanda = $DB->get_record("qanda", array("id" => $g))) {
        print_error('invalidid', 'qanda');
    }
    if (!$course = $DB->get_record("course", array("id" => $qanda->course))) {
        print_error('invalidcourseid');
    }
    if (!$cm = get_coursemodule_from_instance("qanda", $qanda->id, $course->id)) {
        print_error('invalidcoursemodule');
    }
    $id = $cm->id;
} else {
    print_error('invalidid', 'qanda');
}

require_course_login($course->id, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/qanda:view', $context);

// Prepare format_string/text options
$fmtoptions = array(
    'context' => $context);

require_once($CFG->dirroot . '/comment/lib.php');
comment::init();

/// redirecting if adding a new entry
if ($tab == QANDA_ADDENTRY_VIEW) {
    redirect("edit.php?cmid=$cm->id&amp;mode=$mode");
}

/// setting the defaut number of entries per page if not set
if (!$entriesbypage = $qanda->entbypage) {
    $entriesbypage = $CFG->qanda_entbypage;
}

/// If we have received a page, recalculate offset
if ($page != 0 && $offset == 0) {
    $offset = $page * $entriesbypage;
}

/// setting the default values for the display mode of the current qanda
/// only if the qanda is viewed by the first time
if ($dp = $DB->get_record('qanda_formats', array('name' => $qanda->displayformat))) {
/// Based on format->defaultmode, we build the defaulttab to be showed sometimes
    switch ($dp->defaultmode) {
        /* case 'cat':
          $defaulttab = qanda_CATEGORY_VIEW;
          break;
          case 'author':
          $defaulttab = qanda_AUTHOR_VIEW;
          break;        *
         */
        case 'date':
            $defaulttab = QANDA_DATE_VIEW;
            break;

        default:
            $defaulttab = QANDA_DATE_VIEW;
    }
/// Fetch the rest of variables
    $printpivot = $dp->showgroup;
    if ($mode == '' and $hook == '' and $show == '') {
        $mode = $dp->defaultmode;
        $hook = $dp->defaulthook;
        $sortkey = $dp->sortkey;
        $sortorder = $dp->sortorder;
    }
} else {
    $defaulttab = QANDA_DATE_VIEW;
    $printpivot = 1;
    if ($mode == '' and $hook == '' and $show == '') {
        $mode = 'date';
        $hook = 'ALL';
    }
}

if ($displayformat == -1) {
    $displayformat = $qanda->displayformat;
}

if ($show) {
    $mode = 'term';
    $hook = $show;
    $show = '';
}
/// Processing standard security processes
if ($course->id != SITEID) {
    require_login($course);
}
if (!$cm->visible and !has_capability('moodle/course:viewhiddenactivities', $context)) {
    echo $OUTPUT->header();
    notice(get_string("activityiscurrentlyhidden"));
}
add_to_log($course->id, "qanda", "view", "view.php?id=$cm->id&amp;tab=$tab", $qanda->id, $cm->id);

// Mark as viewed
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

/// stablishing flag variables
if ($sortorder = strtolower($sortorder)) {
    if ($sortorder != 'asc' and $sortorder != 'desc') {
        $sortorder = '';
    }
}
if ($sortkey = strtoupper($sortkey)) {
    if ($sortkey != 'CREATION' and
            $sortkey != 'UPDATE' and
            $sortkey != 'FIRSTNAME' and
            $sortkey != 'LASTNAME'
    ) {
        $sortkey = '';
    }
}

switch ($mode = strtolower($mode)) {
    case 'search': /// looking for terms containing certain word(s)
        $tab = QANDA_DATE_VIEW; //QANDA_STANDARD_VIEW;
        //Clean a bit the search string
        $hook = trim(strip_tags($hook));

        break;

    case 'entry':  /// Looking for a certain entry id
        $tab = QANDA_DATE_VIEW; //QANDA_STANDARD_VIEW;
        if ($dp = $DB->get_record("qanda_formats", array("name" => $qanda->displayformat))) {
            $displayformat = $dp->popupformatname;
        }
        break;
    case 'approval':    /// Looking for entries waiting for approval
        $tab = QANDA_APPROVAL_VIEW;
        // Override the display format with the approvaldisplayformat
        if ($qanda->approvaldisplayformat !== 'default' && ($df = $DB->get_record("qanda_formats", array("name" => $qanda->approvaldisplayformat)))) {
            $displayformat = $df->popupformatname;
        }
        if (!$hook and !$sortkey and !$sortorder) {
            $hook = 'ALL';
        }
        break;
    case 'date':
        $tab = QANDA_DATE_VIEW;
        if (!$sortkey) {
            $sortkey = 'UPDATE';
        }
        if (!$sortorder) {
            $sortorder = 'desc';
        }
        break;

    /*   case 'cat':    /// Looking for a certain cat
      $tab = qanda_CATEGORY_VIEW;
      if ($hook > 0) {
      $category = $DB->get_record("qanda_categories", array("id" => $hook));
      }
      break;



      case 'term':   /// Looking for entries that include certain term in its question, answer or aliases
      $tab = QANDA_STANDARD_VIEW;
      break;



      case 'author':  /// Looking for entries, browsed by author
      $tab = qanda_AUTHOR_VIEW;
      if (!$hook) {
      $hook = 'ALL';
      }
      if (!$sortkey) {
      $sortkey = 'FIRSTNAME';
      }
      if (!$sortorder) {
      $sortorder = 'asc';
      }
      break;

      case 'letter':  /// Looking for entries that begin with a certain letter, ALL or SPECIAL characters
     */
    default:
        $tab = QANDA_DATE_VIEW; //QANDA_STANDARD_VIEW;
        if (!$hook) {
            $hook = 'ALL';
        }
        break;
}

switch ($tab) {
    case QANDA_IMPORT_VIEW:
    case QANDA_EXPORT_VIEW:
    case QANDA_APPROVAL_VIEW:
        $showcommonelements = 0;
        break;

    default:
        $showcommonelements = 1;
        break;
}

/// Printing the heading
$strqandas = get_string("modulenameplural", "qanda");
$strqanda = get_string("modulename", "qanda");
$strallcategories = get_string("allcategories", "qanda");
$straddentry = get_string("addentry", "qanda");
$strnoentries = get_string("noentries", "qanda");
$strsearchinanswer = get_string("searchinanswer", "qanda");
$strsearch = get_string("search");
$strwaitingapproval = get_string('waitingapproval', 'qanda');

/// If we are in approval mode, prit special header
$PAGE->set_title(format_string($qanda->name));
$PAGE->set_heading($course->fullname);
$url = new moodle_url('/mod/qanda/view.php', array('id' => $cm->id));
if (isset($mode)) {
    $url->param('mode', $mode);
}
$PAGE->set_url($url);

if (!empty($CFG->enablerssfeeds) && !empty($CFG->qanda_enablerssfeeds)
        && $qanda->rsstype && $qanda->rssarticles) {

    $rsstitle = format_string($course->shortname, true, array('context' => context_course::instance($course->id))) . ': %fullname%';
    rss_add_http_header($context, 'mod_qanda', $qanda, $rsstitle);
}

if ($tab == QANDA_APPROVAL_VIEW) {
    require_capability('mod/qanda:answer', $context);
    $PAGE->navbar->add($strwaitingapproval);
    echo $OUTPUT->header();
    echo $OUTPUT->heading($strwaitingapproval);
} else { /// Print standard header
    echo $OUTPUT->header();
}




/// Info box
if ($qanda->intro && $showcommonelements) {
    // echo '<div id="qanda-title">Q & A</div>';
    echo '<div class="titleBox">';
    echo $OUTPUT->box('Q&A', 'generalbox', 'qanda-title');
    echo $OUTPUT->box(format_module_intro('qanda', $qanda, $cm->id), 'generalbox', 'intro');
    echo '</div>';
}


//echo '<br /><div class="controls">';
/// All this depends if whe have $showcommonelements
if ($showcommonelements) {

    echo '<div class="add-and-search-box">';


/// To calculate available options
    $availableoptions = '';

/// Decide about to print the import link
    /* if (has_capability('mod/qanda:import', $context)) {
      $availableoptions = '<span class="help-link">' .
      '<a href="' . $CFG->wwwroot . '/mod/qanda/import.php?id=' . $cm->id . '"' .
      '  title="' . s(get_string('importentries', 'qanda')) . '">' .
      get_string('importentries', 'qanda') . '</a>' .
      '</span>';
      }
      /// Decide about to print the export link
      if (has_capability('mod/qanda:export', $context)) {
      if ($availableoptions) {
      $availableoptions .= '&nbsp;/&nbsp;';
      }
      $availableoptions .='<span class="help-link">' .
      '<a href="' . $CFG->wwwroot . '/mod/qanda/export.php?id=' . $cm->id .
      '&amp;mode='.$mode . '&amp;hook=' . urlencode($hook) . '"' .
      '  title="' . s(get_string('exportentries', 'qanda')) . '">' .
      get_string('exportentries', 'qanda') . '</a>' .
      '</span>';
      } */


/// Show the add entry button if allowed

    if (has_capability('mod/qanda:write', $context) && $showcommonelements) {
        echo '<div class="single-button qanda-add-entry">';
        echo "<form id=\"newentryform\" method=\"get\" action=\"$CFG->wwwroot/mod/qanda/edit.php\">";
        echo '<div>';
        echo "<input type=\"hidden\" name=\"cmid\" value=\"$cm->id\" />";
        echo '<input type="submit" value="' . get_string('addentry', 'qanda') . '" />';
        echo '</div>';
        echo '</form>';
        echo "</div>";
    }



/// Decide about to print the approval link
    if (has_capability('mod/qanda:answer', $context)) {
        /// Check we have pending entries
        if ($hiddenentries = $DB->count_records('qanda_entries', array('qandaid' => $qanda->id, 'approved' => 0))) {
            if ($availableoptions) {
                $availableoptions .= '<br />';
            }
            $availableoptions .='<span class="help-link">' .
                    '<a href="' . $CFG->wwwroot . '/mod/qanda/view.php?id=' . $cm->id .
                    '&amp;mode=approval' . '"' .
                    '  class="approve-link" title="' . s(get_string('waitingapproval', 'qanda')) . '">' .
                    get_string('waitingapproval', 'qanda') . ' (' . $hiddenentries . ')</a>' .
                    '</span>';
        }
    }

/// Start to print qanda controls
//        print_box_start('qanda-control clearfix');
    echo '<div class="qanda-control" style="text-align: right">';
    echo $availableoptions;

/// The print icon
    if ($showcommonelements and $mode != 'search') {
        if (has_capability('mod/qanda:manageentries', $context) or $qanda->allowprintview) {
//                print_box_start('printicon');
            echo '<span class="wrap printicon">';
            echo " <a title =\"" . get_string("printerfriendly", "qanda") . "\" href=\"print.php?id=$cm->id&amp;mode=$mode&amp;hook=" . urlencode($hook) . "&amp;sortkey=$sortkey&amp;sortorder=$sortorder&amp;offset=$offset\"><img class=\"icon\" src=\"" . $OUTPUT->pix_url('print', 'qanda') . "\" alt=\"" . get_string("printerfriendly", "qanda") . "\" /></a>";
            echo '</span>';
//                print_box_end();
        }
    }
/// End qanda controls
//        print_box_end(); /// qanda-control
    echo '</div>';

//        print_box('&nbsp;', 'clearer');
}



//echo '<div class="add-and-search-box">';
/// Search box
if ($showcommonelements) {
    echo '<div class="search-box">';
    echo '<form method="post" action="view.php">';

    echo '<table class="box-align-center" width="70%" border="0">';
    echo '<tr><td align="center" class="qanda-search-box">';


    if ($mode == 'search') {
        echo '<input type="text" name="hook" size="20" value="' . s($hook) . '" alt="' . $strsearch . '" /> ';
    } else {
        echo '<input type="text" name="hook" size="20" value="" alt="' . $strsearch . '" /> ';
    }
    if ($fullsearch || $mode != 'search') {
        $fullsearchchecked = 'checked="checked"';
    } else {
        $fullsearchchecked = '';
    }
    echo '<input type="checkbox" name="fullsearch" id="fullsearch" value="1" ' . $fullsearchchecked . ' />';
    echo '<input type="hidden" name="mode" value="search" />';
    echo '<input type="hidden" name="id" value="' . $cm->id . '" />';
    echo '<label for="fullsearch">' . $strsearchinanswer . '</label>';
    echo '<input type="submit" value="' . $strsearch . '" name="searchbutton" /> ';
    echo '</td></tr></table>';

    echo '</form>';

    //echo '<br />';
    echo "</div>\n";
    echo '<br />';
}

echo "</div>\n";




require("tabs.php");

require("sql.php");

/// printing the entries
$entriesshown = 0;
$currentpivot = '';
$paging = NULL;

if ($allentries) {

    //Decide if we must show the ALL link in the pagebar
    $specialtext = '';
    if ($qanda->showall) {
        $specialtext = get_string("allentries", "qanda");
    }
    if ($page < 0) {
        //Avoid negative Q&A pair values when listing ALL entries.
        $offset = 0;
    }

    //Build paging bar
    $paging = qanda_get_paging_bar($count, $page, $entriesbypage, "view.php?id=$id&amp;mode=$mode&amp;hook=" . urlencode($hook) . "&amp;sortkey=$sortkey&amp;sortorder=$sortorder&amp;fullsearch=$fullsearch&amp;", 9999, 10, '&nbsp;&nbsp;', $specialtext, -1);

    echo '<div class="paging">';
    echo $paging;
    echo '</div>';

    /* //load ratings
      require_once($CFG->dirroot . '/rating/lib.php');
      if ($qanda->assessed != RATING_AGGREGATE_NONE) {
      $ratingoptions = new stdClass;
      $ratingoptions->context = $context;
      $ratingoptions->component = 'mod_qanda';
      $ratingoptions->ratingarea = 'entry';
      $ratingoptions->items = $allentries;
      $ratingoptions->aggregate = $qanda->assessed; //the aggregation method
      $ratingoptions->scaleid = $qanda->scale;
      $ratingoptions->userid = $USER->id;
      $ratingoptions->returnurl = $CFG->wwwroot . '/mod/qanda/view.php?id=' . $cm->id;
      $ratingoptions->assesstimestart = $qanda->assesstimestart;
      $ratingoptions->assesstimefinish = $qanda->assesstimefinish;

      $rm = new rating_manager();
      $allentries = $rm->get_ratings($ratingoptions);
      } */

    foreach ($allentries as $entry) {

        // Setting the pivot for the current entry
        $pivot = $entry->qandapivot;
        $upperpivot = textlib::strtoupper($pivot);
        $pivottoshow = textlib::strtoupper(format_string($pivot, true, $fmtoptions));
        // Reduce pivot to 1cc if necessary
        if (!$fullpivot) {
            $upperpivot = textlib::substr($upperpivot, 0, 1);
            $pivottoshow = textlib::substr($pivottoshow, 0, 1);
        }

        // if there's a group break
        if ($currentpivot != $upperpivot) {

            // print the group break if apply
            if ($printpivot) {
                $currentpivot = $upperpivot;

                echo '<div>';
                echo '<table cellspacing="0" class="qanda-category-header">';

                echo '<tr>';
                if (isset($entry->userispivot)) {
                    // printing the user icon if defined (only when browsing authors)
                    echo '<th align="left">';

                    $user = $DB->get_record("user", array("id" => $entry->userid));
                    echo $OUTPUT->user_picture($user, array('courseid' => $course->id));
                    $pivottoshow = fullname($user, has_capability('moodle/site:viewfullnames', context_course::instance($course->id)));
                } else {
                    echo '<th >';
                }

                echo $OUTPUT->heading($pivottoshow);
                echo "</th></tr></table></div>\n";
            }
        }

        /// highlight the term if necessary
        if ($mode == 'search') {
            //We have to strip any word starting by + and take out words starting by -
            //to make highlight works properly
            $searchterms = explode(' ', $hook);    // Search for words independently
            foreach ($searchterms as $key => $searchterm) {
                if (preg_match('/^\-/', $searchterm)) {
                    unset($searchterms[$key]);
                } else {
                    $searchterms[$key] = preg_replace('/^\+/', '', $searchterm);
                }
                //Avoid highlight of <2 len strings. It's a well known hilight limitation.
                if (strlen($searchterm) < 2) {
                    unset($searchterms[$key]);
                }
            }
            $strippedsearch = implode(' ', $searchterms);    // Rebuild the string
            $entry->highlight = $strippedsearch;
        }

        /// and finally print the entry.
        $entry->entrycount = $offset + $entriesshown + 1;
        //echo $entrycount . "<p>";
        qanda_print_entry($course, $cm, $qanda, $entry, $mode, $hook, 1, $displayformat);
        $entriesshown++;
    }
}
if (!$entriesshown) {
    echo $OUTPUT->box(get_string("noentries", "qanda"), "generalbox box-align-center boxwidthwide");
}

if (!empty($formsent)) {
    // close the form properly if used
    echo "</div>";
    echo "</form>";
}

if ($paging) {
    echo '<hr />';
    echo '<div class="paging">';
    echo $paging;
    echo '</div>';
}
echo '<br />';
qanda_print_tabbed_table_end();

/// Finish the page
echo $OUTPUT->footer();
