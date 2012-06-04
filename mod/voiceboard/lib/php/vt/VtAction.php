<?php
/******************************************************************************
 *                                                                            *
 * Copyright (c) 1999-2008  Wimba, All Rights Reserved.                       *
 *                                                                            *
 * COPYRIGHT:                                                                 *
 *      This software is the property of Wimba.                               *
 *      You can redistribute it and/or modify it under the terms of           *
 *      the GNU General Public License as published by the                    *
 *      Free Software Foundation.                                             *
 *                                                                            *
 * WARRANTIES:                                                                *
 *      This software is distributed in the hope that it will be useful,      *
 *      but WITHOUT ANY WARRANTY; without even the implied warranty of        *
 *      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         *
 *      GNU General Public License for more details.                          *
 *                                                                            *
 *      You should have received a copy of the GNU General Public License     *
 *      along with the Wimba Moodle Integration;                              *
 *      if not, write to the Free Software Foundation, Inc.,                  *
 *      51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA                *
 *                                                                            *
 * Author: Thomas Rollinger                                                   *
 *                                                                            *
 * Date: January 2007                                                         *
 *                                                                            *
 ******************************************************************************/

class vtAction{

  var $params;//stack of parameters
  var $creator;
  
    function vtAction($emailCreator,$params=null)
    {
        $this->params=$params;
        $this->creator=$emailCreator;
    }
    
    /**************
    VOICE BOARD
    *****************/
    /*
    * This function creates a voice board on the vt server 
    * params : elements of the form
    */
    function createBoard(){
        $resource = new vtResource(NULL);
        $audio = new vtAudioFormat(NULL); 
        $options = new VtOptions(NULL);   
        // Info                
        $resource->setType("board");     //Voice Baord
        $resource->setMail($this->creator);
        if (isset($this->params["longname"])) 
        {
            $resource->setTitle(stripslashes($this->params["longname"]));
        }
       
        if($this->params['default']=="true")
        {
            $options->setFilter("false");
            $options->setShowCompose("true");
               //Media
            $audio->setName("spx_16_q4");
            //message length     
            $options->setMaxLength("300");
            //Features 
            $options->setShowReply("true");
            $options->setShortTitle("true");
            $options->setGrade("false");
            $options->setPointsPossible("");
        }    
        else
        {
            if (isset($this->params["description"])) 
            {
                $resource->setDescription(stripslashes($this->params["description"]));
            }
            if ($this->params["led"] == "student")
            {
                $options->setFilter("false");
                $options->setShowCompose($this->params['show_compose']);
            }
            else
            {
                $options->setFilter("true");
                $options->setShowCompose("false");
            }
            
            //Media
            $audio->setName($this->params["audio_format"]);
            //message length     
            $options->setMaxLength($this->params["max_length"]);
            //Features 
            //short message titles
            $options->setShortTitle($this->params["short_title"]);
            //chronological order
            $options->setChronoOrder($this->params["chrono_order"]);        
            //forward message
            $options->setShowForward($this->params["show_forward"]);
            
            $options->setShowReply($this->params["show_reply"]);
            if(isset($this->params["grade"])) 
            {
              $options->setGrade($this->params["grade"]);
            }
            if(isset($this->params["points_possible"])) 
            {
                 $options->setPointsPossible($this->params["points_possible"]);
            }
        }
        
        $options->setAudioFormat($audio);  
        $resource->setOptions($options);  
        //create the resource on the vt server
        
        $result = voicetools_api_create_resource($resource->getResource()); 
   
        return $result;
    }
         
    /*
    * This function modifies a voice board on the vt server 
    */
    function modifyBoard($id){
        $resource = new vtResource(NULL);
        $audio = new vtAudioFormat(NULL); 
        $options = new VtOptions(NULL);   
        // Info                
        $resource->setType("board");     //Voice Baord
        $resource->setMail($this->creator);
        if(isset($this->params["description"])) 
        {
            $resource->setDescription(stripslashes($this->params["description"]));
        }else{
            $resource->setDescription("");
        }
        
        if (isset($this->params["longname"])) 
        {
            $resource->setTitle(stripslashes($this->params["longname"]));
        }
        
        if ($this->params["led"] == "student")
        {
            $options->setFilter("false");
            $options->setShowCompose($this->params["show_compose"]);
        }
        else
        {
            $options->setFilter("true");
            $options->setShowCompose("false");
        }
        //Media
        $audio->setName($this->params["audio_format"]);
        //message length
        $options->setMaxLength($this->params["max_length"]);
        //Features 
        //short message titles
        $options->setShortTitle($this->params["short_title"]);
        //chronological order
        $options->setChronoOrder($this->params["chrono_order"]);        
        //forward message
        $options->setShowForward($this->params["show_forward"]);
        
        $options->setShowReply($this->params["show_reply"]);
        if(isset($this->params["grade"])) 
        {
          $options->setGrade($this->params["grade"]);
        }
        if(isset($this->params["points_possible"])) 
        {
             $options->setPointsPossible($this->params["points_possible"]);
        }else{
             $options->setPointsPossible("");   
        }
       
        
        $options->setAudioFormat($audio);  
        $resource->setOptions($options);  
        //update
        $resource->setRid($id);      
        
        //create the resource on the vt server 
        $result = voicetools_api_modify_resource($resource->getResource());
        return $result;
    } 

