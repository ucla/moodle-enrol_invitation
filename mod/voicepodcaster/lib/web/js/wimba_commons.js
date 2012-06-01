/******************************************************************************
 *                                                                            *
 * Copyright (c) 1999-2007 Wimba, All Rights Reserved.                        *
 *                                                                            *
 * COPYRIGHT:                                                                 *
 *      This software is the property of Wimba.                               *
 *      It cannot be copied, used, or modified without obtaining an           *
 *      authorization from the authors or a mandated member of Wimba.         *
 *      If such an authorization is provided, any modified version            *
 *      or copy of the software has to contain this header.                   *
 *                                                                            *
 * WARRANTIES:                                                                *
 *      This software is made available by the authors in the hope            *
 *      that it will be useful, but without any warranty.                     *
 *      Wimba is not liable for any consequence related to                    *
 *      the use of the provided software.                                     *
 *                                                                            *
 * Class:  hwCommons.js                                                       *
 *                                                                            *
 * Author:  Thomas Rollinger                                                  *
 *                                                                            *
 * Date: May 2007                                                             *
 *                                                                            *
 ******************************************************************************/
/* $Id: hwCommons.js 200 2008-01-09 11:37:50Z trollinger $ */

function isInternetExplorer() {
    if (navigator.appName == "Microsoft Internet Explorer") {
        return true;
    }
    return false;
}

function isIE6() {
    var userAgent = navigator.userAgent;
    var start = userAgent.indexOf("(", 0);
    var end = userAgent.indexOf(")", 0);
    var version = userAgent.substring(start, end);
    if (version.indexOf("MSIE 6") != -1) {
        return true;
    } else {
        return false;
    }
}

function wimbaOnClick(element,onclickFunction)
{
    if( !(element.parentNode.hasClassName('toolbar_btn-disabled')) )
    {
        eval(onclickFunction)
    }
}


function inactivatekey() {
        $("search").onkeypress = function(e) {
        if (!e) var e = window.event;
        if (e.keyCode) code = e.keyCode;
        if(e.keyCode==13)
            return false;
        }
}
/*
 * Manage the search of a specific element. This function is called every time
 * when the user type a letter into the search field
 */
function searchResource(searchValue) {
    
    var searchValueLength=searchValue.length
    var first=false;
    var hide=0;
    var searchInit=false;
    if(searchValue.indexOf("\"")==0 && searchValue.lastIndexOf("\"")==searchValueLength && searchValue.length>1) {
        searchValue=searchValue.substr(1,(searchValueLength-1));
    }
    if($('list')!=null)
    {
        var allElements = $A($('list').getElementsByTagName('tr')).map(Element.extend);
        
        for(var i=0; i< allElements.length ;i++)
        {        
            if ( allElements[i].hasClassName("element:available") ) 
            {
                if(searchValueLength==0)
                {//back to init.
                    allElements[i].removeClassName('hideElement');
                    parentId=allElements[i].id;
                    
                    while ( allElements[i+1] !=null && 
                             
                            allElements[i+1].hasClassName( "parent:" + parentId )
                          )
                    {
                        if(allElements[i+1].hasClassName( "element:available" )){
                            allElements[i+1].addClassName('hideElement');
                            $("span"+parentId).removeClassName('roomWithoutArchives');
                            $("span"+parentId).removeClassName('roomWithArchives_clicked');
                            $("span"+parentId).addClassName('roomWithArchives');
                        }
                        i++;
                        
                    }  
                    searchInit=true; 
                                        
                }
                else if( allElements[i].getAttribute("name").toLowerCase().match(searchValue.toLowerCase()) )
                {
                   
                    if(first == false) //first element will be the one selected
                    {           
                        if(currentId != "")
                        {
                            $(currentId).removeClassName('selected');
                        }          
                        currentId = allElements[i].id ;
                        $(currentId).addClassName('selected');
                        first = true;
                        if($(currentId).hasClassName( "grade:true" )){
                        	toggleToolBar("disabled",true);//the current element can be graded
                        }else{
                        	toggleToolBar("disabled",false);
                        }
                    }
                        
                    //if liveclassroom, we check the archive
                    if(allElements[i].hasClassName("liveclassroom") && ($("view") == null || $("view").value == "normal") ||  allElements[i].hasClassName("liveclassroom") && $("view").value == "student" && allElements[i].hasClassName( "element:available" ))
                    {
                    	allElements[i].removeClassName('hideElement');
                        parentId=allElements[i].id;
                        var match=false;
                        
                        while ( allElements[i+1] !=null && 
                                allElements[i+1].hasClassName( "element:available" ) && 
                                allElements[i+1].hasClassName( "parent:" + parentId ) 
                              )
                        {
                            if(allElements[i+1].getAttribute("name").toLowerCase().match(searchValue.toLowerCase()))
                            {
                                 allElements[i+1].removeClassName('hideElement');   
                                 match = true;
                            }
                            else
                            {
                                 hide++;
                                 allElements[i+1].addClassName('hideElement');
                            }
                            i++;
                        }
                        
                        if( !match )
                        {
                            $("span"+parentId).removeClassName('roomWithArchives');
                            $("span"+parentId).addClassName('roomWithoutArchives');
                        }
                        else
                        {
                            $("span"+parentId).removeClassName('roomWithArchives');   
                            $("span"+parentId).addClassName('roomWithArchives_clicked');  
                        }    
                    } else if(allElements[i].hasClassName("voicetools")){
                    	 allElements[i].removeClassName('hideElement');
                    } 
                }
                else
                {
                    allElements[i].addClassName('hideElement');
                    hide++;
                    if(allElements[i].hasClassName("liveclassroom"))
                    {
                        parentId=allElements[i].id;
                        var match=false;     
                                       
                        while ( allElements[i+1] !=null &&                                 
                                allElements[i+1].hasClassName( "parent:" + parentId ) 
                              )
                        {
                            if( allElements[i+1].hasClassName( "element:available" ) )
                            {
                                if(allElements[i+1].getAttribute("name").toLowerCase().match(searchValue.toLowerCase()))
                                {
                                     allElements[i+1].removeClassName('hideElement');
                                     match = true;
                                }
                                else
                                {
                                     allElements[i+1].addClassName('hideElement');
                                     hide++;
                                }
                            }
                            i++;
                        }
                
                        if( !match )
                        {
                           $("span"+parentId).removeClassName('roomWithArchives');
                           $("span"+parentId).addClassName('roomWithoutArchives');  
                        }
                        else
                        {
                                        
                           $(parentId).removeClassName('hideElement'); 
                           $("span"+parentId).removeClassName('roomWithArchives');   
                           $("span"+parentId).addClassName('roomWithArchives_clicked');  
                           if(first == false) //first element will be the one selected
                           {   
                               if(currentId != "")
                               {
                                    $(currentId).removeClassName('selected');
                               }             
                               currentId = parentId ;
                               $(currentId).addClassName('selected'); 
                               first = true;
                               if($(currentId).hasClassName( "grade:true" )){
                               	toggleToolBar("disabled",true);//the current element can be graded
                               }else{
                               	toggleToolBar("disabled",false);
                               }
                           }
                        }
                    }
                }
            }     
        }
        var products = $A($("list").getElementsByTagName("div")).map(Element.extend);
       
        for(var i=0;i<products.length;i++){
            if(products[i].hasClassName("product")){
               
                var hideElements = $$('tr.hideElement');
                var allElements = products[i].getElementsByTagName('tr') 
                var allElementsLength = allElements.length;
      			if(products[i].hasClassName("liveclassroom")) 
      				allElementsLength = allElementsLength -1; //-1 for the title bar
      
                if(allElementsLength == hideElements.length)
                {
                    $(products[i].id+"_NoElement").removeClassName("hideElement");
                }
                else
                {
                    $(products[i].id+"_NoElement").addClassName("hideElement");
        
                }
            }
        }
        if( searchInit == true )
        {
            //init the toolbar
             if(currentId != "")
             {
                $(currentId).removeClassName('selected');
             }             
            currentId = "" ;
            currentGradeId="";
            toggleToolBar("enabled");
        }    
    }
}

