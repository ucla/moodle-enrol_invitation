<?php require_once("../../config.php")  ?>
<html>

<head>
    <title>Error Voice Authoring</title>
</head>
<link rel="STYLESHEET" href="css/StyleSheet.css" type="text/css" />   
<body>
    <div class="headerBar" style="width:210px">
        <div class="headerBarLeft" >
            <span>Blackboard Collaborate</span>
        </div>
    </div>     
    <div class='error_frame' style="background-color: rgb(255, 208, 208); height: 150px; width: 210px; padding-left: 0px;"> 
       <div style="position:absolute;left:10px;">
            <span class='warning'></span>
       </div>  
       <span class='error_title'>Error : <?php echo get_string($_GET["error"]."_recorder","voiceauthoring");?></span>             
    </div>
</html>
</body>