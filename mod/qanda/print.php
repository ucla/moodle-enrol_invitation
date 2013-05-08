<?php

global $CFG;

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/lib.php');

$id = required_param('id', PARAM_INT);                     // Course Module ID
$sortorder = optional_param('sortorder', 'asc', PARAM_ALPHA);     // Sorting order
$offset = optional_param('offset', 0, PARAM_INT);              // number of entries to bypass
$displayformat = optional_param('displayformat', -1, PARAM_INT);

$mode = required_param('mode', PARAM_ALPHA);             // mode to show the entries
$hook = optional_param('hook', 'ALL', PARAM_CLEAN);       // what to show
$sortkey = optional_param('sortkey', 'UPDATE', PARAM_ALPHA); // Sorting key

$url = new moodle_url('/mod/qanda/print.php', array('id' => $id));
if ($sortorder !== 'asc') {
    $url->param('sortorder', $sortorder);
}
if ($offset !== 0) {
    $url->param('offset', $offset);
}
if ($displayformat !== -1) {
    $url->param('displayformat', $displayformat);
}
if ($sortkey !== 'UPDATE') {
    $url->param('sortkey', $sortkey);
}
if ($mode !== 'letter') {
    $url->param('mode', $mode);
}
if ($hook !== 'ALL') {
    $url->param('hook', $hook);
}
$PAGE->set_url($url);

if (!$cm = get_coursemodule_from_id('qanda', $id)) {
    print_error('invalidcoursemodule');
}

if (!$course = $DB->get_record("course", array("id" => $cm->course))) {
    print_error('coursemisconf');
}

if (!$qanda = $DB->get_record("qanda", array("id" => $cm->instance))) {
    print_error('invalidid', 'qanda');
}

if (!$entriesbypage = $qanda->entbypage) {
    $entriesbypage = $CFG->qanda_entbypage;
}

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/qanda:view', $context);

// Prepare format_string/text options
$fmtoptions = array(
    'context' => $context);

$PAGE->set_pagelayout('print');
$PAGE->set_title(get_string("modulenameplural", "qanda"));
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();

if (!has_capability('mod/qanda:manageentries', $context) and !$qanda->allowprintview) {
    notice(get_string('printviewnotallowed', 'qanda'));
}

/// setting the default values for the display mode of the current qanda
/// only if the qanda is viewed by the first time
if ($dp = $DB->get_record('qanda_formats', array('name' => $qanda->displayformat))) {
    $printpivot = $dp->showgroup;
    if ($mode == '' and $hook == '' and $show == '') {
        $mode = $dp->defaultmode;
        $hook = $dp->defaulthook;
        $sortkey = $dp->sortkey;
        $sortorder = $dp->sortorder;
    }
} else {
    $printpivot = 1;
    if ($mode == '' and $hook == '' and $show == '') {
        $mode = 'letter';
        $hook = 'ALL';
    }
}

if ($displayformat == -1) {
    $displayformat = $qanda->displayformat;
}

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
    case 'entry':  /// Looking for a certain entry id
        $tab = QANDA_STANDARD_VIEW;
        break;

    /* case 'cat':    /// Looking for a certain cat
      $tab = qanda_CATEGORY_VIEW;
      if ( $hook > 0 ) {
      $category = $DB->get_record("qanda_categories", array("id"=>$hook));
      }
      break;
     * 
     * 
     * case 'author':  /// Looking for entries, browsed by author
      $tab = qanda_AUTHOR_VIEW;
      if ( !$hook ) {
      $hook = 'ALL';
      }
      if ( !$sortkey ) {
      $sortkey = 'FIRSTNAME';
      }
      if ( !$sortorder ) {
      $sortorder = 'asc';
      }
      break;
     */
    case 'approval':    /// Looking for entries waiting for approval
        $tab = QANDA_APPROVAL_VIEW;
        if (!$hook and !$sortkey and !$sortorder) {
            $hook = 'ALL';
        }
        break;

    case 'term':   /// Looking for entries that include certain term in its question, answer or aliases
        $tab = QANDA_STANDARD_VIEW;
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



//case 'letter':  /// Looking for entries that begin with a certain letter, ALL or SPECIAL characters
    default:
        $tab = QANDA_STANDARD_VIEW;
        if (!$hook) {
            $hook = 'ALL';
        }
        break;
}

include_once("sql.php");

$entriesshown = 0;
$currentpivot = '';

$site = $DB->get_record("course", array("id" => 1));
echo '<p style="text-align:right"><span style="font-size:0.75em">' . userdate(time()) . '</span></p>';
echo get_string("site") . ': <strong>' . format_string($site->fullname) . '</strong><br />';
echo get_string("course") . ': <strong>' . format_string($course->fullname) . ' (' . format_string($course->shortname) . ')</strong><br />';
echo get_string("modulename", "qanda") . ': <strong>' . format_string($qanda->name, true) . '</strong>';
if ($allentries) {
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

        // If there's  group break
        if ($currentpivot != $upperpivot) {

            // print the group break if apply
            if ($printpivot) {
                $currentpivot = $upperpivot;

                if (isset($entry->userispivot)) {
                    // printing the user icon if defined (only when browsing authors)
                    $user = $DB->get_record("user", array("id" => $entry->userid));
                    $pivottoshow = fullname($user);
                }

                echo "<p class='mdl-align'><strong>" . clean_text($pivottoshow) . "</strong></p>";
            }
        }
        if ($offset < 0) {
            //Avoid negative Q&A pair values when listing ALL entries.
            $offset = 0;
        }
        $entry->entrycount = $offset + $entriesshown + 1;
        qanda_print_entry($course, $cm, $qanda, $entry, $mode, $hook, 1, $displayformat, true);
        $entriesshown++;
    }
}

echo $OUTPUT->footer();