    /****************
    VOICE PRESENTATION
    ****************/  
    /*
    * This function creates a voice presentation the vt server 
    */
    function createPresentation(){
        $resource = new vtResource(NULL);
        $audio = new vtAudioFormat(NULL); 
        $options = new VtOptions(NULL);   
        // Info                
        $resource->setType("presentation");     //Voice Presentation
        $resource->setMail($this->creator);
        if (isset($this->params["longname"])) 
        {
                $resource->setTitle(stripslashes($this->params["longname"]));
        }
        
        if($this->params['default']=="true")
        {
            $options->setFilter("true");
            //slides comments private  
            $options->setShowReply("true");
               //Media
            $audio->setName("spx_16_q4");
            //message length     
            $options->setMaxLength("300");
        }    
        else
        {        
            if (isset($this->params["description"])) 
            {
                $resource->setDescription(stripslashes($this->params["description"]));
            } 
            
            $options->setFilter($this->params["filter"]);
            //slides comments private  
            $options->setShowReply($this->params["show_reply"]);        
            $audio->setName($this->params["audio_format"]);
            $options->setMaxLength($this->params["max_length"]);
        }
        $options->setAudioFormat($audio);  
        $resource->setOptions($options);  
        //create the resource on the vt server 
        $result = voicetools_api_create_resource($resource->getResource());
        return $result;
    } 

  /*
  * This function modifies a voice presentation the vt server 
  * @param id : id of the resource
  */
    function modifyPresentation($id)
    {
        $resource = new vtResource(NULL);
        $audio = new vtAudioFormat(NULL); 
        $options = new VtOptions(NULL);   
        // Info                
        $resource->setType("presentation");     //Voice Presentation
        $resource->setMail($this->creator);
        if (isset($this->params["description"])) 
        {
            $resource->setDescription(stripslashes($this->params["description"]));
        }
        if (isset($this->params["longname"])) 
        {
            $resource->setTitle(stripslashes($this->params["longname"]));
        }
    
        $options->setFilter($this->params["filter"]);
        //slides comments private         
        $options->setShowReply($this->params["show_reply"]); 
        $audio->setName($this->params["audio_format"]);
        //message length
        $options->setMaxLength($this->params["max_length"]);
    
        $options->setAudioFormat($audio);  
        $resource->setOptions($options);  
        $resource->setRid($id);       
        //create the resource on the vt server 
        $result = voicetools_api_modify_resource($resource->getResource());
        return $result;
    }      

    /********
    Podcaster
    **********/ 
    /*
    * This function creates a podcaster the vt server 
    */
    function createPodcaster(){
    
        $resource = new vtResource(NULL);
        $audio = new vtAudioFormat(NULL); 
        $options = new VtOptions(NULL);
        
        // Info                
        $resource->setType("pc");//Podcaster
        $resource->setMail($this->creator);
        if (isset($this->params["longname"])) 
        {
            $resource->setTitle(stripslashes($this->params["longname"]));
        }
        
        if($this->params['default']=="true")
        {
            $options->setShowCompose("true");
               //Media
            $audio->setName("spx_16_q4");
            //message length     
            $options->setDelay("300");
            //Features 
            //short message titles
            $options->setShortTitle("true");
        }    
        else
        {   
            if (isset($this->params["description"])) 
            {
                $resource->setDescription(stripslashes($this->params["description"]));
            }
            $options->setShowCompose($this->params["show_compose"]);        
            //Media      
            $audio->setName($this->params["audio_format"]);
            $options->setDelay($this->params["delay"]);    //no delay   
            //Features    
            $options->setShortTitle($this->params["short_title"]);
        }
        $options->setMaxLength(1200);     //set to 20 min  
        $options->setAudioFormat($audio);  
        $resource->setOptions($options);       
       
        //create the resource on the vt server 
        $result = voicetools_api_create_resource($resource->getResource());                    
        return $result;
    }  
           
     /*
      * This function modifies a podcaster the vt server 
      * @param id : id of the resource
      */
    function modifyPodcaster($id){
        $resource = new vtResource(NULL);
        $audio = new vtAudioFormat(NULL); 
        $options = new VtOptions(NULL);
        
        // Info                
        $resource->setType("pc");//Podcaster
        $resource->setMail($this->creator);
        if (isset($this->params["description"])) 
        {
            $resource->setDescription(stripslashes($this->params["description"]));
        }
        if (isset($this->params["longname"])) 
        {
            $resource->setTitle(stripslashes($this->params["longname"]));
        }
         
        $options->setShowCompose($this->params["show_compose"]);        
        //Media      
        $audio->setName($this->params["audio_format"]);
        $options->setDelay($this->params["delay"]);    //no delay   
         //Features    
        $options->setShortTitle($this->params["short_title"]);
        
        $options->setMaxLength(1200);     //set to 20 min  
        $options->setAudioFormat($audio);  
        $resource->setOptions($options); 
        
        $resource->setRid($id);     
        //create the resource on the vt server 
        $result = voicetools_api_modify_resource($resource->getResource());
        return $result;
    }
    
