<?php
/******************************************************************************
 *                                                                            *
 * Copyright (c) 1999-2008  Wimba, All Rights Reserved.                       *
 *                                                                            *
 * COPYRIGHT:                                                                 *
 *      This software is the property of Wimba.                               *
 *      You can redistribute it and/or modify it under the terms of           *
 *      the GNU General Public License as published by the                    *
 *      Free Software Foundation.                                             *
 *                                                                            *
 * WARRANTIES:                                                                *
 *      This software is distributed in the hope that it will be useful,      *
 *      but WITHOUT ANY WARRANTY; without even the implied warranty of        *
 *      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         *
 *      GNU General Public License for more details.                          *
 *                                                                            *
 *      You should have received a copy of the GNU General Public License     *
 *      along with the Wimba Moodle Integration;                              *
 *      if not, write to the Free Software Foundation, Inc.,                  *
 *      51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA                *
 *                                                                            *
 * Author: Hugues Pisapia                                                     *
 *                                                                            *
 * Date: April 2006                                                           *
 *                                                                            *
 ******************************************************************************/

/* $Id: WimbaVoicetoolsAPI.php 76193 2009-09-11 15:48:18Z trollinger $ */
error_reporting(E_ALL);
global $CFG;

require_once("WimbaVoicetools.php");

define("VTAPI_DEBUG", false); //for the log
// / Library of functions and constants for the voicetool API
// Resources
define("VT_API_VBOARD", 'board');
define("VT_API_VMAIL", 'vmail');
define("VT_API_VDIRECT", 'voicedirect');
define("VT_API_VRECORDER", 'recorder');
define("VT_API_VPRESENTATION", 'presentation');
define("VT_API_PODCASTER", 'pc');
// Qudio Qualities
define("VT_API_QUALITY_BASIC", 'spx_8_q3');
define("VT_API_QUALITY_STANDARD", 'spx_16_q4');
define("VT_API_QUALITY_GOOD", 'spx_16_q6');
define("VT_API_QUALITY_SUPERIOR", 'spx_32_q8'); //ou q10 ??

// API calls
define("VT_API_SERVICES", '/services/Broker?wsdl');
define("VT_API_CREATE_RESOURCE", 'createResource');
define("VT_API_MODIFY_RESOURCE", 'modifyResource');
define("VT_API_DELETE_RESOURCE", 'deleteResource');
define("VT_API_GET_RESOURCE", 'getResource');
define("VT_API_GET_RESOURCES", 'getResources');
define("VT_API_RESOURCE_EXISTS", 'resourceExists');
define("VT_API_CREATE_SESSION", 'createSession');
define("VT_API_ALLOWEDDOCBASE", 'isDocumentBaseAllowed');
define("VT_API_MESSAGE_EXISTS", 'messageExists');
define("VT_API_STORE_AUDIO", 'storeAudio');
define("VT_API_GET_AUDIO", 'getAudio');
define("VT_API_AUDIO_EXISTS", 'audioExists');
define("VT_API_GET_VERSION", 'getVersion');
define("VT_API_GET_AVERAGE_MESSAGE_LENGTH_PER_USER", 'getAverageMessageLengthPerUser');
define("VT_API_NB_MESSAGE_PER_USER", 'getNbMessagePerUser');
// Voice Tools Module Tables
define("VT_API_ACTIVITY", 'voicetools_activity');
define("VT_API_INSTANCE", 'voicetools_instance');
define("VT_API_COPY_RESOURCE", 'copyResource');

/**
 * Send an SDK request to the VT server to create the resource.
 * 
 * @uses CFG
 * @uses VT_API_SERVICES
 * @uses VT_API_CREATE_RESOURCE
 * @param  $resource_data - the resource to create
 * @return - the object returned by the call, or false if something goes wrong
 */
