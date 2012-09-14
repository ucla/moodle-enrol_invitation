<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.


/**
 * Library file for UCLA roles admin tool.
 * 
 * Contains class definitions.
 *
 * @package    tool
 * @subpackage uclaroles
 * @copyright  2012 UC Regent
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/' . $CFG->admin . '/tool/uclasiteindicator/lib.php');
require_once($CFG->dirroot . '/local/ucla/lib.php');

class uclaroles_manager {
    const ROLE_TYPE_SUPPORTSTAFF = 'supportstaff';    
    const ROLE_TYPE_INSTEQUIV = 'instequiv';
    const ROLE_TYPE_REDUCEDEDITING = 'reducedediting';
    const ROLE_TYPE_STUDENTEQUIV = 'studentequiv';
    const ROLE_TYPE_GUESTEQUIV = 'guestequiv';
    const ROLE_TYPE_SECONDARY = 'secondaryaddon';
    const ROLE_TYPE_CORE = 'moodle_core';

    /**
     * Returns html table to display role mapping for system.
     * 
     * @return html_table
     */
    static function display_role_mappings() {
        global $CFG;
        require($CFG->dirroot . '/local/ucla/rolemappings.php');
        
        $data = array();
        foreach ($role as $registrar_role => $mapping) {
            foreach ($mapping as $subject_area => $moodle_role) {
                $row = new stdClass();
                $row->registrar_role    = $registrar_role;
                $row->subject_area      = $subject_area;
                $row->moodle_role       = $moodle_role;
                $data[] = $row;
            }
        }

        $ret_val = new html_table();
        $ret_val->head = array(
            get_string('registrar_role', 'tool_uclaroles'),
            get_string('subject_area', 'tool_uclaroles'),
            get_string('moodle_role', 'tool_uclaroles'),            
        );
        $ret_val->data = $data;
        
        return $ret_val;
    }    
    
    /**
     * Returns html table to display roles using given filter.
     * 
     * @global moodle_database $DB
     * 
     * @param string $role_type
     * @param string $site_type
     * 
     * @return html_table
     */
    static function display_roles_table($role_type = null, $site_type = null) {
        global $DB;
        $where = '';
        
        // do we need to filter on role type?
        if (!empty($role_type)) {
            // note, $role_type should have been validated beforehand
            $where .=  sprintf("description LIKE '%%%s%%'", 
                    $DB->sql_like_escape($role_type));
        }
        
        if (!empty($site_type)) {
            // get assignable roles for site type
            $assignable_roles = self::get_assignable_roles($site_type);
            $where .= '(';
            $first_entry = true;
            foreach ($assignable_roles as $shortname => $name) {
                $first_entry ? $first_entry = false : $where .= ' OR ';
                $where .= sprintf("shortname = '%s'", $shortname);
            }
            $where .= ')';
        }
        
        if (empty($where)) {
            // no filter, just get all roles
            $where .= '1=1';
        }
        
        // get roles that match role_type (if any)
        $roles = $DB->get_records_select('role', $where, null, 'name');
                
        // prepare to parse roles
        
        // get all role types
        $role_types = self::get_role_types();

        // get site types and their assignable roles
        $site_types = self::get_site_types();
        $assignable_roles = array();
        foreach ($site_types as $site_type => $type_text) {
            $assignable_roles[$site_type] = self::get_assignable_roles($site_type);
        }            
        
        /* table is outlined as follows:
         * Role type (row divider, alpha sort roles for each type by full name)
         * Role (shortname) | Description | Invitable in following site types | Legacy type
         */
        // have rows indexed in order of role types  
        $rows = array();
        foreach ($role_types as $type => $name) {
            $rows[$type] = array();
        }
        
        foreach ($roles as $role) {
            $found_role_type = null;
            
            // first, find out what role type this is
            $found_role_type = self::find_role_type($role);
                        
            // next, find what site types this role can be invited
            $invitable_types = array();
            foreach ($assignable_roles as $site_type => $type_roles) {
                foreach ($type_roles as $shortame => $type_role) {
                    if ($shortame == $role->shortname) {
                        // only care about display name
                        $invitable_types[] = $site_types[$site_type];
                        break;
                    }
                }
            }
        
            $rows[$found_role_type][] = array(
                sprintf('%s (%s)', $role->name, $role->shortname),
                $role->description,
                implode(', ', $invitable_types),
                $role->archetype
            );
        }
        
        // now construct table
        $table = new html_table();
        $table->head = array(
            sprintf('%s (%s)', get_string('role'), get_string('shortname')),
            get_string('description'),
            get_string('invite_site_types', 'tool_uclaroles'),            
            get_string('legacytype', 'role'));
               
        foreach ($rows as $role_type => $data) {
            if (empty($data)) {
                continue;
            }
            
            // add row that list role type
            if ($role_type == 'noroletype') {
                $role_type_text = get_string('noroletype', 'tool_uclaroles');
            } else {
                $role_type_text = $role_types[$role_type];                
            }
            $row_cell = new html_table_cell($role_type_text);
            $row_cell->colspan = 4;
            $row_cell->header = true;
            $row_header = new html_table_row(array($row_cell));
            $row_header->attributes['class'] = 'role_type_header';                        
            $table->data[] = $row_header;
            
            foreach ($data as $row_data) {
                $table->data[] = new html_table_row($row_data);
            }
        }
        
        return $table;
    }
    
    /**
     * Returns what roles can be assigned for a given site type.
     * 
     * @param string $site_type
     * 
     * @return array        Returns an array in the following format:
     *                      [shortname] => [name]
     */
    static function get_assignable_roles($site_type) {
        global $DB;
        $ret_val = array();
        
        if ($site_type == siteindicator_manager::SITE_TYPE_SRS_INSTRUCTION) {
            $roleids = array('instructional_assistant', 'editor', 'grader', 
                'participant', 'visitor');
            $roles = $DB->get_records_list('role', 'shortname', $roleids, 'sortorder');   
            foreach($roles as $r) {
                $ret_val[$r->shortname] = trim($r->name);
            }        
        } else {
            $siteindicator_manager = new siteindicator_manager();
            $ret_val = $siteindicator_manager->get_assignable_roles($site_type);
        }
        
        return $ret_val;
    }
    
    /**
     * Returns the list of assignable roles for given courseid.
     * 
     * @param int $courseid
     * 
     * @return array        Returns an array in the following format:
     *                      [shortname] => [name]
     */
    static function get_assignable_roles_by_courseid($courseid) {
        // first, find out what site type course is
        $site_type = null;
        
        // see if it is a registrar course
        $result = ucla_map_courseid_to_termsrses($courseid);        
        if (!empty($result)) {  // found registrar course
            $site_type = siteindicator_manager::SITE_TYPE_SRS_INSTRUCTION;
        } else {
            try {
                $indicator = new siteindicator_site($courseid);
                $site_type = $indicator->property->type;            
            } catch (Exception $e) {
                // throws an exception if no site type found, so just do nothing
                // and use default below
            }
        }
        
        // if site type is null, then default to instructional collab sites
        if (empty($site_type)) {
            $site_type =  siteindicator_manager::SITE_TYPE_INSTRUCTION;
        }
        
        return self::get_assignable_roles($site_type);
    }
    
    /**
     * Returns an array of role types.
     * 
     * @return array
     */
    static function get_role_types() {
       return array(
           uclaroles_manager::ROLE_TYPE_SUPPORTSTAFF => get_string(uclaroles_manager::ROLE_TYPE_SUPPORTSTAFF, 'tool_uclaroles'),
           uclaroles_manager::ROLE_TYPE_INSTEQUIV => get_string(uclaroles_manager::ROLE_TYPE_INSTEQUIV, 'tool_uclaroles'),
           uclaroles_manager::ROLE_TYPE_REDUCEDEDITING => get_string(uclaroles_manager::ROLE_TYPE_REDUCEDEDITING, 'tool_uclaroles'),
           uclaroles_manager::ROLE_TYPE_STUDENTEQUIV => get_string(uclaroles_manager::ROLE_TYPE_STUDENTEQUIV, 'tool_uclaroles'),
           uclaroles_manager::ROLE_TYPE_GUESTEQUIV => get_string(uclaroles_manager::ROLE_TYPE_GUESTEQUIV, 'tool_uclaroles'),
           uclaroles_manager::ROLE_TYPE_SECONDARY => get_string(uclaroles_manager::ROLE_TYPE_SECONDARY, 'tool_uclaroles'),
           uclaroles_manager::ROLE_TYPE_CORE => get_string(uclaroles_manager::ROLE_TYPE_CORE, 'tool_uclaroles'),); 
    }  

    /**
     * Returns an array of site types.
     * 
     * Calls site indicator for most of the types, but adds a type for regular,
     * srs instructional course sites.
     * 
     * @return array
     */
    static function get_site_types() {
        // prefix srs instructional site
        $ret_val[siteindicator_manager::SITE_TYPE_SRS_INSTRUCTION] 
                = get_string('srs_instruction', 'tool_uclaroles');  
        
        $site_types = siteindicator_manager::get_types_list();
        // $site_types is in format of [type] => array(), need it to be [type] => [fullname]
        foreach ($site_types as $key => $site_type) {
            $ret_val[$key] = $site_type['fullname'];
        }     
        
        return $ret_val;
    }

    /**
     * Takes array of role objects and orders them by role type
     * 
     * @param array $roles   An array of entries from role table
     */
    static function orderby_role_type($roles) {
        $ret_val = array();
        
        // order ret_val by $role_type order
        $role_types = self::get_role_types();
        foreach ($role_types as $type => $name) {
            $ret_val[$type] = array();
        }        
        
        // go through each role and find its role type
        foreach ($roles as $role) {
            $type = self::find_role_type($role);
            $ret_val[$type][] = $role;
        }
        
        // remove all role types with no roles
        foreach ($ret_val as $index => $roles) {
            if (empty($roles)) {
                unset($ret_val[$index]);
            }
        }
        
        return $ret_val;
    }    
    
    // PRIVATE METHODS
    
    /**
     * For given role object, finds what role type it is.
     * @param object $role      Entry from role table
     * 
     * @return string           Returns role type. If none found, returns 
     *                          'noroletype'.
     */
    private static function find_role_type($role) {
        $found_role_type = null;        
        $role_types = self::get_role_types();
        
        foreach ($role_types as $role_type => $type_text) {
            if (strpos($role->description, $role_type) !== false) {
                $found_role_type = $role_type;
                break;  // found role type
            } 
        }    
        
        if (empty($found_role_type)) {
            $found_role_type = 'noroletype';
        }        
        
        return $found_role_type;
    }
}