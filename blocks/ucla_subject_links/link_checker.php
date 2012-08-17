<?php
function get_links($page) {
    if($fp = fopen($page, 'r')) {
        $content = "";
        while ($line = fread($fp, 1025)) {
            $content .= $line;
        }
    }
    $links = array();
    $len = strlen($content); 
    if ($len > 10) {
        $start = 0;
        $valid = true;
        while ($valid) {
            set_time_limit(0);
            $spos = strpos($content, '<a', $start);
            if ($spos < $start) {
                $valid = false;
                continue;
            }
            $spos = strpos($content, 'href', $spos);
            $spos = strpos($content, '"', $spos);
            $epos = strpos($content, '"', $spos);
            $star = $epos;
            $link = substr($content, $spos, $epos-$spos);
            if (strpos($link, 'http://') !== false) {
                $links[] = $link;
            }
        }
    }
    return $links;
}

function check_link($link) {
    $file = @fopen($link, 'r');
    $status = false;
    if($file) {
        $status = true;
    }
    fclose($file);
    
    return $status;
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html>
<body> 
<?php
    require_once(dirname(__FILE__).'/../../config.php');
    global $CFG;
    require_once($CFG->libdir.'/blocklib.php');
    require_once($CFG->dirroot.'/blocks/moodleblock.class.php');
    require_once($CFG->dirroot . '/blocks/ucla_subject_links/block_ucla_subject_links.php');
    echo 'hi';
    $dirs = scandir(block_ucla_subject_links::get_location());
    unset($dirs[0]);
    unset($dirs[1]);
    foreach ($dirs as $dir) {
        echo $dir;
        $links = get_links(block_ucla_subject_links::get_location() . $dir . '/index.htm');
        foreach ($links as $link) {
            if (check_link($link) == false) {
                echo $dir . '/index.htm' . ': "' . $link . '"';
            }
        }
    }
?> 
</body>
</html>
