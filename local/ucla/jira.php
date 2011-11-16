<?php
/**
 * JIRA functions
 *
 * @package    ucla
 * @copyright  2011 UC Regents                                       
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later 
 */

/**
 * Sends either a "GET", or "POST" request along with the specified parameters
 *
 * @param string $sQuery    UserID of the person who's friends are requested
 * @param array $data       Array containing the parameters for the "GET" or 
 *                          "POST" request where $key=>$val in the array map to 
 *                          ?var=val respectively in the "GET" or "POST" 
 *                          request.
 * @param string $post      "GET" or "POST
 *
 * @return string containing the result of the query
 */
function do_request($sQuery, $data, $post) {
    $data = http_build_query($data, '', '&');

    if (strtoupper($post) == 'POST') { //do post
        $params = array('http' => array(
                'method' => 'POST',
                'header' => 'X-Atlassian-Token: no-check',
                'content' => $data
                ));
        $ctx = stream_context_create($params);
        $sock = @fopen($sQuery, 'rb', false, $ctx);
        if (!$sock) {
            //throw new Exception("Problem with $sQuery, $php_errormsg");
            return;
        }
    } else { //do get
        $sQuery .= "?$data";
        $sock = fopen($sQuery, 'r', false);
    }
    $result = @stream_get_contents($sock);
    if ($result === false) {
        //throw new Exception("Problem with $sQuery, $php_errormsg");
        return;
    }
    return $result;
}

//// START UCLA MOD
///**
// *
// * @param string $sQuery - The URL to connect to
// * @param string $filename - the file to upload
// *                         - Caller must verify it exists
// * @param string $username - Optional: the Jira user
// * @param string $password - Optional: the Jira user's passowrd
// * @return string The response from the server
// *      (Jira usually sends no response on success)
// */
//function upload_file($sQuery, $filename, $username='', $password='') {
//    $curl = curl_init();
//    curl_setopt($curl, CURLOPT_URL, $sQuery);
//    curl_setopt($curl, CURLOPT_USERPWD, $username . ':' . $password);
//
//    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
//    curl_setopt($curl, CURLOPT_POST, true);
//
//    //Ignore invalid security certificate
//    //Not needed if certificate is normally valid
//    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
//    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
//
//    $filedata = array('filename.1' => "@$filename");
//    curl_setopt($curl, CURLOPT_POSTFIELDS, $filedata);
//
//    $contents = curl_exec($curl);
//    if ($contents === false) {
//        //throw new Exception("Problem reading data from $sQuery, $php_errormsg");
//        return;
//    }
//    curl_close($curl);
//    return $contents;
//}
//// END UCLA MOD

