<?php
require_once("../../config.php");
require_once("lib.php");
global $CFG;
$title=optional_param('title','',PARAM_CLEAN);
$mid=optional_param('mid','',PARAM_CLEAN);
$rid=optional_param('rid','',PARAM_CLEAN);
$imgPath = $CFG->wwwroot."/mod/voiceauthoring/lib/web/pictures/items/speaker-18.gif";
$iFramePath = $CFG->wwwroot."/mod/voiceauthoring/displayPlayer.php?rid=".$rid."&mid=".$mid."&title=".urlencode($title);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd" >
<html xmlns="http://www.w3.org/1999/xhtml">
<script language="javascript" src="lib/web/js/lib/prototype/prototype.js"></script>
<head>
<script>
function displayRecorder()
{
    var iframe=parent.document.getElementById('<?php echo $rid?>__<?php echo $mid?>_va' );
   iframe.src='<?php echo $iFramePath?>&id='+iframe.id+"&title=Voice Authoring";
    iframe.style.position="absolute";
    iframe.style.width="250px";
     iframe.style.height="150px";
    iframe.style.display='block';
    return false;   
}

</script>
<style>
*
{
    margin:0px;
    padding:0px;
}
body 
{
    background-color: transparent;
}   

</style>
</head>


<body  style="padding-top:4px;" id="bodyPlayer">
   <div style="padding-left:2px">     
    
    </div>

<script>
    parent.document.getElementById('<?php echo $rid."__".$mid."_iframe" ?>').style.display="none";
    //parent.document.getElementById('<?php echo $rid."__".$mid?>').style.display="none";
    var image=parent.document.getElementById('<?php echo $rid."__".$mid."_image" ?>');
    image.removeAttribute('onclick');
    if (image.addEventListener) {
            // EOMB
        image.addEventListener('click',function(e){
             displayRecorder();
             e.preventDefault();            
        },true);
    } else {
            // IE
        image.attachEvent('onclick',displayRecorder);
    }
    displayRecorder();
    
       

</script>
