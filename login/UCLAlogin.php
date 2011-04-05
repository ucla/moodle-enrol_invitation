<?php // $Id: index.php,v 1.99.2.2 2007-06-20 16:00:00 Eric Bollens Exp $

// Modified 200706191502 by Eric Bollens to Remove Hardcoding
// Shibboleth requires HTTPS
require_once("../config.php");

httpsrequired();
$CFG->httpswwwroot = str_replace("http://", "https://", $CFG->httpswwwroot);

/** Modified 20071214 by Jovca
 If the user got here via standard Moodle login redirect, he'll have the string
 "shibboleth" as a GET parameter, thanks to the Moodle setting for alternative login URL.
 If so, initiate Shibb login. Safety fallback: user coming to the page in an unexpected way
 will not have the ?shibboleth path, and login page will be displayed as usual.

 In order for this code to work, Shibboleth module in Moodle has to be configured to use 
 "UCLAlogin.php?shibboleth" for Alternate Login URL
*/ 
// check for error msg during special cases login, skip redirect if there's one
if (empty($SESSION->ucla_login_error)) {
	$errormsg = false;
} else {
	$errormsg = $SESSION->ucla_login_error;
	$SESSION->ucla_login_error = NULL; // clear the error msg
}
if (!$errormsg and isset($_GET['shibboleth'])) {
  redirect("$CFG->httpswwwroot/auth/shibboleth/index.php");
  exit();
}

// In case of Moodle timeout, redirect to shibboleth login page too
if (isset($_GET['errorcode']) && $_GET['errorcode'] == 4) {
  redirect("$CFG->httpswwwroot/auth/shibboleth/index.php");
  exit();
}

// Modified on 200706201600 by Eric Bollens
// Original Modification on 200704201421 by Mike Franks and Keith Rozett
 /// Check for timed out sessions
 if (!empty($SESSION->has_timed_out)) {
     $session_has_timed_out = true;
     $SESSION->has_timed_out = false;
 } else {
     $session_has_timed_out = false;
 }

 if ($session_has_timed_out) {
     $errormsg = get_string('sessionerroruser', 'error');
 }

 if (get_moodle_cookie() == '') {
     set_moodle_cookie('nobody');   // To help search for cookies
 }

 if (isset($CFG->auth_instructions)) {
     $CFG->auth_instructions = trim($CFG->auth_instructions);
 }
 if ($CFG->auth == "email" or $CFG->auth == "none" or !empty($CFG->auth_instructions)) {
     $show_instructions = true;
 } else {
     $show_instructions = false;
 }

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html dir="ltr" lang="en_us" xml:lang="en_us">
<head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8" />

<!-- Modified 200706191108 by Eric Bollens to Remove Hardcoding -->
<link rel="stylesheet" type="text/css" href="../theme/standard/styles.php" />
<link rel="stylesheet" type="text/css" href="../theme/ucla/styles.php" />

    <meta name="keywords" content="moodle, UCLA CCLE Moodle testing: Login to the site " />
    <title>UCLA CCLE Moodle testing: Login to the site</title>

	<!-- Modified 200706191108 by Eric Bollens to Remove Hardcoding -->
    <link rel="shortcut icon" href="../theme/ucla/favicon.ico" />

    <!--<style type="text/css">/*<![CDATA[*/ body{behavior:url(/lib/csshover.htc);} /*]]>*/</style>-->

<!-- Modified 200706191108 by Eric Bollens to Remove Hardcoding -->
<script language="JavaScript" type="text/javascript" src="../lib/javascript-static.js"></script>
<script language="JavaScript" type="text/javascript" src="../lib/javascript-mod.php"></script>
<script language="JavaScript" type="text/javascript" src="../lib/overlib.js"></script>
<script language="JavaScript" type="text/javascript" src="../lib/cookies.js"></script>

<script language="JavaScript" type="text/javascript" defer="defer">

<!-- // Non-Static Javascript functions

setTimeout('fix_column_widths()', 20);

function openpopup(url,name,options,fullscreen) {
<!--  fullurl = "http://ccle.ucla.edu" + url; -->
  fullurl = "" + url;
  windowobj = window.open(fullurl,name,options);
  if (fullscreen) {
     windowobj.moveTo(0,0);
     windowobj.resizeTo(screen.availWidth,screen.availHeight);
  }
  windowobj.focus();
  return false;
}

function uncheckall() {
  void(d=document);
  void(el=d.getElementsByTagName('INPUT'));
  for(i=0;i<el.length;i++)
    void(el[i].checked=0)
}

