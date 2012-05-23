var $ = YAHOO.util.Dom.get;
function validate(type){
  // name can't be null
  $('isfirst').value = type;
  
  if( isFormValidated == false)
  { 
    return false;
  }
  
  if($("pre_filled_subject_yes").checked && $("subject").value.blank())
  {
    if (!confirm("The subject field is blank. Do you wish to continue?"))
    {
        return false;
    }
  }
  $("form").submit();
}

function hideCalendarEvent(value)
{
    if($("id_calendar_event").checked==true)
    {
        value="visible";
    }
    else
    {
        value="hidden";
    }
    $("calendar").style.visibility=value ;
    $("calendar_extra").style.visibility=value ;
}

function enableSubject(enable)
{
    $("subject").disabled=enable;
}
