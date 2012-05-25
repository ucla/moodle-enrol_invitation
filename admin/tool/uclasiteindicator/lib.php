<?php

/**
 * UCLA Site Indicator 
 * 
 * @package     ucla
 * @subpackage  uclasiteindicator
 * @author      Alfonso Roman
 */

require_once(dirname(__FILE__) . '/../../../config.php');

require_once($CFG->libdir.'/formslib.php');

// From the UCLA help block -- to get support contacts and send jira ticket
require_once($CFG->dirroot . '/local/ucla/jira.php');
require_once($CFG->dirroot . '/blocks/ucla_help/ucla_help_lib.php');
require_once($CFG->dirroot . '/local/ucla/lib.php');


/**
 * A site indicator entry - represents a site indicator for a given course 
 */
class site_indicator_entry {
    
    public $property;
    
    private $_id;
    
    function __construct($courseid) {
        global $DB;
        
        $indicator = $DB->get_record('ucla_siteindicator', 
                array('courseid' => $courseid), '*', MUST_EXIST);
        $this->property->courseid = $courseid;
        $this->property->type = $indicator->type;
        $this->_id = $indicator->id;
        
        $this->set_typeinfo();
   }
    
    /**
     * Delete a site indicator entry
     */
    public function delete() {
        global $DB;
        $DB->delete_records('ucla_siteindicator', array('id' => $this->_id));
    }
    
    private function update() {
        global $DB;
        $DB->update_record('ucla_siteindicator',
                array('id' => $this->_id, 'type' => $this->property->type));
    }
   
    
    /**
     * Change a site type.  Re-maps the role assignments if the site type
     * is of a different role group
     * 
     * @param type $newtype of the site.
     */
    public function change_type($newtype) {
        $uclaindicator = new ucla_site_indicator();
        
        $mygroup = $uclaindicator->get_rolegroup_for_type($this->property->type);
        $newgroup = $uclaindicator->get_rolegroup_for_type($newtype);
        
        // Do we need to change role assignments?
        if($newgroup != $mygroup) {
            
            // Get course context
            $context = get_context_instance(CONTEXT_COURSE, $this->property->courseid);
            
            // Get enrolled users
            $users = get_enrolled_users($context);
            
            // for each user, reassign roles
            foreach($users as $u) {
                $roles = get_user_roles($context, $u->id);
                
                foreach($roles as $r) {
                    $oldrole = $r->shortname;
                    
                    // Only map roles that are remap-able
                    if($newrole = $uclaindicator->get_remapped_role($newgroup, $oldrole)) {
                        role_unassign($r->roleid, $u->id, $context->id);
                        role_assign($newrole->id, $u->id, $context->id);
                    }
                }
            }
        }
        
        // Update new site type
        $this->property->type = $newtype;
        $this->update();
    }
    
    private function set_typeinfo() {
        global $DB;

        $type = $DB->get_record('ucla_siteindicator_type', 
                array('id' => $this->property->type), '*', MUST_EXIST);
        
        $this->type_fullname = $type->fullname;
        $this->type_shortname = $type->shortname;
    }
    
    /**
     * Get assignable roles for this indicator.
     * 
     * @return array of assignable roles 
     */
    public function get_assignable_roles() {
        global $DB;
        
        $uclaindicator = new ucla_site_indicator();
        $roleids = (array)$uclaindicator->get_roles_for_type($this->property->type);
        $roles = $DB->get_records_select('role', 
                'shortname IN ("' . implode('", "', $roleids) . '")');
        
        $list = array();
        
        foreach($roles as $r) {
            $list[$r->id] = $r->name;
        }
        
        return $list;
    }    

    
    /**
     * Load an indicator.
     * 
     * @param type $courseid
     * @return null|\site_indicator_entry if indicator exists
     */
    static function load($courseid) {
        try {
            return new site_indicator_entry($courseid);
            
        } catch(Exception $e) {
            
            return null;
        }
    }
    
