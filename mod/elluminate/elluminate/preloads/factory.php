<?php
class Elluminate_Preloads_Factory{
   
   public function getPreload(){
      global $ELLUMINATE_CONTAINER;
      
      return $ELLUMINATE_CONTAINER['preload'];
   }
}