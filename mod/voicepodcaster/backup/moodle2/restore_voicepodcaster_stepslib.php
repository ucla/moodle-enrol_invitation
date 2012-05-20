<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package moodlecore
 * @subpackage backup-moodle2
 * @copyright 2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the restore steps that will be used by the restore_voicepodcaster_activity_task
 */

/**
 * Structure step to restore one voicepodcaster activity
 */
class restore_voicepodcaster_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('voicepodcaster', '/activity/voicepodcaster');

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    protected function process_voicepodcaster($data) {
        global $DB, $CFG;

        $data = (object)$data;

        $old_rid = $data->rid;
        //add logs
        //Now, build the voicepodcaster record structure
        if("voicepodcaster" == "voiceboard")
        {
          $typeCopy = "0";  //we copy all the content
        }
        else
        {
          $typeCopy = "1";  //we copy top messages
        }

        $userinfo = $this->get_setting_value('userinfo');

        if($userinfo)
        {
            $copyOptions = $typeCopy;
            $resourceDbMatched = $DB->get_record("voicepodcaster_resources", array("fromrid" => $old_rid, "course" => $this->get_courseid(), "copyoptions" => $typeCopy)); //resource which match the current copy options
            $resourceDbOther = $DB->get_record("voicepodcaster_resources", array("fromrid" => $old_rid, "course" => $this->get_courseid(), "copyoptions" => "2"));
        }
        else
        {
            $copyOptions = "2";//delete all
            $resourceDbMatched = $DB->get_record("voicepodcaster_resources", array("fromrid" => $old_rid, "course" => $this->get_courseid(), "copyoptions" => "2"));
            $resourceDbOther = $DB->get_record("voicepodcaster_resources", array("fromrid" => $old_rid, "course" => $this->get_courseid(), "copyoptions" => $typeCopy));
        }

        if(empty($resourceDbMatched))
        { // the resource of the type needed was not created before
            $newResource = voicetools_api_copy_resource($old_rid,null,$copyOptions);
            if($newResource === false){
                return false;//error during the copy
            }
            $newResource =  voicetools_api_get_resource($newResource->getRid()) ; // get all the informations
            if($newResource === false){
                return false;//error to get the resouce
            }
            $newResourceOptions = $newResource->getOptions();
            $isGradable =  $newResourceOptions->getGrade();
            $resourceId = $newResource->getRid();

            if(!empty($resourceDbOther))
            {//the other type was created, need to update one name
                if($copyOptions == $typeCopy) //user data is checked
                {//we have to update the name of the new one
                    $newResource->setTitle($newResource->getTitle()." with user data");
                    if(voicetools_api_modify_resource($newResource->getResource()) === false){
                      return false;//error to get the resouce
                    }
                    //save some parameters that we will used to manage the grade column
                    $title = $newResource->getTitle();
                    $ridForGrade = $newResource->getRid();
                    $pointsPossible =  $newResourceOptions->getPointsPossible();
                    $actionGradebook = "create";//we will only need to create the grade column with grades for the second resource.
                }
                else
                {    //we have to update the other which was the one with user data
                    $otherResource =  voicetools_api_get_resource($resourceDbOther->rid) ;
                    if($otherResource === false){
                        return false;//error to get the resouce
                    }
                    $otherResource->setTitle($otherResource->getTitle()." with user data");

                    if(voicetools_api_modify_resource($otherResource->getResource()) === false){
                        return false;//error to get the resouce
                    }
                    $title = $otherResource->getTitle();
                    $ridForGrade = $otherResource->getRid();
                    $otherResourceOptions = $otherResource->getOptions();
                    $pointsPossible = $otherResourceOptions->getPointsPossible();
                    $actionGradebook = "update";//we will only have to update the name of the grade column and create a new one
               }
                    //we store the new resource in the database;
            }
            //update the moodle database
            voicepodcaster_createResourceFromResource($old_rid, $resourceId, $this->get_courseid(), $copyOptions);
        }
        else
        {
          //the resource already exist
          $resourceId = $resourceDbMatched->rid;
          $isGradable = false;
        }

        $data->course = $this->get_courseid();
        $data->rid = $resourceId;
        $data->name = str_replace($old_rid, $resourceId, $data->name);
        $data->isfirst = 1;
        //The structure is equal to the db, so insert the voicepodcaster
        $newActivityId = $DB->insert_record("voicepodcaster", $data);
        $this->apply_activity_instance($newActivityId);

        if($isGradable == 'true') { //the old vb was gradable
            //the activity linked has changed due to the copy, we need to update it to be able to match the good grade column
            $vb = $DB->get_record("voicepodcaster_resources", array("rid" => $resourceId));
            $vb->gradeid = $newActivityId;
            $DB->update_record("voicepodcaster_resources", $vb);

            $oldResourceDb = $DB->get_record("voicepodcaster_resources", array("rid" => $old_rid));
            $students = getStudentsEnrolled($oldResourceDb->course);
            $users_key = array_keys($students);

            //get the grade of the initial resource
            $gradesfromInitialResource = grade_get_grades($oldResourceDb->course, "mod", "voicepodcaster", $oldResourceDb->gradeid,$users_key);
            $grades = null;
            if(isset($gradesfromInitialResource->items[0]))
            {
                $grades = voicepodcaster_build_gradeObject_From_ArrayOfGradeInfoObjects($gradesfromInitialResource->items[0]->grades);
            }

            if(isset($actionGradebook) && $actionGradebook == "update") {
                //we update the name of the column (add "with user data")
                voicepodcaster_delete_grade_column($ridForGrade, $this->get_courseid(), $newActivityId);//delete the one automatically created by moodle
                voicepodcaster_add_grade_column($ridForGrade, $this->get_courseid(), $title, $pointsPossible, $grades);
                //we need to create the grade column with contains no grade( user data was unchecked);
                voicepodcaster_add_grade_column($newResource->getRid(), $this->get_courseid(), $newResource->getTitle(), $newResourceCopyOptions->getPointsPossible());
            }
            else if(isset($actionGradebook) && $actionGradebook =="create")
            {
                voicepodcaster_add_grade_column($ridForGrade, $this->get_courseid(), $title, $pointsPossible, $grades);
            }
        }
    }
}