function voicetools_api_create_resource ($resource_data)
{
    global $CFG; 
    $options = array ('login' => $CFG->voicetools_adminusername, 'password' => $CFG->voicetools_adminpassword);
    try {
        $soapclient = new SoapClient($CFG->voicetools_servername . VT_API_SERVICES, $options);
    } catch (SoapFault $e) {
        wimba_add_log (WIMBA_ERROR, voiceemail_LOGS, __FUNCTION__ . ' : Error to create the soap element : ' . $e->faultstring);
        return false;
    }
    // Call the WebService and store its result in $result.
    try {
        $resp = $soapclient->{VT_API_CREATE_RESOURCE}($CFG->voicetools_adminusername, $CFG->voicetools_adminpassword, $resource_data);
        $result = object_to_array($resp);
    } catch (Exception $e) {
        wimba_add_log (WIMBA_ERROR, voiceemail_LOGS, __FUNCTION__ . ' : Fault with the web service : ' . $e->faultstring);
        return false;
    }
    $resource = new vtResource($result);

    if ($resource->error == "error") 
    {
        wimba_add_log (WIMBA_ERROR, voiceemail_LOGS, __FUNCTION__ . " : Resource not created because " . $resource->error_message);
        return false;
    } 
    
    wimba_add_log (WIMBA_DEBUG, voiceemail_LOGS, __FUNCTION__ . " : Resource Created");
    return $resource;
} 

/**
 * Send an SDK request to the VT server to create the resource.
 * 
 * @uses CFG
 * @uses VT_API_SERVICES
 * @uses VT_API_MODIFY_RESOURCE
 * @param  $resource_data - the resource to create
 * @return - the object returned by the call, or false if something goes wrong
 */
function voicetools_api_modify_resource ($resource_data)
{
    global $CFG;

    $options = array ('login' => $CFG->voicetools_adminusername, 'password' => $CFG->voicetools_adminpassword);
    try {
        $soapclient = new SoapClient($CFG->voicetools_servername . VT_API_SERVICES, $options);
    } catch (SoapFault $e) {
        wimba_add_log (WIMBA_ERROR, voiceemail_LOGS, __FUNCTION__ . ' : Error to create the soap element : ' . $e->faultstring);
        return false;
    } 

    try {
        $resp = $soapclient->{VT_API_MODIFY_RESOURCE}($CFG->voicetools_adminusername, $CFG->voicetools_adminpassword, $resource_data);
        $result = object_to_array($resp);
    } catch (SoapFault $e) {
        wimba_add_log (WIMBA_ERROR, voiceemail_LOGS, __FUNCTION__ . ' : Fault with the web service : ' . $e->faultstring);
        return false;
    } 

    $resource = new vtResource($result);

    if ($resource->error == "error") 
    {
        wimba_add_log (WIMBA_ERROR, voiceemail_LOGS, __FUNCTION__ . " : Resource not modified because " . $resource->error_message);
        return false;
    } 
    
    wimba_add_log (WIMBA_DEBUG, voiceemail_LOGS, __FUNCTION__ . " : Resource Modified");
    return $resource;
} 

// To CHECK !!!
function voicetools_api_message_exists ($rid, $mid)
{
    global $CFG;

    $options = array ('login' => $CFG->voicetools_adminusername, 'password' => $CFG->voicetools_adminpassword);
    try {
        $soapclient = new SoapClient($CFG->voicetools_servername . VT_API_SERVICES, $options);
    } catch (SoapFault $e) {
        wimba_add_log (WIMBA_ERROR, voiceemail_LOGS, __FUNCTION__ . ' : Error to create the soap element : ' . $e->faultstring);
        return false;
    } 

    try {
        $resp = $soapclient->{VT_API_MESSAGE_EXISTS}($CFG->voicetools_adminusername, $CFG->voicetools_adminpassword, $rid, $mid);
        $result = object_to_array($resp);
    } catch (SoapFault $e) {
        wimba_add_log (WIMBA_ERROR, voiceemail_LOGS, __FUNCTION__ . ' : Fault with the web service : ' . $e->faultstring);
        return false;
    } 

    $res = null;
    foreach ($result['values'] as $item) 
    {
        if ($item['name'] == 'exists') 
        {
            $res = $item['value'];
        } 
    } 
    return $res; 
} 

