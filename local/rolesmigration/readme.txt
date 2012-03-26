There are two different branches in this repo. The project was originally developed as a Moodle report (hence the name) but was later ported to a local plugin as well.
If you want to place the code in the /admin/report/ folder, use the master branch. If you would like to place it in the local folder, use the local branch. Both branches will maintained together.

Place the 'rolesmigration' folder inside /local/ directory and access the UI via Site Administration -> Users -> Permissions -> Import / Export Roles

To use this utility, first use the export roles form to download an XML formated representation of your roles and capabilities. 
Second, use the import roles form to import the roles to another Moodle installation. You may choose to overwrite current roles or to append additional roles.

More discussion can be found at the following locations:
* http://moodle.org/mod/forum/discuss.php?d=170622#p808007
* http://tracker.moodle.org/browse/MDL-17081

== Changelog ==
* Multiple code updates to better align with Moodle coding standards. Props Nicholas Koeppen <nkoeppe@wisc.edu>