<?php

$string['pluginname'] = 'Course alerts';

// Default values
// Section
$string['section_title_site'] = 'CCLE notices';
$string['section_title_course'] = 'Notices';
$string['section_item_default'] = '# Hi there
Notice me!
>{http://www.ccle.ucla.edu} CCLE Home';

// Scratch
$string['scratch_title'] = 'Scratch pad';
$string['scratch_item_default'] = '# disabled item
* Items in the scratch pad will not be visible in the alert block.  
* You can use this as a staging area, or as a saved item repository.';
$string['scratch_item_add'] = '# add more items
You can add more items by clicking the \'add\' button below...';
$string['scratch_item_new'] = '# new item added
You can edit me by double clicking this text!

..or you can delete me by deleting all my text.';
$string['scratch_button_add'] = 'Add another item';

// Item edit
$string['item_edit_save'] = 'Save changes';
$string['item_edit_cancel'] = 'Cancel';

// Commit changes
$string['alert_commit_save'] = 'Update alerts';

// Site headers
$string['header_default'] = '# hello world!
#! date';
$string['header_yellow'] = '# service alert
## You will survive';
$string['header_red'] = '# ccle alert
#! now';
$string['header_blue'] = '# maintenance alert
## Scheduled NOW!';

$string['header_section_item'] = 'Alert block presents itself to the world';

$string['edit_alert_heading'] = 'Edit alert block';

// Tutorial 
$string['edit_tutorial_h1'] = 'Edit alerts like a pro';

$string['edit_tutorial_title'] = '# Adding a title
You can use the symbol "#" to create a title, for example, 
 # my title
will give you:
# my title
Notice that it will automatically uppercase the text.';

$string['edit_tutorial_list'] = '# Adding a list item
You can also create a list using the "*" symbol, for example this list:
 * item 1
 * item 2
becomes:
*item 1
*item 2

You can also specify a color for the item by adding braces like so:
 *{red} red item
 *{#000000} black item
becomes:
*{red} red item
*{#000000} black item';

$string['edit_tutorial_link'] = '# Adding a link
To add a link, you use the ">" symbol with the link in {braces}, for example
 >{http://www.ccle.ucla.edu} CCLE
becomes:
>{http://www.ccle.ucla.edu} CCLE

Note that the entire link markup is on a single line.';

$string['edit_tutorial_summary'] = 'To re-order items in the alert block, you 
    can drag and drop items into different section content areas.  You can 
    double click on an item to edit its contents.  All the changes
    you make are not final until you "Update alerts."';

$string['edit_tutorial_markup'] = 'The alert block uses a simple markdown 
    language with three tokens to specify <span>titles</span>, <span>lists</span> 
    and <span>links</span>.  The tokens are
    <span>#</span>, <span>*</span> and <span>></span> respectively.  Curly braces 
    <span>{ }</span> are used to specify extra information.  
    This is done to keep all alert block items uniform.  You can edit 
    the alert block like a pro by reading the following brief tutorials.';

// Error
$string['alert_block_dne'] = 'This alert block does not exist.';