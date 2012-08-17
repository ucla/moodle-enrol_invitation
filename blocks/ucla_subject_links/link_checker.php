<?php
/**
 * Get all html files in given directory and parse them for html 
 */
// Script should only be run via CLI
define('CLI_SCRIPT', true);

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot.'/blocks/moodleblock.class.php');
require_once($CFG->dirroot. '/blocks/ucla_subject_links/block_ucla_subject_links.php');

// script variables
$show_debugging_messages = false;
$timeout = 10;   // 10 seconds
$broken_links = array();    // should be indexed by file and then link

$directory = new RecursiveDirectoryIterator(block_ucla_subject_links::get_location());
$iterator = new RecursiveIteratorIterator($directory);
$regex = new RegexIterator($iterator, '/^.+\.htm$/i', RecursiveRegexIterator::GET_MATCH);

// before checking links we need to set a timeout for fopen, or else bad urls
// can hang the script
$oldtimeout = ini_set('default_socket_timeout', $timeout);

// files will be an array of paths to the html file we want to parse
foreach ($regex as $files) {
    foreach ($files as $file) {
        cmd_debug('Working on ' . $file);
        $links = checkPage(file_get_contents($file));
        
        if (empty($links)) {
            cmd_debug('No links found');
            continue;   // no links!
        }
        
        $found_broken_link = false;
        foreach ($links as $link) {
            // found links, now see if they are alive
            cmd_debug('pinging link ' . $link);
            if (pingLink($link)) {
                cmd_debug('Link works! ' . $link);
            } else {
                cmd_debug(sprintf("DEAD link found (%s) in %s\n", $link, $file));
                $broken_links[$file][] = $link;
                $found_broken_link = true;
            }
        }
        
        // if $show_debugging_messages is false, show some kind of progress
        // indicator so we know script is processing
        if (empty($show_debugging_messages)) {
            if (!empty($found_broken_link)) {
                echo '!';
            } else {
                echo '.';
            }            
        }        
    }
}

ini_set('default_socket_timeout', $oldtimeout);

// now display results
echo "Broken links found:\n";
print_r($broken_links);

echo "DONE!\n";


// Script functions

/**
 * Given page content, will parse it to find html links.
 * 
 * @link http://www.phptoys.com/tutorial/create-link-checker.html original source
 * 
 * @param string $content
 * @return array            Returns an array of links
 */
function checkPage($content) {
    $links = array();
    $textLen = strlen($content);

    if ($textLen > 10) {
        $startPos = 0;
        $valid = true;

        while ($valid) {
            $spos = strpos($content, '<a ', $startPos);
            if ($spos < $startPos)
                $valid = false;
            $spos = strpos($content, 'href', $spos);
            $spos = strpos($content, '"', $spos) + 1;
            $epos = strpos($content, '"', $spos);
            $startPos = $epos;
            $link = substr($content, $spos, $epos - $spos);
            if ((strpos($link, 'http://') !== false) ||
                    strpos($link, 'https://') !== false) {
                $links[] = $link;
            }
        }
    }

    return $links;
}

/**
 * Throw away function because Moodle debugging() function is very cluttered
 * when reading it from the command line.
 */
function cmd_debug($message) {
    global $show_debugging_messages;
    if ($show_debugging_messages) {
        echo $message . "\n";
    }
}

/**
 * Checks to see if URL exists.
 * 
 * @link http://www.phptoys.com/tutorial/create-link-checker.html original source
 * 
 * @param string $domain    URL to check
 * @return boolean
 */
function pingLink($domain) {
    $file = @fopen($domain, "r");
    $status = false;

    if (!empty($file)) {
        $status = true;
        fclose($file);
    }
    return $status;
}

