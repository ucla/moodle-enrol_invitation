<?php
/**
 * UCLA stats console base class.
 *
 * @package    report
 * @subpackage uclastats
 * @copyright  UC Regents
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Returns list of all reports available for UCLA stats console.
 *
 * @global object $CFG
 * @return array        An sorted array of report => report name
 */
function get_all_reports() {
    global $CFG;
    $ret_val = array();

    $reports = scandir($CFG->dirroot . '/report/uclastats/reports');
    // remove first two entries, since they are '.' and '..'.
    unset($reports[0]);
    unset($reports[1]);

    // replace text with actual report name
    foreach ($reports as $report) {
        $report_class = basename($report, '.php');
        $ret_val[$report_class] = get_string($report_class, 'report_uclastats');
    }

    collatorlib::asort($ret_val);
    return $ret_val;
}

/**
 * Naming conventions for UCLA stats console report classes
 *
 *  - the name of the class is used as the "name" for the cached result entry in
 *    "ucla_statsconsole" table.
 *  - lang strings that should be defined are:
 *      <class name>: what stats class is querying
 *      <class name>_help: explaination of how stats class is getting data
 *  - results should be indexed and the index names should be defined in lang
 */
abstract class uclastats_base implements renderable {
    /**
     * User who is running report.
     * @var int
     */
    protected $_userid = null;

    /**
     * Constructor
     * @param int $userid   User who is running the report. Used for logging.
     */
    public function __construct($userid) {
        if (is_object($userid)) {
            $userid = $userid->id;
        }
        $this->_userid = $userid;
    }

    /**
     * Generates HTML output for display of cached query results.
     *
     * @params int $current_resultid    Defaults to null. If given, then will
     *                                  highlight given result.
     * @return string                   Returns generated HTML
     */
    public function display_cached_results($current_resultid = null) {
        global $OUTPUT;
        $ret_val = '';

        $ret_val .= html_writer::tag('h3',
                get_string('cached_results_table', 'report_uclastats'),
                array('class' => 'cached-results-table-title'));

        $cached_results = new uclastats_result_list(get_class($this));
        if (empty($cached_results)) {
            $ret_val .= html_writer::tag('p', get_string('nocachedresults', 
                    'admin'), array('class' => 'noresults'));
        } else {
            // now display results table
            $cached_table = new html_table();
            $cached_table->attributes = array('class' =>
                'cached-results-table ' . get_class($this));

            // get first element and get its array keys to generate header
            $header = array('header_param', 'header_results', 'header_lastran',
                'header_actions');

            // generate header
            foreach ($header as $name) {
                $cached_table->head[] = get_string($name, 'report_uclastats');
            }

            // format cached results
            foreach ($cached_results as $index => $result) {
                $row = new html_table_row();

                // if result is currently being viewed, give some styling
                if ($result->id == $current_resultid) {
                    $row->attributes = array('class' => 'current-result');
                }

                $row->cells['param'] = implode(', ', $result->params);
                $row->cells['results'] = count($result->results);

                // display information on who ran the query and the timestamp
                $lastran = new stdClass();
                $lastran->who = $result->userfullname;
                $lastran->when = $result->timecreated;
                $row->cells['lastran'] =
                        get_string('lastran', 'report_uclastats', $lastran);

                // TODO: implement result locking/unlocking/deleting
                $row->cells['actions'] = html_writer::link(
                        new moodle_url('/report/uclastats/view.php',
                                array('report' => get_class($this),
                                      'resultid' => $result->id)),
                        get_string('view_results', 'report_uclastats'));

                $cached_table->data[$index] = $row;
            }

            $ret_val .= html_writer::table($cached_table);
        }

        return $ret_val;
    }

    /**
     * Generates HTML output for display of query results with parameters,
     * result table, and other information.
     *
     * Assumes that first row has every column needed to display and that lang
     * strings exist for each key.
     *
     * @params int $resultid    Result to display
     * @return string           Returns generated HTML
     */
    public function display_result($resultid) {
        global $OUTPUT;
        $ret_val = '';

        // do sanity check (
        try {
            $uclastats_result = new uclastats_result($resultid);
        } catch (dml_exception $e) {
            return get_string('nocachedresults','report_uclastats');
        }
        
        // display parameters
        $params = $uclastats_result->params;
        if (!empty($params)) {
            $param_list = array();
            foreach ($params as $name => $value) {
                $param_list[] = get_string($name, 'report_uclastats') . ' = ' .
                        $value;
            }
            $ret_val .= html_writer::tag('p', get_string('parameters',
                    'report_uclastats', implode(', ', $param_list)),
                    array('class' => 'parameters'));
        }

        $results = $uclastats_result->results;
        if (empty($results)) {
            $ret_val .= html_writer::tag('p', get_string('noresults','admin'),
                    array('class' => 'noresults'));
        } else {
            // now display results table
            $results_table = new html_table();
            $results_table->attributes = array('class' => 'results-table ' .
                get_class($this));

            // get first element and get its array keys to generate header
            $header = reset($results);

            // generate header
            foreach ($header as $name => $value) {
                $results_table->head[] = get_string($name, 'report_uclastats');
            }

            $results_table->data = $results;

            $ret_val .= html_writer::table($results_table);
        }

        // display information on who ran the query and the timestamp
        $footer_info = new stdClass();
        $footer_info->who = $uclastats_result->userfullname;
        $footer_info->when = $uclastats_result->timecreated;
        $footer = get_string('lastran', 'report_uclastats', $footer_info);
        $ret_val .= html_writer::tag('p', $footer, array('class' => 'lastran'));

        return $ret_val;
    }

    /**
     * Abstract method to return parameters needed to run report.
     *
     * @return array
     */
    public abstract function get_parameters();

