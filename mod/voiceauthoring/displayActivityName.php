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
<head>
<script>

function displayRecorder()
{
    var iframe=parent.document.getElementById('<?php echo $rid?>_<?php echo $mid?>' );
    iframe.src='<?php echo $iFramePath?>&id='+iframe.id;
    iframe.style.position="absolute";
    iframe.style.display='block';
    iframe.style.left='';
    return false;  
}
    
function displayRecorderForCalendar()
{     
        var str= new String(parent.location);
        if( str.match("<?php echo $rid?>_<?php echo $mid?>") != null)
        {    
            var span = parent.document.getElementById('<?php echo $rid?>_<?php echo $mid?>_span' );
            
            if(parent.document.getElementById(span.id) != null) //there is not an other iframe with the same id
            {
                var iframe = parent.document.getElementById('<?php echo $rid?>_<?php echo $mid?>' );
                iframe.src = '<?php echo $iFramePath?>&id='+iframe.id;
                iframe.style.display = "block";
            }
        }
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
        <img width='16px' height='16px' onclick="displayRecorder()" style="cursor:pointer" src='<?php echo $imgPath?>'/>
    </div>

<script>
    displayRecorderForCalendar();
    var span=parent.document.getElementById('<?php echo $rid?>_<?php echo $mid?>_span');
    if (span.addEventListener) {
            // EOMB
        span.addEventListener('click',function(e){
             displayRecorder();
             e.preventDefault();            
        },true);
    } else {
            // IE
        span.attachEvent('onclick',displayRecorder);
    }
    
</script>
</body>
</html>
    
    