<?php

class collab_handler extends browseby_handler {
    static function get_default_roles_visible() {
        return array('project_lead', 'coursecreator', 'editinginstructor');
    }

    function get_params() {
        return array('category');
    }

    function handle($args) {
        global $CFG, $PAGE;

        $navbar =& $PAGE->navbar;

        $collablibfile = $CFG->dirroot . '/' . $CFG->admin 
            .'/tool/uclasiteindicator/lib.php';

        $collab_cat = false;

        $t = '';
        $s = '';

        if (file_exists($collablibfile)) {
            
            require_once($collablibfile);
        
            // Get YUI searchbox script
            siteindicator_manager::searchbox_js_require();

            // Print the search box
            $s .= siteindicator_manager::print_collab_searchbox();

            $collab_cat = $this->get_collaboration_category();
            siteindicator_manager::filter_category_tree($collab_cat);

            // Check if the category specified is a sub-category
            // of the collaboration category; if so, use that
            if ($collab_cat && isset($args['category'])) {
                $collab_cat_id = $args['category'];

                $collab_subcat = $this->find_category($collab_cat_id, 
                    $collab_cat->categories, 'id');

                if (!$collab_subcat) {
                    print_error('collab_notcollab', 'block_ucla_browseby');
                }
            
                $collab_cat = $collab_subcat;
                $t = get_string('collab_viewin', 'block_ucla_browseby',
                    $collab_subcat->name);
            }
        } else {
            // 
            return array(false, false);
        }

        if (!$collab_cat) {
            print_error('collab_notfound', 'block_ucla_browseby');
        }

        $defaulttitle = get_string('collab_viewall', 'block_ucla_browseby');
        if (empty($t)) {
            $t = $defaulttitle;
        } else {
            $navbar->add($defaulttitle, 
                new moodle_url('/blocks/ucla_browseby/view.php',
                    array('type' => 'collab')));
        }
    
        $categorylist = array();
        if (!empty($collab_cat->categories)) {
            $categorylist = $collab_cat->categories;
        }

        // Render list of categories

        $courselist = array();
        if (!empty($collab_cat->courses)) {
            // Default roles to use, get these shortname's role.id
            $rolenames = self::get_default_roles_visible();
            $allroles = get_all_roles();

            $iroles = array();
            foreach ($allroles as $role) {
                $iroles[$role->shortname] = $role;
            }

            $roleids = array();
            $rolefullnames = array();
            foreach ($rolenames as $rolename) {
                if (isset($iroles[$rolename])) {
                    $role = $iroles[$rolename];

                    $rshortname = $role->shortname;
                    $roleids[$rshortname] = $role->id;
                    $rolefullnames[$rshortname] = $role->name;
                }
            }

            if (empty($roleids)) {
                debugging('No roles to use in printing!');
            } else {
                foreach ($collab_cat->courses as $course) {
                    // Skip NULL courses
                    if(empty($course)) {
                        continue;
                    }
                    
                    $context = $this->get_context_instance(CONTEXT_COURSE,
                        $course->id);

                    $viewroles = $this->get_role_users($roleids, $context,
                        false, 'u.id, u.firstname, u.lastname, r.shortname');

                    $courseroles = array();
                    foreach ($viewroles as $viewrole) {
                        $rsh = $viewrole->shortname;
                        if (isset($iroles[$rsh])) {
                            if (!isset($courseroles[$rsh])) {
                                $courseroles[$rsh] = array();
                            }

                            $courseroles[$rsh][] = $viewrole;
                        }
                    }

                    $course->roles = $courseroles;

                    $courselist[] = $course;
                }
            }
        }
   
        $rendercatlist = array();
        foreach ($categorylist as $category) {
            if(!empty($category)) {
                $rendercatlist[] = html_writer::link(
                    new moodle_url('/blocks/ucla_browseby/view.php',
                        array('category' => $category->id, 'type' => 'collab')),
                    $category->name
                );
            }
        }

        // Category heading
        if(empty($collab_cat->name)) {
            $title = get_string('collab_allcatsincat', 
                    'block_ucla_browseby');
        } else {
            $title = get_string('collab_catsincat', 
                    'block_ucla_browseby') . $collab_cat->name;
        }
        
        if(!empty($rendercatlist)) {
            $s .= $this->heading($title, 3);
        }
        
        $s .= block_ucla_browseby_renderer::ucla_custom_list_render(
            $rendercatlist);

        $title = '';
        $list = '';
        if (!empty($courselist)) {
            $title = get_string('collab_coursesincat', 
                'block_ucla_browseby') . $collab_cat->name;
            $data = array();

            foreach ($courselist as $course) {
                $datum = array();
                $datum[] = html_writer::link(
                    uclacoursecreator::build_course_url($course),
                    $course->fullname
                );

                $nameslist = array();
                foreach ($roleids as $shortname => $roleid) {
                    $nameimploder = array();

                    if (!empty($course->roles[$shortname])) {
                        foreach ($course->roles[$shortname] as $role) {
                            $nameimploder[] = fullname($role);
                        }
                    }
                    
                    if (!empty($nameimploder)) {
                        $nameslist[] = implode(' / ', $nameimploder);
                    }
                }
                
                $datum[] = empty($nameslist) ? get_string('nousersinrole', 
                            'block_ucla_browseby') : implode(' ', $nameslist);

                $data[] = $datum;
            }

            $table = new html_table();
            $table->data = $data;

            $headers = array('sitename', 'projectlead');
            $dispheaders = array();

            foreach ($headers as $header) {
                $dispheaders[] = get_string($header, 'block_ucla_browseby');
            }

            $table->head = $dispheaders;

            $list = html_writer::table($table);
        } else {
            $title = get_string('collab_nocoursesincat', 
                'block_ucla_browseby');
        }

        $s .= $this->heading($title, 3) . $list;

        return array($t, $s);
    }

