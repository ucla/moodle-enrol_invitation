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
            $title = $content->get_key();

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
        }

        if (!empty($disp_cat)) {
            $disp_stuff[] = $disp_cat;
        }

        return $disp_stuff;
    }

    /**
        Builds the string with the string and the descriptions, pre and post.
        @param ucla_cp_module $item_obj - This is the identifier for the 
            current control panel item.
        @return string The DOMs of the control panel description and link.
    **/
    static function general_descriptive_link($item_obj) {
        $fitem = '';
        
        $bucp = 'block_ucla_control_panel';

        $item = $item_obj->item_name;
        $link = $item_obj->get_action();

        if ($item_obj->get_opt('pre')) {
            $fitem .= html_writer::tag('span', get_string($item . '_pre', 
                $bucp, $item_obj), array('class' => 'pre-link'));
        }

        if ($link === null) {
            $fitem .= html_writer::tag('span', get_string($item, $bucp,
                $item_obj), array('class' => 'disabled'));
        } else {
            $fitem .= html_writer::link($link, get_string($item, $bucp, 
                $item_obj));
        }

        // One needs to explicitly hide the post description
        if ($item_obj->get_opt('post') !== false) {
            $fitem .= html_writer::tag('span', get_string($item . '_post', 
                $bucp, $item_obj), array('class' => 'post-link'));
        }

        return $fitem;
    }

    /**
        Adds an icon to the link and description.

        @param ucla_cp_modules $item_obj - The item to display.
        @return string The DOMs of the control panel, with an image
            and whatever is returned by @see general_descriptive_link.
    **/
    static function general_icon_link($item_obj) {
        global $OUTPUT;

        $bucp = 'block_ucla_control_panel';

        $item = $item_obj->item_name;

        $fitem = '';
        $fitem .= html_writer::empty_tag('img', 
            array('src' => $OUTPUT->pix_url('cp_' . $item)));

        if ($item_obj->get_opt('post') === null) {
            $item_obj->set_opt('post', false);
        }

        $fitem .= ucla_cp_renderer::general_descriptive_link($item_obj);

        return $fitem;
    }
   
    /**
        This function will take the contents of a 2-layer deep
        array and generate the string that contains the contents
        in a div-split table. It can also generate the contents.

        @param array $contents - The contents to diplay using the renderer.
        @param boolean $format - If this is true, then we will send the data
            through {@link get_content_array}.
        @param string $orient - Which orientation handler to use to render the
            display. Currently accepts two options (defaults to rows) if the
            option does not exist.

            'col': This means that we expect an array containing 2 arrays of
                the elements we wish to render.

            'row': This means taht we expect an array containing arrays each
                with 2 of the elements we wish to render.

        @param string $handler - This is the callback function used to display
            each element. Defaults to general_descriptive_link, and will crash
            the script if you provide a non-existant function.
    **/
    static function control_panel_contents($contents, $format=false, 
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

        if (!$columns && count($contents) == 2) {
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

                $the_output .= ucla_cp_renderer::$handler(
                    $content_link);

                $the_output .= html_writer::end_tag('div');
                $row_contents .= $the_output;
            }

            // Flip per row
            $eo = !$eo;
            
            if ($columns) {
                if ($lre) {
                    $evenodd = ' right';
                } else {
                    $evenodd = ' left';
                }

                $lre = !$lre;
            }

            $full_table .= html_writer::tag('div', $row_contents, 
                array('class' => 'table' . $orient . $evenodd));
        }

        return $full_table;
    }
}
