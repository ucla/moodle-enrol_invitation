<?php
///
// Block edit GUI elements


/**
 * Edit GUI for site headers 
 */
class alert_edit_header extends html_element {
    public function __construct($headers, $section) {

        $allheaders = array();
        
        foreach($headers as $header) {
            $box = new alert_html_header_box($header->item);
            $box->add_class('alert-header-'. $header->color)
                ->add_class('box-boundary');
            
            $edit = new alert_edit_textarea_box($header->item, 2);
            $edit->add_class('alert-header-' . $header->color);
            
            $box_header = new alert_html_box_content(array($box, $edit));
            $box_header->add_attribs(array(
                'rel' => $header->item,
                'render' => 'header',
                'visible' => $header->visible,
                'color' => $header->color,
                'recordid' => $header->recordid,
                'entity' => $header->entity,
                'class' => 'alert-edit-header-wrapper alert-edit-element'
            ));
            
            $allheaders[] = $box_header;
        }

        parent::__construct('div', array($allheaders, new alert_edit_section($section)), 
                array('class' => 'alert-edit-header block-ucla-alert'));
    }
}

/**
 * Edit GUI for main section 
 */
class alert_edit_section extends html_element {
    public function __construct($section) {

        $title = new alert_html_section_title($section->title);
        
        // Create <li> list
        $ullist = array();
        foreach($section->items as $item_text) {
            $ullist[] = new alert_edit_section_li($item_text);
        }
        
        $ul = new html_element('ul', $ullist);
        $ul->add_attrib('title', trim($section->title))
           ->add_attrib('entity', $section->entity)
           ->add_attrib('visible', $section->visible)
           ->add_attrib('recordid', $section->recordid);
        
        parent::__construct('div', array($title, $ul), 
                array('class' => 'alert-edit-section block-ucla-alert'));
    }
}

class alert_edit_section_li extends html_element {
    public function __construct($text) {
        $item = new alert_html_section_item(trim($text));
        $edit = new alert_edit_textarea_box($text);
        
        $attribs = array(
            'class' => 'alert-edit-item alert-edit-element',
            'rel' => trim($text),
            'render' => 'item'
        );
        
        parent::__construct('li', array($item, $edit), $attribs);
    }
    
}

/**
 * Edit GUI for 'scratch' section 
 */
class alert_edit_section_scratch extends alert_edit_section {
    public function __construct($section) {
        parent::__construct($section);
        
        $add = new html_element('button', get_string('scratch_button_add', 'block_ucla_alert'));
        $add->add_class('btn')
//            ->add_class('btn-mini')
            ->add_class('btn-primary')
            ->add_class('alert-edit-add');
        
        $div = new html_element('div', $add, array('class' => 'alert-edit-scratch-add'));
        $div->add_attrib('rel', get_string('scratch_item_new', 'block_ucla_alert'));
        
        $this->add_content($div);
    }
}

class alert_edit_textarea_box extends html_element {
    public function __construct($text, $rows = 8) {
        
        $textarea = new html_element('textarea');
        $textarea->add_class('alert-edit-textarea')
                 ->add_attrib('rows', $rows)
                 ->add_content($text);
        
        parent::__construct('div', array($textarea, new alert_edit_button_box()), 
                array('class' => 'alert-edit-text-box'));
    }
}

class alert_edit_button_box extends html_element {
    public function __construct() {
        $save = new html_element('button', get_string('item_edit_save', 'block_ucla_alert'));
        $save->add_class('btn')
             ->add_class('btn-mini')
             ->add_class('btn-success')
             ->add_class('alert-edit-save');
        
        $cancel = new html_element('button', get_string('item_edit_cancel', 'block_ucla_alert'));
        $cancel->add_class('btn')
               ->add_class('btn-mini')
               ->add_class('btn-danger')
               ->add_class('alert-edit-cancel');
        
        parent::__construct('div', array($save, $cancel), 
                array('class' => 'alert-edit-button-box'));
    }
}

/**
 * Save changes GUI 
 * 
 * Displays a 'Save | Cancel' butons 
 */
class alert_edit_commit_box extends html_element {
    public function __construct() {
        $save = new html_element('button', get_string('alert_commit_save', 'block_ucla_alert'));
        $save->add_class('btn')
             ->add_class('btn-success')
             ->add_class('alert-edit-save');
        
        $cancel = new html_element('button', get_string('item_edit_cancel', 'block_ucla_alert'));
        $cancel->add_class('btn')
               ->add_class('btn-danger')
               ->add_class('alert-edit-cancel');
        
        parent::__construct('div', array($save, $cancel), 
                array('class' => 'alert-edit-commit-box'));
    }
}