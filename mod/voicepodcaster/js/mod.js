function validate(type){
  // name can't be null
  $('isfirst').value = type;
  if( isFormValidated == false)
  { 
    return false;
  }
  
  $("form").submit();
}

function isOk()
{
  if( !$("nameNewResource").value.blank())
  {
    $("advancedOk").removeClassName("regular_btn-submit-disabled");
    $("advancedOk").addClassName("regular_btn-submit");
    $("advancedOk").disabled="";
  } 
  else
  {
    $("advancedOk").addClassName("regular_btn-submit-disabled");
    $("advancedOk").removeClassName("regular_btn-submit");
    $("advancedOk").disabled="true";
  }
}

function isValidate(){
    isFormValidated = true;
    return;
  // name can't be null
  if( $("id_name").value.blank())
  { 
    isFormValidated=false;
    $("id_submitbutton").addClassName("regular_btn-disabled");
    $("id_submitbutton").removeClassName("regular_btn");
    $("id_submitbutton").disabled="true";
    $("id_submitbutton2").addClassName("regular_btn-disabled");
    $("id_submitbutton2").removeClassName("regular_btn");
    $("id_submitbutton2").disabled="true";
    
    return false;
  }
  else if( $("id_resource").value=="empty" || $("id_resource").value=="new")
  {
    isFormValidated=false;  
    $("id_submitbutton").addClassName("regular_btn-disabled");
    $("id_submitbutton").removeClassName("regular_btn");
    $("id_submitbutton").disabled="true";
    $("id_submitbutton2").addClassName("regular_btn-disabled");
    $("id_submitbutton2").removeClassName("regular_btn");
    $("id_submitbutton2").disabled="true";
    return false;  
  }
  isFormValidated=true; 
  $("id_submitbutton").removeClassName("regular_btn-disabled");
  $("id_submitbutton").addClassName("regular_btn");
  $("id_submitbutton").disabled="";
  $("id_submitbutton2").removeClassName("regular_btn-disabled");
  $("id_submitbutton2").addClassName("regular_btn");
  $("id_submitbutton2").disabled="";
}

function hideCalendarEvent(value)
{
   // if(value=="check")
    //{                              
        if($("id_calendar_event").checked==true)
        {
            value="visible";
        }
        else
        {
            value="hidden";
        }
    //}      
    
    $("calendar").style.visibility=value ;
    $("calendar_extra").style.visibility=value ;
}

function create(name,courseid){
    if (name == '')
        return false;
    createNewResource(M.cfg.wwwroot+"/mod/voicepodcaster/manageAction.php","voicetools","pc",name,$F($('mform1')["url_params"]));
}

function LoadNewFeaturePopup(current)
{
    if( current == "new" ){
        var ret = prompt('Please enter a title for the new Voice Podcaster');
        if (ret == null) {
          $('id_resource').selectedIndex=0;
          return false;
        } else {
          create(ret);
        }
    }
}

