function openWimbaPopup(url,type,course,block)
{
    url = url+"?type="+type+"&course_id="+course+"&block_id="+block;
    popup = window.open (url, "vmail_popup", "width=450px,height=500px,scrollbars=no,toolbar=no,menubar=no,resizable=yes"); 
}

