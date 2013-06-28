<?php
/*
 * Command line script to resent all class links for courses that have been
 * built, but no URL was submitted.
 *
 * First, it tries to find the courses that were built, but have no url set in
 * mdl_ucla_browseall_classinfo.
 *
 * Then if runs through all the records and uses the MyUCLA updater class to
 * submit a url for the class links.
 *
 * NOTE: Be sure to run the BrowseBy cron before executing this script and after
 * to ensure that the latest class links information is available.
 */

define('CLI_SCRIPT', TRUE);

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/' . $CFG->admin . '/tool/myucla_url/myucla_urlupdater.class.php');
require_once($CFG->dirroot . '/local/ucla/lib.php');

list($options, $unrecognized) = cli_get_params(
    array(
        'help' => false
    ),
    array(
        'h' => 'help'
    )
);

if ($options['help']) {
    $help =
"Command line script to resent all class links for courses that have been
 built, but no URL was submitted.

Options:
-h, --help            Print out this help

Example:
\$sudo -u www-data /usr/bin/php local/ucla/scripts/resent_class_links.php [TERM]
";
    echo $help;
    die;
}

// Make sure that first parameter is a term.
if (empty($unrecognized) || !ucla_validator('term', $unrecognized[0])) {
    die("Must pass in a valid term.\n");
}
$term = $unrecognized[0];

$numskipped = 0;
$numupdates = 0;

// Find the courses that were built, but have no url set in
// mdl_ucla_browseall_classinfo.
$emptyurl = $DB->sql_isempty('ucla_reg_classinfo', 'url', TRUE, FALSE);
$sql = "SELECT  DISTINCT c.id,
                c.shortname
        FROM    {course} c
        JOIN    {ucla_request_classes} urc ON (
                    c.id=urc.courseid
                )
        JOIN    {ucla_browseall_classinfo} ubc ON (
                urc.term=ubc.term AND urc.srs=ubc.srs)
        WHERE   $emptyurl AND
                urc.term=?";
$rs = $DB->get_recordset_sql($sql, array($term));
if ($rs->valid()) {
    $urlupdater = new myucla_urlupdater();
    foreach ($rs as $course) {
        mtrace('Working on ' . $course->shortname);

        // Create URL for course.
        $courseurl = new moodle_url(make_friendly_url($course));
        $courseurl = $courseurl->out();

        // Go through each courseid and find all associated crosslists.
        $sql = "SELECT  ubc.*
                FROM    {course} c
                JOIN    {ucla_request_classes} urc ON (
                            c.id=urc.courseid
                        )
                JOIN    {ucla_browseall_classinfo} ubc ON (
                        urc.term=ubc.term AND urc.srs=ubc.srs)
                WHERE   c.id=?";
        $crosslists = $DB->get_records_sql($sql, array($course->id));
        
        mtrace(sprintf('  Updating %d sections', count($crosslists)));
        foreach ($crosslists as $crosslist) {
            // Do quick sanity check and make sure that no URL was set.
            if (!empty($crosslist->url)) {
                mtrace(sprintf('    WARNING: url set for %s|%s -> %s, skipping',
                        $crosslist->term, $crosslist->srs, $crosslist->url));
                ++$numskipped;
                continue;
            }

            $urlupdater->sync_MyUCLA_urls(array(make_idnumber($crosslist) =>
                array('term' => $crosslist->term, 
                      'srs' => $crosslist->srs,
                      'url' => $courseurl)));

            if (empty($urlupdater->successful)) {
                // There was a problem, report it.
                mtrace(sprintf('    ERROR: Did not update URL at MyUCLA for %s|%s', 
                        $crosslist->term, $crosslist->srs));
            } else {
                mtrace(sprintf('    Set url %s|%s -> %s', $crosslist->term,
                        $crosslist->srs, $courseurl));
            }

            ++$numupdates;
        }
    }
}
mtrace(sprintf('Updated %d records and skipped %d records.', $numupdates, $numskipped));
mtrace('DONE!');
