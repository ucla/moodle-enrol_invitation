function validate(type){
  // name can't be null
  $('isfirst').value = type;
  if( isFormValidated == false)
  { 
    return false;
  }
  
  $("form").submit();
}

function isValidate(){
    return true; //validation handled by moodle forms
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

function popupCancel()
{
    $("popup").style.display="none";
    $("hiddenDiv").style.display="none";
    location.href = M.cfg.wwwroot+"/course/view.php?id="+$F($('mform1')['course']);
}

function popupOk()
{
    $("popup").style.display="none";
    $("hiddenDiv").style.display="none";
    location.href = M.cfg.wwwroot+"/mod/liveclassroom/index.php?id="+$F($('mform1')['course']);
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
    if($("nameNewResource").value.blank())
        return false;
    $("newPopup").hide();
    $('loading').show();
    createNewResource(M.cfg.wwwroot+"/mod/liveclassroom/manageAction.php","liveclassroom","",name.value,$F($('mform1')["url_params"]));
    name.value=""; //for the next on
    $('id_name').focus();
}

function LoadNewFeaturePopup(current)
{
    if( current == "new" ){
        $("hiddenDiv").style.height=document.documentElement.clientHeight
        $("hiddenDiv").style.width=document.documentElement.clientWidth
        $("newPopup").show();
        $("hiddenDiv").show();  
        $("nameNewResource").focus();
        var allSelect =  document.getElementsByTagName("select");
        for( i=0;i<allSelect.length;i++)
        {
            allSelect[i].style.visibility="hidden";
        }
    }
}

function onCancelButtonPopup(){
    $('id_resource').selectedIndex=0;
    $('newPopup').hide();
    $('hiddenDiv').hide();
    $('id_name').focus();
    var allSelect =  document.getElementsByTagName("select");
    for( i=0;i<allSelect.length;i++)
    {
        allSelect[i].style.visibility="";
    }
    return false;
}