    /**
     * Creates a site indicator and assigns it a 'test' type
     * 
     * @param type $courseid 
     */
    static function force_create($courseid) {
        global $DB;
        
        $rec = $DB->get_record('ucla_siteindicator_type', 
                array('shortname' => 'test'));
        
        $new = new stdClass();
        $new->courseid = $courseid;
        $new->type = $rec->id;
        
        $DB->insert_record('ucla_siteindicator', $new);
    }
}

/**
 * A site indicator request - retrieves a course request 
 */
class site_indicator_request {

    public $request;
    
    public $entry;
    
    private $_id;
    
    function __construct($requestid) {
        global $DB;

        $this->request = new stdClass();
        $this->entry = new stdClass();

        $request = $DB->get_record('ucla_siteindicator_request', 
                array('requestid' => $requestid), '*', MUST_EXIST);

        $this->_id = $request->id;                           // Indicator request ID
        $this->entry->type = $request->type;                // Indicator type
        $this->request->support = $request->support;        // Support Contact
        $this->request->categoryid = $request->categoryid;  // Requested category
        $this->request->requestid = $requestid;             // Request ID of course_request
        $this->request->requester = $request->requester;    // User who requested the course
    }
 
    /**
     * Create a site indicator entry from a request.  This also deletes
     * the request.
     */
    function create_indicator_entry() {
        global $DB;
        
        $DB->insert_record('ucla_siteindicator', $this->entry);
        
        $this->set_default_role();
        $this->delete();
    }
    
    /**
     * Set default role for user who requested the course.
     * 
     * @todo assign a dummy 'course creator' role
     * @return type 
     */
    private function set_default_role() {
        global $DB;
        
        // Get toprole
        $uclaindicator = new ucla_site_indicator();
        $roles = $uclaindicator->get_roles_for_type($this->entry->type);
        $toprole = array_shift($roles);
        
        $role = $DB->get_record('role', array('shortname' => $toprole));
        
        // Course and user info
        $userid = $this->request->requester;
        $courseid = $this->entry->courseid;
        
        // Get context
        $context = get_context_instance(CONTEXT_COURSE, $courseid);
        
        // Assign default role
        role_assign($role->id, $userid, $context->id, '', NULL);
    }
    
    /**
     * Generates a JIRA ticket and assigns it to a support contact
     */
    public function generate_jira_ticket() {
        global $DB , $CFG;
        
        // Get the information we need to print in the ticket
        $req_course = $DB->get_record('course_request', 
                array('id' => $this->request->requestid));
        
        // Format strings
        $req_course->type = $this->get_type_str();
        $req_course->user = $this->get_user_str($req_course->requester);
        $req_course->category = $this->get_category_str();
        $req_course->summary = $this->fix_linebreaks($req_course->summary);
       
        // Attach the pending course links
        $req_course->action = $CFG->wwwroot . '/course/pending.php?request=' 
                . $this->request->requestid;
        
        // Prepare JIRA params
        $title = get_string('jira_title', 'tool_uclasiteindicator', $req_course);
        $message = get_string('jira_msg', 'tool_uclasiteindicator', $req_course);
        $support = $this->request->support;
        
        // Jira params
        $params = array(
            'pid' => get_config('block_ucla_help', 'jira_pid'),
            'issuetype' => 1,
            'os_username' => get_config('block_ucla_help', 'jira_user'),
            'os_password' => get_config('block_ucla_help', 'jira_password'),
            'summary' => $title,
            'assignee' => $support,
            'reporter' => $support,
            'description' => $message,
        );
        
        echo "jira support user: " . $support . "<br/>";
        echo "<pre>";
        print_r($title);
        echo "</pre>";
        echo "<pre>";
        print_r($message);
        echo "</pre>";

        // Create ticket
        //$result = do_request(get_config('block_ucla_help', 'jira_endpoint'), $params, 'POST');      
    }
    