/**
 * Send an SDK request to the VT server to delete a resource.
 * 
 * @uses CFG
 * @uses VT_API_SERVICES
 * @uses VT_API_DELETE_RESOURCE
 * @param  $rid - the rid of the resource to delete
 * @return - the object returned by the call, or false if something goes wrong
 */
function voicetools_api_delete_resource ($rid)
{
    global $CFG;

    $options = array ('login' => $CFG->voicetools_adminusername, 'password' => $CFG->voicetools_adminpassword);
    try {
        $soapclient = new SoapClient($CFG->voicetools_servername . VT_API_SERVICES, $options);
    } catch (SoapFault $e) {
        wimba_add_log (WIMBA_ERROR, voiceemail_LOGS, __FUNCTION__ . ' : Error to create the soap element : ' . $e->faultstring);
        return false;
    }

    try {
        $resp = $soapclient->{VT_API_DELETE_RESOURCE}($CFG->voicetools_adminusername, $CFG->voicetools_adminpassword, $rid);
        $result = object_to_array($resp);
    } catch (SoapFault $e) {
        wimba_add_log (WIMBA_ERROR, voiceemail_LOGS, __FUNCTION__ . ' : Fault with the web service : ' . $e->faultstring);
        return false;
    } 

    $resource = new vtResource($result);

    if ($resource->error == "error") 
    {
        wimba_add_log (WIMBA_ERROR, voiceemail_LOGS, __FUNCTION__ . " : Resource not deleted because " . $resource->error_message);
        return false;
    } 

    wimba_add_log (WIMBA_DEBUG, voiceemail_LOGS, __FUNCTION__ . " : Resource Deleted");
    return $resource;
} 

/**
 * Send an SDK request to the VT server to test if the resource exist.
 * 
 * @uses CFG
 * @uses VT_API_SERVICES
 * @uses VT_API_RESOURCE_EXISTS
 * @param  $rid - the rid of the resource to find
 * @return - a boolean. true if the resource exist, false elsewhere.
 */
function voicetools_api_resource_exists ($rid)
{
    global $CFG;

    $options = array ('login' => $CFG->voicetools_adminusername, 'password' => $CFG->voicetools_adminpassword);
    try {
        $soapclient = new SoapClient($CFG->voicetools_servername . VT_API_SERVICES, $options);
    } catch (SoapFault $e) {
        wimba_add_log (WIMBA_ERROR, voiceemail_LOGS, __FUNCTION__ . ' : Error to create the soap element : ' . $e->faultstring);
        return false;
    }

    try {
        $resp = $soapclient->{VT_API_RESOURCE_EXISTS}($CFG->voicetools_adminusername, $CFG->voicetools_adminpassword, $rid);
        $result = object_to_array($resp);
    } catch (SoapFault $e) {
        wimba_add_log (WIMBA_ERROR, voiceemail_LOGS, __FUNCTION__ . ' : Fault with the web service : ' . $e->faultstring);
        return false;
    } 

    $res = null;
    foreach ($result['values'] as $item) 
    {
        if ($item['name'] == 'exists') 
        {
            $res = $item['value'];
        } 
    } 
    return $res;
} 

/**
 * Send an SDK request to the VT server to get the resource.
 * 
 * @uses CFG
 * @uses VT_API_SERVICES
 * @uses VT_API_GET_RESOURCE
 * @param  $rid - the rid of the resource to get
 * @return - the object returned by the call, or false if something goes wrong.
 */