/*
* Function called when the user click on a list elements
*/
function clickElement(element, product, type, gradeId) {
  
    if(currentId != "" && currentId != element)
    {
        $(currentId).removeClassName('selected');
    }
    
    $(element).addClassName('selected');
    
    if(currentType == "archive")//the last one was an archive
    {
        $(currentId+"subItem").removeClassName("subItem-selected");
        $(currentId+"subItem").addClassName("subItem");
        
        var elementClassName=$(currentId+"Availability").className.split('-')
        $(currentId+"Availability").removeClassName(elementClassName[0]+"-selected")
        $(currentId+"Availability").addClassName(elementClassName[0])          
    }
    
    if(type == "archive")
    {
        
        $(element+"subItem").removeClassName("subItem");
        $(element+"subItem").addClassName("subItem-selected");
       
        var elementClassName=$(element+"Availability").className
        $(element+"Availability").removeClassName(elementClassName)
        $(element+"Availability").addClassName(elementClassName+"-selected")

       
        
    }
    //update the current context
    currentId = element;
    currentProduct = product;
    currentGradeId = gradeId;
    currentType = type;
    if( element != "" )
    {
        toggleToolBar("disabled");  
    }
    if(currentProduct == "voicetools" && type=="board"){
    	manageGradeButton();
    }
}

function manageGradeButton(){
	
	var button = $("button_Grade");
	var li_button = $("button_Grade_li");
	if(button != null){
		if($(currentId).hasClassName( "grade:-1" )){
			 button.style.visibility="";
	         
	         var newbutton=document.createElement("span");
	         newbutton.className =  button.className;
	         newbutton.id = button.id;
	         newbutton.setAttribute("alt", button.getAttribute("alt").replace("disabled",""));
	         newbutton.setAttribute("onclick", button.getAttribute("onclick"));
	         newbutton.setAttribute("href", "#");   
	         if(button.innerText)//innerText doesnt work on firefox
	         {
	             newbutton.innerText=button.innerText;                   
	         }
	         else
	         {
	             newbutton.textContent=button.textContent;  
	         }
	         li_button.removeChild(button);
	         li_button.appendChild(newbutton);
	         if( li_button.className.indexOf("-disabled") == -1 )
	         {
	        	 li_button.className = li_button.className + "-disabled"; 
	         }
	                
		} else {
			var newbutton=document.createElement("a");
			newbutton.className =  button.className;
			newbutton.id = button.id;
			newbutton.setAttribute("alt", button.getAttribute("alt").replace("disabled",""));
			newbutton.setAttribute("onclick", button.getAttribute("onclick"));
			newbutton.setAttribute("href", "#"); 
			if(button.innerText)//innerText doesnt work on firefox
			{
			    newbutton.innerText = button.innerText;                   
			}
			else
			{
			    newbutton.textContent = button.textContent;  
			}
			
			li_button.removeChild(button);
			li_button.appendChild(newbutton);
			             
			if( li_button.className.indexOf("-disabled") > -1 )
			{
				li_button.className = li_button.className.substr(0, li_button.className.indexOf("-disabled"));
			}
		}
	}
}
/*
 * Manage the expand/collapse of the archive list
 * 
 */
