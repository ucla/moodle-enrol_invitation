<?php
/*
 * One off command line script to manually hide courses and related TA sites for
 * a given term. Makes sure that guest enrollment plugins are disabled and
 * will not touch past courses that were made unhidden.
 */

define('CLI_SCRIPT', true);

$moodleroot = dirname(dirname(dirname(dirname(__FILE__))));
require($moodleroot . '/config.php');
require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/lib/clilib.php');

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
"Command line script to manually hide courses and related TA sites for a given
 term.

Options:
-h, --help            Print out this help

Example:
\$sudo -u www-data /usr/bin/php enrol/database/cli/ucla_sync.php [TERM]
";
    echo $help;
    die;
}

// Make sure that first parameter is a term.
if (empty($unrecognized) || !ucla_validator('term', $unrecognized[0])) {
    die("Must pass in a valid term.\n");
}
$term = $unrecognized[0];

echo "Hiding courses for term: " . $term . "\n";

list($num_hidden_courses, $num_hidden_tasites, $num_problem_courses,
        $num_skipped_courses, $error_messages) = rehide_courses($term);

echo sprintf("Hid %d courses.\n", $num_hidden_courses);
echo sprintf("Hid %d TA sites.\n", $num_hidden_tasites);
echo sprintf("Had %d problem courses.\n", $num_problem_courses);
echo sprintf("Had %d skipped courses.\n", $num_skipped_courses);
echo $error_messages;
die("\nDONE!\n");

/**
 * Exactly like hide_courses, but will not touch unhidden courses.
 *
 * @global object $DB
 * @param string $term
 * @return mixed            Returns false on invalid term. Otherwise returns an
 *                          array of $num_hidden_courses, $num_hidden_tasites,
 *                          $num_problem_courses, $num_skipped_courses,
 *                          $error_messages.
 */
function rehide_courses($term) {
    global $CFG, $DB;
    require_once($CFG->dirroot . '/blocks/ucla_tasites/block_ucla_tasites.php');
    require_once($CFG->dirroot . '/local/publicprivate/lib/course.class.php');

    if (!ucla_validator('term', $term)) {
        return false;
    }

    // Track some stats.
    $num_hidden_courses = 0;
    $num_hidden_tasites = 0;
    $num_problem_courses = 0;
    $num_skipped_courses = 0;
    $error_messages = '';

    // Get list of courses to hide.
    $courses = ucla_get_courses_by_terms(array($term));

    if (empty($courses)) {
        // No courses to hide.
        return array($num_hidden_courses, $num_hidden_tasites,
                     $num_problem_courses, $num_skipped_courses,
                     $error_messages);
    }

    $enrol_guest_plugin = enrol_get_plugin('guest');

    // Now run command to hide all courses for given term. Don't worry about
    // updating visibleold (don't care) and we aren't using update_course,
    // because if might be slow and trigger unnecessary events.
    $courseobj = new stdClass();
    $courseobj->visible = 0;
    foreach ($courses as $courseid => $courseinfo) {
        $courses_processed = array($courseid);
        $courseobj->id = $courseid;
        try {
            ++$num_hidden_courses;

            // Try to see if course had any TA sites.
            $existing_tasites = block_ucla_tasites::get_tasites($courseid);
            if (!empty($existing_tasites)) {
                foreach ($existing_tasites as $tasite) {
                    ++$num_hidden_tasites;
                    $courses_processed[] = $tasite->id;
                }
            }

            // Hide courses and guest plugins.
            foreach ($courses_processed as $courseid) {
                $course = $DB->get_record('course', array('id' => $courseid), 
                        '*', MUST_EXIST);
                if ($course->visible == 1) {
                    ++$num_skipped_courses;
                    continue;
                }

                $courseobj->id = $courseid;
                $DB->update_record('course', $courseobj, true);

                PublicPrivate_Course::set_guest_plugin($course, ENROL_INSTANCE_DISABLED);
            }

        } catch (dml_exception $e) {
            $error_messages .= sprintf("Could not hide courseid %d\n%s\n",
                    $courseobj->id, $e->getMessage());
            ++$num_problem_courses;
        }
    }

    return array($num_hidden_courses, $num_hidden_tasites,
                 $num_problem_courses, $num_skipped_courses, $error_messages);
}