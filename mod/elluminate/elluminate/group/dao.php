<?php
class Elluminate_Group_DAO {

   private $logger;
   
   public function getChildSessions($parentSession)
   {
      global $DB;
      return $DB->get_records('elluminate',
            array('groupparentid'=>$parentSession->id));
   }
}
    