    /********
    Recorder
    **********/ 
    /*
    * This function creates a recorder the vt server 
    * params : blocks' id
    */
    function createRecorder($title){
        $resource = new vtResource(NULL);
        $audio = new vtAudioFormat(NULL); 
        $options = new VtOptions(NULL);
        
        // Info                
        $resource->setType("recorder");
        $resource->setTitle($title);
        $resource->setMail($this->creator);
        
        $options->setMaxLength(1200);     //set to 20 min  
        $options->setAudioFormat($audio);  
        $resource->setOptions($options);       
           
        //create the resource on the vt server 
        $result = voicetools_api_create_resource($resource->getResource());             
        
        return $result;
    }

   
    function deleteResource($rid)  
    {  
        $result = voicetools_api_delete_resource($rid);     
        return $result;
    }

    /*******************
     VOICE EMAIL
    ****************/  
    /*
    * This function creates a voice presentation the vt server 
    */
    function createVMmail($title){
        $resource = new vtResource(NULL);
        $audio = new vtAudioFormat(NULL); 
        $options = new VtOptions(NULL);   
        // Info                
        $resource->setType("vmail");     //Voice Presentation
        $resource->setMail($this->creator);
       
        if (isset($this->params["name"])) 
        {
            $resource->setTitle(stripslashes($this->params["name"]));
        }
        else if(!empty($title))
        {
            
            $resource->setTitle($title);
        }
        
        //Media
        $audio->setName($this->params["audio_format"]);
        $options->setMaxLength($this->params["max_length"]);
        $options->setReplyLink($this->params["reply_link"]);
        
        if (isset($this->params["subject"])) 
        {
            $options->setSubject($this->params["subject"]);
        }
        
        $options->setTo($this->params["recipients"]);
        $options->setAudioFormat($audio);  
        $resource->setOptions($options);  
        
        //create the resource on the vt server 
        $result = voicetools_api_create_resource($resource->getResource());
        return $result;
    } 
     /*******************
     VOICE EMAIL
    ****************/  
    /*
    * This function creates a voice presentation the vt server 
    */
    function updateVMmail($id,$title){
        $resource = new vtResource(NULL);
        $audio = new vtAudioFormat(NULL); 
        $options = new VtOptions(NULL);   
        // Info                
        $resource->setType("vmail");     
        $resource->setMail($this->creator);
        
        if (isset($this->params["name"])) 
        {
            $resource->setTitle(stripslashes($this->params["name"]));
        }
        
        //Media
        $audio->setName($this->params["audio_format"]);
        $options->setMaxLength($this->params["max_length"]);
        $options->setReplyLink($this->params["reply_link"]);
        
        if (isset($this->params["subject"])) 
        {
            $options->setSubject($this->params["subject"]);
        }
        else
        {
             $options->setSubject("");
        }
        
        $options->setTo($this->params["recipients"]);
        $options->setAudioFormat($audio);  
        $resource->setOptions($options);  
        
        //create the resource on the vt server 
        $resource->setRid($id);     
        //create the resource on the vt server 
        $result = voicetools_api_modify_resource($resource->getResource());
        return $result;
    } 
    
    
    function getResource($rid)  
    { 
        $result=voicetools_api_get_resource ($rid) ;  
        return $result;
    }
    
    function createUser($screenName,$email)
    {
        $vtUser = new VtUser(NULL);  
        $vtUser->setScreenName($screenName); 
        $vtUser->setEmail ($email);
        return $vtUser; 
    }

    function createUserRights($product,$role)
    {
        $vtUserRigths = new VtRights(NULL);  
        $vtUserRigths->setProfile ( 'moodle.'.$product.'.'.strtolower($role));  
        if($product=="presentation")
        {   
            $vtUserRigths->add("reply_message");
        }
        return $vtUserRigths;
    }  
    
    function isAudioExist($rid,$mid){

        return voicetools_api_audio_exists($rid,$mid);
    }
    
    function getVtSession($resource,$user,$rights,$message=null)
    {
        if($message!=null)
        {
            return voicetools_api_create_session ($user->getUser(),$resource->getResource(),$rights->getRights(),$message->getMessage()) ;
        }
        else
        {
            return voicetools_api_create_session ($user->getUser(),$resource->getResource(),$rights->getRights()) ;
        }
    }
    
    function getAverageMessageLengthPerUser($rid)
    {
        $result = voicetools_get_average_length_messages_per_user($rid);
        if($result == "not_implemented")
          return $result;
        return convertResultFromGetAverageLengthPerUser($result);
    }
    
    function getNbMessagePerUser($rid)
    {
        $array = voicetools_get_nb_messages_per_user($rid);
        return convertResultFromGetNbMessagePerUser($array);
    }
}


?>