    /**
     * Delete the site indicator request 
     */
    public function delete() {
        global $DB;
        
        $DB->delete_records('ucla_siteindicator_request', 
                array('id' => $this->_id));
    }
        
    /**
     * Create a site indicator request.  
     * 
     * @param obj $newindicator is an object 
     */
    static function create($newindicator) {
        global $DB;
        
        $DB->insert_record('ucla_siteindicator_request', $newindicator);
        
        // Get the request and generate jira ticket
        $request = new site_indicator_request($newindicator->requestid);
        $request->generate_jira_ticket();
    }
    

    /**
     * Load an indicator request.
     * 
     * @param type $requestid
     * @return null|\site_indicator_request if request exists
     */
    static function load($requestid) {
        try {
            return new site_indicator_request($requestid);
        } catch(Exception $e) {
            return null;
        }
    }

    
    private function fix_linebreaks($string) {
        $fix = str_replace('</p>', "\n\n", $string);
        $fix = preg_replace('#<br\s*/?>#i', "\n", $string);
        $fix = strip_tags($fix);
        return $fix;
    }
    
    private function get_category_str() {
        global $DB;
        
        if($this->request->categoryid) {
            $category_string = "";
            
            $category = $DB->get_record('course_categories', 
                    array('id'=>$this->request->categoryid));
            
            if($category->parent) {
                $parent = $DB->get_record('course_categories', 
                        array('id' => $category->parent));
                $category_string = $parent->name . ' > ';
            }
            $category_string .= $category->name;
        } else {
            $category_string = "Other -- specified in 'reason message'";
        }
    }
    
    private function get_type_str() {
        global $DB;
        $rec = $DB->get_record('ucla_siteindicator_type', 
                array('id' => $this->entry->type));
        
        return $rec->fullname;
    }
    
    private function get_user_str($id) {
        global $DB;
        
        $user = $DB->get_record('user', array('id' => $id));
        $str = $user->firstname.' '.$user->lastname.' ('.$user->email.')';
        return $str;        
    }
    
    
}


/**
 * Collection of site indicator functions.
 * 
 */
class ucla_site_indicator {
    
    // A group of roles.  A group contains a set 
    // of roles that are mutually excluseive from other groups.
    private $_indicator_rolegroups;
    
    // Sets of role assignments for a particular group.
    private $_roleassignments;
    
    // A mapping specifiying which role group belongs to a site type
    private $_type_to_rolegroup_mapping;
    
    // A role re-map scheme used when a site changes type
    private $_role_remap;
    
    function __construct() {
       
        $this->_indicator_rolegroups = array(
            'instruction' => get_string('r_instruction', 'tool_uclasiteindicator'),
            'project' => get_string('r_project', 'tool_uclasiteindicator'),
            'test' => get_string('r_test', 'tool_uclasiteindicator'),
            );
        
        // Supported site types:
        //   Instruction
        //   Non-Instruction
        //   Research
        //   Test
        $this->_type_to_rolegroup_mapping = array(
            'instruction' => 'instruction',
            'non_instruction' => 'project',
            'research' => 'project',
            'test' => 'test',
            );
        
        // Define the roles allowed for a particular role group
        $instruction = array(
            'editinginstructor',
            'supervising_instructor',
            'nonediting_instructor',
            'student',
            );
        
        $project = array(
            'projectlead',
            'projectcontributor',
            'projectmember',
            'projectviewer',
            );
        
        // 
        $this->_roleassignments = array(
            'instruction' => $instruction,
            'project' => $project,
            'test' => array_merge($instruction, $project),
            );

        // Re-mapping of roles for site type changes
        $this->_role_remap = array(
            'project' => array(
                'editinginstructor' => 'projectlead',
                'supervising_instructor' => 'projectcontributor',
                'nonediting_instructor' => 'projectmember',
                'student' => 'projectviewer',
                ),
            'instruction' => array(
                'projectlead' => 'editinginstructor',
                'projectcontributor' => 'supervising_instructor',
                'projectmember' => 'nonediting_instructor',
                'projectviewer' => 'student',
                )
            );
    }
    
