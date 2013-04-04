Q&A v0.1 (Built on top of a modified Glossary v0.5-dev by By Williams Castillo)

------------------------

Q&A activity module 

-Students post questions to a Q&A instance.
-The question is pushed onto the question queue that is only visible by the teacher and designated users.
-Once a question is answered, it is added to the Q&A pair list that is visible to the class.
-Multiple instances can be added to a course.
-Q&A lists can be exported and imported.  Lists can be imported into existing lists, or as new lists.
-Printer-friendly view of the Q&A list.
-Teachers may designate role and user-level privileges. 

------------------------

Quick install instructions

1) Moodle 2.3+ recommended
2) Copy to the /mod directory
3) After installation enable TinyMCE HTML Editor with the Dragmath plugin
   My editor toolbar settings:
        bold,italic,underline,sub,sup,|,justifyleft,justifycenter,justifyright,|, bullist,numlist,outdent,indent,|,charmap,table,|,dragmath 

------------------------

JIRA FEATURE TICKET (CCLE-3873)

Question and Answer activity module

h1.Intro
Course activity module enables students to post questions to the instructor.  Pending questions are hidden from the students until answered by the instructor or designated users.

h1.Requirements
* HTML and equation support for questions and answers
* Search capability
* Multiple instances per course
* Q&A pair list exporting and importing  
** Import into existing list or new list 
* Printer-friendly view of the Q&A list
* Additional roles and users may be allowed to manage and answer questions
* Permalinks to individual Q&A pairs

h1.Functionality

A Q&A activity can be added to a course section.

As a student
*Submit a question to the instructor

As an Instructor 
* Answer pending student questions
* Modify or delete pending questions or existing Q&A pairs
* Modify administrative permissions for users
* Backup and recover Q&A
* Import and export Q&A

Everyone
* Search full text questions and answers
* Print view of the current Q&A list view
* Access a specific Q&A entry via URL / permalink

h1.Implementation:
Glossary 0.5-dev utilized as foundation
* Major modifications to enable html and equation support
** Question input, modification, and storage
** Q&A pair list input/export, backup/restore
** TinyMCE's DragMath module handles equation generation in TeX format
* Interface modifications for interactive Q&A functionality and proper formatting

Permissions
* Answer pending questions: users may answer pending questions as well as post a new question with an answer
* Manage entries: users can delete any entry
* Export: users can export the current Q&A pair list
* Import: users can import a Q&A pair list

h1.Testing
Add a Q&A activity to a course.

As a student
* Add questions with HTML formatting and equations.

As an instructor
* Answer or modify pending questions
* Import a test Q&A list from "mod/qanda/Exported_QandA_test_data.xml" to the current Q&A
* Import a test Q&A list from "mod/qanda/Exported_QandA_test_data.xml" as a new Q&A
* Test export and import of one of the Q&A lists
* Test backup and restore functions

h1. Notes
Tex Notation much be enabled in order to view equations
* Site administration / ▶ Plugins / ▶ Filters / ▶ Manage filters
* Enable TeX Notation (requires glibc)

JRE must be functioning on the end-users browser for DragMath to function

My TinyMCE HTML editor toolbar settings:
* bold,italic,underline,sub,sup,|,justifyleft,justifycenter,justifyright,|, bullist,numlist,outdent,indent,|,charmap,table,|,dragmath 