    /**
     * Returns either a list of cached results for current report or specified
     * cached results.
     *
     * @global object $DB
     * @param int $resultid     Default is null. If null, then returns a list
     *                          cached results for gieven report. If id
     *                          specified, then returns that specific cached
     *                          results.
     * @return mixed            Returns either a uclastats_result_list or
     *                          uclastats_result.
     */
    public function get_results($resultid = null) {
        global $DB;

        $ret_val = null;
        if (empty($resultid)) {
            // user wants list of cached results
            $ret_val = new uclastats_result_list(get_class($this));
        } else {
            // user wants a specific cached result
            $ret_val = new uclastats_result($resultid);
        }

        return $ret_val;
    }

    /**
     * Returns Moodle form used to display form to run report.
     *
     * @return moodleform
     */
    public function get_run_form() {
        $report_url = new moodle_url('/report/uclastats/view.php',
                        array('report' => get_class($this)));
        $run_form = new runreport_form($report_url->out(),
                array('fields' => $this->get_parameters()),
                'post',
                '',
                array('class' => 'run-form'));
        return $run_form;
    }

    /**
     * Abstract method that needs to be defined by reports.
     *
     * Should validate params and throw an exception if there is an error.
     *
     * NOTE: Do not worry about casting array of objects returned by Moodle's
     * DB API to arrays, because when they are encoded and then decoded to and
     * from JSON, they will be cast as arrays.
     *
     * @throws  moodle_exception
     *
     * @params array $params
     * @return array            Returns an array of results.
     */
    public abstract function query($params);

    /**
     * Runs query for given parameters and caches the results.
     *
     * @throws  moodle_exception
     *
     * @global object $DB
     * @param array $params
     * @return int            Returns cached result id of the query.
     */
    public function run($params) {
        global $DB;

        $results = $this->query($params);        
        $cached_resultid = uclastats_result::save(get_class($this), $params,
                $results, $this->_userid);

        return $cached_resultid;
    }
}

/**
 * Contains a basic set of information needed to display statistics results.
 *
 * Also handles storing and retriving of cached results.
 */
class uclastats_result implements renderable {
    /**
     * Used to cache results of userid to user object lookups. Can be used to
     * cache other types of data.
     * @var array
     */
    protected static $_cache = array();

    /**
     * Stores results array.
     * @var array
     */
    protected $results;

    /**
     * Creates an instance of result object with specified cache result id.
     *
     * @throws dml_exception    Throws exception if result is not found.
     *
     * @global object $DB
     * @param mixed $resultid   If int, then will retrieve cached result. If an
     *                          object, then assumes that result is from
     *                          database.
     */
    public function __construct($result) {
        global $DB;
        if (is_int($result)) {
            $this->result = $DB->get_record('ucla_statsconsole',
                    array('id' => $result), '*', MUST_EXIST);
        } else if (is_object($result)) {
            $this->result = $result;
        } else {
            throw new dml_exception('invalidrecordunknown');
        }
    }

    /**
     * Magic getter function.
     *
     * Does behind the scenes formatting of results from database to have it
     * displayable.
     * 
     * @param string $name
     */
    public function __get($name) {
        switch ($name) {
            case 'params':
            case 'results':
                // stored as json, so decode it
                return json_decode($this->result->$name, true);
            case 'timecreated':
                // give pretty version of timestamp
                return userdate($this->result->timecreated);
            case 'user':
                // give user object
                return $this->get_user($this->result->userid);
            case 'userfullname':
                // give user fullname
                $user = $this->get_user($this->result->userid);
                return fullname($user);
            default:
                return $this->result->$name;
        }
    }

    /**
     * Queries for user object and tries to use cached result, if any.
     *
     * @global object $DB
     * @param int $userid
     */
    private function get_user($userid) {
        global $DB;
        // cache user object lookups, since there might be many repeats
        if (empty(self::$_cache['user'][$userid])) {
            self::$_cache['user'][$userid] =
                    $DB->get_record('user', array('id' => $userid));
        }
        return self::$_cache['user'][$userid];
    }

    /**
     * Static method to take report results and encode and then save them.
     *
     * @global object $DB
     * @param string $report
     * @param array $params
     * @param array $results
     * @param int $userid
     * @return int              Returns result id of newly created result
     */
    public static function save($report, $params, $results, $userid) {
        global $DB;

        $cache_result = new stdClass();
        $cache_result->name = $report;
        $cache_result->userid = $userid;
        $cache_result->params = json_encode($params);
        $cache_result->results = json_encode($results);
        $cache_result->locked = 0;
        $cache_result->timecreated = time();

        return $DB->insert_record('ucla_statsconsole', $cache_result);
    }
}

/**
 * Class to be used in report renderers and for retrieving list of report
 * results.
 */
class uclastats_result_list implements Iterator, renderable {
    private $position = 0;
    private $array = array();

    /**
     * Retrieves list of cached results for given report.
     *
     * @global type $DB
     * @param string $report
     */
    public function __construct($report) {
        global $DB;
        $this->position = 0;

       $results = $DB->get_records('ucla_statsconsole', 
               array('name' => $report), 'timecreated DESC');

       if (!empty($results)) {
           // cast results as uclastats_result objects
           $index = 0;
           foreach ($results as $result) {
               $this->array[$index] = new uclastats_result($result);
               ++$index;
           }
       }
    }

    function count() {
        return count($this->array);
    }

    function current() {
        return $this->array[$this->position];
    }

    function key() {
        return $this->position;
    }

    function next() {
        ++$this->position;
    }

    function rewind() {
        $this->position = 0;
    }

    function valid() {
        return isset($this->array[$this->position]);
    }
}
