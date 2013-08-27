<?php
interface Elluminate_HTML_Table{
   public function init($columnAlignment);
   public function addHeaderRow($headerText,$colSpan);
   public function addColumnHeaders($columnNameArray);
   public function addNameValueRow($title, $titleSpan = 1, $value, $valueSpan, $action = null);
   public function addRow($cellValueArray);
   public function addSpanRow($rowText, $colSpan);
   public function getTableOutput();
}