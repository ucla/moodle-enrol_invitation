<?php
interface Elluminate_Cron_Action{
   
   public function executeFirstCronAction($memoryLimit);
   public function executeCronAction($lastRunTime,$memoryLimit);
   public function getResultString();
}