<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/navigation/renderer.php');

class block_ucla_course_menu_renderer extends block_navigation_renderer {
    // Default defaults
	private $topic_depth = 1;
	private $chapter_depth = 2;
	private $subchater_depth = 3;
	private $session;
	private $displaysection = 1000;

    /**
     *  Calls block_navigation_renderer's protected function.
     **/
    public function navigation_node($i, $a=array(), $e=null, 
            $o=array(), $d=1) {
        return parent::navigation_node($i, $a, $e, $o, $d);
    }
	
	public function render_chapter_tree($instance, $config, $chapters, 
            $sections, $displaysection) {
		$this->displaysection = $displaysection;

		if ($config->chapenable) {
			$this->topic_depth++;
			if ($config->subchapenable) {
				$this->topic_depth++;
			}
		}

		$sectionindex = 0;
		$contents = '';

		foreach ($chapters as $chapter) {
			$subchapter = '';

			foreach ($chapter['childelements'] as $child) {
				$topic = '';
				$cl = "";

                // Split the groups up between subchapters
				if ($child['type'] == 'subchapter') {
					for ($i = 0; $i < $child['count']; $i++) {
						$topic .= $this->render_topic($config, 
                            $sections[$sectionindex], 0, 
                            $displaysection == $sectionindex);
						$sectionindex++;
					}

					if ($config->subchapenable) {
						$title = html_writer::tag('span', $child['name'], 
                            array('class' => 'item_name'));
						$p = html_writer::tag('p', $title, 
                            array('class' => 'cm_tree_item tree_item branch'));
						$topic = html_writer::tag('ul', $topic);
						$collapsed = "collapsed";
						if ($child['expanded']) {
							$collapsed = "";
						}

						$topic = html_writer::tag('li', $p . $topic, array(
                            'class' => "type_structure "
                                . "depth_{$this->subchater_depth} "
                                . "{$collapsed} contains_branch"
                        ));
					}
				} else { //topic
					$d = $this->topic_depth;
					if ($config->subchapenable) {
						$d--;
					}

					$topic = $this->render_topic($config, 
                        $sections[$sectionindex], $d, 
                        $displaysection == $sectionindex);

					$sectionindex++;
				}

				$subchapter .= $topic;
			}

			// $subchapter - a collection of <li> elements
			if ($config->chapenable) {
				$subchapter = html_writer::tag('ul', $subchapter);
				$title = html_writer::tag('span', $chapter['name'], 
                    array('class' => 'item_name'));

				$p = html_writer::tag('p', $title, 
                    array('class' => 'cm_tree_item tree_item branch'));

				$collapsed = "collapsed";
				if ($chapter['expanded']) {
					$collapsed = "";
				}

				$contents .= html_writer::tag('li', $p . $subchapter, array(
                    'class' => "type_structure " 
                        . "depth_{$this->chapter_depth} $collapsed "
                        . "contains_branch"
                ));
			} else {
				$contents .= $subchapter;
			}
		}

		return '<li style="height: 5px">&nbsp;</li>' 
            . $contents . '<li style="height: 5px">&nbsp;</li>';
	}
	
	function render_topic($config, $section, $depth=0, $current=false) {
		global $OUTPUT;

		if ($depth == 0) {
			$depth = $this->topic_depth;
		}

		$html = '';
        $attributes = array('class' => 'section_link', 
            'title' => $section['name']);

        if (!$section['visible']) {
            $attributes['class'] .= 'dimmed_text';
        }

        if ($current) {
            $attributes['class'] .= ' active_tree_node';
        }

        $leaficon = $this->default_nav_icon($section['trimmed_name']);
			
        $html = $this->render_leaf($section['trimmed_name'], $leaficon, 
            $attributes, $section['url'], $current); 	
		
		return $html;
	}

    function default_nav_icon($name) {
        global $OUTPUT;

        return $this->icon($OUTPUT->pix_url('i/navigationitem'),
            $name, array('class' => 'smallicon'));
    }
	
	public function render_leaf($visible_title, $icon, $attributes, $link, 
            $current=false, $extraNode='') {

        if (!$icon) {
            $icon = $this->default_nav_icon($visible_title);
        }

		$html = html_writer::link($link, $icon . $visible_title . $extraNode, 
            $attributes);

		$html = html_writer::tag('p', $html, 
            array ('class' => 'tree_item leaf hasicon'));

		$append = "";
		if ($current) {
			$append = "active_tree_node";
		}

		$html = html_writer::tag('li', $html,
            array('class' => "type_custom item_with_icon {$append}"));

		return $html;
	}
	
	public function icon($src, $title, $props=array()) {
		$p = "";

		foreach ($props as $p => $v) {
			$p .= '"' . $p . '=' . $v . '" ';
		}

		return '<img src="' . $src . '" 
				class="smallicon" title="' . $title . '"
				alt="' . $title . '" ' . $p . ' />';
	}
	
	public function render_link($link, $course)	{
		global $CFG;
		$url = $link['url'];
		if ($link['keeppagenavigation']) {
			$url = $CFG->wwwroot 
                . "/blocks/ucla_course_menu/link_with_navigation.php?courseid={$course}&url={$link['url']}&name={$link['name']}";
		}
		$icon = '';
		if ($link['icon']) {
			$icon = $this->icon($link['icon'], $link['name'], array('class' => 'smallicon navicon'));
		}
		return $this->render_leaf($link['name'], $icon, array(), $url);
	}
	
    public function render_navigation_node(navigation_node $node, $expansionlimit) 
    {
    	$content = '';
    	if ($node->children->count()) {
    		$content = $this->navigation_node($node->children, array('class' => ''), $expansionlimit);
	    	$span = html_writer::tag('span', $node->get_content(), array('class' => 'item_name'));
	    	$p = html_writer::tag('p', $span, array('class' => 'cm_tree_item tree_item branch'));
	    	$collapsed = "";
	    	if (!in_array(md5($node->get_content()), $this->session)) {
	    		$collapsed = "collapsed";
	    	}
	    	$content = html_writer::tag('li', $p . $content, array('class' => "type_system depth_2 contains_branch {$collapsed}"));
    	}
    	return $content;
    }
    

}
