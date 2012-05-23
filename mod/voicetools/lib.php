<?php

define("voicetools_LOGS", "voicetools");
define("VOICETOOLS_MODULE_VERSION", "@VERSION@");

/*
 * will be called during the installation of the module
 */
function voicetools_install(){  
  return true;
}

/*
 * will be called when we add a new instance of widget
 */
function voicetools_add_instance(){
  return true;  
}

/*
 * will be called when we update a new instance of widget
 */
function voicetools_update_instance(){
  return true;
}

/*
 * will be called when we delete a new instance of widget
 */
function voicetools_delete_instance(){
  return true;
}

/*
 * return a summary of a user's contribution
 */
function voicetools_user_outline() {
  return true;
}

/*
* print details of a user's contribution
*/
function voicetools_user_complete(){
  return true;
}

function voicetools_get_view_actions() {
  return true;
  
}

?>
