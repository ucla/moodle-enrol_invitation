<?php
class Elluminate_Cron_Utils{
   
   const UNLIMITED_MEMORY = -1;
   
   /**
    * This function is used to check if the cron job is getting too close to the system
    * memory limit, in which case we abort the cron run.
    * @param unknown_type $memoryLimit
    * @return boolean
    */
   public static function memoryUsageExceeded($memoryLimit){
      $logger = Elluminate_Logger_Factory::getLogger("Elluminate_Cron");
      if ($memoryLimit == self::UNLIMITED_MEMORY){
         $logger->debug("Cron Memory Limit set to unlimited, no memory check completed");
         //nothing we can check for unlimited memory
         return false;
      }
   
      //Check at 95% to prevent server crashes
      $safeMemoryLimit = $memoryLimit * 0.95;
      if (memory_get_usage() > $safeMemoryLimit){
         $logger->error("Collaborate Cron exceeded memory usage and will be aborted. " .
                  " Limit = " . $safeMemoryLimit . " Usage =" . memory_get_usage());
         return true;
      }
   }
}