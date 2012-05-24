<?php
 $dir = 'db';

 // create new directory with 777 permissions if it does not exist yet
 // owner will be the user/group the PHP script is run under
 if ( !file_exists($dir) ) {
  mkdir ($dir, 0777);
 }
