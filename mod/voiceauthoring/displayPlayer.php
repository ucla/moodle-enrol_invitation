<!doctype html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
        <meta http-equiv="Content-Type" content="IE=EmulateIE8"/>
        <title>Blackboard Collaborate Voice Authoring</title>
    </head>
    <body>
<?php
require_once("../../config.php");
require_once("lib.php");

$iframeId=optional_param('id','',PARAM_CLEAN);
$title = urldecode( optional_param('title','',PARAM_CLEAN));
$mid=optional_param('mid','',PARAM_CLEAN);
$rid=optional_param('rid','',PARAM_CLEAN);

if(strlen($title)>50){
    $title=substr($title,0,50)."...";
}

$closeclick = ($iframeId == '') ? 'javascript:window.close();' : "javascript:parent.document.getElementById('".$iframeId."').data=null;javascript:parent.document.getElementById('".$iframeId."').style.display='none';return false;";

?>
<script>
if(parent.document.getElementById( "<?php echo $iframeId;?>" ) != null)
{
    parent.document.getElementById( "<?php echo $iframeId;?>" ).style.position ="absolute";
    parent.document.getElementById( "<?php echo $iframeId;?>" ).style.width = "330px";
    parent.document.getElementById( "<?php echo $iframeId;?>" ).style.height = "170px";
    parent.document.getElementById( "<?php echo $iframeId;?>" ).style.overflow ="hidden";
    parent.document.getElementById( "<?php echo $iframeId;?>" ).style.zIndex ="1000";
}
</script>
<link rel="STYLESHEET" href="css/StyleSheet.css?<?php echo VOICEAUTHORING_STYLE_VERSION; ?>" type="text/css" />
<style>

body 
{
    background-color: transparent;
}   

</style>
<div class="wimba_box" style="height:100px;">
    <div class="wimba_boxTop">
        <div class="wimbaBoxTopDiv" style="_height:80px">
            <span class="wimba_boxTopTitle"><?php echo $title; ?>         
            </span>
            
            <img src="lib/web/pictures/buttons/flat/close/x_round-16_14.png" onclick="<?php echo $closeclick; ?>" class="wimba_close_box" alt="Close" title="Close" />
            
            <p id="applet_container"></p>
            <SCRIPT type="text/javascript">
              this.focus();
            </SCRIPT>

            <SCRIPT type="text/javascript" SRC="<?php echo $CFG->voicetools_servername; ?>/ve/play.js"></SCRIPT>
            <SCRIPT type="text/javascript">
                var w_p = new Object();
                w_p.mid="<?php echo $mid;?>";
                w_p.rid="<?php echo $rid;?>";
                w_p.language = "en";
                w_p.autostart = "true";

                if (window.w_ve_play_tag) w_ve_play_tag(w_p, document.getElementById("applet_container"));
                else document.write("Applet should be there, but the Blackboard Collaborate Voice server is down");

            </SCRIPT>
           
        </div>
    </div>
    <div class="wimba_boxBottom">
        <div>
        </div> 
    </div>
</div>
    </body>
</html>
            
