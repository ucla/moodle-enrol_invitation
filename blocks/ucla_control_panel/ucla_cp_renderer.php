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

class ucla_cp_renderer {
    private $history = array();

    /**
        get_content_array()

        @return Array This will return the data sorted into tables.
            Normally, this table will be 2 levels deep (Array => Array).
            Each key should be the identifier within the lang file
            that uses a language convention.

            <item>_pre represents strings that are printed before the link.
            <item>_post represents the string that is printed after the link.
    **/
    static function get_content_array($contents, $size=null) {
        $all_stuff = array();

        if ($size === null) {
            $size = floor(count($contents) / 2) + 1;

            if ($size == 0) {
                $size = 1;
            }
        }

        foreach ($contents as $content) {
            $action = $content;
            $title = $content->item_name;

            $all_stuff[$title] = $action;
        }

        ksort($all_stuff);

        $disp_stuff = array();

        $disp_cat = array();
        foreach ($all_stuff as $title => $action) {
            if (count($disp_cat) == $size) {
                $disp_stuff[] = $disp_cat;
                $disp_cat = array();
            }

            $disp_cat[$title] = $action;
            // Figure out how the fuck to do this?
        }

        if (!empty($disp_cat[$title])) {
            $disp_stuff[] = $disp_cat;
        }

        return $disp_stuff;
    }

    /**
        Builds the string with the string and the descriptions, pre and post.
        @param string $item - This is the identifier for the current control 
            panel item.
        @param moodle_url $link - This is the link that users should be
            sent to with regards to the link.
        @param boolean $pre - Whether we should enable displaying of the
            get_string($item . '_pre', ... ).
        @param boolean $post - Whether we should enable displaying of the
            get_string($item . '_post', ... ).
        @return string The DOMs of the control panel description and link.
    **/
    static function general_descriptive_link($item, $link, 
            $pre=false, $post=true) {
        $fitem = '';
        
        $bucp = 'block_ucla_control_panel';

        if ($pre !== false) {
            $fitem .= html_writer::tag('span', get_string($item . '_pre', 
                $bucp), array('class' => 'pre-link'));
        }

        $fitem .= html_writer::link($link, get_string($item, $bucp));

        if ($post !== false) {
            $fitem .= html_writer::tag('span', get_string($item . '_post', 
                $bucp), array('class' => 'post-link'));
        }

        return $fitem;
    }

    /**
        Adds an icon to the link and description.

        @param string $item - @see general_descriptive_link.
        @param moodle_url $link - @see general_descriptive_link.
        @param boolean $pre - @see general_descriptive_link.
        @param boolean $post - @see general_descriptive_link.
        @return string The DOMs of the control panel, with an image
            and whatever is returned by @see general_descriptive_link.
    **/
    static function general_icon_link($item, $link, 
            $pre=false, $post=true) {
        global $OUTPUT;

        $bucp = 'block_ucla_control_panel';

        $fitem = '';
        $fitem .= html_writer::empty_tag('img', 
            array('src' => $OUTPUT->pix_url('cp_' . $item)));

        $fitem .= ucla_cp_renderer::general_descriptive_link($item, $link, 
            $pre, $post);

        return $fitem;
    }
   
    /**
        This function will take the contents of a 2-layer deep
        array and generate the string that contains the contents
        in a div-split table.
    **/
    function control_panel_contents($contents, $format=false, 
            $orient='col', $handler='general_descriptive_link') {
        if ($format) {
            $contents = ucla_cp_renderer::get_content_array($contents);
        }

        $full_table = '';
        
        $columns = ($orient == 'col');

        // even odd toggle
        $eo = false;

        // left-right enable
        $lre = false;

        if ($columns && count($contents) == 2) {
            $lre = true;
        }

        foreach ($contents as $content_row) {
            if ($eo) {
                $evenodd = ' even';
            } else {
                $evenodd = ' odd';
            }

            $row_contents = '';

            if (!$columns && count($content_row) <= 2) {
                $lre = true;
            } 

            $add_class = '';
            foreach ($content_row as $content_item => $content_link) {
                if (!$columns && $lre) {
                    if ($add_class == ' left') {
                        $add_class = ' right';
                    } else {
                        $add_class = ' left';
                    }
                }

                $the_output = html_writer::start_tag('div', 
                    array('class' => 'item' . $add_class));
    
                $content_action = $content_link->get_action();
                if ($content_action != null) {
                    // this part sucks
                    if ($content_link->get_opts('pre') !== null) {
                        if ($content_link->get_opts('post') !== null) {
                            $the_output .= ucla_cp_renderer::$handler(
                                $content_item, $content_action,
                                $content_link->get_opts('pre'),
                                $content_link->get_opts('post'));
                        } else {
                            $the_output .= ucla_cp_renderer::$hanlder(
                                $content_item, $content_action,
                                $content_link->get_opts('pre'));
                        }
                    } else {
                        $the_output .= ucla_cp_renderer::$handler(
                            $content_item, $content_action);
                    }
                } else {
                    debugging($content_item . ' is set incorrectly!');
                }

                $the_output .= html_writer::end_tag('div');
                $row_contents .= $the_output;
            }

            // Flip per row
            $eo = !$eo;
            
            if ($columns) {
                if ($lre) {
                    $evenodd = ' left';
                } else {
                    $evenodd = ' right';
                }

                $lre = !$lre;
            }

            $full_table .= html_writer::tag('div', $row_contents, 
                array('class' => 'table' . $orient . $evenodd));
        }

        return $full_table;
    }
}
