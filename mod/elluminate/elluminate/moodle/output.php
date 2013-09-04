<?php

class Elluminate_Moodle_Output{
   
   public function getMoodleUrl($url){
      return new moodle_url($url);
   }
   
   public function notify($notifyText){
      global $OUTPUT;
      return $OUTPUT->notification($notifyText);
   }
   
   public function getActionIcon($url, $image, $altKey,$attributes = null,$altTextInfo = ''){
      global $OUTPUT;
      
      if ($altTextInfo != ''){
         $altText = get_string($altKey,'elluminate',$altTextInfo);
      }else{
         $altText = get_string($altKey,'elluminate');
      }
      $icon = new pix_icon($image,$altText,'elluminate');
      
      if ($attributes == null){
         $attributes = array();
      }
      $attributes["id"] = $altKey;

      return $OUTPUT->action_icon($url, $icon, null, $attributes);
   }  
   
   public function getMoodleImage($image){
      global $OUTPUT;
      return $OUTPUT->pix_url($image, 'elluminate');
   }
   
   public function getMoodleHeading($textkey, $level){
      global $OUTPUT;
      return $OUTPUT->heading(get_string($textkey, 'elluminate'),$level);
   }

   public function getMoodleDate($epochDate){
      return userDate($epochDate);
   }
}