function hideArchive(element) {
    
    var allElements = $A($('list').getElementsByTagName( "tr" )).map(Element.extend);
    var match = false;
    for(var i=0; i< allElements.length ;i++)
    {           
    
        if(allElements[i].hasClassName( "parent:" + element ))
        {
            var viewnull = false;
            if ($('view') == 'undefined') {
                viewnull = true;
            } else {
                viewnull = $('view') == null || $('view').value == 'normal';
            }
            if(  allElements[i].hasClassName('hideElement') && (viewnull || ($("view").value == "student" && allElements[i].hasClassName('element:available'))))
            {
                allElements[i].removeClassName('hideElement');
                $("span"+element).removeClassName("roomWithArchives");
                $("span"+element).addClassName("roomWithArchives_clicked");
            }
            else
            {
            	
                allElements[i].addClassName('hideElement');
                $("span"+element).addClassName("roomWithArchives");
                $("span"+element).removeClassName("roomWithArchives_clicked");
               
            }
        }
    }

    if (currentId != "") 
    {
        $(currentId).removeClassName('selected');
    }
    
    if(currentType == "archive")
    {
        $(currentId+"subItem").removeClassName("subItem-selected");
        $(currentId+"subItem").addClassName("subItem");
        
        var elementClassName=$(currentId+"Availability").className.split('-')
        $(currentId+"Availability").removeClassName(elementClassName[0]+"-selected")
        $(currentId+"Availability").addClassName(elementClassName[0])          
    }
    currentType =""
    currentId = "";  
    toggleToolBar("enabled",null);
}

/*
* This function changes the state of the button when the user click on an element
*/
function toggleToolBar(state,canBeGraded) {

    var buttons = $A($("toolBar").getElementsByTagName("li")).map(Element.extend);
    for (var x = 0; x < buttons.length; x++) 
    {
        if( $("view") == null ||  $("view").value == "normal" || buttons[x].getAttribute("context") == "all")
        { 
            if( buttons[x].getAttribute("state") == "disabled" && state == "disabled") 
            {
            	//the grade button is enabled only if the vb can be graded
                if(buttons[x].childNodes[0].id != "button_Grade" || buttons[x].childNodes[0].id == "button_Grade" && canBeGraded) {
	            	buttons[x].style.visibility="";
	                                  
	                var newbutton=document.createElement("a");
	                newbutton.className =  buttons[x].childNodes[0].className;
	                newbutton.id = buttons[x].childNodes[0].id;
	                newbutton.setAttribute("alt", buttons[x].childNodes[0].getAttribute("alt").replace("disabled",""));
	                newbutton.setAttribute("onclick", buttons[x].childNodes[0].getAttribute("onclick"));
	                newbutton.setAttribute("href", "#");   
	                if(buttons[x].childNodes[0].innerText)//innerText doesnt work on firefox
	                {
	                    newbutton.innerText=buttons[x].childNodes[0].innerText;                   
	                }
	                else
	                {
	                    newbutton.textContent=buttons[x].childNodes[0].textContent;  
	                }
	                buttons[x].removeChild(buttons[x].childNodes[0]);
	                buttons[x].appendChild(newbutton);
	                             
	                if( buttons[x].className.indexOf("-disabled") > -1 )
	                {
	                    buttons[x].className = buttons[x].className.substr(0, buttons[x].className.indexOf("-disabled"));
	                }
                }
            }
            else if( buttons[x].getAttribute("state") == "disabled" && state == "enabled") // we want to disable the button
            {
                    buttons[x].style.visibility="";
                                      
                    var newbutton=document.createElement("span");
                    newbutton.className =  buttons[x].childNodes[0].className;
                    newbutton.id = buttons[x].childNodes[0].id;
                    newbutton.setAttribute("alt", buttons[x].childNodes[0].getAttribute("alt").replace("disabled",""));
                    newbutton.setAttribute("onclick", buttons[x].childNodes[0].getAttribute("onclick"));
                    newbutton.setAttribute("href", "#");   
                    if(buttons[x].childNodes[0].innerText)//innerText doesnt work on firefox
                    {
                        newbutton.innerText=buttons[x].childNodes[0].innerText;                   
                    }
                    else
                    {
                        newbutton.textContent=buttons[x].childNodes[0].textContent;  
                    }
                    buttons[x].removeChild(buttons[x].childNodes[0]);
                    buttons[x].appendChild(newbutton);
                    if( buttons[x].className.indexOf("-disabled") == -1 )
                    {
                        buttons[x].className = buttons[x].className + "-disabled"; 
                    }
                               
             }
       }
    }
    
}


/*
* This function change the type of view of the toolbar :

*/
function changeToolBarView(typeView) {
    
    var buttons = $A($("toolBar").getElementsByTagName("li")).map(Element.extend);
    for (var x = 0; x < buttons.length; x++) {
        if ( typeView == "student" &&  buttons[x].getAttribute("context") != "all" ) 
        { 
            
            buttons[x].style.visibility="hidden";    
        }
        else
        {
            //make sure that the button is initiliaze
            buttons[x].style.visibility="";
            initButton(buttons[x]);
        }
    }
}

function initButton(button) {
    
    if( button.getAttribute("state") == "disabled" )//only the dsiable one has to be initialize
    {
        if(button.className.search("-disabled") == -1)
        {//get the style not disabled
            button.className = button.className + "-disabled";
        }
        button.innerHTML = button.innerHTML.replace("<a", "<span");
        var newbutton=document.createElement("span");
        newbutton.className =  button.childNodes[0].className;
        newbutton.id = button.childNodes[0].id;
        newbutton.setAttribute("alt", button.childNodes[0].getAttribute("alt").replace("disabled",""));
        newbutton.setAttribute("onclick", button.childNodes[0].getAttribute("onclick"));
        newbutton.setAttribute("href", "#");   
        if(button.childNodes[0].innerText)//innerText doesnt work on firefox
        {
            newbutton.innerText=button.childNodes[0].innerText;                   
        }
        else
        {
            newbutton.textContent=button.childNodes[0].textContent;  
        }
        button.removeChild(button.childNodes[0])
        button.appendChild(newbutton)
    }
}

