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
 * Naming conventions for UCLA stats console report classes
 *
 *  - the name of the class is used as the "name" for the cached result entry in
 *    "ucla_statsconsole" table.
 *  - lang strings that should be defined are:
 *      <class name>: what stats class is querying
 *      <class name>_help: explaination of how stats class is getting data
 *  -
 */
abstract class uclastats_base {
    /**
     * Used to cache results of userid to user object lookups. Can be used to
     * cache other types of data.
     * @var array
     */
    protected static $_cache = array();

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
        $this->_userid = $userid;
    }

    /**
     * Abstract method that needs to be defined by reports.
     *
     * Generates HTML output for display of query results.
     *
     * @params mixed $results
     * @return string           Returns generated HTML
     */
    public abstract function display($results);

    /**
     * Returns either a list of cached results for current report or specified
     * cached results.
     *
     * @global object $DB
     * @param int $resultid     Default is null. If null, then returns a list
     *                          cached results for gieven report. If id
     *                          specified, then returns that specific cached
     *                          results.
     *
     * @return mixed            Returns either a result listing or specified
     *                          cached results.
     */
    public function get_results($resultid = null) {
        global $DB;

        $ret_val = null;
        $params = array('name' => get_class($this));
        
        if (empty($resultid)) {
            // user wants list of cached results
            $results = $DB->get_records('ucla_statsconsole', $params, 'timecreated DESC');
            if (!empty($results)) {
                $ret_val = array();
                foreach ($results as $index => $result) {
                    $ret_val[$index] = $this->process_result($result);
                }
            }
        } else {
            // user wants a specific cached result
            $params['id'] = $resultid;
            $result = $DB->get_record('ucla_statsconsole', $params);
            if (!empty($result)) {
                $ret_val = $this->process_result($result);
            }
        }

        return $ret_val;
    }

    /**
     * Processes cached result so that it is displayable. Meaning it will:
     *  - convert userid to a user object
     *  - format timecreated into a human readable timestamp
     *  - unserialize parameters and results
     *
     * @global object $DB
     * @param object $cached_result
     * @return object
     */
    protected function process_result($cached_result) {
        global $DB;

        // cache user object lookups, since there might be many repeats
        if (empty(self::$_cache['user'][$cached_result->userid])) {
            self::$_cache['user'][$cached_result->userid] =
                    $DB->get_record('user', array('id' => $cached_result->userid));
        }

        $cached_result->user = self::$_cache['user'][$cached_result->userid];
        $cached_result->params = unserialize($cached_result->params);
        $cached_result->results = unserialize($cached_result->results);
        $cached_result->timecreated = userdate($cached_result->timecreated);

        return $cached_result;
    }

    /**
     * Abstract method that needs to be defined by reports.
     *
     * Should validate params and throw an exception if there is an error.
     *
     * @throws  moodle_exception
     *
     * @params mixed $params
     * @return mixed            Returns result as defined by query.
     */
    public abstract function query($params);

    /**
     * Runs query for given parameters and caches the results.
     *
     * @throws  moodle_exception
     *
     * @global object $DB
     * @param mixed $params
     * @return mixed            Returns results of query.
     */
    public function run($params) {
        global $DB;

        $results = $this->query($params);

        // now cache results
        $cache_result = new stdClass();
        $cache_result->name = get_class($this);
        $cache_result->userid = $this->_userid;
        $cache_result->params = serialize($params);
        $cache_result->results = serialize($results);
        $cache_result->locked = 0;
        $cache_result->timecreated = time();

        $cached_resultid = $DB->insert_record('ucla_statsconsole', $cache_result);

        return $results;
    }
}