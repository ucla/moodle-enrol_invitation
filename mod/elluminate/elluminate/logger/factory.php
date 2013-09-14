<?php

class Elluminate_Logger_Factory{

   const DEFAULT_LOG_LEVEL = 4;
   const LOG_KEY = 'sas';
   const LOG_LEVEL_CONFIG_KEY = "elluminate_log_level";

   public static function getLogger($className){
      global $CFG, $SITE;
      $returnLogger = new Elluminate_Logger_Logger();

      $returnLogger->init(self::LOG_KEY,
            $className,
            $CFG->dataroot,
            $SITE->shortname,
            self::LOG_LEVEL_CONFIG_KEY);
      return $returnLogger;
   }
}