/*
* This function manage the studentView :
* Remove the buttons not available for the student and remove the unavailable elements
*/
function switchView() {
 
    if (currentId != "") 
    {///unselect the current one
        $(currentId).removeClassName('selected');
        currentId = "";
        currentGradeId = "";
    }
  
    //changeFilterStatus(currentFilter, "disabled");
    //changeFilterStatus("filter_all", "enabled");
    //currentFilter = "filter_all";
    //init search
    $("searchField").value = "";
    searchResource("");//init the list
    changeToolBarView($("view").value);
    var allElements = $A($("list").getElementsByTagName("tr")).map(Element.extend);
    
    for (var i = 0; i < allElements.length; i++) 
    {
        if( $("view").value == "student" )
        {
            studentView = true;
            if( allElements[i].hasClassName("preview:unavailable") )
            {
                allElements[i].addClassName("hideElement");
                allElements[i].removeClassName("element:available");
            }
            
            if(allElements[i].hasClassName("liveclassroom")) // we have to check the archive
            {
                parentId=allElements[i].id;
                var match=false;
                while (  allElements[i+1] !=null && allElements[i+1].hasClassName( "parent:" + parentId ) )
                {
                	if($(parentId).hasClassName( "preview:unavailable" ))
                	{
                		allElements[i+1].removeClassName("element:available");
                	}
                    else if($(parentId).hasClassName( "preview:available" ) && allElements[i+1].hasClassName( "preview:unavailable" ) )
                    {
                        allElements[i+1].removeClassName("element:available");
                    }
                    else
                    {
                        match=true//we still have one
                    }
                    i++;
                }
                
                if(!match) //no archive available for the studentView
                {   
                   $("span"+parentId).removeClassName('roomWithArchives');
                   $("span"+parentId).addClassName('roomWithoutArchives');  
                }
            }
            else if(allElements[i].hasClassName("orphanedarchivestudent"))
            {
                allElements[i].removeClassName("orphanedarchivestudent");
                allElements[i].addClassName("orphanedarchivestudentAvailable");
            }
        }
        else
        {
            studentView = false;
            if(!allElements[i].hasClassName("titlebar"))
            	allElements[i].removeClassName("hideElement");
            	
            if(allElements[i].hasClassName("liveclassroom")) // we have to check the archive
            {
                parentId=allElements[i].id;
                allElements[i].addClassName("element:available");
                var match=false;
                while (  allElements[i+1] !=null && allElements[i+1].hasClassName( "parent:" + parentId ) )
                {         
                    allElements[i+1].addClassName("element:available");
                    allElements[i+1].addClassName("hideElement");
                    match=true;
                    i++;
                }
                
                if(match)
                {
                        
                   $("span"+parentId).addClassName('roomWithArchives');
                   $("span"+parentId).removeClassName('roomWithoutArchives');  
                }
            }
            else if(allElements[i].hasClassName("orphanedarchivestudentAvailable"))
            {
                allElements[i].addClassName("orphanedarchivestudent");
                allElements[i].removeClassName("orphanedarchivestudentAvailable");
            }
        }
    }
    var products = $("list").getElementsByTagName("div");
    for(var i=0;i<products.length;i++)
    {
        if(products[i].hasClassName("product"))
        {
                var hideElements = $$('tr.hideElement');
                var allElements = products[i].getElementsByTagName('tr');
      
                if(allElements.length == hideElements.length)

                {
                    $(products[i].id+"_NoElement").removeClassName("hideElement");
                }
                else
                {
                    $(products[i].id+"_NoElement").addClassName("hideElement");
        
                }
        }
    }
    
}

function activateFilter(product) {
    var otherProduct = LC_PRODUCT;
    if (currentId != "") {
        $(currentId).style.backgroundColor = "white";
    }
    //InitToolBar();//the toolbar has to be initialized
    currentDiv = product;
    currentId = "";
    currentGradeId = "";
    currentNid = "";
    currentProduct = "";
    isFilter = true;
    
    changeFilterStatus(currentFilter, "disabled");
    changeFilterStatus("filter_" + product, "enabled");
    currentFilter = "filter_" + product;
    if ($(currentId) != null) {
        $(currentId).style.background = "white";
    }
    //display only the selected product into the list
    var list = $("list");
    var innerList = list.getElementsByTagName("div");
    for (var i = 0; i < innerList.length; i++) {
        var divTitle = "divTitle_" + product;
        if (product == "all") {
            innerList[i].style.display = "block";
            gestionDisplay();
        } else {
            if (innerList[i].getAttribute("product") == product) {
                innerList[i].style.display = "block";
                innerList[i].style.height = "";
                gestionDisplay(product);
            } else {
                innerList[i].style.display = "none";
                gestionDisplay(product);
            }
        }
    }
}

/*
* manage the rollover of the filter
*/
function onFilter(id) {
    if (currentFilter != id) {
        changeFilterStatus(id, "rollover");
    }
}

function outFilter(id) {
    if (currentFilter == id) {
        changeFilterStatus(id, "enabled");
    } else {
        changeFilterStatus(id, "disabled");
    }
}

function changeFilterStatus(id, status) {
    if ($(id) != null) {
        $(id).className = "filter" + status;
    }
}

