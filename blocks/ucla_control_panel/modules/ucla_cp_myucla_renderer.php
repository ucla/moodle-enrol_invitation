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

class ucla_cp_myucla_renderer extends ucla_cp_renderer {
 
   
    /**
     *  This function will take the contents of a 2-layer deep
     *  array and generate the string that contains the contents
     *  in a div-split table. It can also generate the contents.
     *
     *  @param array $contents - The contents to diplay using the renderer.
     **/
    static function control_panel_contents($contents) {

        $full_table = '';
        $handler='general_descriptive_link';
        
        foreach ($contents as $content_row) {
            
            $row_contents = '';
            
            foreach ($content_row as $content_item => $content_link) {
                $row_contents .= html_writer::tag('td',ucla_cp_renderer::$handler(
                    $content_link));
            }
            $full_table .= html_writer::tag('tr', $row_contents);
        }

        return $full_table;
    }
}
