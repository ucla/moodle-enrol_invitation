<?php

abstract class ucla_cp_module {
    /** This is used designate which function to use to display the
        data. **/
    var $handler;

    /** This designates column mode or rows mode **/
    var $orientation;

    /**
        get_content_array()

        @return Array This will return the data sorted into tables.
            Normally, this table will be 2 levels deep (Array => Array).
            Each key should be the identifier within the lang file
            that uses a language convention.

            <item>_pre represents strings that are printed before the link.
            <item>_post represents the string that is printed after the link.
    **/
    abstract function get_content_array($course);

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
    function general_descriptive_link($item, $link, $pre=false, $post=true) {
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
    function general_icon_link($item, $link, $pre=false, $post=false) {
        global $OUTPUT;

        $bucp = 'block_ucla_control_panel';

        $fitem = '';
        $fitem .= html_writer::empty_tag('img', 
            array('src' => $OUTPUT->pix_url('cp_' . $item)));

        $fitem .= $this->general_descriptive_link($item, $link, $pre, $post);

        return $fitem;
    }
   
    /**
        This function will take the contents of a 2-layer deep
        array and generate the string that contains the contents
        in a div-split table.
    **/
    function control_panel_contents($course) {
        $full_table = '';
        
        $columns = ($this->orientation == 'col');

        // even odd toggle
        $eo = false;

        // left-right enable
        $lre = false;

        $contents = $this->get_content_array($course);

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
                $handler = 'handler_' . $content_item;
 
                if (!$columns && $lre) {
                    if ($add_class == ' left') {
                        $add_class = ' right';
                    } else {
                        $add_class = ' left';
                    }
                }

                $the_output = html_writer::start_tag('div', 
                    array('class' => 'item' . $add_class));

                if (method_exists($this, $handler)) {
                    $the_output .= $this->$handler($course, $content_item);
                } else if ($content_link != null) {
                    $the_output .= $this->{$this->handler}($content_item, 
                        $content_link);
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
                array('class' => 'table' . $this->orientation . $evenodd));
        }

        return $full_table;
    }
}
