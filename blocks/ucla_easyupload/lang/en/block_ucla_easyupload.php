<?php

$string['pluginname'] = 'UCLA Easy upload tools';

// Easy add/upload strings
// This string should not be used, it is a placeholder.
$string['easy_upload_form'] = '';
$string['select_section'] = 'Add to section';
$string['returntocourse'] = 'Return to course';
$string['returntosection'] = 'Return to section';
$string['successfuladd'] = 'Successfully added {$a} to section.';
$string['select_copyright'] = 'Assign copyright status';
$string['select_copyright_list'] = 'Choose the most appropriate copyright status for this file';

// Should I split out the "to {$a}" part?
$string['easyupload_link_form'] = 'Upload a link to {$a}';
$string['dialog_add_link'] = 'Add a link';
$string['dialog_add_link_box'] = 'Enter link URL';
$string['dialog_rename_link'] = 'Name link';

$string['easyupload_file_form'] = 'Upload a file to {$a}';
$string['dialog_add_file'] = 'Select file';
$string['dialog_add_file_box'] = 'File';
$string['dialog_rename_file'] = 'Name file';

$string['easyupload_activity_form'] = 'Upload an activity to {$a}';
$string['dialog_add_activity'] = 'Select activity type';
$string['dialog_add_activity_box'] = 'Activity';

$string['easyupload_resource_form'] = 'Upload a resource to {$a}';
$string['dialog_add_resource'] = 'Select resource type';
$string['dialog_add_resource_box'] = 'Resource';

$string['easyupload_subheading_form'] = 'Add a subheading to {$a}';
$string['dialog_add_subheading'] = 'Enter subheading';
$string['dialog_add_subheading_box'] = 'Subheading you want displayed in section';

$string['easyupload_text_form'] = 'Add text to {$a}';
$string['dialog_add_text'] = 'Enter text';
$string['dialog_add_text_box'] = 'Text you want displayed in section';

// These are for the link into control panel
$string['add_file'] = 'Upload a file';
$string['add_file_post'] = '';

$string['add_link'] = 'Add a link';
$string['add_link_post'] = '';

$string['add_activity'] = 'Add an activity';
$string['add_activity_post'] = 'Add an Assignment, Forum, Quiz, Wiki or other activity to a specific section of your class site.';

$string['add_resource'] = 'Add a resource';
$string['add_resource_post'] = 'Create a separate text or web page, display a directory, use the advanced File Manager, or use the Book module.';

$string['add_subheading'] = 'Add a subheading';
$string['add_subheading_post'] = 'Use subheadings within a section to organize links and files (e.g. Readings, Assignments).';

$string['add_text'] = 'Add text';
$string['add_text_post'] = 'Add text to appear on a course site.';

$string['missingparam'] = 'Missing a parameter for redirection URL: {$a}';
$string['redirectimplementationerror'] = 'The implementation for a redirect type is missing the function get_send_params().';

// Rearrange
$string['rearrangejsrequired'] = 'The Rearrange Tool requires JavaScript to work.';

//Specify license

// CCLE-2669 - Copyright Modifications - help text
$string['license'] = 'Copyright status';
$string['license_help']='This question requires you to declare the copyright 
status of the item you are uploading. Each option is explained in greater detail 
below.
    
<strong>I own the copyright.</strong>
<br />
You are an author of this work and have not transferred the rights to a 
publisher or any other person.

<strong>The UC Regents own the copyright.</strong>
<br />
This item’s copyright is owned by the University of California Regents; most 
items created by UC staff fall into this category.

<strong>Item is licensed by the UCLA Library.</strong>
<br />
This item is made available in electronic form by the UCLA library. <i> Note: 
the UCLA Library would prefer that you provide a link to licensed electronic 
resources rather than uploading the file to your CCLE course.</i>

<strong>Item is in the public domain.</strong>
<br />
Generally, an item is in the public domain if one of the following applies:
<ol>
    <li>It was published in the U.S. before 1923.</li>
    <li>It is a product of the federal government.</li>
    <li>The term of copyright, which is generally the life of the author plus 
    seventy years, has expired.</li>
</ol>

<strong>Item is available for this use via Creative Commons license.</strong>
<br />
Many items are made available through Creative Commons licenses, which specify 
how an item may be reused without asking the copyright holder for permission. 
Similar “open source” licenses would also fit under this category. See 
<a href="http://creativecommons.org/" target="_blank">creativecommons.org</a> 
for more information.

<strong>I have obtained written permission from the copyright holder.</strong>
<br />
This answer applies if you have contacted the copyright holder of the work and 
have written permission to use the work in this manner.  Note: You should keep 
this written permission on file.

<strong>I am using this item under fair use.</strong><br />
Fair use is a right specifically permitting educational, research, and scholarly 
uses of copyrighted works.  However, <u>not every educational use is 
automatically a fair use</u>; a 
<a href="http://copyright.universityofcalifornia.edu/fairuse.html#2" target="_blank">four-factor analysis</a> 
must be applied to each item.

<strong>Copyright status not yet identified.</strong>
<br />
Select <strong>only</strong> if this upload is being performed by <u>someone besides the 
instructor of record</u> at the instructor’s behest, but the instructor did not 
clarify the copyright status.

Note: if you believe none of these answers apply, you should not upload the item. 
For more details  on copyright status and fair use, go to the 
<a href="http://copyright.universityofcalifornia.edu/fairuse.html" target="_blank">UC copyright fair use page</a>, 
use ARL’s <a href="http://www.knowyourcopyrights.org/bm~doc/kycrbrochurebw.pdf" target="_blank">Know Your Copy Rights</a> 
brochure, or read their great <a href="http://www.knowyourcopyrights.org/resourcesfac/faq/online.shtml" target="_blank">FAQ</a>.  
If you have questions regarding the above or need assistance in determining 
copyright status, please email <a href="mailto:copyright@library.ucla.edu">copyright@library.ucla.edu</a> 
for a consultation. <strong>It is the instructor of record’s responsibility to 
comply with copyright law in the handling of course materials;</strong> see the 
<a href="'.$CFG->wwwroot.'/theme/uclashared/view.php?page=copyright">CCLE copyright information page</a>
    for more details.
';

/** End of file **/
