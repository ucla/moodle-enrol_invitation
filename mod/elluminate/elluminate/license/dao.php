<?php

class Elluminate_License_DAO{
   public function add($license){
      global $DB;
      $id = $DB->insert_record('elluminate_option_licenses', $license->getDBInsertObject());
      if (!$id){
         throw new Elluminate_Exception('Could not save url to DB', 0, 'user_error_database');
      }
      return $id;      
   }
   
   public function update($license){
      global $DB;
      $id = $DB->update_record('elluminate_option_licenses', $license->getDBInsertObject());
   }
   
   public function loadLicense($optionName,$variationName = null){
      global $DB;
      return $DB->get_record('elluminate_option_licenses', array('optionname'=>$optionName,'variationname'=>$variationName));
   }
   
   public function getAllLicenses(){
      global $DB;
      return $DB->get_records('elluminate_option_licenses');
   }
   
   public function deleteAllLicenses() {
   	  global $DB;
   	  return $DB->delete_records('elluminate_option_licenses');
   }
}