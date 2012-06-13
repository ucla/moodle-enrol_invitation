<?php
/*
 * A collection of date/time format helpers.
 */

/**
 * Reports the approximate distance in time between two times given in seconds
 * or in a valid ISO string like.
 * For example, if the distance is 47 minutes, it'll return
 * "about 1 hour". See the source for the complete wording list.
 *
 *  Integers are interpreted as seconds. So,
 * <tt>$date_helper->distance_of_time_in_words(50)</tt> returns "less than a minute".
 *
 * Set <tt>include_seconds</tt> to true if you want more detailed approximations if distance < 1 minute
 * 
 * Code borrowed/inspired from: 
 * http://www.8tiny.com/source/akelos/lib/AkActionView/helpers/date_helper.php.source.txt
 * 
 * Which was in term inspired by Ruby on Rails' similarly called function
 */
function distance_of_time_in_words($from_time, $to_time = 0, $include_seconds = false) {
    $from_time = is_numeric($from_time) ? $from_time : strtotime($from_time);
    $to_time = is_numeric($to_time) ? $to_time : strtotime($to_time);
    $distance_in_minutes = round((abs($to_time - $from_time)) / 60);
    $distance_in_seconds = round(abs($to_time - $from_time));

    if ($distance_in_minutes <= 1) {
        if ($include_seconds) {
            if ($distance_in_seconds < 5) {
                return get_string('less_than_x_seconds', 'local_ucla', 5);
            } else if ($distance_in_seconds < 10) {
                return get_string('less_than_x_seconds', 'local_ucla', 10);
            } else if ($distance_in_seconds < 20) {
                return get_string('less_than_x_seconds', 'local_ucla', 20);
            } else if ($distance_in_seconds < 40) {
                return get_string('half_minute', 'local_ucla');
            } else if ($distance_in_seconds < 60) {
                return get_string('less_minute', 'local_ucla');
            } else {
                return get_string('a_minute', 'local_ucla');
            }
        }
        return ($distance_in_minutes == 0) ? get_string('less_minute', 'local_ucla') : get_string('a_minute', 'local_ucla');
    } else if ($distance_in_minutes <= 45) {
        return get_string('x_minutes', 'local_ucla', $distance_in_minutes);
    } else if ($distance_in_minutes < 90) {
        return get_string('about_hour', 'local_ucla');
    } else if ($distance_in_minutes < 1440) {
        return get_string('about_x_hours', 'local_ucla', round($distance_in_minutes / 60));
    } else if ($distance_in_minutes < 2880) {
        return get_string('a_day', 'local_ucla');
    } else {        
        return get_string('x_days', 'local_ucla', round($distance_in_minutes / 1440));
    }
}
