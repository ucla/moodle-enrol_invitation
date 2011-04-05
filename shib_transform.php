<?PHP
// Program: shib_transform.php
// Purpose: Set the firstname to equal SHIB_GIVENNAME + SHIB_MIDDLENAME, and lastname to add Suffix
// Usage: In admin/shibboleth/Data modification API:  use: /usr/local/moodle/shib_transform.php
// UPdated: 2-22-09 Mike Franks - using displayname from campus directory, if available
// Updated: 8-22-08 Mike Franks - fix for SSC's Shibboleth config different attribute names, and shrunk institution
// Updated: 1-10-08 Jovca - fix for Moodle 1.8, change "get_first_string" to "$this->get_first_string"
// Updated: 4-10-07 Mike Franks - previous switch failed, apparently can't edit username here, switched to eduPersonPPN
//   which comes in as uclalogin@ucla.edu
// Updated: 4-6-07 Mike Franks - switching to uclaLogonID which comes in as mfranks, need to add @ucla.edu
// Updated: 3-5-07 Mike Franks - got it working, with Keith's help. 
//          Copied from auth/shibboleth/README.txt example

//   Apparently can't affect username here. This line doesn't have any effect.
//      $result["username"] = $result[username] . '@ucla.edu';

// Changing to retrieve displayname and if it exists, use it instead of official name.
        $displayname = $this->get_first_string($_SERVER['HTTP_SHIB_DISPLAYNAME']);
        if (!empty($displayname)) {
            list($lastname,$firstname,$suffix) = split(',',$displayname);
            $result["firstname"] = strtoupper($firstname);
            $result["lastname"]  = strtoupper($lastname);
            $suffix = strtoupper($suffix);
            if ($suffix == "JR") {
                $result["lastname"] .= ", $suffix";  // SMITH, JR
            } elseif (!empty($suffix)) {
                $result["lastname"] .= $suffix;      // SMITH II or SMITH III
            }    
        } else {     
            $middlename  = $this->get_first_string($_SERVER['HTTP_UCLA_PERSON_MIDDLENAME']);
            if (!empty($middlename)) {
                $result["firstname"] = "{$result['firstname']} $middlename";
            }
            $suffix = $this->get_first_string($_SERVER['HTTP_SHIB_UCLAPERSONNAMESUFFIX']);
            $suffix = strtoupper($suffix);
            if ($suffix == "JR") {
                $result["lastname"] .= ", $suffix";  // SMITH, JR
            } elseif (!empty($suffix)) {
                $result["lastname"] .= $suffix;      // SMITH II or SMITH III
            }    
        }    
        $result["institution"] = str_replace("urn:mace:incommon:","",$result["institution"]);
?>