    function get_collaboration_category() {
        global $CFG;

        $colcat = false;
                
        // START UCLA MOD CCLE-2389 - adding site indicator support
        // Want the whole category tree for siteindicator to filter
        if (!$colcat) {
            $colcat->categories = $this->get_category_tree();
        }
        // END UCLA MOD CCLE-2389

        // Try a custom collaboration category name
        if (!empty($CFG->collaboration_category_name)) {
            $colcat = $this->get_category($CFG->collaboration_category_name);
        } 

        // Try the hard-coded default
        if (!$colcat) {
            $colcat = $this->get_category('Collaboration Sites');
        }

        // Give up
        if (!$colcat) {
            return false;
        }

        return $colcat;
    }
    
    /**
     *  Finds the category from the tree.
     **/
    function get_category($name) {
        if (!isset($this->cat_tree)) {
            $this->cat_tree = $this->get_category_tree();
        }

        $tree = $this->cat_tree;

        return $this->find_category($name, $tree);
    }

    function find_category($name, $categories, $field='name') {
        foreach ($categories as $category) {
            if (!empty($category) && $category->{$field} == $name) {
                return $category;
            } 
           
            $dfs = false;
            if (!empty($category->categories)) {
                $dfs = $this->find_category($name, $category->categories, 
                    $field);
            }

            if ($dfs) {
                return $dfs;
            }
        }

        return false;
    }

    /**
     *  Some more decoupled functions...
     **/
    protected function get_role_users($roles, $context, $parent=false, 
                                      $fields='') {
        return get_role_users($roles, $context, $parent, $fields);
    }

    protected function get_category_tree() {
        return get_course_category_tree();
    }

    protected function get_context_instance($ct, $id) {
        return get_context_instance($ct, $id);
    }

    protected function heading($heading, $level=1) {
        global $OUTPUT;

        return $OUTPUT->heading($heading, $level);
    }
}
