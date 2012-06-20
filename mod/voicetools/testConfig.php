<?php
    global $CFG;
    require_once("../../config.php");
    require_once('lib/php/common/WimbaLib.php');
    require_once('lib/php/vt/WimbaVoicetoolsAPI.php');
    require_once('lib.php');

    $user=optional_param("user",'',PARAM_RAW);
    $pass=optional_param("pass",'',PARAM_RAW);
    $server=optional_param("server",'',PARAM_TEXT);

    $result = voicetools_api_check_documentbase ($server, $user, $pass,$CFG->wwwroot);
    if ($result != "ok") 
    {   
        $result = str_replace(' ', '_', $result);
        $compare = false;
        try {
            if (get_string($result, 'voicetools') == "[[".$result."]]")
                $compare = true;
        } catch (Exception $e) {
            $compare = true;
        }
        if($compare)
        {//the error description is not in the bundle
            wimba_add_log(WIMBA_ERROR,"wimbaConfiguration",$result);
            echo  get_string("generic_error", 'voicetools');
        }  
        else
        {
            wimba_add_log(WIMBA_ERROR,"wimbaConfiguration",get_String($result, 'voicetools'));
            echo get_string($result, 'voicetools');
        } 
    }
    else
    {
        
        $php_extension = get_loaded_extensions();
        for( $i = 0; $i< count($php_extension); $i++)
        {
            if($php_extension[$i] == "libxml" || $php_extension[$i] == "domxml")
            {
                  wimba_add_log(WIMBA_INFO,'wimbaConfiguration',"The module is well configured");
                  echo $result;
                  exit();
            }
        }
        wimba_add_log(WIMBA_INFO,'wimbaConfiguration',"domxml is not installed");
        echo get_string("domxml", 'voicetools');
        
    }
    //add important informations to the log
    wimba_add_log(WIMBA_INFO,'wimbaConfiguration',"php info :\n" .print_r(get_loaded_extensions(),true)); 
    
?>