function gestionDisplay(product) {
    var more = "more";
    if (navigator.language == "fr") {
        more = "de plus";
    }       
    //manage the heigth for the choice panel
    var productChoice = $("productChoice");
    if (productChoice != null) {
        var products = productChoice.getElementsByTagName("tr");
        var style = "choiceAll";
        if (products.length == 3) {
            style = "choiceVtonly";
        }
        for (var i = 0; i < products.length; i++) {
            products[i].className = style;
        }
    }
    
    
    if($("collapsePictureliveclassroom")) {
        changePicture("collapsePictureliveclassroom", pathPictures+"/items/category-expanded.png");
        $("div_liveclassroom").setAttribute("state", "expand");
    }
    if($("collapsePictureboard")) {
        changePicture("collapsePictureboard", pathPictures+"/items/category-expanded.png");
        $("div_board").setAttribute("state", "expand");
    }
    if($("collapsePicturepresentation")) {
        changePicture("collapsePicturepresentation", pathPictures+"/items/category-expanded.png");
        $("div_presentation").setAttribute("state", "expand");
    }
    if($("collapsePicturepc")) {
        changePicture("collapsePicturepc", pathPictures+"/items/category-expanded.png");
        $("div_pc").setAttribute("state", "expand");
    }
    
    //manage the display of the list
    var list = $('list')
    if(list!=null){
        var products= 1;//list.getElementsByTagName("div")
        for( var i = 0; i < products.length; i++ ) {
           if(products[i].getAttribute("typeElement")=="productList" && (product==products[i].getAttribute("product") || product==null)){
                var divProductMore=$('div_'+products[i].getAttribute("typeproduct")+'_More');
                var divProductNoElement=$('div_'+products[i].getAttribute("typeproduct")+'_NoElement');
                
                var tr=products[i].getElementsByTagName("tr");
                var numElement=0;
                //count the number of element available of the current product
                for(var j=0;j<tr.length;j++){
                    if(tr[j].getAttribute("typeProduct")==products[i].getAttribute("typeProduct") || $("view")!=null && $("view").value == "student" && tr[j].getAttribute("typeProduct")=="orphanedarchivestudent" || (products[i].getAttribute("typeProduct") == LC_PRODUCT && tr[j].getAttribute("typeProduct")=="orphanedarchive") )
                        if(tr[j].getAttribute("stateSearch")!="ignore" && tr[j].getAttribute("stateStudentView")!="ignore")
                            numElement++;
                }
                //4 div per product
                if(numElement > 5 && product==null && products.length>4){
                    products[i].style.height = 5 * 19 + "px";
                    divProductMore.style.display = "block";
                    products[i].style.display = "block";
                    divProductMore.innerHTML = "<label class='moreRoom' onclick=\"displayAllDiv('"+products[i].id+"','div_"+products[i].getAttribute("typeProduct")+"_More','"+numElement+"')\">" + (numElement - 5) + " "+more+"...</label>";
                    divProductNoElement.style.display = "none";
                }else if(numElement == 0){
                    products[i].style.display = "none";
                    divProductNoElement.style.display = "block";
                    divProductMore.style.display = "none";
                }else{  
                     products[i].style.display = "block";
                     products[i].style.height = "";
                     divProductMore.style.display = "none";
                     divProductNoElement.style.display = "none";
                }
            }
        }

    }

}

/*
 * Called to display the complete list of element for a product
 */
function displayAllDiv(name, nameMore, number) {
    if (currentId != "") {
        $(currentId).style.backgroundColor = "white";
    }
    currentId = "";
    currentGradeId = "-1";
    expandState = 1;
    $(name).style.height = "";
    $(nameMore).innerHTML = "<label class='moreRoom' onclick='RemoveAllDiv(\"" + name + "\",\"" + nameMore + "\",\"" + number + "\")'>Show Top 5...</label>";
}
/*
 * Called to hide the complete list of element and display just 5 elements
 */
function RemoveAllDiv(name, nameMore, number) {
    if (currentId != "") {
        $(currentId).style.backgroundColor = "white";
    }
    currentId = "";
    currentGradeId = "-1";
    expandState = 0;
    $(name).style.height = 5 * 19 + "px";
    $(nameMore).innerHTML = "<label class='moreRoom' onclick='displayAllDiv(\"" + name + "\",\"" + nameMore + "\",\"" + number + "\")'>" + (number - 5) + " more...</label>";
}

/*
 * Called to hide the complete list of elements
 */
function collapseGroupElement(product, pictureId) {
    var divProduct = $("div_" + product);
    var divProductMore = $("div_" + product + "_More");
    var elements = divProduct.getElementsByTagName("tr");
    if (divProduct.getAttribute("state") == "expand") {
        divProduct.style.display = "none";
        divProductMore.style.display = "none";
        changePicture(pictureId, pathPictures+"/items/category-collapsed.png");
        divProduct.setAttribute("state", "collapse");
    } else {
        divProduct.style.display = "block";
        if(currentFilter == "filter_all") {
            divProductMore.style.display = "block";
        } else {
            divProductMore.style.display = "none";
        }
        changePicture(pictureId, pathPictures+"/items/category-expanded.png");
        divProduct.setAttribute("state", "expand");
    }
}


/*
 * Filled the session array thanks to the xmlDocument
 */
