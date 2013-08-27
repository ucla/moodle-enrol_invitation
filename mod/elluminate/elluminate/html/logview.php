<?php

class Elluminate_HTML_LogView{
   
   const BYTES_TO_KB = 1024;
   
   private $logger;
   private $logDir;
   
   public function __construct(){
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_HTML_LogView");
      $this->logDir = $this->logger->getLogDir();
   }
   
   public function getLogList(){
      $table = new Elluminate_Moodle_Table();
      $table->init(array('left','left','left'));
      $table->addHeaderRow(get_string('serverlogs','elluminate'), 3);
      $table->addColumnHeaders(array('logname','logdate','logsize'));

      array_multisort(array_map('filemtime', ($files = glob($this->logDir . "*.log"))), SORT_DESC, $files);

      foreach($files as $filename) {
         $table->addRow(
            array(
               $this->getLogDownloadLink(basename($filename)),
               userdate(filemtime($filename)),
               $this->getLogSizeInKB(filesize($filename))));
      }

      return $table->getTableOutput();
   }
   
   private function getLogSizeInKB($byteSize){
      return floor($byteSize / self::BYTES_TO_KB) . " " .  get_string('logsizeunits','elluminate');;
   }
   
   private function getLogDownloadLink($logName){
      $strippedName = basename($logName,".log");
      $logUrl = "download-log.php?logname=" . $strippedName;
      $logLink = "<a href='" . $logUrl . "'>" . $logName . "</a>";
      return $logLink;
   }
}