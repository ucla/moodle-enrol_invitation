<?php
/**
 * Events for UCLA course format.
 *
 * @package format_ucla
 * @copyright 2012 UC Regents
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$handlers = array(
    'course_created' => array(
        'handlerfile'     => '/course/format/ucla/eventslib.php',
        'handlerfunction' => 'fix_coursedisplay',
        'schedule'        => 'instant'
    ),
    
    'course_updated' => array(
        'handlerfile'     => '/course/format/ucla/eventslib.php',
        'handlerfunction' => 'fix_coursedisplay',
        'schedule'        => 'instant'
    )    
);
