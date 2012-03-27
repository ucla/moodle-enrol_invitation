<?php
/**
 * My sites block
 *
 * Based off of blocks/course_overview.
 *
 * @package   blocks
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot.'/lib/weblib.php');
require_once($CFG->dirroot . '/lib/formslib.php');

require_once($CFG->dirroot.'/local/ucla/lib.php');

class block_ucla_my_sites extends block_base {
    /**
     * block initializations
     */
    public function init() {
        $this->title   = get_string('pluginname', 'block_ucla_my_sites');
    }

    /**
     * block contents
     *
     * Get courses that user is currently assigned to and display them either as
     * class or collaboration sites.
     * 
     * @return object
     */
    public function get_content() {
        global $USER, $CFG;
        if($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        $content = array();

        $courses = enrol_get_my_courses('id, shortname', 'visible DESC,sortorder ASC');
        $site = get_site();
        $course = $site; //just in case we need the old global $course hack

        if (array_key_exists($site->id,$courses)) {
            unset($courses[$site->id]);
        }

        // go through each course and categorize them into either class or
        // collaboration sites
        $class_sites = array(); $collaboration_sites = array();
        foreach ($courses as $c) {
            $reg_info = ucla_get_course_info($c->id);
            if (!empty($reg_info)) {
                $c->reg_info = $reg_info;
                $class_sites[] = $c;
            } else {
                $collaboration_sites[] = $c;
            }
        }

        // print class sites
        $content[] = html_writer::tag('h3', get_string('classsites', 
                'block_ucla_my_sites'), array('class' => 'mysitesdivider'));
        if (empty($class_sites)) {
            $content[] = html_writer::tag('p', get_string('noclasssites', 
                    'block_ucla_my_sites'));
        } else {
            $t = new html_table();
            $t->head = array(get_string('classsitesnamecol', 'block_ucla_my_sites'), 
                    get_string('rolescol', 'block_ucla_my_sites'));
            foreach ($class_sites as $class) {
                // build class title in following format:
                // <subject area> <cat_num>, <activity_type e.g. Lec, Sem> <sec_num> (<term name e.g. Winter 2012>): <full name>
                
                // there might be multiple reg_info records for cross-listed courses
                $class_title = ''; $first_entry = true;
                foreach ($class->reg_info as $reg_info) {
                    $first_entry ? $first_entry = false : $class_title .= '/';
                    $class_title .= sprintf('%s %s, %s %s', 
                            $reg_info->subj_area,
                            $reg_info->coursenum,
                            $reg_info->acttype,
                            $reg_info->sectnum);                    
                }
                
                $reg_info = $class->reg_info[0];
                $title = sprintf('%s (%s): %s', 
                        $class_title,
                        ucla_term_to_text($reg_info->term, $reg_info->session_group),
                        $class->fullname);
                
                // add link
                $class_link = sprintf('<a href="%s/course/view.php?id=%d">%s<a/>', 
                        $CFG->wwwroot, $class->id, $title);
                
                // get user's role               
                $roles = get_user_roles_in_course($USER->id, $class->id);                
                $roles = strip_tags($roles);    // remove links from role string
                
                $t->data[] = array($class_link, $roles);
            }
            $content[] = html_writer::table($t);            
        }
        
        // print collaboration sites (if any)
        if (!empty($collaboration_sites)) {
            $content[] = html_writer::tag('h3', get_string('collaborationsites', 
                    'block_ucla_my_sites'), array('class' => 'mysitesdivider'));          
            
            $t = new html_table();
            $t->head = array(get_string('collaborationsitesnamecol', 'block_ucla_my_sites'), 
                    get_string('rolescol', 'block_ucla_my_sites'));      
            
            foreach ($collaboration_sites as $collab) {
                
                // make link
                $collab_link = sprintf('<a href="%s/course/view.php?id=%d">%s<a/>', 
                        $CFG->wwwroot, $collab->id, $collab->fullname);                
                
                // get user's role               
                $roles = get_user_roles_in_course($USER->id, $collab->id);                
                $roles = strip_tags($roles);    // remove links from role string  
                
                $t->data[] = array($collab_link, $roles);                
            }
                
            $content[] = html_writer::table($t);    
        }
        
        $this->content->text = implode($content);

        return $this->content;
    }

    /**
     * allow the block to have a configuration page
     *
     * @return boolean
     */
    public function has_config() {
        return false;
    }

    /**
     * locations where block can be displayed
     *
     * @return array
     */
    public function applicable_formats() {
        return array('my-index'=>true);
    }
}
?>
