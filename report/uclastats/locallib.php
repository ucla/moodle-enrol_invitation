<?php
/**
 * UCLA stats console base class.
 *
 * @package    report
 * @subpackage uclastats
 * @copyright  UC Regents
 */

defined('MOODLE_INTERNAL') || die;

/* Constants */
define('UCLA_STATS_ACTION_DELETE', 'delete');
define('UCLA_STATS_ACTION_UNLOCK', 'unlock');
define('UCLA_STATS_ACTION_LOCK', 'lock');

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
        global $USER;
        
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
            
            $can_manage_report = has_capability('report/uclastats:manage', 
                                 context_course::instance(SITEID));
           
            // format cached results
            foreach ($cached_results as $index => $result) {
                
                $is_locked = $result->locked;
                
                //only person who locked the result or site admins
                //should be able to unlock the result
                
                $can_unlock = ($result->userid == $USER->id) || is_siteadmin();
                
                $row = new html_table_row();

                // if result is currently being viewed, give some styling
                if ($result->id == $current_resultid) {
                    $row->attributes = array('class' => 'current-result');
                }
                
                
                //indicate to user if row is locked
                if ($is_locked) {                    
                    if(isset($row->attributes['class'])) {
                         $row->attributes['class'] .= ' locked'; 
                    } else {
                         $row->attributes['class'] = 'locked';
                    }
                }


                $row->cells['param'] = $this->format_cached_params($result->params);
                $row->cells['results'] = $this->format_cached_results($result->results);

                // display information on who ran the query and the timestamp
                $lastran = new stdClass();
                $lastran->who = $result->userfullname;
                $lastran->when = $result->timecreated;
                $row->cells['lastran'] =
                        get_string('lastran', 'report_uclastats', $lastran);

                //view results
                $row->cells['actions'] = html_writer::link(
                new moodle_url('/report/uclastats/view.php',
                array('report' => get_class($this),
                      'resultid' => $result->id)), 
                get_string('view_results', 'report_uclastats'));
                 
                // Indicate if result is locked and user cannot unlock it.
                if ($is_locked && !$can_unlock) {
                    $row->cells['actions'] .= ' (';
                    $row->cells['actions'] .= html_writer::tag('span',
                            get_string('locked_results' , 'report_uclastats'));
                    $row->cells['actions'] .= ')';
                }

                if ($can_manage_report) {                    
                    //unlock
                    if($is_locked && $can_unlock) {
                        $row->cells['actions'] .= html_writer::link(
                            new moodle_url('/report/uclastats/view.php',
                            array('report' => get_class($this),
                                  'resultid' => $result->id,
                                  'action' => UCLA_STATS_ACTION_UNLOCK)),
                                  get_string('unlock_results' , 'report_uclastats'),
                            array('class' => 'edit'));
                    } 
                    
                    if(!$is_locked) {                    
                        //lock
                        $row->cells['actions'] .= html_writer::link(
                        new moodle_url('/report/uclastats/view.php',
                        array('report' => get_class($this),
                             'resultid' => $result->id,
                             'action' => UCLA_STATS_ACTION_LOCK )),
                             get_string('lock_results', 'report_uclastats'),
                        array('class' => 'edit'));

                        //delete
                        $row->cells['actions'] .= html_writer::link(
                        new moodle_url('/report/uclastats/view.php',
                        array('report' => get_class($this),
                              'resultid' => $result->id,
                              'action' => UCLA_STATS_ACTION_DELETE)), 
                              get_string('delete_results', 'report_uclastats'),
                        array('class' => 'edit'));
                    }
                    
                } 
                
                $row->cells['actions'] = html_writer::tag('span',
                        $row->cells['actions'],array('class'=>'editing_links'));

                $cached_table->data[$index] = $row;
            }

            $ret_val .= html_writer::table($cached_table);
        }

        return $ret_val;
    }

    /**
     * Generates HTML output for display of export options for given report 
     * resultid.
     * 
     * @param int $resultid
     * @return string
     */
    public function display_export_options($resultid) {
        global $OUTPUT;
        $export_options = html_writer::start_tag('div',
                array('class' => 'export-options'));
        $export_options .= get_string('export_options', 'report_uclastats');

        // right now, only supporting xls
        $xls_string = get_string('application/vnd.ms-excel', 'mimetypes');
        $icon = html_writer::empty_tag('img',
                array('src' => $OUTPUT->pix_url('f/spreadsheet'),
                      'alt' => $xls_string,
                      'title' => $xls_string));
        $export_options .= html_writer::link(
                new moodle_url('/report/uclastats/view.php',
                        array('report' => get_class($this),
                              'resultid' => $resultid,
                              'export' => 'xls')), $icon);

        $export_options .= html_writer::end_tag('div');
        return $export_options;
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
            $params_display = $this->format_cached_params($params);
            $ret_val .= html_writer::tag('p', get_string('parameters',
                    'report_uclastats', $params_display),
                    array('class' => 'parameters'));
        }

        $results = $uclastats_result->results;
        if (empty($results)) {
            $ret_val .= html_writer::tag('p', get_string('noresults','admin'),
                    array('class' => 'noresults'));
        } else {
            // now display results table
            $results_table = new html_table();
            $results_table->id = 'uclastats-results-table';
            $results_table->attributes = array('class' => 'results-table ' .
                get_class($this));

            $results_table->head = $uclastats_result->get_header();
            $results_table->data = $results;

            $ret_val .= html_writer::table($results_table);
        }

        // display export options
        $ret_val .= $this->display_export_options($resultid);

        // display information on who ran the query and the timestamp
        $footer_info = new stdClass();
        $footer_info->who = $uclastats_result->userfullname;
        $footer_info->when = $uclastats_result->timecreated;
        $footer = get_string('lastran', 'report_uclastats', $footer_info);
        $ret_val .= html_writer::tag('p', $footer, array('class' => 'lastran'));

        return $ret_val;
    }

    /**
     * Sends result data as a xls file.
     *
     * @params int $resultid    Result to send
     */
    public function export_result_xls($resultid) {
        global $CFG;
        require_once($CFG->dirroot.'/lib/excellib.class.php');

        // do sanity check (
        try {
            $uclastats_result = new uclastats_result($resultid);
        } catch (dml_exception $e) {
            return get_string('nocachedresults','report_uclastats');
        }

        // file name is report name
        $report_name = get_string(get_class($this), 'report_uclastats');
        $filename = clean_filename($report_name . '.xls');
        
        // creating a workbook (use "-" for writing to stdout)
        $workbook = new MoodleExcelWorkbook("-");
        // sending HTTP headers
        $workbook->send($filename);
        // adding the worksheet
        $worksheet = $workbook->add_worksheet($report_name);
        $bold_format = $workbook->add_format();
        $bold_format->set_bold(true);

        // add title
        $worksheet->write_string(0, 0, $report_name, $bold_format);

        // add parameters (if any)
        $params = $uclastats_result->params;
        if (!empty($params)) {
            $params_display = $this->format_cached_params($params);
            $worksheet->write_string(1, 0, get_string('parameters',
                    'report_uclastats', $params_display));
        }

        // now go through the result set
        $results = $uclastats_result->results;
        $row = 3; $col = 0;
        if (empty($results)) {
            $worksheet->write_string(2, 0, get_string('noresults','admin'));
        } else {
            // first display table header
            $header = $uclastats_result->get_header();
            foreach ($header as $name) {
                $worksheet->write_string($row, $col, $name, $bold_format);
                ++$col;
            }

            // now go through result set
            foreach ($results as $result) {
                ++$row; $col = 0;
                foreach ($result as $value) {
                    // values might have HTML in them
                    $value = clean_param($value, PARAM_NOTAGS);
                    if (is_numeric($value)) {
                        $worksheet->write_number($row, $col, $value);
                    } else {
                        $worksheet->write_string($row, $col, $value);
                    }
                    ++$col;
                }
            }
        }

        // display information on who ran the query and the timestamp
        $row += 2;
        $footer_info = new stdClass();
        $footer_info->who = $uclastats_result->userfullname;
        $footer_info->when = $uclastats_result->timecreated;
        $footer = get_string('lastran', 'report_uclastats', $footer_info);
        $worksheet->write_string($row, 0, $footer);

        // close the workbook
        $workbook->close();
        exit;
    }

    /**
     * Helper function to figure how to best display parameters column in cached
     * results table.
     *
     * @param array $params
     * @return string
     */
    public function format_cached_params($params) {
        $param_list = array();
        foreach ($params as $name => $value) {
            $param_list[] = get_string($name, 'report_uclastats') . ' = ' .
                    $value;
        }
        return implode(', ', $param_list);
    }

    /**
     * Helper function to figure how to best display results column in cached
     * results table.
     *
     * @param array $results
     * @return string
     */
    public function format_cached_results($results) {
        return count($results);
    }

    /**
     * Returns associated help text for given report.
     *
     * @return string
     */
    public function get_help() {
        return html_writer::tag('p', get_string(get_class($this) .
                '_help', 'report_uclastats'), array('class' => 'report-help'));
    }

    /**
     * Abstract method to return parameters needed to run report.
     *
     * @return array
     */
    public abstract function get_parameters();
    
    /**
     * Gets the start and end times for term.
     * Regular terms are indexed by 'start' and 'end'.
     * 
     * For summer term, 121, the sessions
     * 6A 8A  9A 1A are indexed as 'start_Xa', 'end_Xa'  
     * and 6C is indexed as 'start_c' and 'end_c' 
     * because there is only one session C
     *
     * 
     * @param string $term the school term
     * @return array
     */
    
    protected function get_term_info($term) {
        // We need to query the registrar
        ucla_require_registrar();

        $results = registrar_query::run_registrar_query('ucla_getterms', array($term), true);

        if (empty($results)) {
            return null;
        }

        $ret_val = array();

        // Get the term start and term end,
        //if it's a summer session,
        // then get start and end of entire summer

        $summer_session_a = array('6A','8A','9A','1A');
        
        foreach ($results as $r) {
            
            $session = $r['session'];

            if ($session == 'RG') {
                $ret_val['start'] = strtotime($r['session_start']);
                $ret_val['end'] = strtotime($r['session_end']);
                break;
            } else if (in_array($session,$summer_session_a)) {
                $ret_val['start_' . strtolower($session)] = strtotime($r['session_start']);
                $ret_val['end_' . strtolower($session)] = strtotime($r['session_end']);
            } else if ($session == '6C') {
                $ret_val['start_c'] = strtotime($r['session_start']);
                $ret_val['end_c'] = strtotime($r['session_end']);
            }
        }
        
        return $ret_val;
    }
    
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
                array('fields' => $this->get_parameters(),
                      'is_high_load' => $this->is_high_load()),
                'post',
                '',
                array('class' => 'run-form'));
        return $run_form;
    }

    /**
     * Allows reports to indicate if they run complex queries that might take
     * a long time to run or puts a high load on the server. An example of such
     * a query is one that makes extensive use of the mdl_log table, since 
     * none of those columns are indexed.
     *
     * @return boolean  Default return value is false
     */
    public function is_high_load() {
        return false;
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
     * Generate FROM statement that joins {course} c with {ucla_request_classes} urc 
     * and {ucla_reg_classinfo} urci to enforce that 
     * we are filtering out non-hostcourses and cancelled courses
     *
     * 
     * NOTE: for consistency and simplicity use c as the alias of {course}
     * urc as the alias of {ucla_request classes}
     * and urci as the alias of {ucla_reg_classinfo}. 
     *
     * @param boolean $include_hostcourese
     * 
     * @return string
     */   
    protected function from_filtered_courses($include_hostcourse = true) {

        $sql =  " FROM {course} c
                  JOIN {ucla_request_classes} urc 
                  ON ( c.id = urc.courseid AND
                       urc.term = :term "
                . ($include_hostcourse ? " AND urc.hostcourse = 1 "  : "")
                . ")
                  JOIN {ucla_reg_classinfo} urci ON (
                    urci.term = urc.term AND
                    urci.srs = urc.srs AND
                    urci.enrolstat <> 'X' 
                  )" ;
        
        return $sql;
    }

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
    protected $result;

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
                // stored as json, so decode it
                return json_decode($this->result->params, true);
            case 'results':
                // results might be obtained multiple times
                return $this->decode_results();
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
     * Returns an array of strings to use as the header for displaying the
     * results.
     *
     * @return array
     */
    public function get_header() {
        $ret_val = array();
        // get first element and get its array keys to generate header
        $results = $this->decode_results();
        $header = reset($results);

        // generate header
        foreach ($header as $name => $value) {
            $ret_val[] = get_string($name, 'report_uclastats');
        }

        return $ret_val;
    }

    /**
     * Since the result is encoded as a JSON object, need to decode it. Might
     * be returning the result many times, so cache it.
     *
     * @return array
     */
    private function decode_results() {
        if (!isset(self::$_cache['decode_results'][$this->result->id])) {
            self::$_cache['decode_results']
                    = json_decode($this->result->results, true);
        }
        return self::$_cache['decode_results'];
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
    
     private static $table = 'ucla_statsconsole';

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

       return $DB->insert_record(self::$table, $cache_result);
    }
    
    /**
     * Static method to delete result
     * 
     * @param int $resultid the corresponding id of result
     * 
     * @return boolean true if deletion was sucessful.
     *                 false if resultid is locked. 
     *                 exception automatically thrown if query error.
     */
    public static function delete($resultid){
        global $DB;
        
        
        $params =  array('id' => $resultid, 'locked'=> 0);
        
        //ensure that the record being deleted exists and is currently unlocked
        //delete_records returns true if the query is successful
        //even if nothing is matched/deleted
        //but we need any extra check that returns false
        //to indicate record was not deleted because it was locked
        //if the query fails an exception will be thrown
        if($DB->record_exists(self::$table,$params)) {
        
              return $DB->delete_records(self::$table,$params);
        
        }
        
        return false;
    }
    
    /**
     * Static method to lock result
     * 
     * @param int $resultid
     */
    public static function lock($resultid) {
        self::change_lock($resultid,true);
    }
    
    /**
     * Static method to unlock result
     * 
     * @param int $resultid
     */
    public static function unlock($resultid) {
        self::change_lock($resultid,false);
    }
    
    /**
     * Static private method to toggle lock
     * 
     * @param int $resultid
     * @param bool $lock true to lock,false to unlock
     */
    private static function change_lock($resultid,$lock) {
        global $DB;
        $params = new stdClass();
        $params->id = $resultid;
        $params->locked = ($lock) ? 1 : 0;
        $DB->update_record(self::$table, $params, false);
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