function voicetools_api_get_resource($rid)
{
    global $CFG;

    $options = array ('login' => $CFG->voicetools_adminusername, 'password' => $CFG->voicetools_adminpassword);
    try {
        $soapclient = new SoapClient($CFG->voicetools_servername . VT_API_SERVICES, $options);
    } catch (SoapFault $e) {
        wimba_add_log (WIMBA_ERROR, voiceemail_LOGS, __FUNCTION__ . ' : Error to create the soap element : ' . $e->faultstring);
        return false;
    }

    try {
        $resp = $soapclient->{VT_API_GET_RESOURCE}($CFG->voicetools_adminusername, $CFG->voicetools_adminpassword, $rid);
        $result = object_to_array($resp);
    } catch (SoapFault $e) {
        wimba_add_log (WIMBA_ERROR, voiceemail_LOGS, __FUNCTION__ . ' : Fault with the web service : ' . $e->faultstring);
        return false;
    } 

    $resource = new vtResource($result);
    if ($resource->error == "error") 
    {
        wimba_add_log (WIMBA_ERROR, voiceemail_LOGS, __FUNCTION__ . " : Impossible to get the resource (rid=" . $rid . ") because " . $resource->error_message);
        return false;
    } 
    
    wimba_add_log (WIMBA_DEBUG, voiceemail_LOGS, __FUNCTION__ . " : No problem to get the resource " . $rid);
    return $resource;
} 

/**
 * Send an SDK request to the VT server to get the resources.
 * 
 * @uses CFG
 * @uses VT_API_SERVICES
 * @uses VT_API_GET_RESOURCES
 * @param  $ridtable - a table with the rid of the resources to get
 * @return - an array of the objects returned by the call, or false if something goes wrong.
 */
function voicetools_api_get_resources($ridtable)
{
    global $CFG;

    $options = array ('login' => $CFG->voicetools_adminusername, 'password' => $CFG->voicetools_adminpassword);
    try {
        $soapclient = new SoapClient($CFG->voicetools_servername . VT_API_SERVICES, $options);
    } catch (SoapFault $e) {
        wimba_add_log (WIMBA_ERROR, voiceemail_LOGS, __FUNCTION__ . ' : Error to create the soap element : ' . $e->faultstring);
        return false;
    }

    try {
        $resp = $soapclient->{VT_API_GET_RESOURCES}($CFG->voicetools_adminusername, $CFG->voicetools_adminpassword, $ridtable);
        $result = object_to_array($resp);
    } catch (SoapFault $e) {
        wimba_add_log (WIMBA_ERROR, voiceemail_LOGS, __FUNCTION__ . ' : Fault with the web service : ' . $e->faultstring);
        return false;
    } 

    $resources = new vtResources($result);

    if ($resources->error == "error") 
    {
        wimba_add_log (WIMBA_ERROR, voiceemail_LOGS, __FUNCTION__ . " : Impossible to get the resources " . "because " . $resources->error_message);
        return false;
    } 
    
    wimba_add_log (WIMBA_DEBUG, voiceemail_LOGS, __FUNCTION__ . " : No problem to get the resources " . print_r($ridtable,true));
    return $resources;
} 

/**
 * Create the session for a voice direct applet.
 * 
 * @uses CFG
 * @uses VT_API_SERVICES
 * @uses VT_API_CREATE_SESSION
 * @param  $ - the voicetool information
 * @return - an sessionInfo object
 */
function voicetools_api_create_session ($user, $ressource, $rights, $message = null)
{
    global $CFG; 
    // $sessiondata = voicetools_create_session_data($voicetool);
    $options = array ('login' => $CFG->voicetools_adminusername, 'password' => $CFG->voicetools_adminpassword);
    try {
        $soapclient = new SoapClient($CFG->voicetools_servername . VT_API_SERVICES, $options);
    } catch (SoapFault $e) {
        wimba_add_log (WIMBA_ERROR, voiceemail_LOGS, __FUNCTION__ . ' : Error to create the soap element : ' . $e->faultstring);
        return false;
    }

    try {
        $resp = $soapclient->{VT_API_CREATE_SESSION}($CFG->voicetools_adminusername, $CFG->voicetools_adminpassword, $user, $ressource, $message, $rights);
        $result = object_to_array($resp);
    } catch (SoapFault $e) {
        wimba_add_log (WIMBA_ERROR, voiceemail_LOGS, __FUNCTION__ . ' : Fault with the web service : ' . $e->faultstring);
        return false;
    } 
    $session = new vtSessionInfo($result);
    if ($session->error == "error") 
    {
        wimba_add_log (WIMBA_ERROR, voiceemail_LOGS, __FUNCTION__ . " : Impossible to create the session because " . $resources->error_message);
        return false;
    } 

    wimba_add_log (WIMBA_DEBUG, voiceemail_LOGS, __FUNCTION__ . " : Session created ");
    return $session;
} 