function checkall() {
  void(d=document);
  void(el=d.getElementsByTagName('INPUT'));
  for(i=0;i<el.length;i++)
    void(el[i].checked=1)
}

function inserttext(text) {
  text = ' ' + text + ' ';
  if ( opener.document.forms['theform'].message.createTextRange && opener.document.forms['theform'].message.caretPos) {
    var caretPos = opener.document.forms['theform'].message.caretPos;
    caretPos.text = caretPos.text.charAt(caretPos.text.length - 1) == ' ' ? text + ' ' : text;
  } else {
    opener.document.forms['theform'].message.value  += text;
  }
  opener.document.forms['theform'].message.focus();
}
<?php // BEGIN CCLE MODIFICATION CCLE-1583
      // this now gives focus to special cases login
?>
function setfocus() { if(document.login) document.login.username.focus(); }

// done hiding -->
</script>


<style type="text/css">
div.loginbox {
width: 450px;
margin: auto;
margin-top: 30px;
margin-bottom: 30px;
border: 1px solid #ccc;
padding-bottom: 5px;
}
 
div.loginboxContent:after {
    content: ".";
    display: block;
    height: 0;
    clear: both;
    visibility: hidden;
}
html>body div.loginboxContent:after {
*display: inline-block;
}
 
 
 
div.loginbox h2 {
background: #536895;
color: #fff;
font-size: 16px;
text-align: center;
padding: 1px 0 1px 0;
margin: 0;
}
 
div.loginbox p {
text-align: left;
font-size: 14px;
}
 
div.ucla p.hasPassword {
width: 270px;
float: left;
margin-left: 20px;
margin-top: 20px;
}
 
 
/*\*/ * html div.ucla p.hasPassword {
margin-left: 10px;
}/**/
 
div.ucla form input {
float: left;
width: 7em;
margin-left: 30px;
margin-top: 35px;
}
 
html>body div.ucla form input {
*margin-top: 15px;
}
 
/*\*/ * html div.ucla form input {
margin-top: 15px;
}/**/
 

/*
div.ucla p.resetPassword {
clear: both;
text-align: center;
margin-bottom: 3px;
padding-top: 10px;
}

div.ucla span.uclaForgot {
display: block;
width: 300px;
height: 30px;
margin: auto;
text-align: center;
}

div.ucla span.uclaForgot input {
float: none;
margin: 3px 0 0 0!important;
width: 9em;
}
*/

div.ucla p.resetPassword {
clear: both;
float: left;
margin-left: 20px;
display: inline;
width: 270px;
}
 
 
div.ucla span.uclaForgot input {
float: left;
width: 9em;
margin-left: 3px;
margin-top: 10px;
}
 
/*\*/ html * div.ucla span.uclaForgot input {
[margin-left: 3px;
margin-left: 6px;
]margin-left: 3px;
[margin-top: 0px;
margin-top: 13px;
]margin-top: -3px;
}/**/

/*********************IE fixes ******************/
 
html>body div.loginField {
*height: 1%;
}
 
* html div.loginField {
height: 1%
}
 
 
/***********************Special Cases   ******/
 
div.specialCases p {
text-align: center;
}
 
div.specialCases div.loginField {
margin-top: 25px;
}
 
div.specialCases ul.username,
div.specialCases ul.password {
margin: 0 0 0 80px;
padding: 0;
list-style: none;
float: left;
width: 208px;
display: inline;
}
 
div.specialCases ul.username {
margin-bottom: 4px;
}
 
div.specialCases ul.username li,
div.specialCases ul.password li {
margin: 0;
padding: 0 5px 0 0;
list-style: none;
display: inline;
font-size: 14px;
}
 
div.specialCases ul.password li {
/*padding: 0  8px 0 0; */ <?php // CCLE-1583 - this causes rendering bug in Chrome ?>
}
 
/*\*/ * html div.specialCases ul.password li input {
width: 119px;
} /**/
 
form#login ul.username li input,
form#login ul.password li input {
margin: 0!important;
padding: 0!important;
float: right; <?php // CCLE-1583 ?>
}
 
div.loginField form span.loginLocal {
float: left;
width: 4em;
height: 1.5em;
margin: 0 0 0 0px;
text-align: center;
position: relative;
top: -12px;
clear:right;        <?php // CCLE-1583 ?>
margin-left:10px;
}
 
 
div.specialCases div.loginField p {
clear: both;
padding-top: 30px;
margin-bottom: 3px;
padding-bottom: 0;
margin-top: 0;
}
 