    /**
     * For a given role group, returns the set of roles in that group.
     * 
     * @param string $group shortname of the role group
     * @return array of roles (shortnames)
     */
    function get_roles_for_group($group) {
        return $this->_roleassignments[$group];
    }
    
    /**
     * For a given type, returns the set of roles for that type.
     * 
     * @param mixed $type of site
     * @return array of role (shortnames) 
     */
    function get_roles_for_type($type) {
        $ntype = $this->disambiguate_type($type);
        return $this->_roleassignments[$this->_type_to_rolegroup_mapping[$ntype]];
    }
    
    /**
     * For a given type, returns the rolegroup assigned to the type.
     * 
     * @param mixed $type of site
     * @return string role group 
     */
    function get_rolegroup_for_type($type) {
        $ntype = $this->disambiguate_type($type);
        return $this->_type_to_rolegroup_mapping[$ntype];
    }
    
    /**
     * For a given rolegroup and (non-rolegroup role, returns the equivalent role.
     * 
     * @param string $rolegroup of site
     * @param string $role shortname
     * @return null|$newrole if the mapping exists
     */
    function get_remapped_role($rolegroup, $role) {
        global $DB;
        
        $newrole = new stdClass();

        if(empty($this->_role_remap[$rolegroup][$role])) {
            $newrole = null;
        } else {
            $newrole->shortname = $this->_role_remap[$rolegroup][$role];
            $record = $DB->get_record('role', 
                    array('shortname' => $newrole->shortname));
            $newrole->id = $record->id;
        }
        
        return $newrole;
    }

    private function disambiguate_type($type) {
        global $DB;
        
        if(is_numeric($type) || is_int($type)) {
            $rec = $DB->get_record('ucla_siteindicator_type', array('id' => $type));
            $type = $rec->shortname;
        }
        return $type;
    }

    /**
     * Returns a filtered categories list
     * 
     * @todo: hide categorie we don't want to make visible -- add option 
     * in admin area
     * 
     * @param array $parentlist
     * @return type 
     */
    static function get_categories_list(&$parentlist = null) {
        global $DB;
        
        $displaylist = array();
        if($parentlist == null) {
            $parentlist = array();
        }

        //make_categories_list($displaylist, $parentlist, 'moodle/course:create');
        // @todo: pick up from DB eventually
        $exclusion_list = array('Miscellaneous');        
        
        // Division level categories
        $categories = $DB->get_records('course_categories', array('parent' => 0));
        
        // Subject area categories
        foreach($categories as $cat) {
            
            if(in_array($cat->name, $exclusion_list)) {
                continue;
            }

            $displaylist[$cat->id] = $cat->name;
            
            // Subject area level categories
            if($children = $DB->get_records('course_categories', 
                    array('parent' => $cat->id))) {
                foreach($children as $child) {
                    $displaylist[$child->id] = $cat->name . ' > ' . $child->name;
                }
            }
        }
        
        $displaylist[0] = get_string('req_selopt_other', 'tool_uclasiteindicator');
        
        return $displaylist;
    }
    
    /**
     * Get list of available support contacts
     * 
     * @return type 
     */
    static function get_support_contacts_list() {
        $manager = get_support_contacts_manager();
        $support_contacts = $manager->get_support_contacts();
        return $support_contacts;
    }
    
    /**
     * Get a specific support contact for a given category.  If the contact
     * is not found, the parent category will be searched.
     * 
     * @param type $categoryid
     * @param type $contacts
     * @return type 
     */
    static function get_support_contact($categoryid, &$contacts) {
        global $DB;
        // Attempt to find the support contact for category
        $category = $DB->get_record('course_categories', 
                array('id' => $categoryid));

        if(key_exists($category->name, $contacts)) {
            return $contacts[$category->name];
        } else if(!empty($category->parent)) {
            self::get_support_contact($category->parent, $contacts);
        }
        
        return $contacts['System'];
    }
    