/**
 * Checks that the documentabase is allowed on the VT server
 * 
 * @param string $server the url of the VT server
 * @param string $login the API login to use
 * @param string $password the API password to use
 * @param string $ the url that need to be checked
 * @return string the string 'ok' if the url given is authorized to display voice tools,
 * or an error message otherwise
 */
function voicetools_api_check_documentbase($server, $login, $password, $url)
{
    $options = array ('login' => $login, 'password' => $password);
    try {
        $soapclient = new SoapClient($server.VT_API_SERVICES, $options);
    } catch (SoapFault $e) {
        wimba_add_log (WIMBA_ERROR, voiceemail_LOGS, __FUNCTION__ . ' : Error to create the soap element');
        return $e->faultstring;
    }

    try {
        $resp = $soapclient->{VT_API_ALLOWEDDOCBASE}($login, $password, $url);
        $result = object_to_array($resp);
    } catch (SoapFault $e) {
        wimba_add_log (WIMBA_ERROR, voiceemail_LOGS, __FUNCTION__ . ' : Fault with the web service : ' . $e->faultstring);
        return $e->faultstring;
    }

    if (voicetools_api_get_status_code($result) != "ok") 
    {
        return voicetools_api_get_error_message($result);
    } 
    return "ok";
} 

function voicetools_api_audio_exists ($rid, $mid)
{
    global $CFG;
    $options = array ('login' => $CFG->voicetools_adminusername, 'password' => $CFG->voicetools_adminpassword);
    try {
        $soapclient = new SoapClient($CFG->voicetools_servername.VT_API_SERVICES, $options);
    } catch (SoapFault $e) {
        wimba_add_log (WIMBA_ERROR, voiceemail_LOGS, __FUNCTION__ . ' : Error to create the soap element');
        return $e->faultstring;
    }

    try {
        $resp = $soapclient->{VT_API_AUDIO_EXISTS}($CFG->voicetools_adminusername, $CFG->voicetools_adminpassword, $rid, $mid);
        $result = object_to_array($resp);
    } catch (SoapFault $e) {
        wimba_add_log (WIMBA_ERROR, voiceemail_LOGS, __FUNCTION__ . ' : Fault with the web service : ' . $e->faultstring);
        return $e->faultstring;
    }

    $res = null;
    foreach ($result['values'] as $item) 
    {
        if ($item['name'] == 'exists') 
        {
            $res = $item['value'];
        } 
    } 
    return $res; 
} 

function voicetools_api_store_audio($rid, $mid, $audio, $filename)
{
    global $CFG;

    $options = array ('login' => $CFG->voicetools_adminusername, 'password' => $CFG->voicetools_adminpassword);
    try {
        $soapclient = new SoapClient($CFG->voicetools_servername.VT_API_SERVICES, $options);
    } catch (SoapFault $e) {
        return false;
    }

    try {
        $resp = $soapclient->{VT_API_STORE_AUDIO}($CFG->voicetools_adminusername, $CFG->voicetools_adminpassword, $rid, $mid, $audio, $filename);
        $result = object_to_array($resp);
    } catch (SoapFault $e) {
        return false;
    }
} 

// //////// Helper Function, kind of private
/**
 * Returns the version of the Voice Tools server contacted.
 * 
 * @return a string that contains:
 *    - "unknown" if the server parameters are not set
 *    - "error" if the server was not contacted successfully
 *    - the version string returned by the server on success
 */