html>body div.specialCases div.loginField p {
*padding-top: 15px;
}
 
/*\*/ * html div.specialCases div.loginField p {
padding-top: 15px;
}/**/
 
div.specialCases div.loginField span.forgot {
display: block;
width: 300px;
height: 30px;
margin: auto;
text-align: center;
}
 
div.specialCases div.loginField span.forgot input {
float: none;
margin: 3px 0 0 0!important;
width: 10em;
}
 
/***********************Guest   ******/
 
div.guest p {
width: 220px;
float: left;
margin-left: 20px;
margin-top: 20px;
}
 
div.guest form input {
float: left;
width: 10em;
margin-left: 43px;
margin-top: 25px;
}
 
html>body div.guest form input  {
*margin-top: 5px;
}
 
/*\*/ * html div.guest form input  {
margin-top: 5px;
}/**/
 
html>body div.guest {
*padding-bottom: 20px;
}
 
/*\*/ * html div.guest  {
padding-bottom: 20px;
}/**/

input:hover {
    cursor: pointer;
}

#header {
    background-color: #536895 !important; <?php // CCLE-1583 -- force blue color on header background ?>
}
</style>
</head>


<body  class="login course-1" id="login-index" onload="setfocus()">
    
<div id="page">

    <div id="header" class="clearfix">
        <h1 class="headermain">
	
<!-- Modified 200706191108 by Eric Bollens to Remove Hardcoding -->
<?php 
    if (current_theme() == 'ssc') {
        echo '<img src="../theme/ssc/pix/ucla_logoSSC.jpg" width="654" height="80" alt="UCLA Collaboration and Learning" title="UCLA Collaboration and Learning" id="logo" />';
    } else {
        echo '<img src="../theme/ucla/pix/ucla_logoCCLE.jpg" width="654" height="80" alt="UCLA Collaboration and Learning" title="UCLA Collaboration and Learning" id="logo" />';
    }
?>

</h1>
        <div class="headermenu"></div>
    </div>
    <div class="navbar clearfix">
        <div class="breadcrumb"><h2 class="accesshide">You are here</h2><ul>
	
<!-- Modified 200706191108 by Eric Bollens to Remove Hardcoding -->
<li class="first"><a target="_top" style="color: #536895;" href="../">CCLE</a></li>

<li><span class="sep">&#x25BA;</span> Login to the site</li>
</ul>
</div>
        
</div></div>
    </div>   
    <!-- END OF HEADER -->
    <div id="content">
<div class="loginbox specialCases">
<div class="loginboxContent">
<h2>Special Cases Login</h2>

      <p>Login here using your username and password:<br />
       (Cookies must be enabled in your browser)<span class="helplink">
	
	 <!-- Modified 200706191108 by Eric Bollens to Remove Hardcoding -->
	<a target="popup" title="Help, Cookies must be enabled in your browser" href="../help.php?module=moodle&amp;file=cookies.html" onclick="return openpopup('../help.php?module=moodle&amp;file=cookies.html', 'popup', 'menubar=0,location=0,scrollbars,resizable,width=500,height=400', 0);">
	<img alt="Help, Cookies must be enabled in your browser" src="../pix/help.gif" />
	
	</a></span><br />
	
	<?php
	// Modified on 200706201600 by Eric Bollens
	// Original Modification on 200704201421 by Mike Franks and Keith Rozett
	/// Output form error message
	formerr($errormsg);
	?>
	
	</p>
<div class="loginField">
      <form action="index.php" method="post" name="login" id="login">

              <ul class="username">
<li>Username:</li>
<li><input type="text" name="username" size="15" value="" alt="Username" /> </li></ul>
              <ul class="password">
<li>Password:</li>
<li><input type="password" name="password" size="15" value="" alt="Password" /></li></ul>
<span class="loginLocal"><input type="submit" value="Login" />
            <input type="hidden" name="testcookies" value="1" />
</span>
      </form>

      <p>Forgotten your username or password?</p>
<span class="forgot"><form action="forgot_password.php" method="post" name="changepassword">
          <input type="hidden" name="sesskey" value="Makz670Yz9" />
          <input type="submit" value="Help me log in" />
        </form></span>
</div>
</div>
</div>
</div> <!-- end div containerContent -->
<!-- START OF FOOTER -->
<div id="footer">

<p class="helplink"></p>

<!-- Modified 200706191108 by Eric Bollens to Remove Hardcoding -->
<div class="homelink"><a target="_top" href="../">Home</a></div>

</div>
</div>
</body>
</html>
