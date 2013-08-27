<?php
/**
 * This is a wrapper class for the moodle html_table set of objects, to allow
 * for testing of table construction and contents
 */
class Elluminate_Moodle_Table implements Elluminate_HTML_Table{
   
   const TITLE_CLASS = 'elluminatetitle';
   const TABLE_HEADER_CLASS = 'header elluminatesessioninfotableheader';
   const TABLE_TITLE_CLASS = 'header elluminatesessiontabletitle';
   const TABLE_NAMEVALUE_CLASS = 'elluminatesessioninfotablename';
   
   private $moodleTable;
   private $tableRows;
   
   public function init($columnAlignment){
      $this->moodleTable = new html_table();
      $this->moodleTable->tablealign = 'center';
      $this->moodleTable->align = $columnAlignment;
      $this->tableRows = array();
   }  
   
   /**
    * Adds a row at the top of the table that spans the other rows
    * and acts as a title for the table
    * @see Elluminate_HTML_Table::addHeaderRow()
    */
   public function addHeaderRow($headerText,$colSpan){
      $header = new html_table_cell($headerText);
      $header->attributes['class'] = self::TABLE_TITLE_CLASS;
      $header->colspan = $colSpan;
   
      $headerRow = new html_table_row(array($header));
      $this->tableRows[] = $headerRow;
   }
   
   public function addSpanRow($cellValue,$colSpan){
      $spanCell = new html_table_cell($cellValue);
      $spanCell->colspan = $colSpan;
       
      $spanRow = new html_table_row(array($spanCell));
      $this->tableRows[] = $spanRow;
   }
   
   public function addColumnHeaders($columnNameKeyArray){
      $cellArray = array();
      foreach($columnNameKeyArray as $header){
         if ($header == ''){
            $headerString = '';
         }else{
            $headerString = get_string($header,'elluminate');
         }
         $headerCell = new html_table_cell($headerString);
         $headerCell->attributes['class'] = self::TABLE_HEADER_CLASS;
         $cellArray[] = $headerCell;
      }
      
      $headerRow = new html_table_row($cellArray);
      $this->tableRows[] = $headerRow;
   }
   
   public function addNameValueRow($title, $titleSpan = 0, $value, $valueSpan = 0, $action = null){
      $titleCell = new html_table_cell($title);
      if ($titleSpan > 1){
         $titleCell->colspan = $titleSpan;
      }
      $titleCell->attributes['class'] = self::TABLE_NAMEVALUE_CLASS;
   
      $actionHTML = '';
      if ($action != null){
         $actionHTML = "<div class='elluminateactionicon'>" . $action . '</div>';
      }
      
      $valueCell = new html_table_cell($value . $actionHTML);
   
      if ($valueSpan > 1){
         $valueCell->colspan = $valueSpan;
      }
      
      $row = new html_table_row(array($titleCell, $valueCell));
       
      $this->tableRows[] = $row;
   }
   
   public function addRow($cellValueArray,$styleClasses = array()){
      $rowArray = array();
      foreach ($cellValueArray as $key=>$value){
         $tableCell = new html_table_cell($value);
         if (array_key_exists($key,$styleClasses)){
            $class = $styleClasses[$key];
            if ($class != null){
               $tableCell->attributes['class'] = $class;
            }
         }
         
         $rowArray[] = $tableCell;
      }
      $row = new html_table_row($rowArray);
      $this->tableRows[] = $row;
   }
   
   public function getTableOutput(){
      $this->moodleTable->data = $this->tableRows;
      return html_writer::table($this->moodleTable);
   }   
}