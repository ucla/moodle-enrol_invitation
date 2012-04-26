<?php
###########################################
# Name: console.php
# Purpose: 1st attempt at a Support Console
# Usage: restricted to those with access to class_requestor
#
# To Do: add queries as needed, use the Count of Moodle Log Entries sections as models since they auto-display the column headings
# NOTE: Some of these reports will require configuration to your local log locations, databases, etc.

//Only Allow Admin Users
require_once("../../../config.php");
require_once($CFG-> libdir.'/adminlib.php');
error_reporting(E_ALL); 
ini_set( 'display_errors','1');

if (!isset($_POST['console']) || ($_POST['console'] !== "Show Logs: Last 1000 Lines of ")) {
    admin_externalpage_setup('reportsupportconsole');
    echo $OUTPUT->header();
}

require_login();
require_capability('moodle/site:viewreports', get_context_instance(CONTEXT_SYSTEM));

if (empty($SERVER{'HTTP_SHIB_UID'})) {
    $id = NULL;
    $displayname = NULL;
} else {
    $id =  $_SERVER{'HTTP_SHIB_UID'};
    $displayname =  $_SERVER{'HTTP_SHIB_CN'};
    if(!isset($displayname)) {
        $displayname = '';
    }
    # authorization is handled at the directory level by Shib/Apache config in /etc/httpd/conf.d/shib.conf
}

function printhead() {  
    static $headerprinted = 0;
    $headerprinted++;
    if ($headerprinted > 1) { 
        return; 
    } else {
?>
<font face=verdana>
<table width=100%>
    <tr>
        <td>
            <table width=80%>
                <tr>
                    <td align=right>
                    <font color=white>
                    <?php if (isset($displayname)) echo $displayname ?> currently logged in</font>
                    </td>
                </tr>
                <tr>
                    <td>
                        <h3><font color=white>MOODLE SUPPORT CONSOLE</font>
                        </h3>
                    </td>
                </tr>
                <tr>
                    <td>
                    </td>
                </tr>
            </table>
<?php
// show return link on results screen
/*        if (isset($_POST['console'])) { 
            printhead(); 
            echo "<a href=\"".$_SERVER['PHP_SELF']."\">Return</a>\n"; 
        }*/
    } 
}

function createTable($result_keys, $result_val)
{
global $CFG;
echo'<table id="myTable" class="tablesorter" cellspacing="1">';
    echo "<thead>";
    echo "<tr>";
    foreach($result_keys as $key){
        echo '<th>'.$key.'</th>';
    }
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";
    foreach($result_val as $res)
    {
        $row=array_values(get_object_vars($res));
        echo "<tr>";
        foreach($row as $res)
        {
            echo '<td>'.$res.'</td>';
        }
        echo "</tr>";
    }
    echo "</tbody>";
    echo "</table>\n";
?>
 <script src="http://jquery.com/src/jquery-latest.js"></script>
<link rel="stylesheet" type="text/css" href="<?php echo $CFG->wwwroot;?>/lib/tablesorter/themes/blue/style.css" />
<script type="text/javascript" src="<?php echo $CFG->wwwroot;?>/lib/tablesorter/jquery-latest.js"></script>
<script type="text/javascript" src="<?php echo $CFG->wwwroot;?>/lib/tablesorter/jquery.tablesorter.js"></script>
<script type="text/javascript">
$(document).ready(function() 
    { 
        $("#myTable").tablesorter({widthFixed: true}); 
    } 
); 
</script>
<?php
}

////////////////////////////////////////////////////////////////////
$title="Show Logs: Last 1000 Lines of ";
$log_names = array('Apache Error'           => 'log_apache_error',
                   'Apache Access'          => 'log_apache_access',
                   'Apache SSL Access'      => 'log_apache_ssl_access',
                   'Apache SSL Error'       => 'log_apache_ssl_error',
                   'Apache SSL Request'     => 'log_apache_ssl_request',
                   'Shibboleth Daemon'      => 'log_shibboleth_shibd',
                   'Shibboleth Transaction' => 'log_shibboleth_trans',
                   'IMS Enterprise'         => 'enrol_logtolocation',
                   'Course Creator'         => 'log_course_creator',
                   'Course Creator Error'   => 'log_course_creator_error',
                   'Moodle Cron'            => 'log_moodle_cron');

// START UCLA MODIFCATION SSC #769 - More configurable and dynamic paths for log files
foreach ($log_names as $log_title => $cfg_var) {
    if (isset($CFG->$cfg_var) && file_exists($CFG->$cfg_var)) {
        $optnames[$log_title] = TRUE;
    } else {
        $optnames[$log_title] = FALSE;
    }
}

