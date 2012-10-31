<?php

require_once($CFG->dirroot.'/local/ucla/lib.php');
require_once('ucla_grade_grade.php');
require_once('ucla_grade_item.php');

final class grade_reporter {

    // Vars
    const SUCCESS = 0;
    const DATABASE_ERROR = 1;
    const BAD_REQUEST = 2;
    const CONNECTION_ERROR = 3;
    const MAX_COMMENT_LENGTH = 7900;

    private static $_instance = null;
    

    //Private constructor so no one else can use it
    private function __construct() {
    }

    //Cloning is not allowed
    private function __clone() {
    }

    /**
     * Gets the singleton instance of a SOAP connection to MyUCLA
     *
     * @return Object - The connection to MyUCLA
     */
    public static function get_instance() {
        if (self::$_instance === NULL) {
            global $CFG;
            $settings = array('exceptions' => true);
  
            //Careful - can raise exceptions
            self::$_instance = new SoapClient($CFG->gradebook_webservice, $settings);
        }
        return self::$_instance;
    }
    
    public static function change_class(&$obj, $class_type) {
        if (class_exists($class_type, true)) {
            $obj = unserialize(preg_replace("/^O:[0-9]+:\"[^\"]+\":/i", "O:" . strlen($class_type) . ":\"" . $class_type . "\":", serialize($obj)));
            return $obj;
        }
    }

}
