<?php
include ('../includes/moodle.required.php');

//recording_file or session
$type = optional_param('type',null, PARAM_ALPHAEXT);

require_login();

$systemcontext = context_system::instance();
require_capability('mod/elluminate:manage',$systemcontext);

try{
   $cacheManager = $ELLUMINATE_CONTAINER['cacheManager'];
   $cacheManager->clearCacheContent($type);
   Elluminate_Audit_Log::log(Elluminate_Audit_Constants::CACHE_CLEAR_START,'/mod/elluminate/web/util/cacheclear.php');
}catch(Exception $e){
   echo "Unable to clear cache: " . $e->getMessage();
}

echo "Cache has been cleared";