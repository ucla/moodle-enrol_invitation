<?php
require_once('../../config.php');
require_once('lib.php');   

//Create the Voice E-mail linked to this actvity
$course_id=optional_param('course_id', '',PARAM_ALPHANUM);
$block_id=optional_param('block_id', '',PARAM_ALPHANUM);

$context = get_context_instance(CONTEXT_COURSE, $course_id);
   
$users = get_enrolled_users(get_context_instance(CONTEXT_COURSE, $course_id));
//$users also contain the users which have this capabilities at the system level
$users_key = array_keys($users);

$PAGE->set_url('/mod/voiceemail/listAvailableRecipients.php');
$PAGE->set_context($context);
?>
<html>
<head>
    <link rel="STYLESHEET" href="css/StyleSheet.css" type="text/css" />
</head>
<body>
    <form action="manageActionBlock.php" method="post">
        <div >
            <p style="font-size:12px;">Select the users to whom you want to send an email:</p>
            <div class="datatable" style="overflow-y:scroll;height:350px;padding:10px 40px;background-color:#F2F3F9;width:340px;">
                <table style="border-spacing:0pt">
                <thead>
                    <tr>
                    <th class="cellAlign1" scope="col">User Name</th>
                    <th class="cellAlign1" scope="col">Email</th>
                    <th class="cellAlign1" scope="col">Selected</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                      
                    for($i=0;$i<count($users_key);$i++)
                    {  
                    	$roleInCourse = get_user_roles_in_course($users[$users_key[$i]]->id,$course_id);
						if(!empty($roleInCourse))
				        { //A role is assigned to this user in the course context, this user has to be displayed                    
                    	?>
                        <tr>
                            <td class="cellAlign1" style="width:150px" >
                                <span><?php echo $users[$users_key[$i]]->firstname." ".$users[$users_key[$i]]->lastname;?></span>
                                </td>
                            <td class="cellAlign1"><span><?php echo $users[$users_key[$i]]->email;?></span></td>
                            <td class="cellAlign2">
                                <input type="checkbox" name="users[]" value="<?php echo $users[$users_key[$i]]->email;?>"/>
                            </td>
                        </tr> 
                    <?php 
                    	}
                    }
                    
                    
                    ?>
                </tbody>
                </table>
            </div>
            <p style="text-align:center">
                <input type="submit" name="launch" value="Launch" style="float:right" class="regular_btn-submit"/>
            </p>
        </div>
    <input type="hidden" name="course_id" value="<?php echo $course_id;?>">
    <input type="hidden" name="block_id" value="<?php echo $block_id;?>">
    <input type="hidden" name="type" value="other">
    </form>
</body>
</html>
