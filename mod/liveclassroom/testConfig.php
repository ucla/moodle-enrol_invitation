<?php
    require_once("../../config.php");

    $user=optional_param("user",'',PARAM_RAW);
    $pass=optional_param("pass",'',PARAM_RAW);
    $server=optional_param("server",'',PARAM_TEXT);

    require_once("lib.php");
    require_once("lib/php/lc/LCAction.php");
    require_once("lib/php/lc/lcapi.php");

    $prefixUtil = new PrefixUtil();
    $prefix = $prefixUtil->getPrefix($user);
    $api = LCApi::getInstance($server, $user, $pass,$prefix, $PAGE->course->id, $CFG->dataroot);
    $api->lcapi_invalidate_auth();
    $auth = $api->lcapi_authenticate();

    if ($api->lcapi_get_error() != '' || $auth === false)
    {
        echo get_string('wrongconfigurationURLincorrect', 'liveclassroom');
    }
    else
    {
        $php_extension = get_loaded_extensions();
        for( $i = 0; $i< count($php_extension); $i++)
        {
            if($php_extension[$i] == "libxml" || $php_extension[$i] == "domxml")
            {
                  wimba_add_log(WIMBA_INFO,'wimbaConfiguration',"The module is well configured");
                  echo get_string('wellconfigured', 'liveclassroom');
                  exit();
            }
        }
        wimba_add_log(WIMBA_INFO,'wimbaConfiguration',"domxml is not installed");
        echo get_string("domxml", 'liveclassroom');
    }
    //add important informations to the log
    wimba_add_log(WIMBA_INFO,'wimbaConfiguration',"php info :\n" .print_r(get_loaded_extensions(),true)); 
?>
