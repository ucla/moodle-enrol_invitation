<?php
/**
 * UCLA specific functions should be defined here.
 */

defined('MOODLE_INTERNAL') || die();

/**
 * @param string type   Type can be 'term', 'srs', 'uid'
 * @param mixed value   DDC (two digit number with C being either F, W, S, 1)
                        SRS/UID: (9 digit number, can have leading zeroes)
 * 
 * @return boolean
 */
function ucla_validator($type, $value){
    $result = 0;
    switch($term) {
        case 'term':
            $result = preg_match('/^[0-9]{2}[FWS1]$/', $value);
            break;
        case 'srs':
        case 'uid':
            $result = preg_match('/^[0-9]{9}$/', $value);
            break;
        default:
            throw new moodle_exception('invalid type', 'ucla_validator');
            break;
    }
    
    if ($result == 1) {
        return true;
    } else {
        return false;
    }
        
}