    /**
     * Given a category ID, retrieve the user assigned a category manager. 
     * This will eventually be used to filter the 'pending request' list 
     * so that only the category manager is able to see coruses requested 
     * in their category.
     * 
     * @todo: finish implementing
     * 
     * @param type $categoryid
     * @return type 
     */
    static function get_category_manager($categoryid) {
        global $DB;
        
        $query = "SELECT ra.userid, r.name
                FROM {role_assignments} ra
                JOIN {context} c ON ra.contextid = c.id
                JOIN {role} r ON ra.roleid = r.id
                LEFT JOIN {role_names} rn ON rn.roleid = ra.roleid
                AND rn.contextid = ra.contextid
                WHERE c.instanceid = ?
                AND r.shortname = ?";
        
        $record = $DB->get_records_sql($query, array($categoryid, 'manager'));

        return -1;
    }
    
    /**
     * Create an indicator request
     * 
     * @param type $data 
     */
    static function request($data) {
        global $DB;
        
        // Find the ID of the course_request
        $request = $DB->get_record('course_request', 
                array('fullname' => $data->fullname, 
                'shortname' => $data->shortname), '*', MUST_EXIST);
        
        // Determine support contact for JIRA ticket. 
        $contacts = self::get_support_contacts_list();
        $contact = self::get_support_contact($data->indicator_category, $contacts);

        // Now save to site indicator request table
        $newindicator = new stdClass();
        $newindicator->requestid = $request->id;
        $newindicator->support = $contact;
        $newindicator->type = $data->indicator_type;
        $newindicator->categoryid = $data->indicator_category;
        $newindicator->requester = $request->requester;
        
        // Finally, create request
        site_indicator_request::create($newindicator);
    }
    
    /**
     * Create a site indicator entry from an existing request.  
     * 
     * @param int $courseid for indicator entry
     * @param int $requestid of existing indicator request
     * @return int category ID specified in the indicator request
     */
    static function create($courseid, $requestid = 0) {

        if($request = site_indicator_request::load($requestid)) {
            $request->entry->courseid = $courseid;

            // Create record for course
            $request->create_indicator_entry();
        } else {
            site_indicator_entry::force_create($courseid);
        }
    }
    
    /**
     * Reject a indicator request.
     * 
     * @param type $requestid 
     */
    static function reject($requestid) {
        if($request = site_indicator_request::load($requestid)) {
            $request->delete();
        }
    }

    /** 
     * Retrieves sorted indicator types as array of objects
     * 
     * @return type array
     */
    static function get_indicator_types() {
        global $DB;

        return $DB->get_records('ucla_siteindicator_type');
    }
}

/**
 * @todo: implement admin functions 
 */
class ucla_indicator_admin {
    
    /**
     * Populate the types table
     */
    static function sql_populate_types() {
        global $DB;
        
        // Populate types
        $query1 = "INSERT INTO {ucla_siteindicator_type} (id, sortorder, fullname, shortname, description, visible) VALUES
                (1, '".get_string('site_instruction', 'tool_uclasiteindicator')."', 'instruction', '".get_string('site_instruction_desc', 'tool_uclasiteindicator')."'),
                (2, '".get_string('site_non_instruction', 'tool_uclasiteindicator')."', 'non_instruction', '".get_string('site_non_instruction_desc', 'tool_uclasiteindicator')."'),
                (3, '".get_string('site_research', 'tool_uclasiteindicator')."', 'research', '".get_string('site_research_desc', 'tool_uclasiteindicator')."'),
                (4, '".get_string('site_test', 'tool_uclasiteindicator')."', 'test', '".get_string('site_test_desc', 'tool_uclasiteindicator')."')";
        $DB->execute($query1);

    }
    
    static function foo() {
        
    }
}