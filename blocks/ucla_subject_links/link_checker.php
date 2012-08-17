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
$show_debugging_messages = true;
$broken_links = array();    // should be indexed by file and then link

$path_to_check = block_ucla_subject_links::get_location();
$path_to_check = '/vagrant/moodle/blocks/ucla_subject_links/content/HIST';
$directory = new RecursiveDirectoryIterator($path_to_check);

$iterator = new RecursiveIteratorIterator($directory);
$regex = new RegexIterator($iterator, '/^.+\.htm$/i', RecursiveRegexIterator::GET_MATCH);

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
        foreach ($links as $link => $link_text) {
            // found links, now see if they are alive
            cmd_debug('pinging link ' . $link);
            if (pingLink($link)) {
                cmd_debug('Link works! ' . $link);
            } else {
                cmd_debug(sprintf("DEAD link found (%s) in %s\n", $link, $file));
                $broken_links[$file][$link] = $link_text;
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

// now display results
echo "\nBroken links found:\n";
print_r($broken_links);

echo "DONE!\n";


// Script functions

/**
 * Given page content, will parse it to find html links. Dependant on links
 * being in following format:
 * 
 *  <a href="<link>"
 * 
 * @link http://www.phptoys.com/tutorial/create-link-checker.html original source
 * 
 * @param string $content
 * @return array            Returns an array of links, in following format:
 *                          [link] => [link text]
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
                // found link, so try to get link text
                $start_link_text = strpos($content, '>', $epos) + 1;
                $end_link_text = strpos($content, '<', $start_link_text);
                
                $link_text = substr($content, $start_link_text, $end_link_text - $start_link_text);
                
                $links[$link] = $link_text;
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

 * @link http://stackoverflow.com/questions/981954/how-can-one-check-to-see-if-a-remote-file-exists-using-php/982045#982045 original source
 * 
 * @param string $domain    URL to check
 * @return boolean
 */
function pingLink($domain) {
    $ch = curl_init($domain);

    // Note, need to fake browser user agent, because some websites, like 
    // (Humanities!) are setup to prevent bots from accessing their websites.
    // Got this user agent from http://davidwalsh.name/set-user-agent-php-curl-spoof
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
    curl_setopt($ch, CURLOPT_NOBODY, true); // prevents html from being returned
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);  // set timeout or certain links will hang
    curl_exec($ch);
    
    // first make sure that curl executed sucessfully
    $curlretcode = curl_errno($ch);
    if ($curlretcode > 0) {
        cmd_debug('cURL error code: ' . $curlretcode);
        return false;
    }
    
    $retcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);    

    // $retcode > 400 -> not found, $retcode = 200, found.    
    if ($retcode >= 400) {
        cmd_debug('HTTP error code: ' . $retcode);
        return false;
    }
    
    return true;    
}

