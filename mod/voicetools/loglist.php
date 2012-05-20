<div  style="width:700px">
<p style="width:700px">
    <span style="font-size:14pt"><?php echo get_string('serverlogs' , 'voicetools');?></span> 
    <a href="<?php echo $_SERVER['HTTP_REFERER'];?>" style="display:block;text-align:right">
        <span><?php echo get_string('logback' , 'voicetools');?></span>
    </a>
</p>
</div>

<div style="width:700px;border:2px solid grey;background-color:#e7f7f7;padding-left:40px;padding-bottom:20px;padding-top:20px;font-size:11pt;">
<?php
        
        if ($logsfolder = @opendir(WIMBA_DIR.'/voiceboard/logs'))
        {
            //If the separato id \, set this to the whole path (for Windows)
            $logs_dir = WIMBA_DIR;
            if (DIRECTORY_SEPARATOR == '\\'){
                $logs_dir = str_replace("/",DIRECTORY_SEPARATOR,WIMBA_DIR);
            }
            
            echo "<p><b>".get_string('voiceboard_logs' , 'voicetools')."</b>(".$logs_dir."/voicetools)"."</p>";
            while ($logname = readdir($logsfolder)) 
            {
                if (!is_dir($logname)) {
                    //For each logs in the folder, creates a corresponding link to download them
                    echo "<a href=".$CFG->wwwroot."/mod/voicetools/logs.php?action=download&log=voiceboard/logs/".$logname.">".$logname." - (".filesize(WIMBA_DIR."/voiceboard/logs/".$logname)." b)</a><br>";
                }
            }       
            closedir($logsfolder);
        }
        else
        {
            echo get_string('no_logs' , 'voicetools');
        }
        
        if ($logsfolder = @opendir(WIMBA_DIR.'/voicepresentation/logs'))
        {
            //If the separato id \, set this to the whole path (for Windows)
            $logs_dir = WIMBA_DIR;
            if (DIRECTORY_SEPARATOR == '\\'){
                $logs_dir = str_replace("/",DIRECTORY_SEPARATOR,WIMBA_DIR);
            }
            
            echo "<p><b>".get_string('voicepresentation_logs' , 'voicetools')."</b>(".$logs_dir.")"."</p>";
            while ($logname = readdir($logsfolder)) 
            {
                if (!is_dir($logname)) {
                    //For each logs in the folder, creates a corresponding link to download them
                    echo "<a href=".$CFG->wwwroot."/mod/voicetools/logs.php?action=download&log=voiceboard/logs".$logname.">".$logname." - (".filesize(WIMBA_DIR."/voicepresentation/logs/".$logname)." b)</a><br>";
                }
            }       
            closedir($logsfolder);
        }
        else
        {
            echo get_string('no_logs' , 'voicetools');
        }
        
        if ($logsfolder = @opendir(WIMBA_DIR.'/voicepodcaster/logs'))
        {
            //If the separato id \, set this to the whole path (for Windows)
            $logs_dir = WIMBA_DIR;
            if (DIRECTORY_SEPARATOR == '\\'){
                $logs_dir = str_replace("/",DIRECTORY_SEPARATOR,WIMBA_DIR);
            }
            
            echo "<p><b>".get_string('voicepodcaster_logs' , 'voicetools')."</b>(".$logs_dir.")"."</p>";
            while ($logname = readdir($logsfolder)) 
            {
                if (!is_dir($logname)) {
                    //For each logs in the folder, creates a corresponding link to download them
                    echo "<a href=".$CFG->wwwroot."/mod/voicetools/logs.php?action=download&log=voicepodcaster/logs".$logname.">".$logname." - (".filesize(WIMBA_DIR."/voicepodcaster/logs/".$logname)." b)</a><br>";
                }
            }       
            closedir($logsfolder);
        }
        else
        {
            echo get_string('no_logs' , 'voicetools');
        }       

        if ($logsfolder = @opendir(WIMBA_DIR.'/voiceemail/logs'))
        {
            //If the separato id \, set this to the whole path (for Windows)
            $logs_dir = WIMBA_DIR;
            if (DIRECTORY_SEPARATOR == '\\'){
                $logs_dir = str_replace("/",DIRECTORY_SEPARATOR,WIMBA_DIR);
            }
            
            echo "<p><b>".get_string('voiceemail_logs' , 'voicetools')."</b>(".$logs_dir.")"."</p>";
            while ($logname = readdir($logsfolder)) 
            {
                if (!is_dir($logname)) {
                    //For each logs in the folder, creates a corresponding link to download them
                    echo "<a href=".$CFG->wwwroot."/mod/voicetools/logs.php?action=download&log=voiceemail/logs".$logname.">".$logname." - (".filesize(WIMBA_DIR."/voiceemail/logs/".$logname)." b)</a><br>";
                }
            }       
            closedir($logsfolder);
        }
        else
        {
            echo get_string('no_logs' , 'voicetools');
        }       
        
        if ($logsfolder = @opendir(WIMBA_DIR.'/voiceauthoring/logs'))
        {
            //If the separato id \, set this to the whole path (for Windows)
            $logs_dir = WIMBA_DIR;
            if (DIRECTORY_SEPARATOR == '\\'){
                $logs_dir = str_replace("/",DIRECTORY_SEPARATOR,WIMBA_DIR);
            }
            
            echo "<p><b>".get_string('voiceauthoring_logs' , 'voicetools')."</b>(".$logs_dir.")"."</p>";
            while ($logname = readdir($logsfolder)) 
            {
                if (!is_dir($logname)) {
                    //For each logs in the folder, creates a corresponding link to download them
                    echo "<a href=".$CFG->wwwroot."/mod/voicetools/logs.php?action=download&log=voiceauthoring/logs".$logname.">".$logname." - (".filesize(WIMBA_DIR."/voiceauthoring/logs/".$logname)." b)</a><br>";
                }
            }       
            closedir($logsfolder);
        }
        else
        {
            echo get_string('no_logs' , 'voicetools');
        }  
        
        if ($logsfolder = @opendir(WIMBA_DIR.'/general/logs')){
        
            //If the separato id \, set this to the whole path (for Windows)
            $logs_dir = WIMBA_DIR;
            if (DIRECTORY_SEPARATOR == '\\'){
                $logs_dir = str_replace("/",DIRECTORY_SEPARATOR,WIMBA_DIR);
            }
        
            echo "<p><b>".get_string('general_logs' , 'voicetools')."</b>(".$logs_dir.")"."</p>";

            while ($logname = readdir($logsfolder)) 
            {
                if (!is_dir($logname)) 
                {
                    //For each logs in the folder, creates a corresponding link to download them
                    echo "<a href=".$CFG->wwwroot."/mod/voicetools/logs.php?action=download&log=general/logs".$logname.">".$logname." - (".filesize(WIMBA_DIR."/general/logs/".$logname)." b)</a><br>";
                }
            }       
            closedir($logsfolder);
        }
        else
        {
            echo get_string('no_logs' , 'voicetools');
        }

    ?>
</div>
