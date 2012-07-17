<?php

require_once($CFG->libdir.'/formslib.php');
require_once(dirname(__FILE__) . '/requestor_shared_form.php');

// View entries already requested
class requestor_view_form extends requestor_shared_form {
    var $type = 'viewcourses';
    var $noterm = true;

    const noviewcourses = 'noviewcourses';

    function specification() {
        $rucr = 'tool_uclacourserequestor';

        $mf =& $this->_form;

        $filters = $this->_customdata['prefields'];

        $group = array();

        foreach ($filters as $filter => $possibilities) {
            $filterall = $this->get_all_filter($filter);

            $options = array($filterall => get_string($filterall, $rucr));

            if (!empty($possibilities)) {
                foreach ($possibilities as $poss) {
                    $posstext = requestor_statuses_translate($poss);

                    if (empty($poss)) {
                        $posstext = get_string('none');
                    }

                    $options[$poss] = $posstext;
                }
            }

            $group[] =& $mf->createElement('select', $filter,
                null, $options);
        }

        if (empty($group)) {
            $this->type = self::noviewcourses;
            $group[] =& $mf->createElement('static', 'staticlabel',
                self::noviewcourses);
        }

        return $group;
    }

    function post_specification() {
        if ($this->type == self::noviewcourses) {
            $this->_form->hardFreeze();
        }
    }

    /**
     *  Build the Moodle DB API conditions and fetch requests from tables.
     **/
    function respond($data) {
        global $DB;

        $filters = $this->_customdata['prefields'];
        $ci = $data->{$this->groupname};

        foreach ($filters as $filter => $result) {
            $all = $this->get_all_filter($filter);
            
            // Check if a non-"all" value is submitted for each filter.
            if (!empty($ci[$filter]) && $ci[$filter] == $all) {
                // For an "all" value, just remove it from the WHERE
                unset($filters[$filter]);
            } else {
                $filters[$filter] = $ci[$filter];
            }
        }

        // No need to repeat courses if we're not searching for a specific
        // course
        if (!isset($filters['srs'])) {
            $filters['hostcourse'] = 1;
        }

        $reqs = $DB->get_records('ucla_request_classes', $filters,
            'term, department, course');

        $sets = array();
        foreach ($reqs as $req) {
            $req = get_object_vars($req);
            $set = get_crosslist_set_for_host($req);
            $host = $set[set_find_host_key($set)];

            $sets[make_idnumber($host)] = $set;
        }

        return $sets;
    }

    function get_all_filter($filter) {
        return 'all_' . $filter;
    }
}