function createSessionByXmlDocument(data) {

    objDomTree = data.documentElement;

    informationNode = objDomTree.getElementsByTagName("information")[0];
    if(informationNode != null ) //Error xml doesnt contain information element
    { 
        
        
       if (informationNode.getElementsByTagName("timeOfLoad")[0].hasChildNodes() == true) 
            session["timeOfLoad"] = objDomTree.getElementsByTagName("timeOfLoad")[0].childNodes[0].nodeValue;
        else
            session["timeOfLoad"] ="";
    
        if (informationNode.getElementsByTagName("firstName")[0].hasChildNodes() == true) 
            session["firstName"] = objDomTree.getElementsByTagName("firstName")[0].childNodes[0].nodeValue;
        else
            session["firstName"] ="";
            
        if (informationNode.getElementsByTagName("lastName")[0].hasChildNodes() == true) 
            session["lastName"] = objDomTree.getElementsByTagName("lastName")[0].childNodes[0].nodeValue;
        else
            session["lastName"] ="";
    
        if (informationNode.getElementsByTagName("courseId")[0].hasChildNodes() == true) 
            session["courseId"] = objDomTree.getElementsByTagName("courseId")[0].childNodes[0].nodeValue;
        else
            session["courseId"] ="";
    
        if (informationNode.getElementsByTagName("email")[0].hasChildNodes() == true) 
            session["email"] = objDomTree.getElementsByTagName("email")[0].childNodes[0].nodeValue;
        else
            session["email"] ="";
           
        if (informationNode.getElementsByTagName("signature")[0].hasChildNodes() == true) 
            session["signature"] = objDomTree.getElementsByTagName("signature")[0].childNodes[0].nodeValue;
        else
            session["signature"] ="";
            
         if (informationNode.getElementsByTagName("role")[0].hasChildNodes() == true) 
            session["role"] = objDomTree.getElementsByTagName("role")[0].childNodes[0].nodeValue;
        else
            session["role"] ="";
    }
    //preload all the pictures

}


/*
 * Manage the launch of an element switch the context
 */
function LaunchElement(url) {
    if ( currentProduct == LC_PRODUCT && currentId != "") 
    {
        LaunchLiveClassroom(url);
    } 
    else 
    {
        if ( currentProduct == VT_PRODUCT  && currentId != "") 
        {
            LaunchVoiceTools(url);
        }
    }
}

/*
 * Launch the LiveClassroom into a popup
 */
function LaunchLiveClassroom(url, params) {
    var result = true;
    if (session["role"] == "StudentBis" || (session["role"] == "Instructor" && studentView == true)) {
        result = window.confirm("You are in Student View.\nYou will not have access to instructor features.");
    }
    if (result == true) {
        var complete_url = url + "?action=launch&" + getUrlParameters();
        window.open(complete_url, "lc_popup", "scrollbars=no,resizable=yes,width=900,height=648");
    }
}

/*
 * Launch the VoiceTools into a popup
 */
function LaunchVoiceTools(url, params) {
    var result = true;
    //Student view
    if (session["role"] == "StudentBis" || (session["role"] == "Instructor" && studentView == true)) {
        result = window.confirm("You are in Student View.\nYou will not have access to instructor features.");
    }
    if (result == true) {
    	if( params == null || params.empty()){
        	complete_url = url + "?action=launch&" + getUrlParameters();
    	}else{
	    	complete_url = url + "?action=launch&" + params;
    	}
        window.open(complete_url, "vt_popup", "scrollbars=no,resizable=yes,width=1000,height=700");
    }
}


/*
 * function called when you click on a tab in the settings
 */
function onTab(id, additionalfunction) 
{
    if( !$("tab" + id).hasClassName('disabled'))
    {
        if (advancedSettings == true) {
            advancedSettings = false;
            currentIdtab = id;
            if (lcPopup != null) {
                lcPopup.close();
            }
            //execute a second function if necessary(used for advanced settings)
            eval(additionalfunction);
        } 
        else 
        {
            
            if (currentIdtab == "" || currentIdtab == null) {//first time
                currentIdtab = "Info";//default tab
            }
            
            if (currentIdtab != id && id == "Advanced") 
            {//click on the advanced tab
                $("hiddenDivAdvanced").show();
                $("advancedOk").onclick=function(){gotoAdvanced(currentIdtab , id , additionalfunction );};
                $("advancedPopup").show();
            } 
            else 
            {
                if ( currentIdtab != id && $("tab" + id).className != "tabDisabled" ) 
                {
                    $("tab" + id).addClassName("current");
                    $("tab" + currentIdtab).removeClassName("current");
                    $("div" + currentIdtab).hide();
                    $("div" + id).show();
                    currentIdtab = id;
                }
            }
        }
    }
}
/*
 * Manage the options and the tabs when you toogle the type of the room
 */
function toggleTypeOfRoom(type) {
    
    if ( $('tabChat').hasClassName('disabled')) //hide 
    {
        $('tabChat').removeClassName('disabled');
        $('tabChat').title='active';
        $('tabChatspan').innerHTML= "<a href='#'>Chat</a>";
    }
    else
    {
        $('tabChat').addClassName('disabled');
        $('tabChat').title='disable';
        $('tabChatspan').innerHTML= "Chat";
    }
    
    element = $$("p.lectureRoom"); //element only display for lecture room

    for (var x = 0; x < element.length; x++) {
        if (type == "lectureRoom") //hide 
        {
            element[x].addClassName("showElement");
            element[x].removeClassName("hideElement");
        } 
        else 
        {
            element[x].removeClassName("showElement");
            element[x].addClassName("hideElement");
        }
    }
    
    element = $$("input.discussionRoomDisabled"); //element disabled for discussion room

    for (var x = 0; x < element.length; x++) {
        if (type == "discussionRoom") //hide 
        {
            element[x].disabled=true;
        } 
        else 
        {
            element[x].disabled=false;
           
        }
    }
    
    element = $$("p.discussionRoom");//element only display for discussion room

    for (var x = 0; x < element.length; x++) {
        if (type == "discussionRoom") //hide 
        {
            element[x].addClassName("showElement");
            element[x].removeClassName("hideElement");
        } 
        else 
        {
            element[x].removeClassName("showElement");
            element[x].addClassName("hideElement");
        }
    }
    
}

var last_state = true;
function managePublicState(id, type) {
    if (type == "private") {
        $(id).disabled = true;
        last_state = $(id).checked;
        $(id).checked = false;
    } else {
        $(id).disabled = false;
        $(id).checked = last_state;
    }
}