if (empty($_POST['console'])) {
?>
    <form method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>">
        <input type="submit" name="console" value="<?php echo $title; ?>">
        <select name="logname">
<?php
    foreach ($optnames as $logfile => $enable) {
        if ($enable) {
            echo "<option value=\"" . $log_names[$logfile] . "\">$logfile</option>\n";
        } else {
            echo "<option value=\"" . $log_names[$logfile] . "\" disabled>$logfile</option>\n";
        }
    } 
?>
        </select>
        If an option is disabled, it means no log file was found.
    </form>

<?php
} else if ($_POST['console'] == "$title") {
    $logfile = preg_replace('/[^a-zA-Z_]/', '', $_POST['logname']);

    if (empty($logfile) || !preg_grep("/^$logfile$/", $log_names)) { 
        echo "Invalid logfile name. $logfile <br>\n";
        exit;
    }
    
    header('Content-type: text/plain');
    echo $title . " " . $CFG->$logfile . "*\n";
    echo "  Output is plain text instead of html to avoid interpretting html in log files. ";
    echo "Use Browser Back Button to return to Console.\n\n";
   
    // Use the specified CFG variable to display the log names.
    $tail_command = "/usr/bin/tail -1000 ";
    system($tail_command . '`ls -t ' . $CFG->$logfile . '* | /usr/bin/head -1`');
    exit;
} 
////////////////////////////////////////////////////////////////////
if (file_exists("/logs/prepop_db")) {
    $title="List Prepopulate Cron Job Output Logfiles";
    if (empty($_POST['console'])) { 
?>
    <form method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>">
        <input type="submit" name="console" value="<?php echo $title; ?>">
    </form>
<?php
    } elseif ($_POST['console'] == "$title") { 
        echo "<h3>$title</h3>\n";
        echo "<pre>\n";
        system("ls -alt {$CFG->log_prepop_folder}/prepop_*");     //missing file
        echo "</pre>\n";

    } 
    
////////////////////////////////////////////////////////////////////
    $title="List Most Recent Prepopulate Cron Job Output";
    if (empty($_POST['console'])) { 
?>
    <form method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>">
        <input type="submit" name="console" value="<?php echo $title; ?>">
    </form>
<?php 
    } elseif ($_POST['console'] == "$title") { 
        echo "<h3>$title</h3>\n";
        system("ls -alt {$CFG->log_prepop_folder}/prepop_* | head -1");   //missing file
        echo "<pre>\n";
        passthru("ls -t {$CFG->log_prepop_folder}/prepop_* | head -1 | xargs cat");   //missing file

        echo "</pre>\n";
    }
}
////////////////////////////////////////////////////////////////////
$title = "Show Role Assignments Distribution";
if (empty($_POST['console'])) { 
?>
    <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
        <input type="submit" name="console" value="<?php echo $title; ?>">
    </form>
<?php
} elseif ($_POST['console'] == "$title") { 
    echo "<h3>$title</h3>\n";
    
   /*$db_moodle = mysql_connect($CFG->dbhost,$CFG->dbuser,$CFG->dbpass)
        or die("Unable to connect to Moodle DB server {$CFG->dbhost}");
     mysql_select_db($CFG->dbname, $db_moodle) 
        or die("Unable to select DB {$CFG->dbname}");
   */

    $result=$DB->get_records_sql("select b.name,b.shortname,component,count(*) as cnt from {$CFG->prefix}role_assignments a left join {$CFG->prefix}role b on(a.roleid=b.id) group by component,roleid");
//  $coursers = mysql_query("select id,idnumber from {$CFG->prefix}course where idnumber like '$term-$srs%'", $db_moodle) or die("Unable to get     course IDs from Moodle: " . mysql_error());
    $result_val=array_values($result);
    $result_keys=array_keys(get_object_vars($result_val[0]));

$admin_result=$CFG->siteadmins;
if(empty($admin_result)& empty($result))
{
    print_error("There are no enrollments");
}
    $admin_cnt=count(explode(',',$admin_result));
    $adminObj= new stdClass;
    $adminObj->name = 'Admin';
    $adminObj->shortname = 'admin';
    $adminObj->component = 'manual';
    $adminObj->cnt=$admin_cnt;
    array_unshift($result_val,$adminObj);
$counter=0;
foreach( $result_val as $res)
{
	if($res->component == '')
	{
	    $res->component='manual';
	    $result_val[$counter]=$res;
	}
	$counter++;
}
createTable($result_keys,$result_val);
} 
////////////////////////////////////////////////////////////////////
$title="Show Last 100 Log Entries";
if (empty($_POST['console'])) { 
?>
    <form method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>">
        <input type="submit" name="console" value="<?php echo $title; ?>">
        <!-- Start UCLA SSC MOD #619-->
        <input type="radio" name="radio" value="all" CHECKED>All
        <input type="radio" name="radio" value="admin">Admin Config Changes
        <input type="radio" name="radio" value="gradebooktrue">Gradebook Successes
        <input type="radio" name="radio" value="gradebookfalse">Gradebook Failures
        <!-- End UCLA SSC MOD #619--> 
    </form>
<?php
} elseif ($_POST['console'] == "$title") { 
    //START UCLA SSC MODIFICATION #619
    // START SSC MODIFICATION #1095
    echo "<h3>$title";
    $log_query = '';
    if ($_POST['radio'] == "all") {
	$gradebookradio = false;
        $log_query = "select
	    a.id, 
            from_unixtime(time) as time,
            b.firstname,
            b.lastname,
            ip,
            c.shortname,
            module,
            action
        from {$CFG->prefix}log a
        left join {$CFG->prefix}user b on (a.userid=b.id)
        left join {$CFG->prefix}course c on (a.course=c.id)
        order by a.id desc limit 100";
    
    } else if($_POST['radio'] == "admin") {
        echo " of Admin Config Changes\n";
	$gradebookradio= false;
        $log_query = "select a.id, from_unixtime(time) as time, a.userid, b.firstname,b.lastname,ip,info
        from {$CFG->prefix}log a
        left join {$CFG->prefix}user b on (a.userid=b.id)
        where a.module='ucla admin' and a.action='config change'
        order by a.id desc limit 100";
    } else if ($_POST['radio'] == 'gradebooktrue') {
        echo " of MyUCLA Gradebook successful pushes";
	$gradebookradio= true;
        $log_query = "select 
	    a.id,
            from_unixtime(time) as time, 
            b.firstname, 
            b.lastname, 
            ip, 
            info,
	    a.course,
	    c.shortname
        from {$CFG->prefix}log a
        left join {$CFG->prefix}user b on (a.userid = b.id)
        left join {$CFG->prefix}course c on (a.course = c.id)
        where a.action LIKE 'Gradebook push success'
        order by a.id desc limit 200";
    } else if ($_POST['radio'] == 'gradebookfalse') {
        echo " of MyUCLA Gradebook failed pushes";
	$gradebookradio= true;
        $log_query = "select 
            a.id,
	    from_unixtime(time) as time, 
            b.firstname, 
            b.lastname, 
            ip, 
            info,
	    a.course,
	    c.shortname
        from {$CFG->prefix}log a
        left join {$CFG->prefix}user b on (a.userid = b.id)
        left join {$CFG->prefix}course c on (a.course = c.id)
        where a.action LIKE 'Gradebook push failure'
        order by a.id desc limit 200";
    }
    echo "</h3>";
/*
    $db_moodle = mysql_connect($CFG->dbhost,$CFG->dbuser,$CFG->dbpass)
        or die("Unable to connect to Moodle DB server {$CFG->dbhost}");
     mysql_select_db($CFG->dbname, $db_moodle) 
        or die("Unable to select DB {$CFG->dbname}");

    //END UCLA SSC MODIFICATION #619
    $result=mysql_query($log_query, $db_moodle)
        or die(mysql_error());
*/

  $result=$DB->get_records_sql($log_query);
if(empty($result))
{
	echo"No results were found";
}
else
{
$result_val=array_values($result);
$result_keys=array_keys(get_object_vars($result_val[0]));

unset($result_keys[0]);
if($gradebookradio ==true)
{
   unset($result_keys[7]);
   $result_keys[6]='url';
}
$counter=0;
foreach($result_val as $res)
{
        if($gradebookradio==true)
        {
                $res->course='<a href='.$CFG->wwwroot.'/course/view.php?id='.$res->course.'>'.$res->shortname.'</a>';
                unset($res->shortname);
        }
        unset($res->id);
	$result_val[$counter]= $res;
	$counter++;
}
createTable($result_keys,$result_val);
}
    // END UCLA SSC MODIFICATION #1095a
/*
    $cols = 0;
    while ($get_info = mysql_fetch_assoc($result)){
        if($cols == 0) {
            $cols = 1;
            echo "<tr>";
            foreach($get_info as $col => $value) {
                echo "<th align='left'>$col</th>";
            }
            echo "<tr>\n";
        }
        echo "<tr>\n";
        foreach ($get_info as $field) {
            echo "\t<td>$field</td>\n";
        }
        echo "</tr>\n";
    }

  }    
    echo "</table>\n"; */
} 
////////////////////////////////////////////////////////////////////
$title="Show Logins During Last 24 Hours";
if (empty($_POST['console'])) { 
?>
    <form method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>">
        <input type="submit" name="console" value="<?php echo $title; ?>">
    </form>
<?php
} elseif ($_POST['console'] == "$title") { 
    echo "<h3>$title</h3>\n";
/*    $db_moodle = mysql_connect($CFG->dbhost,$CFG->dbuser,$CFG->dbpass)
        or die("Unable to connect to Moodle DB server {$CFG->dbhost}");
     mysql_select_db($CFG->dbname, $db_moodle) 
        or die("Unable to select DB {$CFG->dbname}");
*/
     $log_query="select a.id, from_unixtime(time) as Time,b.Firstname,b.Lastname,IP,a.URL,Info
        from {$CFG->prefix}log a 
        left join {$CFG->prefix}user b on(a.userid=b.id)
        where from_unixtime(time)  >= DATE_SUB(CURDATE(), INTERVAL 1 DAY) and action='login'
        order by a.id desc";
	$result=$DB->get_records_sql($log_query);
if(empty($result))
{
        echo"No results were found";
}
else
{
$result_val=array_values($result);
$result_keys=array_keys(get_object_vars($result_val[0]));

unset($result_keys[0]);
foreach($result_val as $res)
{
	unset($res->id);
}
        $num_rows = sizeof($result);
        if ($num_rows === 1) {
            echo "There was 1 login.<P>";
        }
        else {
            echo "There were $num_rows logins.<P>";
        }
createTable($result_keys,$result_val);
     /*   echo "<table>\n";
        $cols = 0;
        while ($get_info = mysql_fetch_assoc($result)){
            if($cols == 0) {
                $cols = 1;
                echo "<tr>";
                foreach($get_info as $col => $value) {
                    echo "<th align='left'>$col</th>";
                }
                echo "<tr>\n";
            }
            echo "<tr>\n";
            foreach ($get_info as $field) {
                echo "\t<td>$field</td>\n";
            }
            echo "</tr>\n";
        }    
        echo "</table>\n"; */
}
} 
////////////////////////////////////////////////////////////////////
$title="Count of Moodle Log by Day";
if (empty($_POST['console'])) { 
?>
    <form method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>">
        <input type="submit" name="console" value="<?php echo $title; ?>">
        Days: <input type="textfield" name="days" size="3" VALUE="7">
        <input type="radio" name="radio" value="login" CHECKED>Logins
        <input type="radio" name="radio" value="entries">Log Entries
    </form>
<?php
// save for later when figure out how sql query should look    <input type="radio" name="radio" value="unique" CHECKED>Unique Logins
} elseif ($_POST['console'] == "$title") { 
    $whereclause=$_POST['radio'];
    $days=(int) $_POST['days'];
    $distinct = ""; 
    if ($days < 1 or $days > 999) {
        echo "Invalid number of days.<br>\n";
        exit;
    }    
    if ($whereclause!="login" and $whereclause!="entries") {
        echo "Invalid search options.<br>\n";
        exit;
    }    
    if ($whereclause=="login") {
        $whereclause = "AND action='login'";
        echo "<h3>Count of Moodle Logins for the Last $days Days</h3>\n";
    } else {
        $whereclause = "";
        echo "<h3>Count of Moodle Log Entries from the Last $days Days</h3>\n";
    }    
    $days--;  # decrement days by 1 to get query to work
     $result=$DB->get_records_sql("select a.id,from_unixtime(time,'%Y-%m-%d') as Date,count(*) as Count 
        from {$CFG->prefix}log a 
        where from_unixtime(time)  >= DATE_SUB(CURDATE(), INTERVAL $days DAY) 
        $whereclause
        group by Date
        order by a.id desc");
if(empty($result)) 
{
        echo"No results were found";
}   
else
{   
$result_val=array_values($result);
$result_keys=array_keys(get_object_vars($result_val[0]));

unset($result_keys[0]);
foreach($result_val as $res)
{
        unset($res->id);
}
createTable($result_keys,$result_val);
}

/*
        echo "<table width=30% border=1>\n";
        $cols = 0;
        while ($get_info = mysql_fetch_assoc($result)){
            if($cols == 0) {
                $cols = 1;
                echo "<tr>";
                foreach($get_info as $col => $value) {
                    echo "<th align='left'>$col</th>";
                }
                echo "<tr>\n";
            }
            echo "<tr>\n";
            foreach ($get_info as $field) {
                echo "\t<td align='center'>$field</td>\n";
            }
            echo "</tr>\n";
        }    
        echo "</table>\n"; 
*/
} 
////////////////////////////////////////////////////////////////////
$title="Count of Moodle Log Entries by Day, Course for Last 7 days";
if (empty($_POST['console'])) { 
?>
    <form method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>">
        <input type="submit" name="console" value="<?php echo $title; ?>">
    </form>
<?php
} elseif ($_POST['console'] == "$title") { 
    echo "<h3>$title</h3>\n";
     $result=$DB->get_records_sql("select a.id, from_unixtime(time,'%Y-%m-%d') as Date,c.shortname as Course,count(*) as Count 
        from {$CFG->prefix}log a 
        left join {$CFG->prefix}course c on(a.course=c.id) 
        where from_unixtime(time)  >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
        group by Date,Course 
        order by a.id desc");
if(empty($result)) 
{
        echo"No results were found";
}   
else
{   
$result_val=array_values($result);
$result_keys=array_keys(get_object_vars($result_val[0]));

unset($result_keys[0]);
foreach($result_val as $res)
{
        unset($res->id);
}
createTable($result_keys,$result_val);
}

/*        
echo "<table width=4dd0% border=1>\n";
        $cols = 0;
        while ($get_info = mysql_fetch_assoc($result)){
            if($cols == 0) {
                $cols = 1;
                echo "<tr>";
                foreach($get_info as $col => $value) {
                    echo "<th align='left'>$col</th>";
                }
                echo "<tr>\n";
            }
            echo "<tr>\n";
            foreach ($get_info as $field) {
                echo "\t<td>$field</td>\n";
            }
            echo "</tr>\n";
        }    
        echo "</table>\n"; 
*/
} 
////////////////////////////////////////////////////////////////////
$title="Count of Moodle Log Entries by Day, Course, User for Last 7 days";
if (empty($_POST['console'])) { 
?>
    <form method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>">
        <input type="submit" name="console" value="<?php echo $title; ?>">
    </form>
<?php
} elseif ($_POST['console'] == "$title") { 
    echo "<h3>$title</h3>\n";
     $result=$DB->get_records_sql("select a.id, from_unixtime(time,'%Y-%m-%d') as Day,c.shortname as Course,b.Firstname,b.Lastname,count(*) as Count 
        from {$CFG->prefix}log a 
        left join {$CFG->prefix}user b on(a.userid=b.id) 
        left join {$CFG->prefix}course c on(a.course=c.id) 
        where from_unixtime(time)  >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
        group by day,course,a.userid 
        order by a.id desc");
  if(empty($result))  
{
        echo"No results were found";
}
else
{   
$result_val=array_values($result);
$result_keys=array_keys(get_object_vars($result_val[0]));

unset($result_keys[0]);
foreach($result_val as $res)
{
        unset($res->id);
}
createTable($result_keys,$result_val);
}
      
/*
        echo "<table width=60% border=0>\n";
        $cols = 0;
        while ($get_info = mysql_fetch_assoc($result)){
            if($cols == 0) {
                $cols = 1;
                echo "<tr>";
                foreach($get_info as $col => $value) {
                    echo "<th align='left'>$col</th>";
                }
                echo "<tr>\n";
            }
            echo "<tr>\n";
            foreach ($get_info as $field) {
                echo "\t<td>$field</td>\n";
            }
            echo "</tr>\n";
        }    
        echo "</table>\n"; 
*/
} 
////////////////////////////////////////////////////////////////////
//MDL-1196:"Comparison of ucla_request_classes & ucla_request_crosslisted with classes table." was removed due new tables not requiring the functions of this script
////////////////////////////////////////////////////////////////////
$title="Look up Moodle User by First or Last Name";
// Note: this report has an additional column at the end, with an SRDB button that points to the enroll2 Registrar class lookup
if (empty($_POST['console'])) { 
?>
    <form method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>">
        <input type="submit" name="console" value="<?php echo $title; ?>">
        First Name: <input type="textfield" name="firstname">
        Last Name: <input type="textfield" name="lastname">
    </form>
<?php
} elseif ($_POST['console'] == "$title") { 
    echo "<h3>$title</h3>\n";
    $firstname=htmlentities($_POST['firstname']);
    $lastname =htmlentities($_POST['lastname']);
?>
    <form method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>">
        <input type="submit" name="console" value="<?php echo $title; ?>">
        <select name="searchtype">
        <option value="contains">contains</option>
        <option value="equals">equals</option>
        <option value="beginswith">begins with</option>
        <option value="endswith">ends with</option>
        </select>
        First Name: <input type="textfield" name="firstname" value="<?php echo $firstname;?>">
        Last Name: <input type="textfield" name="lastname" value="<?php echo $lastname;?>">
    </form>
<?php
    $firstname = addslashes($firstname);
    $lastname = addslashes($lastname);

    //Use short-circuit eval to check for empty
    if (empty($_POST['searchtype']) || $_POST['searchtype'] === "contains" || $_POST['searchtype'] === "endswith") {
        $searchstart = '%';
     }
     else {
         $searchstart = '';
     }
    if (empty($_POST['searchtype']) || $_POST['searchtype'] === "contains" || $_POST['searchtype'] === "beginswith") {
        $searchend = '%';
    }
    else {
        $searchend = '';
    }

    if (!empty($firstname) and !empty($lastname)) {
        $whereclause = "WHERE firstname LIKE \"$searchstart$firstname$searchend\" AND lastname LIKE \"$searchstart$lastname$searchend\" AND deleted=0"; 
    } elseif (empty($firstname) and empty($lastname)) {
        echo "Can't search without any names.";
        exit;
    } elseif (empty($firstname) and !empty($lastname)) {
        $whereclause = "WHERE lastname LIKE \"$searchstart$lastname$searchend\" AND deleted=0"; 
    } elseif (!empty($firstname) and empty($lastname)) {
        $whereclause = "WHERE firstname LIKE \"$searchstart$firstname$searchend\" AND deleted=0"; 
    }    
      
    echo "Searching $whereclause<br>\n"; 
     $result=$DB->get_records_sql("select id,auth,username,firstname,lastname,idnumber,email,from_unixtime(lastaccess) as last_access,lastip
        from {$CFG->prefix}user a 
        $whereclause
        order by a.lastname,a.firstname");
        $num_rows = sizeof($result);
        if ($num_rows === 1) {
            echo "There is $num_rows row.<P>";
        }
        else {
            echo "There are $num_rows rows.<P>";
        }
if(empty($result))  
{
        echo"No results were found";
}
else
{   
$result_val=array_values($result);
$result_keys=array_keys(get_object_vars($result_val[0]));
$result_keys[]='SRDB';
$counter=0;
foreach($result_val as $res)
{
	$uid=$res->idnumber;
$serverAdd=$_SERVER['PHP_SELF'];
$res->SRBDButton='<form method="post" action="'.$serverAdd.' ">
        <input type="submit" name="console" value="SRDB">
        <input type="hidden" name="uid" value="'.$uid.'">
        <input type="hidden" name="srdb_view" value="enroll2">
    </form>';
       $result_val[$counter]=$res;

}
createTable($result_keys,$result_val);
}

/*        
        echo "<table>\n";
        $cols = 0;
        while ($get_info = mysql_fetch_assoc($result)){
            if($cols == 0) {
                $cols = 1;
                foreach($get_info as $col => $value) {
                    echo "<th align='left'>$col</th>";
                }
                echo "<th align='left'>SRDB</th>"; # added column for SRDB lookup
                echo "<tr>\n";
            }
            echo "<tr>\n";
            foreach ($get_info as $col=>$field) {
                if ($col === "idnumber") { $uid = $field; }  # save this for link to Registrar lookup
                echo "\t<td>$field</td>\n";
            }
            echo "<td>";
?>  
    <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
        <input type="submit" name="console" value="SRDB">
        <input type="hidden" name="uid" value="<?php echo $uid;?>">
        <input type="hidden" name="srdb_view" value="enroll2">
    </form>  
<?php
            echo "</td>";
            echo "</tr>\n";
        }    
        echo "</table>\n"; -
*/
} 
////////////////////////////////////////////////////////////////////
$title="Get All Fields for Classes View (enroll2) from Registrar by UID";
// Note: this has code which allows post from Name Lookup report 
if (empty($_POST['console'])) {
    printhead();
?>
    <form method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>">
        <input type="submit" name="console" value="<?php echo $title; ?>">
        UID: <input type="textfield" name="uid">
        SRDB view: 
        <select name="srdb_view">
        <option value="enroll2">enroll2 (fix for TAs)</option>
        <!--<option value="enroll">enroll (fix for UNEX))</option>-->
        </select>
    </form>
<?php
} elseif (($_POST['console'] == "$title") or ($_POST['console'] == "SRDB")) {  # tie-in to link from name lookup
    echo "<h3>$title</h3>\n";
    if (!empty($_POST['uid']) and !empty($_POST['srdb_view'])) {
        $db_conn = odbc_connect($CFG->registrar_dbhost, $CFG->registrar_dbuser, $CFG->registrar_dbpass) or die( "ERROR: Connection to Registrar failed.");

        // SQL query to find the courses the user is enroled in
        $wherefield = "uid";
        $sortfield = "term_int DESC, subj_area, catlg_no, sect_no";

        $sql = "SELECT *
        FROM " . $_POST['srdb_view'] . "
        WHERE $wherefield = " . $_POST['uid'] . "
        ORDER BY $sortfield";
        $result = odbc_exec($db_conn,  $sql)
            or die ("Query failed:");
        echo "<table width=60% border=1>\n";
        echo "<tr>\n";

        if ($result === false) {
            echo "Error: Failure while accessing Registrar table, line: ".__LINE__."\n";
        }
        // Show the headings
        // Also store the field names so that we can find field values from the $fields_obj later

        $field_count = odbc_num_fields($result);
        $field_names = array();
        for ($i = 1; $i <= $field_count; $i++) {
            $fieldname = odbc_field_name($result, $i);
            echo "<th>" . $fieldname. "</th>\n";
            $field_names[] = $fieldname;
        }

        // Show the content
        $c=0; $nrows=0;
        while(odbc_fetch_row($result)) { // getting data
           $c=$c+1;
           if ( $c%2 == 0 )
               echo "<tr bgcolor=\"#d0d0d0\" >\n";
           else
               echo "<tr bgcolor=\"#eeeeee\">\n";
           for($j=1; $j<=odbc_num_fields($result); $j++) {       
               echo "<td>";
               echo odbc_result($result,$j);
               echo "</td>";        
               if ( $j%$i == 0 ) {
                   $nrows+=1; // counting no of rows   
               }  
           }
           echo "</tr>";
       }
        echo "</table>\n";
    } else {
        echo "Can't search with no UID.";
        exit;
    }
}
////////////////////////////////////////////////////////////////////
	$title="Sort Class Sites by Count of Resources or Activities ";

/*$result=$DB->get_records_sql("select left(idnumber,3) as term,count(*) as cnt from {$CFG->prefix}course group by term");
*/

$result=$DB->get_records_sql("select term, count(*) as cnt from {$CFG->prefix}ucla_request_classes group by term");
$item_names = array('resource','assignment','forum','questionnaire','quiz','ouwiki','lesson','exercise','forumposts');
if (empty($_POST['console'])) {
?>
    <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
        <input type="submit" style="background:red" name="console" value="<?php echo $title; ?>">
        <select name="term">
        <option value="">TERM (count)</option>
<?php
$term_val=array_values($result);echo"next";
foreach($term_val as $tVal)
{
$row=get_object_vars($tVal);
 if (!empty($row['term'])) {
         echo  '<option value="'.$row['term'].'">'.$row['term'].'  ('.$row['cnt'].")</option>\n";
     }
}

?>
        </select>
        <select name="itemname">
<?php foreach ($item_names as $itemfile) {
    echo "<option value=\"$itemfile\">$itemfile</option>\n";
} ?>
        </select>

    </form>
<?php
} elseif ($_POST['console'] == "$title") {
	$itemfile = $_POST['itemname'];
	$term     = $_POST['term'];
    echo "<h3>$title $itemfile</h3>\n";
    echo "<i>Term: $term Resource/Activity: $itemfile</i><br>\n";
	
	if($itemfile=='forumposts'){
		$log_query="SELECT c.id as ID, c.shortname as COURSE ,count(*) as Posts, c.fullname as Full_Name
							FROM mdl_course c 
							INNER JOIN  mdl_forum_discussions d ON d.course = c.id 
							INNER JOIN mdl_forum_posts p ON p.discussion = d.id
							WHERE c.idnumber LIKE '$term%'
							GROUP by c.id
							ORDER BY Posts DESC
							";
/*
      $num_rows = mysql_num_rows($result);
      echo "There are $num_rows classes with posts<P>";
    
		echo "<table width=100% border=0>\n";

        $cols = 0;
        while ($get_info = mysql_fetch_assoc($result)){
            if($cols == 0) {
                $cols = 1;
                echo "<tr>";
                foreach($get_info as $col => $value) {
                    echo "<th align='left'>$col</th>";
                }
                echo "<tr>\n";
            }
            echo "<tr>\n";
            foreach ($get_info as $field) {
                echo "\t<td>$field</td>\n";
            }
            echo "</tr>\n";
        }
        echo "</table>\n";
*/
	} else {
	$log_query="SELECT c.id, COUNT(l.id) as count, c.shortname
        FROM {$CFG->prefix}$itemfile l
        		INNER JOIN {$CFG->prefix}course c on l.course = c.id
        WHERE c.idnumber like '$term%'        
        GROUP BY left(c.idnumber,3), course
        ORDER BY left(c.idnumber,3), count DESC";
}
$result=$DB->get_records_sql($log_query);
/*
        echo "<table width=60% border=0>\n";
        $cols = 0;
        while ($get_info = mysql_fetch_assoc($result)){
            if($cols == 0) {
                $cols = 1;
                echo "<tr>";
                foreach($get_info as $col => $value) {
                    echo "<th align='left'>$col</th>";
                }
                echo "<tr>\n";
            }
            echo "<tr>\n";
            foreach ($get_info as $field) {
                echo "\t<td>$field</td>\n";
            }
            echo "</tr>\n";
        }
        echo "</table>\n";
*/
	if(empty($result))
	{
        echo"No results were found";
	}
	else
{
$result_val=array_values($result);
$result_keys=array_keys(get_object_vars($result_val[0]));
/*
CONCAT('<a target=\"_blank\" href=\"{$CFG->wwwroot}/course/edit.php?id=',c.id,'\">',c.id,'</a>') as ID, 
                                                        CONCAT('<a target=\"_blank\" href=\"{$CFG->wwwroot}/course/view.php?id=',c.id,'\">',c.shortname,'</a>') as Course 
*/
if($itemfile=='forumposts')
{	$counter=0;
	foreach ($result_val as $res)
        {
                $savedID='<a target="_blank" href='.$CFG->wwwroot.'/course/edit.php?id='.$res->id.'>'.$res->id.'</a>';
                $savedCourse='<a target="_blank" href='.$CFG->wwwroot.'/course/view.php?id='.$res->id.'>'.$res->course.'</a>';
		$res->id = $savedID;
		$res->course = $savedCourse;
                $result_val[$counter]= $res;
                $counter++;
        }

}
else{
	unset($result_keys[2]);
   	$result_keys[0]='coursename';

	$counter=0;
	foreach ($result_val as $res)
	{
		$saved='<a target="_blank" href='.$CFG->wwwroot.'/course/view.php?id='.$res->id.'>'.$res->shortname.'</a>';
		$res->id = $saved;
		unset($res->shortname);
		$result_val[$counter]= $res;
	        $counter++;
	}
}
/*SELECT COUNT(l,id) count, CONCAT('<a target=\"_blank\" href=\"{$CFG->wwwroot}/course/view.php?id=',c.id,'\">',c.shortname,'</a>') as coursename
*/
/*
unset($result_keys[0]);
foreach($result_val as $res)
{
        unset($res->id);
}
*/
createTable($result_keys,$result_val);
}


}
////////////////////////////////////////////////////////////////////
$title="Get Class Roster from Registrar";
$sp = array("CCLE_ROSTER_CLASS" => "CCLE_ROSTER_CLASS", "hu_facultyCourseStudentsGetAlpha2" => "hu_facultyCourseStudentsGetAlpha2", "CIS_ROSTER_CLASS" => "CIS_ROSTER_CLASS");

if (empty($_POST['console'])) {
    printhead();
?>
    <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
    <input type="submit" name="console" value="<?php echo $title; ?>">
TERM: <input type="textfield" name="term" size="3" value="<?php echo $CFG->currentterm; ?>">
SRS: <input type="textfield" name="srs">
<select name="sp">
<?php foreach ($sp as $sp => $stored_procedure) {
echo "<option value=\"$sp\">$sp</option>\n";
} ?>
</select>
</form>
<?php
} elseif ($_POST['console'] == "$title") {  # tie-in to link from name lookup
printhead();
$term     = $_POST['term'];
$srs      = $_POST['srs'];
$stored_procedure = $sp[$_POST['sp']];
echo "<h3>$title - ".$_POST['sp']."</h3>\n";     
if (!empty($term) and !empty($srs)) {
$db_conn = odbc_connect($CFG->registrar_dbhost, $CFG->registrar_dbuser, $CFG->registrar_dbpass) or die( "ERROR: Connection to Registrar failed.");
if ($stored_procedure == 'hu_facultyCourseStudentsGetAlpha2') {
    $sql = "EXECUTE $stored_procedure '$srs','$term'";
    $start_time = microtime(true);
    $result = odbc_exec($db_conn, $sql) or die ("Query failed: error message = " . odbc_errormsg($db_conn));
    $stop_time = microtime(true);
    echo '"' . $sql . '" took ' . ($stop_time - $start_time) . ' seconds <br>';
} else if ($stored_procedure == 'CCLE_ROSTER_CLASS') {
    $sql = "EXECUTE $stored_procedure '$term','$srs'";
    $start_time = microtime(true);
    $result = odbc_exec($db_conn, $sql) or die ("Query failed: error message = " . odbc_errormsg($db_conn));
    $stop_time = microtime(true);
    echo '"' . $sql . '" took ' . ($stop_time - $start_time) . ' seconds <br>';
} else if ($stored_procedure == 'CIS_ROSTER_CLASS') {
    $sql0 = "EXECUTE ccle_getClasses '$term', '$srs'";
    $start_time0 = microtime(true);
    $result0 = odbc_exec($db_conn, $sql0) or die ("Query failed: error message = " . odbc_errormsg($db_conn));
    $stop_time0 = microtime(true);
    echo '"' . $sql0 . '" took ' . ($stop_time0 - $start_time0) . ' seconds <br>';
    $row0 = odbc_fetch_array($result0);
    odbc_free_result($result0);
    $sql = "EXECUTE CIS_ROSTER_CLASS '{$row0['term']}', '{$row0['subj_area']}', '{$row0['crsidx']}', '{$row0['classidx']}'";
    $start_time = microtime(true);
    $result = odbc_exec($db_conn, $sql) or die ("Query failed: error message = " . odbc_errormsg($db_conn));
    $stop_time = microtime(true);
    echo '"' . $sql . '" took ' . ($stop_time - $start_time) . ' seconds <br>';
}
    
echo "<table>\n";
echo "<tr>\n";

// Show the headings
// Also store the field names so that we can find field values from the $fields_obj later

$field_count = odbc_num_fields($result);
for ($j = 1; $j <=$field_count; $j++) {
    echo "<th>" . odbc_field_name ($result, $j ). "</th>\n";
}
echo "</tr>\n";
$j=$j-1;
// end of field names

// Show the content
$c=0; $nrows=0;
while(odbc_fetch_row($result)) { // getting data
   $c=$c+1;
   if ( $c%2 == 0 )
       echo "<tr bgcolor=\"#d0d0d0\" >\n";
   else
       echo "<tr bgcolor=\"#eeeeee\">\n";
   for($i=1; $i<=odbc_num_fields($result); $i++) {       
       echo "<td>";
       echo odbc_result($result,$i);
       echo "</td>";        
       if ( $i%$j == 0 ) {
	   $nrows+=1; // counting no of rows   
       }  
   }
   echo "</tr>";
}

odbc_close ($db_conn);
echo "</table>\n";
if ($nrows==0) echo "<br/><center> Nothing for $term and $srs. Try back later</center>  <br/>";
else echo "<br/><center> Total Records:  $nrows </center>  <br/>";
}    
}
////////////////////////////////////////////////////////////////////
$title="Get Class Instructors from Registrar";

if (empty($_POST['console'])) {
printhead();
?>
<form method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>">
<input type="submit" name="console" value="<?php echo $title; ?>">
TERM: <input type="textfield" name="term" size="3" value="<?php echo $CFG->currentterm; ?>">
sRS: <input type="textfield" name="srs">
    </form>
<?php
} elseif ($_POST['console'] == "$title") {  # tie-in to link from name lookup
    printhead();
	$term     = $_POST['term'];
	$srs      = $_POST['srs'];
	$stored_procedure = "ccle_CourseInstructorsGet";
    echo "<h3>$title - $stored_procedure</h3>\n";     
    if (!empty($term) and !empty($srs)) {
        $db_conn = odbc_connect($CFG->registrar_dbhost, $CFG->registrar_dbuser, $CFG->registrar_dbpass) or die( "ERROR: Connection to Registrar failed.");
        $result = odbc_exec($db_conn, "EXECUTE $stored_procedure '$term','$srs'")
            or die ("Query failed: error message = " . mysql_error ());

        echo "<table>\n";
        echo "<tr>\n";

        // Show the headings
        // Also store the field names so that we can find field values from the $fields_obj later

            $field_count = odbc_num_fields($result);
            for ($j = 1; $j <=$field_count; $j++) {
                echo "<th>" . odbc_field_name ($result, $j ). "</th>\n";
            }
            echo "</tr>\n";
            $j=$j-1;
        // end of field names

        // Show the content
        $c=0; $nrows=0;
        while(odbc_fetch_row($result)) { // getting data
           $c=$c+1;
           if ( $c%2 == 0 )
               echo "<tr bgcolor=\"#d0d0d0\" >\n";
           else
               echo "<tr bgcolor=\"#eeeeee\">\n";
           for($i=1; $i<=odbc_num_fields($result); $i++) {       
               echo "<td>";
               echo odbc_result($result,$i);
               echo "</td>";        
               if ( $i%$j == 0 ) {
                   $nrows+=1; // counting no of rows   
               }  
           }
           echo "</tr>";
       }

        odbc_close ($db_conn);
        echo "</table>\n";
        if ($nrows==0) echo "<br/><center> Nothing for $term and $srs. Try back later</center>  <br/>";
        else echo "<br/><center> Total Records:  $nrows </center>  <br/>";
   }    
}
////////////////////////////////////////////////////////////////////
$title="Get Class Information from Registrar";

if (empty($_POST['console'])) {
    printhead();
?>
    <form method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>">
    <input type="submit" name="console" value="<?php echo $title; ?>">
    TERM: <input type="textfield" name="term" size="3" value="<?php echo $CFG->currentterm; ?>">
    SRS: <input type="textfield" name="srs">
    </form>
<?php
} elseif ($_POST['console'] == "$title") {  # tie-in to link from name lookup
    printhead();
	$term     = $_POST['term'];
	$srs      = $_POST['srs'];
	$stored_procedure = "ccle_getClasses";
    echo "<h3>$title - $stored_procedure $term $srs</h3>\n";     
    if (!empty($term) and !empty($srs)) {
        $db_conn = odbc_connect($CFG->registrar_dbhost, $CFG->registrar_dbuser, $CFG->registrar_dbpass) or die( "ERROR: Connection to Registrar failed.");
        $result = odbc_exec($db_conn, "EXECUTE $stored_procedure '$term','$srs'")
            or die ("Query failed: error message = " . mysql_error ());
        echo "<table>\n";

        // Show the content
        $c=0; $nrows=0;
        while(odbc_fetch_row($result)) { // getting data
           for($i=1; $i<=odbc_num_fields($result); $i++) {       
               echo "<tr bgcolor=\"#d0d0d0\" ><td><b>" . odbc_field_name ($result, $i ). "</b></td></tr>";
               echo "<tr><td>";
               echo odbc_result($result,$i);
               echo "</td></tr>\n";        
           }
       }

        odbc_close ($db_conn);
        echo "</table>\n";
   }    
}
////////////////////////////////////////////////////////////////////
$title="Library Reserves Class List";

if (empty($_POST['console'])) {
    printhead();
?>
    <form method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>">
    <input type="submit" name="console" value="<?php echo $title; ?>">
    </form>
<?php
} elseif ($_POST['console'] == "$title") {  # tie-in to link from name lookup
    printhead();
    echo "<h3>$title</h3>\n";
    echo '<i>This only shows classes that match on term-srs. You can also check <a href="ftp://ftp.library.ucla.edu/incoming/eres/voyager_reserves_data.txt">The Library Reserves Datafeed</a></i><br><br>';
    $result=mysql_query("select CONCAT('<a href=\"{$CFG->wwwroot}/course/view/',b.shortname,'\">',b.shortname,'</a>') as class,b.idnumber from {$CFG->prefix}ucla_libreserves a inner join {$CFG->prefix}course b on CONCAT(a.term,'-',a.srs)=b.idnumber order by b.shortname", $db_moodle)
        or die(mysql_error());
    $num_rows = mysql_num_rows($result);
    echo "There are $num_rows classes.<P>";
    echo "<table width=60% border=0>\n";
//  echo "<table>\n";
    $cols = 0;
    while ($get_info = mysql_fetch_assoc($result)){
        if($cols == 0) {
            $cols = 1;
            echo "<tr>";
            foreach($get_info as $col => $value) {
                echo "<th align='left'>$col</th>";
            }
            echo "<tr>\n";
        }
        echo "<tr>\n";
        foreach ($get_info as $field) {
            echo "\t<td>$field</td>\n";
        }
        echo "</tr>\n";
    }
    echo "</table>\n";
}
////////////////////////////////////////////////////////////////////
$title="Video-Furnace List by Class";

if (empty($_POST['console'])) {
    printhead();
?>
    <form method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>">
    <input type="submit" name="console" value="<?php echo $title; ?>">
    </form>
<?php
} elseif ($_POST['console'] == "$title") {  # tie-in to link from name lookup
    printhead();
    echo "<h3>$title</h3>\n";
    echo '<i>This only shows classes that match on term-srs.<br>You can also check the <a href="http://164.67.141.31/~guest/VF_LINKS.TXT">Video-Furnace Data Feed (UCLA IP address only.)</a></i><br><br>';
    $result=mysql_query("select CONCAT('<a href=\"{$CFG->wwwroot}/course/view/',b.shortname,'\">',b.shortname,'</a>') as class,b.idnumber,CONCAT('<a href=\"',a.video_url,'\">',a.video_title,'</a>') as video from {$CFG->prefix}ucla_vidfurn a inner join {$CFG->prefix}course b on CONCAT(a.term,'-',a.srs)=b.idnumber order by b.shortname", $db_moodle) or die(mysql_error());
    $num_rows = mysql_num_rows($result);
    echo "There are $num_rows classes.<P>";
    echo "<table>\n";
    $cols = 0;
    while ($get_info = mysql_fetch_assoc($result)){
        if($cols == 0) {
            $cols = 1;
            echo "<tr>";
            foreach($get_info as $col => $value) {
                echo "<th align='left'>$col</th>";
            }
            echo "<tr>\n";
        }
        echo "<tr>\n";
        foreach ($get_info as $field) {
            echo "\t<td>$field</td>\n";
        }
        echo "</tr>\n";
    }
    echo "</table>\n";
}

////////////////////////////////////////////////////////////////////
$title="List Collab Sites";
if (empty($_POST['console'])) {
    printhead();
?>
    <form method="post" action="<?php echo "${_SERVER['PHP_SELF']}"; ?>">
    <input type="submit" name="console" value="<?php echo $title; ?>">
    </form>
<?php
} elseif ($_POST['console'] == "$title") {  # tie-in to link from name lookup
    printhead();
    $result=mysql_query("select "
        . "elt(c.visible + 1, 'Hidden', 'Visible') as Hidden,elt(c.guest + 1, 'Private', 'Public') as Guest,c.format,cc.name, concat('<a href=\"{$CFG->wwwroot}/course/view.php?id=', c.id, '\">', c.shortname, '</a>') as 'Link', c.fullname "
        . "from mdl_course c "
        . "left join mdl_ucla_tasites t using(shortname) "
        . "left join mdl_course_categories cc on c.category=cc.id "
        . 'where idnumber="" '
        . 'and t.shortname is NULL '
        . 'and format <>"uclaredir" '
        . 'and cc.name not in ("To Be Deleted", "Demo/Testing") '
        . 'order by cc.name, c.shortname') or die(mysql_error());
    $days = $_POST['days'];

    echo "<h3>$title</h3>\n";

    $num_rows = mysql_num_rows($result);
    echo "There are $num_rows courses.<P>";
    echo "<table>\n";
    $cols = 0;
    while ($get_info = mysql_fetch_assoc($result)){
		if($cols == 0) {
            $cols = 1;
            echo "<tr>";
            foreach($get_info as $col => $value) {
                echo "<th align='left'>$col</th>";
            }
            echo "<tr>\n";
        }
        echo "<tr>\n";
        foreach ($get_info as $field) {
            echo "\t<td>$field</td>\n";
        }
        echo "</tr>\n";
    }
    echo "</table>\n";
}

////////////////////////////////////////////////////////////////////
$title="Courses with Changed Titles";

if (empty($_POST['console'])) {
    printhead();
?>
    <form method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>">
    <input type="submit" name="console" value="<?php echo $title; ?>">
    </form>
<?php
} elseif ($_POST['console'] == "$title") {  # tie-in to link from name lookup
    printhead();
    echo "<h3>$title</h3>\n";
    $result=mysql_query("SELECT * FROM 
							(SELECT CONCAT('<a target=\"_blank\" href=\"{$CFG->wwwroot}/course/edit.php?id=',b.id,'\">',b.id,'</a>') as id, 
							CONCAT('<a target=\"_blank\" href=\"{$CFG->wwwroot}/course/view.php?id=',b.id,'\">',b.shortname,'</a>') AS Course, 
							if(a.sectiontitle is not NULL , CONCAT(a.coursetitle,': ',a.sectiontitle ),a.coursetitle) AS OldTitle, 
							b.fullname AS NewTitle
							FROM {$CFG->prefix}ucla_reg_classinfo a 
							INNER JOIN {$CFG->prefix}course b 
							ON (CONCAT(a.term,'-',a.srs) = b.idnumber OR CONCAT(a.term,'-Master_',a.srs) = b.idnumber)
							ORDER BY b.shortname
							) t WHERE OldTitle != NewTitle 
						 ") or die(mysql_error());
    $num_rows = mysql_num_rows($result);
    echo "There are $num_rows classes.<P>";
    echo "<table>\n";
    $cols = 0;
    while ($get_info = mysql_fetch_assoc($result)){
		if($cols == 0) {
            $cols = 1;
            echo "<tr>";
            foreach($get_info as $col => $value) {
                echo "<th align='left'>$col</th>";
            }
            echo "<tr>\n";
        }
        echo "<tr>\n";
        foreach ($get_info as $field) {
            echo "\t<td>$field</td>\n";
        }
        echo "</tr>\n";
    }
    echo "</table>\n";
}
//////////////////////////////////////////////////////////////////////////////////////////
$title="Courses with Changed Descriptions";

if (empty($_POST['console'])) {
    printhead();
?>
    <form method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>">
    <input type="submit" name="console" value="<?php echo $title; ?>">
    TERM: <input type="textfield" name="term" size="3" value="<?php echo $CFG->currentterm; ?>">
    </form>
<?php 
} elseif ($_POST['console'] == "$title") {  # tie-in to link from name lookup
    printhead();
	$term     = $_POST['term'];
    echo "<h3>$title $term</h3>\n";
    $result=mysql_query("SELECT * FROM 
							(SELECT CONCAT('<a target=\"_blank\" href=\"{$CFG->wwwroot}/course/edit.php?id=',b.id,'\">',b.id,'</a>') as id, 
							CONCAT('<a target=\"_blank\" href=\"{$CFG->wwwroot}/course/view.php?id=',b.id,'\">',b.shortname,'</a>') AS Course, 
							a.crs_desc AS OldDesc, 
							b.summary AS NewDesc
							FROM {$CFG->prefix}ucla_reg_classinfo a 
							INNER JOIN {$CFG->prefix}course b 
							ON (a.term='$term' and (CONCAT(a.term,'-',a.srs) = b.idnumber OR CONCAT(a.term,'-Master_',a.srs) = b.idnumber))
							ORDER BY b.shortname
							) t WHERE OldDesc != NewDesc
						 ") or die(mysql_error());
    $num_rows = mysql_num_rows($result);
    echo "There are $num_rows classes.<P>";
    echo "<table>\n";
    $cols = 0;
    while ($get_info = mysql_fetch_assoc($result)){
		if($cols == 0) {
            $cols = 1;
            echo "<tr>";
            foreach($get_info as $col => $value) {
                echo "<th align='left'>$col</th>";
            }
            echo "<tr>\n";
        }
        echo "<tr>\n";
        foreach ($get_info as $field) {
            echo "\t<td>$field</td>\n";
        }
        echo "</tr>\n";
    }
    echo "</table>\n";
}
////////////////////////////////////////////////////////////////////
$title="List of Users by Newest Accounts";
if (empty($_POST['console'])) { 
?>
    <form method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>">
    <input type="submit" name="console" value="<?php echo $title; ?>">
    Count: <input type="textfield" name="count" size="3" VALUE="20">
    </form>
<?php
// save for later when figure out how sql query should look    <input type="radio" name="radio" value="unique" CHECKED>Unique Logins
} elseif ($_POST['console'] == "$title") { 
    $days=(int) $_POST['count'];
	$days++;
    $distinct = ""; 
/*
    $db_moodle = mysql_connect($CFG->dbhost,$CFG->dbuser,$CFG->dbpass)or die("Unable to connect to Moodle DB server {$CFG->dbhost}");
    mysql_select_db($CFG->dbname, $db_moodle) or die("Unable to select DB {$CFG->dbname}");
    $days--;  # decrement days by 1 to get query to work
     $result=mysql_query("SELECT 
	    CONCAT('<a target = \"blank\" href=\"{$CFG->wwwroot}/admin/user.php?sesskey=$USER->sesskey&delete=',id,'\">','Delete','</a>') as Action,idnumber, 
	    CONCAT('<a target = \"blank\" href=\"{$CFG->wwwroot}/user/view.php?id=',id,'\">',lastname,'</a>') as lastname, firstname,
		if(timemodified=0,'Never',from_unixtime(timemodified,'%Y-%m-%d')) AS Time_Modified,
		if(firstaccess=0,'Never',from_unixtime(firstaccess,'%Y-%m-%d')) AS First_Access,
		if(lastaccess=0,'Never',from_unixtime(lastaccess,'%Y-%m-%d')) AS Last_Access,
		if(lastlogin=0,'Never',from_unixtime(lastlogin,'%Y-%m-%d')) AS Last_Login
		FROM {$CFG->prefix}user order by id desc limit $days", $db_moodle)
        or die(mysql_error());
      $num_rows = mysql_num_rows($result);
	  echo "<h3>$title</h3>";
        echo "<table width=80% border=0>\n";
        $cols = 0;
        while ($get_info = mysql_fetch_assoc($result)){
            if($cols == 0) {
                $cols = 1;
                echo "<tr>";
                foreach($get_info as $col => $value) {
                    echo "<th align='left'>$col</th>";
                }
                echo "<tr>\n";
            }
            echo "<tr>\n";
            foreach ($get_info as $field) {
                echo "\t<td>$field</td>\n";
            }
            echo "</tr>\n";
        }    
        echo "</table>\n"; 
*/
$days--;
$result=$DB->get_records_sql("SELECT 
	id,
	idnumber,
	lastname, 
        firstname,
                if(timemodified=0,'Never',from_unixtime(timemodified,'%Y-%m-%d')) AS Time_Modified,
                if(firstaccess=0,'Never',from_unixtime(firstaccess,'%Y-%m-%d')) AS First_Access,
                if(lastaccess=0,'Never',from_unixtime(lastaccess,'%Y-%m-%d')) AS Last_Access,
                if(lastlogin=0,'Never',from_unixtime(lastlogin,'%Y-%m-%d')) AS Last_Login
                FROM {$CFG->prefix}user order by id desc limit $days");
if(empty($result))
        {
	        echo"No results were found";
        }
        else
{
$result_val=array_values($result);
$result_keys=array_keys(get_object_vars($result_val[0]));
$result_keys[0] ="Action";
$counter =0;
foreach ($result as $res)
{
	$deleteURL = '<a target = "blank" href="'.$CFG->wwwroot.'/admin/user.php?sesskey='.$USER->sesskey.'&delete='.$res->id.'">Delete</a>';
	$userURL= '<a target = "blank" href="'.$CFG->wwwroot.'/user/view.php?id='.$res->id.'">'.$res->lastname.'</a>';
	$res->id=$deleteURL;
	$res->lastname = $userURL;
	$result_val[$counter]= $res;

	$counter++;
}

createTable($result_keys, $result_val);
}
}
////////////////////////////////////////////////////////////////////
// START SSC #775 - Adding missing reports from SSC into CommonCode
/////////////////////////////////////////////////////////////////////////
///// Moodle 2.0 does not have TA Sites any longer thus this code was commented out
/*
$title="List of TA Sites by Term";
if (empty($_POST['console'])) { 
    printhead();
    ?>
    <form method="post" action="<?php echo "${_SERVER['PHP_SELF']}"; ?>">
    <input type="submit" name="console" value="<?php echo $title; ?>">
    TERM: <input type="textfield" name="term" size="3" VALUE="<?php echo $CFG->currentterm; ?>">
    </form>
    <?php
} elseif ($_POST['console'] == "$title") { 
    printhead();
    $term=$_POST['term'];
    $distinct = ""; 

	$db_moodle = mysql_connect($CFG->dbhost,$CFG->dbuser,$CFG->dbpass)or die("Unable to connect to Moodle DB server {$CFG->dbhost}");
    mysql_select_db($CFG->dbname, $db_moodle) or die("Unable to select DB {$CFG->dbname}");
    $result = mysql_query("SELECT 
     CONCAT('<a target = \"blank\" href=\"{$CFG->wwwroot}/course/view/', shortname ,'\">',shortname,'</a>') as course,
     if(timestamp=0,'',from_unixtime(timestamp,'%y-%m-%d %r')) as timestamp,
     CONCAT('<a target = \"blank\" href=\"{$CFG->wwwroot}/user/view.php?id=',b.id,'\">',b.firstname,' ',b.lastname,'</a>') as TA,
     concat(c.firstname, ' ', c.lastname) as creator , type 
     FROM {$CFG->prefix}ucla_tasites a 
     LEFT JOIN {$CFG->prefix}user b ON a.taid=b.id 
     LEFT JOIN {$CFG->prefix}user c on c.id = a.creator_id 
     WHERE shortname like '$term%' ORDER BY shortname", $db_moodle)
        or die(mysql_error());
    $num_rows = mysql_num_rows($result);
    print "<br/><br/>There are $num_rows classes.<P>";
    print "<h3>$title</h3>";
    print "<table class='tablesorter' width=80%>\n";
    $cols = 0;
    while ($get_info = mysql_fetch_assoc($result)){
        if($cols == 0) {
            $cols = 1;
            print "<thead><tr>";
            foreach($get_info as $col => $value) {
                print "<th align='left'>$col</th>";
            }
            print "<tr></thead><tbody>\n";
        }
        print "<tr>\n";
        foreach ($get_info as $field) {
            print "\t<td>$field</td>\n";
        }
        print "</tr>\n";
    }    
    print "</tbody></table>\n"; 
}*/
////////////////////////////////////////////////////////////////////
$title="Courses with no syllabus";

if (empty($_POST['console'])) {
    printhead();
    ?>
    <form method="post" action="<?php echo "${_SERVER['PHP_SELF']}"; ?>">
    <input type="submit" name="console" value="<?php echo $title; ?>">
    TERM: <input type="textfield" name="term" size="3" VALUE="<?php echo $CFG->currentterm; ?>">
    </form>
    <?php
} elseif ($_POST['console'] == "$title") {  # tie-in to link from name lookup
    echo "<h3>$title for {$_POST['term']}</h3>\n";
    $result=$DB->get_records_sql("select id,idnumber,
                         shortname
                         from {$CFG->prefix}course 
                         where idnumber like '{$_POST['term']}%'
                         and visible=1 
                         and id not in 
                            (select a.id 
                            from {$CFG->prefix}course a 
                            inner join {$CFG->prefix}resource b on a.id=b.course 
                            where a.idnumber like '{$_POST['term']}%' and (b.name LIKE '%course description%' OR b.name LIKE '%course outline%' OR b.name LIKE '%syllabus%'))  
                        group by shortname
                        order by shortname
						 ");
//CONCAT('<a target = \"blank\" href=\"{$CFG->wwwroot}/course/view/', shortname ,'\">',shortname,'</a>') as shortname
  if(empty($result))
{
        echo"No results were found";
}
else
{
$num_rows = sizeof($result);
if($num_rows == 1)
{
 print "There is 1 class.<P>";
}
else
{
 print "There are $num_rows classes.<P>";
}
$result_val=array_values($result);
$result_keys=array_keys(get_object_vars($result_val[0]));
unset($result_keys[0]);
$counter=0;
foreach($result_val as $res)
{
        $saved='<a target="_blank" href='.$CFG->wwwroot.'/course/view.php?id='.$res->id.'>'.$res->shortname.'</a>';
                $res->shortname = $saved;
		unset($res->id);
                $result_val[$counter]= $res;
                $counter++;

}
createTable($result_keys,$result_val);
}
}
/*
//  print "<table width=60% border=0>\n";
    print "<table>\n";
    $cols = 0;
    while ($get_info = mysql_fetch_assoc($result)){
		if($cols == 0) {
            $cols = 1;
            print "<tr>";
            foreach($get_info as $col => $value) {
                print "<th align='left'>$col</th>";
            }
            print "<tr>\n";
        }
        print "<tr>\n";
        foreach ($get_info as $field) {
            print "\t<td>$field</td>\n";
        }
        print "</tr>\n";
    }
    print "</table>\n";*/

////////////////////////////////////////////////////////////////////
$title="Assignments and Quizzes Due Soon from Date";

if (empty($_POST['console'])) {
    ?>
    <form method="post" action="<?php echo "${_SERVER['PHP_SELF']}"; ?>">
    <input type="submit" name="console" value="<?php echo $title; ?>">
    Start (MM/DD/YYYY): <input type="textfield" name="start" size="10" VALUE="<?php echo date('m/d/Y') ?>">
    Days From Start: <input type="textfield" name="days" size="3" VALUE="7">
    
    </form>
    <?php
} elseif ($_POST['console'] == "$title") {  # tie-in to link from name lookup
    $days = $_POST['days'];
    echo "<h3>$title</h3>\n";
    $timefrom = strtotime($_POST['start']);
    echo '<h2> From ' . date('m/d/Y', $timefrom) . ' due until ' . date('m/d/Y', $timefrom + $days * 24 * 3600) . '</h2>';
$result=$DB->get_records_sql("SELECT c.id , m.Due_date , c.shortname as Class , c.Fullname, m.modtype, m.Name
    FROM ((
            SELECT 'quiz' AS modtype, course, name, from_unixtime( timeclose ) AS Due_Date
            FROM `mdl_quiz`
            WHERE timeclose
            BETWEEN  {$timefrom}
            AND {$timefrom} + {$days}*24*3600
        ) UNION (
            SELECT 'assignment' AS modtype, course, name, from_unixtime( timedue , '%m-%d-%y %H:%i %a') AS Due_Date
            FROM mdl_assignment
            WHERE timedue
            BETWEEN {$timefrom}
            AND {$timefrom} + {$days}*24*3600
        )
    ) AS m
    INNER JOIN mdl_course c ON c.id = m.course
    ORDER BY `m`.`Due_Date` ASC
                                                 ");    
/*
$result=mysql_query("
    SELECT m.Due_Date, CONCAT('<a href=\"{$CFG->wwwroot}/course/view/',c.shortname,'\">',c.shortname,'</a>') as Class, c.Fullname, m.modtype, m.Name
    FROM ((
            SELECT 'quiz' AS modtype, course, name, from_unixtime( timeclose ) AS Due_Date
            FROM `mdl_quiz`
            WHERE timeclose
            BETWEEN  {$timefrom}
            AND {$timefrom} + {$days}*24*3600
        ) UNION (
            SELECT 'assignment' AS modtype, course, name, from_unixtime( timedue , '%m-%d-%y %H:%i %a') AS Due_Date
            FROM mdl_assignment
            WHERE timedue
            BETWEEN {$timefrom}
            AND {$timefrom} + {$days}*24*3600
        )
    ) AS m
    INNER JOIN mdl_course c ON c.id = m.course
    ORDER BY `m`.`Due_Date` ASC
						 ") or die(mysql_error());
                         
  */                     
  if(empty($result))
{
        echo"No results were found";
}
else
{
$num_rows = sizeof($result);
if($num_rows == 1)
{
 print "There is $num_rows assignment/quiz.<P>";

}

else
{
 print "There are $num_rows assignments/quizzes.<P>";
}
$result_val=array_values($result);
$result_keys=array_keys(get_object_vars($result_val[0]));
unset($result_keys[0]);
$counter=0;
foreach($result_val as $res)
{
        $saved='<a target="_blank" href='.$CFG->wwwroot.'/course/view.php?id='.$res->id.'>'.$res->class.'</a>';
                $res->class= $saved;
                unset($res->id);
                $result_val[$counter]= $res;
                $counter++;

}
createTable($result_keys,$result_val);
}
/*//  print "<table width=60% border=0>\n";
    print "<table>\n";
    $cols = 0;
    while ($get_info = mysql_fetch_assoc($result)){
		if($cols == 0) {
            $cols = 1;
            print "<tr>";
            foreach($get_info as $col => $value) {
                print "<th align='left'>$col</th>";
            }
            print "<tr>\n";
        }
        print "<tr>\n";
        foreach ($get_info as $field) {
            print "\t<td>$field</td>\n";
        }
        print "</tr>\n";
    }
    print "</table>\n";
*/
}

////////////////////////////////////////////////////////////////////
// START SSC MODIFICATION #678
$title="List syllabus count percentages in categories: None, Public, Private";

$db_moodle = mysql_connect($CFG->dbhost,$CFG->dbuser,$CFG->dbpass)
    or die("Unable to connect to Moodle DB server {$CFG->dbhost}");
mysql_select_db($CFG->dbname, $db_moodle) or die("Unable to select DB {$CFG->dbname}");
$result=mysql_query("select left(idnumber,3) as term,count(*) as cnt from {$CFG->prefix}course group by term", $db_moodle) or die(mysql_error());

if (empty($_POST['console'])) {
    printhead();
?>
    <form method="post" action="<?php echo "${_SERVER['PHP_SELF']}"; ?>">
    <input type="submit" name="console" value="<?php echo $title; ?>">
    <select name="term">
    <option value="">TERM (count)</option>
<?php
    while ($row = mysql_fetch_assoc($result)) {
        if (!empty($row['term'])) {
            echo  '<option value="'.$row['term'].'"'.($row['term'] == $CFG->currentterm ? ' selected' : '').'>'.$row['term'].'  ('.$row['cnt'].")</option>\n";
        }
    }
?>
    <input type="checkbox" name="draw" >Display Courses</input>
    <input type="checkbox" name="sort" >Sort by Syllabus Status</input>
    </form>
    
<?php
} elseif ($_POST['console'] == "$title") {  # tie-in to link from name lookup
    printhead();
    $uterm = $_POST['term'];
    $draw_tables = isset($_POST['draw']) ? ($_POST['draw'] == 'on') : false;
    $sortbysyl = isset($_POST['sort']) ? ($_POST['sort'] == 'on') : false;
    
    echo "<h3>$title</h3>\n";
    
    //echo $draw_tables;
    echo "For term  $uterm. ";
    if ($draw_tables) {
        echo "Print all courses. ";
        if ($sortbysyl) {
            echo "Sort by syllabus. ";
        }
    }
    
    ////////////////////////////// Get master and normal courses only
    $sql_query = 
    "SELECT c.id as cid, c.shortname, c.idnumber, r.name, cs.title, cs.summary, cs.sequence, cm.groupmembersonly, "
        . "c.grouppublicprivate, c.guest "
        . "FROM {$CFG->prefix}course c "
        . "LEFT JOIN {$CFG->prefix}resource r ON r.course = c.id AND r.name LIKE '%yllabus%' "
        . "LEFT JOIN {$CFG->prefix}course_sections cs ON cs.course = c.id AND cs.title LIKE '%yllabus%' "
        . "LEFT JOIN {$CFG->prefix}course_modules cm ON cm.instance = r.id "
        . "WHERE c.idnumber LIKE '$uterm%' "
        . "AND c.id NOT IN "
            . "(SELECT DISTINCT child_course FROM {$CFG->prefix}course_meta "
            . "WHERE parent_course IN "
                . "(SELECT DISTINCT id FROM {$CFG->prefix}course WHERE idnumber LIKE '$uterm%')"
            . " )"
        . "ORDER BY c.shortname";

    $result = mysql_query($sql_query) or die(mysql_error());
   
    $num_rows = mysql_num_rows($result);

    echo "<table border=1>\n";
        
    $cs = array();    
    $atci = array();
    
    $cols = 0;
    while ($get_info = mysql_fetch_assoc($result)) {

        if (!isset($atci[$get_info['cid']])) {
            $atci[$get_info['cid']] = array('cid' => $get_info['cid'], 'shortname' => $get_info['shortname'],
                'idnumber' => $get_info['idnumber']);
        }
        
        // Indexing:
        // $cs[cid] == 0 means private
        // $cs[cid] > 0 means public
        // $cs[cid] < 0 means no syllabus
        // Let's reset this if we have never checked this course
        // OR if this course previously has a no/private syllabus
        if (!isset($cs[$get_info['cid']]) || $cs[$get_info['cid']] < 0) {
            $cs[$get_info['cid']] = 0;
        }
        
        // They have a resource called syllabus
        if (isset($get_info['name'])) {
            // If the course has publicprivate enabled AND
            // The module is set to viewed to private
            if (isset($get_info['grouppublicprivate']) && $get_info['grouppublicprivate'] == 1 
                && isset($get_info['groupmembersonly']) && $get_info['groupmembersonly'] == 1) {
                // Private
               
            } else {
                $cs[$get_info['cid']]++;
            }
        }
        
        // This means that they have a section remotely called Syllabus
        if (isset($get_info['title'])) {
            // If they have content summary
            if (isset($get_info['summary'])) {
                // If there can be guests AND
                // PublicPrivate is disabled
                // Then public site
                if (isset($get_info['guest']) && $get_info['guest'] == 1
                    && isset($get_info['grouppublicprivate']) && $get_info['grouppublicprivate'] == 0) {
                    $cs[$get_info['cid']]++;
                } else {
                    
                }
            } 
            
            // If they have content in the section
            if (isset($get_info['sequence']) && !empty($get_info['sequence'])) {
                // Quick check, is the site public?
                if (isset($get_info['guest']) && $get_info['guest'] == 1
                    && isset($get_info['grouppublicprivate']) && $get_info['grouppublicprivate'] == 0) {
                    
                    $cs[$get_info['cid']]++;
                } else {
                    // No, goddammit, now we have to go through each sequence?
                    $sql_query = 
                        "SELECT cm.groupmembersonly "
                    . "FROM {$CFG->prefix}course_modules cm "
                    . "WHERE cm.instance IN (" . $get_info['sequence'] . ")";
          
                    $result_seq = mysql_query($sql_query) or die(mysql_error());                    
                    while ($seq = mysql_fetch_assoc($result_seq)) {
                        if (isset($seq['groupmembersonly']) && $seq['groupmembersonly'] == 0) {
                            $cs[$get_info['cid']]++;
                        }
                    }
                }
            }
            
            // This means they have a title but no summary or sequence, 
            // No syllabus in a syllabus section!
            if (!isset($get_info['summary']) && !isset($get_info['sequence']) && !empty($get_info['sequence'])) {
                $cs[$get_info['cid']] = -1;
            }
            
        } 
        
        // No resource called syllabus and no 
        if (!isset($get_info['title']) && !isset($get_info['name'])) {
            $cs[$get_info['cid']] = -1;
        }
    }
    echo "</table>\n";
   
    $count_none = 0;
    $count_private = 0;
    $count_public = 0;
    
    /////////////////////////////////// Cross check with child courses
    $sql_query = 
    "SELECT c.id as cid, c.shortname, c.idnumber, cm.child_course "
        . "FROM mdl_course c "
        . "LEFT JOIN {$CFG->prefix}course_meta cm ON cm.parent_course = c.id "
        . "WHERE c.idnumber LIKE '$uterm%' "
        . "ORDER BY c.id";

    $result = mysql_query($sql_query) or die(mysql_error());
   
    $count_helper = array();
    $child_record = array();
    $child_rh = array();
    // Get a list of parent*children and normal courses
    while ($get_info = mysql_fetch_assoc($result)) {
        if (isset($cs[$get_info['cid']])) {
            
            if (!isset($count_helper[$get_info['cid']])) {
                $count_helper[$get_info['cid']] = 0;
            }
            
            $count_helper[$get_info['cid']]++;
            
            if (isset($get_info['child_course'])) {
                // This is a list of child courses, that have been
                // counted towards a master course
                $child_rh[$get_info['child_course']] = $get_info;
            }
        } else {
            // All these courses should be child courses 
            $child_record[$get_info['cid']] = $get_info;
        }
    }

    $null_count = 0;
    foreach($child_record as $crk => $cr) {
        if(!isset($child_rh[$crk])) {
            $null_count++;
            // Keep this.
            //echo $cnt . " $crk: " . $cr['shortname'] . " " . $cr['idnumber'] . "<br>\n";
            //$record = get_records_sql("SELECT * FROM mdl_course_meta WHERE child_course = '" . $crk . "'");
            //foreach($record as $facts) {
                //echo "Parent course: " . $facts->parent_course . " ";
                //$courser = get_records_sql("SELECT * FROM mdl_course WHERE id = '" . $facts->parent_course . "'");
                // This should give you sites that are child courses because they have
                // TA Sites, which makes them a child course to a non-existant metacourse
                //echo "<br>";
            //}
        }
    }
    
    echo "<BR>";
    
    ///////////////////////////////////// DISPLAY INFORMATION
    $child_cnt = count($child_record);

    echo "Count of records (meta + normal courses): " . count($cs) . "<BR>";
    
    echo "There are $null_count invalid child courses.<BR>";
    
    foreach ($cs as $key => $value) {
        if (!isset($count_helper[$key])) {
            $count_helper[$key] = 1;
        }
        
        if ($value < 0) {
            $count_none += $count_helper[$key];
        }
        
        if ($value == 0) {
            $count_private += $count_helper[$key];
        }
        
        if ($value > 0) {
            $count_public += $count_helper[$key];
        }
    }           
    
    /////////////////// GENERATE CROSS REFERENCES ////////////////////
    $records = get_records_sql("SELECT * FROM {$CFG->prefix}course WHERE idnumber LIKE '$uterm%'");
    
    
    // The analyzed courses        
    $total_checked = ($count_none + $count_private + $count_public);
    
    echo "There are " . $total_checked . " courses (child + normal courses) in analysis. <br>";
    
    echo "NONE: $count_none (" . number_format($count_none / $total_checked * 100, 0) . "%) <br>";
    echo "PRIVATE: $count_private (" . number_format($count_private / $total_checked * 100, 0) . "%)<br>";
    echo "PUBLIC: $count_public (" . number_format($count_public / $total_checked * 100, 0) . "%)<br>";
    echo "That means " . ($count_private +  $count_public) . " (" 
        . number_format(($count_public + $count_private) / $total_checked * 100, 0) . "%) "
        . " have syllabi.<br>";
        
    $cols = 0;
    if ($draw_tables) {
        echo '<table border=1>';
        $keyset = array();
        
        // Append additional data
        foreach($atci as $key => $value) {
            $value['syllabus'] = $cs[$key];
            $value['count'] = $count_helper[$key];
            $atci[$key] = $value;
            if ($sortbysyl) {
                $keyset[$cs[$key]] = $cs[$key];
            }
        }
            
        if ($sortbysyl) {
            // Shortsort
            sort($keyset);
            
            $atcis = array();
            foreach($keyset as $key => $value) {
                foreach($atci as $akey => $avalue) {
                    if ($value == $avalue['syllabus']) {
                        $atcis[$akey] = $avalue;
                    }
                }
            }
        } else {
            $atcis = $atci;
        }
        
        foreach($atcis as $key => $value) {
            if($cols == 0) {
                $cols = 1;
                print "<tr>";
                foreach($value as $col => $row) {
                    print "<th align='left'>$col</th>";
                }
                print "<tr>\n";
            }
            print "<tr>\n";
            foreach ($value as $title => $field) {
                print "\t<td>";
                if ($title == 'syllabus') {
                    if ($field < 0) {
                        echo '<span style="color:black;">None</span>';
                    } else if ($field == 0) {
                        echo '<span style="color:red;">Private</span>';
                    } else if ($field > 0) {
                        echo '<span style="color:green;">Public</span>';
                    }
                } else {
                    if ($title == 'shortname') {
                        echo '<a href="';
                        echo $CFG->wwwroot . "/course/view.php?id=" . $key;
                        echo '">' . $field . '</a>';
                    } else {
                        echo $field;
                    }
                    
                }
                echo "</td>\n";
            }
            print "</tr>\n";
        }
        
        echo '</table>';
    }
}
// END SSC MODIFICATION #678
// END SSC MODIFICATION #775
////////////////////////////////////////////////////////////////////
/** Removing this section for now, if sequencereport.php is functioning, uncomment this block.
$title="Course Sections With Malformed Sequence";
if (empty($_POST['console'])) { 
    printhead();
?>
    <form method="post" action="sequencereport.php">
        <input type="submit" name="console" value="<?php echo $title; ?>">
    </form>
<?php
}**/


///////////////////////////////////////////////////////////////////////
// START SSC MODIFICATION #828 : SRDB tests moved to support console //
///////////////////////////////////////////////////////////////////////
$title = "SRDB Stored Procedure Tests";

if (empty($_POST['console'])) {
    printhead();
?>

    <form method="POST" action="<?php echo $_SERVER['PHP_SELF'] ?>">
    <input type="submit" name="console" value="<?php  echo $title ?>">
    <input type="text" name="query">
    <input type="checkbox" name="tabled">Display in tables</input>
    - - (e.g. ccle_ClassCalendar,10F,180052200)
    </form>
<?php

} else if ($_POST['console'] == $title) {
    printhead();

    echo "<br>";

    $tablarized = isset($_POST['tabled']);
    $the_query = $_POST['query'];

    // Handle it. DB handles that is.
    $db_conn = odbc_connect($CFG->registrar_dbhost, $CFG->registrar_dbuser, $CFG->registrar_dbpass);

    if (!$db_conn) {
        echo "ERROR - Cannot connect to registrar database host " . $CFG->registrar_dbhost . "<br>";
    } else {
        if ($the_query != '') {
            // Finally we will do the stored procedure
            $odbc_params = explode(',', $the_query);
    
            // Build the query
            $odbc_function = array_shift($odbc_params);

            $odbc_query = 'EXECUTE ' . $odbc_function;
            $odbc_query .= " '" . implode("', '", $odbc_params) . "'";

            echo 'Running query: [' . $odbc_query . ']';

        } else {
            echo "Man, you gave us an empty query. We cannot do anything with that.";
        }
    }

    if (isset($odbc_query)) {
        $result = odbc_exec($db_conn, $odbc_query);

        if ($result) {
            // Store results
            for ($i = 1; $i <= odbc_num_fields($result); $i++) {
                $fields[] = odbc_field_name($result, $i);
            }

            while ($row = odbc_fetch_array($result)) {
                $rows[] = $row;
            }

            if ($tablarized) {
                // Draw results
                echo '<table border="1">';
                echo '<tr>';

                foreach ($fields as $field) {
                    echo '<th>';

                    echo $field;

                    echo '</th>';
                }

                echo '</tr>';

                $colored_back = false;
                foreach ($rows as $row) {
                    echo '<tr ';
                    if ($colored_back) {
                        echo 'bgcolor="#eeeeee"';
                    }                
                    echo '>';

                    $colored_back = !$colored_back;

                    foreach ($row as $fiel => $data) {                
                        echo '<td>';

                        echo $data;

                        echo '</td>';
                    }

                    echo '</tr>';
                }

                echo '</table>';
            } else {
                echo '"' . implode('","', $fields) . '"<br>';
                foreach ($rows as $row) {
                   echo '"' . implode('","', $row) . '"<br>';
                }
            }
        }
    }
}

// END SSC MODIFICATION #828
//////////////////////////////////////////////////////////////////////////////////////////
$title="Count of Modules by Course";

if (empty($_POST['console'])) {
?>
    <form method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>">
    <input type="submit" name="console" value="<?php echo $title; ?>">
    </form>
<?php 
} elseif ($_POST['console'] == "$title") {  # tie-in to link from name lookup
    echo "<h3>$title</h3>\n";
    
    // Mapping of [course shortname, module name] => count of instances of this module in this course
    // count($course_indiv_module_counts[<course shortname>]) has the number kinds of modules used in this course
    $course_indiv_module_counts = array();
    
    // Mapping of course shortname => count of instances of all modules in this course
    $course_total_module_counts = array();
    
    $result=$DB->get_records_sql("SELECT cm.id,c.shortname AS c_shortname, m.name AS m_name, count( * ) AS cnt
                         FROM mdl_course c
                         JOIN mdl_course_modules cm ON c.id = cm.course
                         JOIN mdl_modules m ON cm.module = m.id
                         GROUP BY c.id, m.id");

$result_val=array_values($result);
$result_keys=array("Course",	"Module name", "Instance count (module)", "Modules count (course)","Modules instance count (course)");

$counter=0;
foreach($result_val as $row){
        if (isset($course_indiv_module_counts[$row->c_shortname][$row->m_name]))
            $course_indiv_module_counts[$row->c_shortname][$row->m_name] += $row->cnt;
        else
            $course_indiv_module_counts[$row->c_shortname][$row->m_name] = $row->cnt;
        
        if (isset($course_total_module_counts[$row->c_shortname]))
            $course_total_module_counts[$row->c_shortname] += $row->cnt;
        else
            $course_total_module_counts[$row->c_shortname] = $row->cnt;
    }
$counter =0;
foreach($result_val as $res){
	$res->indiv_mc ="<div align=center>".$course_indiv_module_counts[$res->c_shortname][$res->m_name]."</div>";
	$res->ttl_mc ="<div align=center>".$course_total_module_counts[$res->c_shortname]."</div>";
	$rescnt= "<div align=center>".$res->cnt."</div>";
	$res->cnt = $rescnt;
	$res->c_shortname ='<a target = "blank" href='.$CFG->wwwroot.'/course/view.php?id='.$res->id.'>'.$res->c_shortname.'</a>';
	unset($res->id);
	$result_val[$counter]=$res;
	$counter ++;
}
createTable($result_keys, $result_val);
/*
    echo "<table>\n";
    echo "<tr>\n";
    echo "<th>Course</th>\n";
    echo "<th>Modules count (course)</th>\n";
    echo "<th>Modules instance count (course)</th>\n";
    echo "<th>Module name</th>\n";
    echo "<th>Instance count (module)</th>\n";
    echo "</tr>\n";
    $cols = 0;
    while ($row = mysql_fetch_assoc($result)){
        if (isset($course_indiv_module_counts[$row['c_shortname']][$row['m_name']]))
            $course_indiv_module_counts[$row['c_shortname']][$row['m_name']] += $row['cnt'];
        else
            $course_indiv_module_counts[$row['c_shortname']][$row['m_name']] = $row['cnt'];
        
        if (isset($course_total_module_counts[$row['c_shortname']]))
            $course_total_module_counts[$row['c_shortname']] += $row['cnt'];
        else
            $course_total_module_counts[$row['c_shortname']] = $row['cnt'];
    }
    
    foreach ($course_total_module_counts as $course_shortname => $course_total_module_instance_count) {
        foreach ($course_indiv_module_counts[$course_shortname] as $module_name => $module_instance_count) {
            echo "<tr>\n";
      #     echo "<td>" . $course_shortname . "</td>\n";
            echo "<td>" . "<a target = \"blank\" href=\"{$CFG->wwwroot}/course/view/".$course_shortname."\">".$course_shortname."</a>\n";
            echo "<td align=center>" . count($course_indiv_module_counts[$course_shortname]) . "</td>\n";
            echo "<td align=center>" . $course_total_module_instance_count . "</td>\n";
            echo "<td>" . $module_name . "</td>\n";
            echo "<td align=center>" . $module_instance_count . "</td>\n";
            echo "</tr>\n";
        }
    }
    echo "</table>\n";
*/
}

?>
        </td>
    </tr>
</table>

<?php
echo $OUTPUT->footer();
?>  
