<?php
// CCLE-3794
// Script to email admins when event queue is not being processed


// Satisfy Moodle's requirement for running CLI scripts
define('CLI_SCRIPT', true);

$moodleroot = dirname(dirname(dirname(dirname(__FILE__)))); 
require($moodleroot . '/config.php');
require($CFG->libdir . '/clilib.php');

global $DB;

// Support a 'tolerance' param
// Will allow admin to set a value threshold for retry count
list($ext_argv, $unrecog) = cli_get_params(
    array(
        'tolerance' => false,
    ),
    array(
        't' => 'tolerance',
    )
);


// Default values
$default_tolerance = 5;
$default_display = 20;

$tolerance = (!empty($ext_argv['tolerance']) && !empty($unrecog[0])) 
    ? $unrecog[0] : $default_tolerance;

try {

    // Find records with the 'status' count that's greater than the tolerance
    $records = $DB->get_records_select('events_queue_handlers', 'status >= :limit', 
            array('limit' => $tolerance));
    
    // If we find such records, notify admins
    if(!empty($records)) {
        $out = "";
        
        $count = count($records);
        
        // Only display a small sampling.  Event queue backlog can grow
        // rather large.
        if($count <= $default_display) {
            foreach($records as $r) {
                $out .= json_encode($r, JSON_PRETTY_PRINT);
                $out .= "\n\n";
            }
        } else {
            $out = "There are more than $default_display records to display!";
        }

        // Prepare message
        $message = "There are $count failed events in the queue that have been retried more than $tolerance times: \n";
        $message .= "------------------\n";
        $message .= $out;
        
        $subject = "Events queue report (" . $count . ") events";
        $to = get_config('local_ucla', 'admin_email');
        if (empty($to)) {
            // variable not properly set
            echo "Event queue check: Error -- you're missing the 'to' email field!\n";
            return 0;
        }
        
        echo "Event queue check: Event queue has grown too much, email sent\n";
        return ucla_send_mail($to, $subject, $message);
    }
    
    echo "Event queue check: Successfully ran the script\n";
    return 1;
    
} catch (Exception $e) {
    // DB error
    echo "Event queue check: There was an error in the script.\n";
    return 0;
}