function managePointsPossible(){
	if($("grade").checked == false){//the user uncheck the grade setting, a message has to be displayed when the user click on submit
	  if(!confirm("Are you sure you want to permanently clear all grades for this Voice Board?\nAfter selecting OK all grades will be lost and are not recoverable.")){
		  $("grade").checked = true;//revert the change
		  return false;
	  }
	}
	$("points_possible").disabled=!$("points_possible").disabled;
	$("points_possible").value="";
	
}




/*
* Launch the verification of the form switch the context before submitting the form
*/
function submitForm(url, action, id) {
	   
    currentId = id;
    if (action == "createCalendar") {
        verifyCalendarForm(url + "?action=" + action + "&" + getUrlParameters());
    } else {
        if (currentProduct == LC_PRODUCT) {
            if (action == "update") {
            
                verifyFormLiveClassRoomUpdate(url + "?action=" + action + "&" + getUrlParameters());
            } else {
                
                verifyFormLiveClassRoom(url + "?action=" + action + "&" + getUrlParameters());
            }
        } else {
        
            verifyFormVoiceBoard(url + "?action=" + action + "&" + getUrlParameters());
        }
    }
    currentId = "";
    currentGradeId = "-1";
}
function checkGrade()
{
  var alist = document.getElementsByTagName("input");
  for (i = 0; i < alist.length; i++)
  {
    if (alist[i].type == "text")
    {
      if (alist[i].value != "" && !isNumeric(alist[i].value))
      {
        alert("A valid numeric must be entered");
        alist[i].focus();    //give the focus
        return false;
      }
    }
  }
  return true;
}

function submitGradeForm(url, action, params){
	if(checkGrade()){
			
		var theForm = window.document.myform;
		theForm.action = url + "?action=" + action + "&" + params ;
		theForm.submit();
	}
	return false;
}



function redirectToActivity(url, action, id){
    
    location.href=url + "?action=" + action + "&" + getUrlParameters();
}
/*
 * hide the message bar
 */
function closeMessageBar() {
    $("message").hide();
}
/*
 * called when you clik on settings
 */
function editSettings(url, div) {
    if (currentId != "") {
        loadSettings(url, "update", div);
    }
}
/*
 * called when you clik on report
 */
function openReport(url) {
    if (currentId != "" && currentProduct == LC_PRODUCT) {
        doOpen(url + "?action=openReport&", true);
    }
}
/*
 * called when you clik on delete
 */
function deleteResource(url) {
    if (currentId != "" && confirmDelete()) {
        doOpen(url + "?action=delete&", false);
    }
}
/*
 * called when you clik on content
 */
function openContent(url) {
    if (currentId != "" && currentProduct == LC_PRODUCT) {
        doOpen(url + "?action=openContent&", true);
    }
}
/*
 * ask for a confirmation before deleting something
 */
function confirmDelete()
{
    var type = ""
    if( currentProduct == LC_PRODUCT )
    {
        type = "Room"
    }
    else if( currentProduct == VT_PRODUCT && currentType == VB_PRODUCT )
    {
        type = "Voice Board"
    }
    else if( currentProduct == VT_PRODUCT && currentType == VP_PRODUCT ) 
    {
        type = "Voice Presentation" 
    }
    else if( currentProduct == VT_PRODUCT && currentType == PC_PRODUCT )
    {
        type = "Voice Podcaster"
    }
    
    if(navigator.language=="fr")
    {
        return confirm("Etes vous sur de vouloir supprimer ce " + type +" ?")
    }
    else
    {
         return confirm("Are you sure you want to delete this " + type +" ?")
    }
}



function doOpen(url, popup) {
    var complete_url = url + getUrlParameters();
    if (popup == true) {
        var w = window.open(complete_url, "lc_popup", "scrollbars=yes,resizable=yes,width=900,height=650");
        w.focus();
    } else {
        window.open(complete_url, "_self");
    }
}

function doOpenPodcaster(url, popup) {
    var complete_url = url;
    if (popup == true) {
        var w = window.open(complete_url, "pc_popup", "scrollbars=yes,resizable=yes,width=800,height=500");
        w.focus();
    } else {
        window.open(complete_url, "_self");
    }
}

//advanced Setting
function cancelAdvanced() {
    $("hiddenDivAdvanced").hide();
    $("advancedPopup").hide();
}

/* 
* Called when you click on the Ok button of the modal popup
* behavior : exceute a function to save the old settings
* and display the advanced panel
*/
function gotoAdvanced(currentId, id, additionalFunction) {
    
    if ( eval(additionalFunction)!= false) {
    
        $("tab" + id).addClassName("current");
        $("tab" + currentIdtab).removeClassName("current");
        $("div" + currentIdtab).hide();
        $("div" + id).show();
        currentIdtab = id;
        advancedSettings = true;
    }
    
    $("hiddenDivAdvanced").hide();
    $("advancedPopup").hide();
  
    var validationElement =  $A($("validationBar").getElementsByTagName("li")).map(Element.extend);
   
    for (var i = 0; i < validationElement.length; i++) 
    {
        validationElement[i].toggleClassName ("hideElement"); 
    }
}

/*
 * toogle the state of the chat setting
 */
function doChangeChat() {
    $("privateChatEnabled").disabled = !$("privateChatEnabled").disabled;
}
/*
 * toogle the state of the breakout settings
 */
function doBreakoutEnabled() {
    $("enabled_students_breakoutrooms").disabled = !$("enabled_students_breakoutrooms").disabled;
    $("enabled_students_mainrooms").disabled = !$("enabled_students_mainrooms").disabled;
}

/*
 * toogle the state of the status settings
 */
function doStatusEnabled() {
    $("status_appear").disabled = !$("status_appear").disabled;
}
/*
 * toogle the display of the dial-in popup
 */
function opendDialInformations() {
   $("popupDial").toggle();
}
/*
 * toogle the display of the opac backgound
 */
function displayBackground() {
    $("hiddenDiv").toggle();  
}