function voicetools_api_get_version ()
{
    global $CFG;
    if (!isset($CFG->voicetools_adminusername) ||
        !isset($CFG->voicetools_adminpassword) ||
        !isset($CFG->voicetools_servername) ||
        $CFG->voicetools_servername == '')
        return false;
    $resource = array ('login' => $CFG->voicetools_adminusername, 'password' => $CFG->voicetools_adminpassword);
    try {
        $soapclient = new SoapClient($CFG->voicetools_servername . VT_API_SERVICES, $resource);
    } catch (SoapFault $e) {
        wimba_add_log (WIMBA_ERROR, voiceemail_LOGS, __FUNCTION__ . ' : Error to create the soap element');
        return false;
    }

    try {
        $resp = $soapclient->{VT_API_GET_VERSION}();
        $result = object_to_array($resp);
    } catch (Exception $e) {
        wimba_add_log (WIMBA_ERROR, voiceemail_LOGS, __FUNCTION__ . ' : Fault with the web service : ' . $e->faultstring);
        return false;
    }

    if (!voicetools_api_check_result($result)) {
        return false;
    } 

    foreach ($result['values'] as $values) {
        if ($values['name'] == 'default') {
            return $values['value'];
        } 
    } 

    return false;
} 

/**
 * Return true if the result does not contain an error code, or false wise.
 * 
 * @param  $result - a pairset resulting from an API call to $soapclient->call()
 * @return true f $result does not contain an error code, false otherwise.
 */
function voicetools_api_check_result ($result)
{
    if (empty($result)) {
        // error ("Empty result after call to ".VT_API_SERVICES);
        wimba_add_log(WIMBA_ERROR,voiceemail_LOGS,"voicetool_api_check_result: Empty result after call to " . VT_API_SERVICES);
        return false;
    } 

    if (voicetools_api_get_status_code($result) != 'ok') {
        wimba_add_log(WIMBA_ERROR,voiceemail_LOGS,"voicetool_api_check_result: " . voicetools_api_get_error_message($result));
        return false;
    } 
    return true;
} 

/**
 * Return the status code as a string
 * 
 * @param array $result - the result from a soap call
 * @return string - the status code returned if any, or the empty string if none is found
 */
function voicetools_api_get_status_code ($result)
{
    foreach ($result['values'] as $values) {
        if ($values['name'] == 'status_code') {
            return $values['value'];
        } 
    } 
    return '';
} 

/**
 * Return the error message as a string
 * 
 * @param array $result - the result from a soap call
 * @return string - the error message returned if any, or the empty string if none is found
 */
function voicetools_api_get_error_message ($result)
{
    foreach ($result['values'] as $values) {
        if ($values['name'] == 'error_message') {
            return $values['value'];
        } 
    } 
    return '';
} 

/**
 * Send an SDK request to the VT server to create the resource.
 * 
 * @uses CFG
 * @uses VT_API_SERVICES
 * @uses VT_API_CREATE_RESOURCE
 * @param  $resource_data - the resource to create
 * @return - the object returned by the call, or false if something goes wrong
 */
function voicetools_api_copy_resource ($rid,$new_rid="",$option)
{
    global $CFG; 

    $options = array ('login' => $CFG->voicetools_adminusername, 'password' => $CFG->voicetools_adminpassword);
    try {
        $soapclient = new SoapClient($CFG->voicetools_servername.VT_API_SERVICES, $options);
    } catch (SoapFault $e) {
        wimba_add_log (WIMBA_ERROR, voiceemail_LOGS, __FUNCTION__ . ' : Error to create the soap element');
        return $e->faultstring;
    }

    $nameValuePair = Array();
    $nameValuePair[0]["name"] = "copy_type";
    $nameValuePair[0]["value"] = $option;
    $pairsetOptions = array("values"=>$nameValuePair);

    try {
        $resp = $soapclient->{VT_API_COPY_RESOURCE}($CFG->voicetools_adminusername, $CFG->voicetools_adminpassword, $rid, null, $pairsetOptions);
        $result = object_to_array($resp);
    } catch (SoapFault $e) {
        wimba_add_log (WIMBA_ERROR, voiceemail_LOGS, __FUNCTION__ . ' : Fault with the web service : ' . $e->faultstring);
        return $e->faultstring;
    }

    $resource = new vtResource($result);

    if ($resource->error == "error") 
    {
        wimba_add_log (WIMBA_ERROR, voiceemail_LOGS, __FUNCTION__ . " : Resource not created because " . $resource->error_message);
        return false;
    } 
    
    wimba_add_log (WIMBA_DEBUG, voiceemail_LOGS, __FUNCTION__ . " : Resource Created");
    return $resource;
} 

 /**
   * Gets the average number of message that have been posted by a user
   * @param rid - Resource ID
   * @return an array. Each item contains an other array where the key will be nb_message and user(array where screen name is a key)
   */
