<?php

class Elluminate_HTML_LicenseView{
   
   private $logger;
   private $licenseManager; 
   
   public function __set($property, $value)
   {
      if (property_exists($this, $property)) {
         $this->$property = $value;
      }
      return $this;
   }
   
   public function __construct(){
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_HTML_LicenseView");
   }
    
   public function getLicenseList(){
      $table = new Elluminate_Moodle_Table();
      $table->init(array('left','left','left'));
      $table->addHeaderRow(get_string('licenses','elluminate'), 3);
      $table->addColumnHeaders(array('licenseoption','licensevariation','licensed'));
      
      $licenseList = $this->licenseManager->getAllLicenses();
      foreach($licenseList as $license){
         $cells = array();
         $cells[] = $license->optionname;
         $cells[] = $license->variationname;
         $cells[] = $license->licensed;
         
         $table->addRow($cells);
      }
      return $table->getTableOutput();
   }
}