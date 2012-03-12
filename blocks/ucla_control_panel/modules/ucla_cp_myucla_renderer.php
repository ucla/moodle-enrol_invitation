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
        $table = new html_table();
        $table->attributes = array ('id' => 'myUCLAFunctions');
        $handler='general_descriptive_link';
        
        foreach ($contents as $content_rows) {
            //print_object($content_rows);
            $content_rows_elements = $content_rows->elements;
            $table_row = new html_table_row();
            foreach ($content_rows_elements as $content_item) {
               $table_row->cells[] = html_writer::tag('td',ucla_cp_renderer::$handler(
                    $content_item));
            }
            $table->data[] = $table_row;
        }

        return html_writer::table($table);
    }
}
