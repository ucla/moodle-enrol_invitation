<?php

header('Content Type: application/rss+xml');

include(dirname(__FILE__) . '/config.php');

$site = get_site();

$wwwroot = $CFG->wwwroot . '/';

?>
<?xml version="1.0"?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
  <channel>
    <title><?php echo $site->fullname ?></title>
    <link><?php echo $wwwroot ?></link>
    <description><?php echo strip_tags($site->summary) ?></description>
    <language>en-us</language>

    <item>
      <title>Login</title>
      <description>
        &lt;a href="<?php echo $wwwroot ?>login/ucla_index.php?loginguest=1"&gt;Guest Access&lt;/a&gt; - access to publicly viewable sites&lt;br&gt;
        &lt;a href="<?php echo $wwwroot ?>login/ucla_login.php"&gt;Special Case Login&lt;/a&gt; -  only if you have been assigned a special case ID&lt;br&gt;
        Getting an error after a successful login? Your Logon ID might not be &lt;a href="http://kb.ucla.edu/articles/moodle-needs-certain-shibboleth-attributes"&gt;working properly&lt;/a&gt;.
      </description>
    </item>

  </channel>
</rss>
