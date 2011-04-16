<?
/**
 *  Course creation parameters from the config file and generic functions
 **/

/**
 * Directly prints a message to STDERR.
 * @param The error message
 **/
function _error($text) {
	$STDERR = fopen("php://stderr", "r");
	fwrite($STDERR, "$text\n");
	fclose($STDERR);
	mylog("ERROR: $text");
}

/**
 * Prints a message with a timestamp, when called after the first time.
 * @param The message
 * @param boolean 
 *      true = print a timestamp for the next call
 *      false = do not print a timestamp for the next call
 **/
function mylog($text, $eol=true) {
	static $last_eol="start value";
	if ($last_eol === true) {
		echo '[' . date('Y-m-d H:i:s') . "]\t$text"; 
	} else {
		echo $text; 
	}
	$last_eol = $eol;
	if ($eol) echo "\n";
}

/**
 * Prints out an entry for the IMS XML
 * @param The long title
 * @param The idnumber
 * @param The summer session ('' means none)
 * @param The description of the course
 * @param The course name ([subject][number]-[section][c])
 * @param The term of the course (built into shortname)
 * @param The category to place the course in
 * @param The visibible flag
 * @return The IMS string
 **/
function course_IMS($titlevalue, $idvalue, $session, $description, 
        $course, $term, $category, $visible) {
    $group = "
    <group recstatus=1>
    <sourcedid>
    <source>$term$session-$course</source>
    <id>$idvalue</id>
    </sourcedid>
    <description>
    <short>$term$session-$course</short>
    <long><![CDATA[$titlevalue]]></long>
    <full><![CDATA[$description]]></full>
    </description>
    <org>
    <id><![CDATA[$term]]></id>
    <orgunit>$category</orgunit>
    </org>
    <extension>
    <visible>$visible</visible>
    </extension>
    </group>
    ";

    return $group;
}

/**
 * + Convert Term to Text
 * ! assumes $term is in the form YYQ
 * ! if Q is not F W or S then default is summer
 * F -> Fall
 * @param The term to convert
 * @param The summer session if needed
 * @return The pretty version of the term
 **/
function term_to_text($term, $session) {
    $term_letter = substr($term, -1, 1);
    $years = substr($term, 0, 2);

    if ($term_letter == "F" || $term_letter == "f") {
        $termtext = "20" . $years . " Fall";
    }
    // W -> Winter
    else if ($term_letter == "W" || $term_letter == "w") {
        $termtext = "20" . $years . " Winter";
    }
    // S -> Spring
    else if ($term_letter == "S" || $term_letter == "s") {
        $termtext = "20" . $years . " Spring";
    }
    // 1 -> Summer
    else {
        $termtext = "20" . $years . " Summer Session " . $session;
    }

    return $termtext;
}

/**
 * This will return true if this term is a summer term
 * @param The term
 * @return Is summer term?
 **/
function match_summer($term) {
    return preg_match('/1$/', $term);
}

/**
 * This will build the idnumber of the course.
 * @param The term
 * @param The SRS of the course
 * @param boolean If the course is a master course
 * @return The course's idnumber
 **/
function build_idnumber($term, $srs, $master = FALSE) {                             
    $build = "$term-";

    if ($master) {
        $build .= 'Master_';
    }
    
    $build .= $srs;

    return $build;
}

/**
 * Parses the reference file into an array.
 * @param The file location
 * @return The elements of the email parsed into an array
 **/
function parsefile($file) {
    $email_params = array();

    $fp = fopen($file, 'r');

    if (!$fp) {
        echo "ERROR: could not open email template file $file \n";
        return ;
    }

    echo "Parsing $file ...\n";
    // first 3 lines are headers
    for ($x = 0; $x < 3; $x++) {
        $line = fgets($fp);
        if (preg_match('/'.'^FROM:(.*)'.'/i',$line, $matches)) {
            $email_params['from'] = trim($matches[1]);
        } else if (preg_match('/'.'^BCC:(.*)'.'/i',$line, $matches)) {
            $email_params['bcc'] = trim($matches[1]);
        } else if (preg_match('/'.'^SUBJECT:(.*)'.'/i',$line,$matches)) {
            $email_params['subject'] = $matches[1];
        }
    }
    
    if(sizeof($email_params) != 3) {
        echo "ERROR: failed to parse headers in $file \n";
        return false;
    }
    
    $email_params['body'] = '';
    
    while (!feof($fp)) { //the rest of the file is the body
        $email_params['body'] .= fread($fp, 8192);
    }
   
    echo "Parsing $file successful \n";
    fclose($fp);
    
    return $email_params;
} 

/**
 * Replaces values in the email with values provided in arguments.
 * @param The parsed email
 * @param The values to replace the parsed entries with
 * @return The reparsed emails
 **/
function filltemplate($params, $arguments) {
    
    foreach ($params as $key => $value) { 
        // fill in template placeholders
        foreach ($arguments as $akey => $avalue) {
            $params[$key] = str_replace('#=' . $akey . '=#',
                $avalue, $params[$key]);
        }

        if (preg_match('/#=.*?=#/', $params[$key])) {
            echo $params[$key];
        }
    }

    return $params;
}

/**
 * Fall back to returning the index if a search does not exist.
 * @param The index we are searching for
 * @param The Array we are looking for the index in
 * @return The object at Array[index] or the index itself
 **/
function array_fblu($index, $search) {
    if (!isset($search[$index])) {
        return $index;
    }

    return $search[$index];
}

/** End of util.php **/