/*
* Open the advanced Room settings
*/
function openRoomSettings(url) {
    lcPopup = window.open(url + "?action=openAdvancedRoom&" + getUrlParameters(), "lc_popup", "scrollbars=yes,resizable=yes,width=800,height=500");
}
/*
* Open the advanced Media settings
*/
function openMediaSettings(url) {
    lcPopup = window.open(url + "?action=openAdvancedMedia&" + getUrlParameters(), "lc_popup", "scrollbars=yes,resizable=yes,width=800,height=500");
}

function toggleUserlimit(isLimited) {
    $("userlimittext").disabled = (isLimited == false);
}
/*
 * manage the availibilty of the access date
 */
function manageAvailibility() {
    var disabled = true;
    if ($("accessAvailable").checked == true) {
        disabled = false;
    } else {
        disabled = true;
    }
    $("start_date").disabled = disabled;
    $("end_date").disabled = disabled;
    manageAvailibilityDate("start", disabled);
    manageAvailibilityDate("end", disabled);
}

/*
 * manage the availability of the date fields
 */
function manageAvailibilityDate(type, disabled) {
    var disabledDate = false;
    if (disabled == true) {
        disabledDate = true;
    } else {
        if ($(type + "_date").checked == false) {
            disabledDate = true;
        }
    }
    $(type + "_hr_field").disabled = disabledDate;
    $(type + "_min_field").disabled = disabledDate;
    $(type + "_day_field").disabled = disabledDate;
    $(type + "_month_field").disabled = disabledDate;
    $(type + "_year_field").disabled = disabledDate;
}
/*
 * Display the loading popup
 */
function togglePopup() {
    $("all").toggle();
    $("loading").toggle();
}


//Manage the display of the rss Feed
function convertDate(str) {
    var strSplit = str.split(" ");
    var time = strSplit[4];
    var timeSplit = time.split(":");
    return strSplit[1] + " " + strSplit[2] + " " + strSplit[3] + "   " + timeSplit[0] + ":" + timeSplit[1];
}
/*
 * Display a rss feed
 */
function displayRssFeed(data, div) {
    objDom = new XMLDoc(data, null);
    objDomTree = objDom.docNode;
    var error = objDomTree.getElements("error");
    var channel = objDomTree.getElements("channel");
    $(div).innerHTML = displayItem(channel);
}

//display a feed item
function displayItem(elementParameters) {
    var length = 0;
    var display = "";
    //Get items
    var item = elementParameters[0].getElements("item");
    display += "<table cellspacing=5 cellpadding=0 width=100% style='padding-left:15px'>";
    if (item.length == 0) {
        display += "<tr>";
        display += "<td class=noPodcaster>The Blackboard Collaborate Podcaster for this course is empty. To make a recording, click the link below to open the Blackboard Collaborate Podcaster and create a new post.</td>";
        display += "</tr>";
    } else {
        if (item.length >= 5) {
            length = 5;
        } else {
            length = item.length;
        }
        for (var i = 0; i < length; i++) {
            display += "<tr>";
            display += "<td align=left style='padding-left:10px'>" + item[i].getElements("title")[0].getText() + "</td>";
            display += "<td>" + convertDate(item[i].getElements("pubDate")[0].getText()) + "</td>";
            display += "<td align=right style='padding-right:10px' class='link' onclick='javascript:doOpenPodcaster(\"" + item[i].getElements("link")[0].getText() + "\",true)'>Read & Listen</td>";
            display += "</tr>";
        }
    }
    display += "</table>";
    return display;
}

function launchPodcast(url) {
    if (currentId != "") {
        doOpen(url + "?action=launch&", true);
    }
}




function addLoadEvent(func) {
    var oldonload = window.onload;
    if (typeof window.onload != 'function') {
        window.onload = func;
    } else {
        window.onload = function() {
            if (oldonload) {
                oldonload();
            }
            func();
        }
    }
}

function init() {
    $("searchField").onkeyup = function() {
        searchResource(this.value);
        displaySearchFieldResetBtn(this.value);
    }       
    
    $("searchField").onchange = function() {
        displaySearchFieldResetBtn(this.value);
    }   
    
    $("searchFieldResetBtn").onclick = function() {
        resetSearchField();
        searchResource("");
    }       
}

function detectSafari() {
    if ((navigator.userAgent).indexOf("Safari") != -1) {
        $('searchBox').className = "searchBox safari";
        $('searchField').setAttribute('results', "5");
    }
}
            
function displaySearchFieldResetBtn(s) {
    if (s.length > 0) {
            $('searchFieldResetBtn').style.visibility="visible";
        } else{
            $('searchFieldResetBtn').style.visibility="hidden";
        }
}
        
function resetSearchField() {
    $('searchField').value = "";
    displaySearchFieldResetBtn("");
}   

function showGrades(url){
	  if (currentId != "" && currentGradeId !="-1" ) {
		  currentProduct="voiceboard";
		  location.href=url + "?gradeId="+currentGradeId+"&resource_id=" + currentId + "&" + getUrlParameters();
	  }
}

function doChangeMediaPriority()
{
  if (document.getElementById('mp4_media_priority_content_focus_with_video').checked == false)
  {
    document.getElementById('mp4_media_priority_content_include_video').checked = false;
    document.getElementById('mp4_media_priority_content_include_video').disabled = true;
    document.getElementById('mp4_media_priority_content_include_video_label').style.color = "gray";
  }
  else
  {
    document.getElementById('mp4_media_priority_content_include_video').disabled = false;
    document.getElementById('mp4_media_priority_content_include_video').checked = false;
    document.getElementById('mp4_media_priority_content_include_video_label').style.color = "black";
  }

}

function closePopup(){
	
	 $("hiddenDiv").hide(); 
	 $("downloadPopup").hide(); 
}
