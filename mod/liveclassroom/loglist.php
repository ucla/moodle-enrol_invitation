<div  style="width:700px">
<p style="width:700px">
    <span style="font-size:14pt"><?php echo get_string('serverlogs' , 'liveclassroom');?></span> 
    <a href="<?php echo $_SERVER['HTTP_REFERER'];?>" style="display:block;text-align:right">
        <span><?php echo get_string('logback' , 'liveclassroom');?></span>
    </a>
</p>
</div>

<div style="width:700px;border:2px solid grey;background-color:#e7f7f7;padding-left:40px;padding-bottom:20px;padding-top:20px;font-size:11pt;">
<?php
        
        if ($logsfolder = @opendir(WIMBA_DIR.'/liveclassroom/logs'))
        {
            //If the separato id \, set this to the whole path (for Windows)
            $logs_dir = WIMBA_DIR;
            if (DIRECTORY_SEPARATOR == '\\'){
                $logs_dir = str_replace("/",DIRECTORY_SEPARATOR,WIMBA_DIR);
            }
            
            echo "<p><b>".get_string('wc_logs' , 'liveclassroom')."</b>(".$logs_dir."/liveclassroom)"."</p>";
            while ($logname = readdir($logsfolder)) 
            {
                if (!is_dir($logname)) {
                    //For each logs in the folder, creates a corresponding link to download them
                    echo "<a href=".$CFG->wwwroot."/mod/liveclassroom/logs.php?action=download&log=liveclassroom/logs/".$logname.">".$logname." - (".filesize(WIMBA_DIR."/liveclassroom/logs/".$logname)." b)</a><br>";
                }
            }       
            closedir($logsfolder);
        }
        else
        {
            echo get_string('no_logs' , 'liveclassroom');
        }
        
        if ($logsfolder = @opendir(WIMBA_DIR.'/general/logs')){
        
            //If the separato id \, set this to the whole path (for Windows)
            $logs_dir = WIMBA_DIR;
            if (DIRECTORY_SEPARATOR == '\\'){
                $logs_dir = str_replace("/",DIRECTORY_SEPARATOR,WIMBA_DIR);
            }
        
            echo "<p><b>".get_string('general_logs' , 'liveclassroom')."</b>(".$logs_dir.")"."</p>";

            while ($logname = readdir($logsfolder)) 
            {
                if (!is_dir($logname)) 
                {
                    //For each logs in the folder, creates a corresponding link to download them
                    echo "<a href=".$CFG->wwwroot."/mod/liveclassroom/logs.php?action=download&log=general/logs/".$logname.">".$logname." - (".filesize(WIMBA_DIR."/general/logs/".$logname)." b)</a><br>";
                }
            }       
            closedir($logsfolder);
        }
        else
        {
            echo get_string('no_logs' , 'liveclassrom');
        }

    ?>
</div>