function voicetools_get_nb_messages_per_user ($rid)
{
    global $CFG;

    $options = array ('login' => $CFG->voicetools_adminusername, 'password' => $CFG->voicetools_adminpassword);
    try {
        $soapclient = new SoapClient($CFG->voicetools_servername.VT_API_SERVICES, $options);
    } catch (SoapFault $e) {
        wimba_add_log (WIMBA_ERROR, voiceemail_LOGS, __FUNCTION__ . ' : Error to create the soap element');
        return $e->faultstring;
    }

    try {
        $resp = $soapclient->{VT_API_NB_MESSAGE_PER_USER}($CFG->voicetools_adminusername, $CFG->voicetools_adminpassword, $rid);
        $result = object_to_array($resp);
    } catch (SoapFault $e) {
        wimba_add_log (WIMBA_ERROR, voiceemail_LOGS, __FUNCTION__ . ' : Fault with the web service : ' . $e->faultstring);
        return $e->faultstring;
    }

    return pairsetToArray($result); 
} 

 /**
   * This method returns average message length for all users who are all posted messages for given resource id.
   * @param rid - Resource ID
   * @return an array. Each item contains an other array where the key will be message_len and screen_name
   */
function voicetools_get_average_length_messages_per_user ($rid)
{
    global $CFG;

    $options = array ('login' => $CFG->voicetools_adminusername, 'password' => $CFG->voicetools_adminpassword);
    try {
        $soapclient = new SoapClient($CFG->voicetools_servername.VT_API_SERVICES, $options);
    } catch (SoapFault $e) {
        wimba_add_log (WIMBA_ERROR, voiceemail_LOGS, __FUNCTION__ . ' : Error to create the soap element');
        return $e->faultstring;
    }

    try {
        $resp = $soapclient->{VT_API_GET_AVERAGE_MESSAGE_LENGTH_PER_USER}($CFG->voicetools_adminusername, $CFG->voicetools_adminpassword, $rid);
        $result = object_to_array($resp);
    } catch (SoapFault $e) {
        wimba_add_log (WIMBA_ERROR, voiceemail_LOGS, __FUNCTION__ . ' : Fault with the web service : ' . $e->faultstring);
        return $e->faultstring;
    }

    return pairsetToArray($result); 
} 

 /**
   * This method convert a pairset to an array. Each item will contain an array where the keys will change according to the function called
   * @param pairset - Object to convert
   * @return an array.
   */
function pairsetToArray($pairset){
	
        $hparams = array();
        $groups = $pairset["groups"];
        $values = $pairset["values"];
        
        if($groups != null) {
            for ($i = 0; $i < count($groups); $i++) 
            {
                if ($groups[$i] != null && $groups[$i]["pairSet"] != null && $groups[$i]["name"] != null) {
                  $hparams[$groups[$i]["name"]] = pairsetToArray($groups[$i]["pairSet"]);
                }
            }
        }
        if ($values != null) {
            for ($i = 0; $i < count($values); $i++) {
              if ($values[$i] != null && $values[$i]["value"] != null && $values[$i]["name"] != null) {
                $hparams[$values[$i]["name"]] = $values[$i]["value"];
              }
            }
        }
        return $hparams;
}

 /**
  * This method converts an object returned by SoapClient into an array, converting all sub-objects as well
  * @param object - object to convert
  * @return an array
  */
function object_to_array($object)
{
    if(!is_object($object) && !is_array($object))
    {
        return $object;
    }
    if(is_object($object))
    {
        $object = get_object_vars($object);
    }
    return array_map('object_to_array', $object);
}
?>
