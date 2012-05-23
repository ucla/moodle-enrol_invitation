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
 * Class:  ajax.js                                                            *
 *                                                                            *
 * Author:  Thomas Rollinger                                                  *
 *                                                                            *
 * Date: May 2007                                                             *
 *                                                                            *
 ******************************************************************************/

function initPath(xsl,pictures){
    pathXsl=xsl;
    pathPictures=pictures;
}


/*
 * Get the parameters passed with the GET Method and put them into a
 * javascript array  
 */
function getURLParametersTab() {
    var sURL = window.document.URL;
    
    var params = new Array();
    if (sURL.indexOf("?") > 0) {
        var arrParams = sURL.split("?");
        var arrURLParams = arrParams[1].split("&");
        var i = 0;
        for (i = 0; i < arrURLParams.length; i = i + 1) {
            var param = arrURLParams[i].split("=");
            params[param[0]] = unescape(param[1].replace(/\+/g,"%20"));
        }
    }
    return params;
}

/*
 * Get the parameters passed with the GET Method and 
 * create a string with them.
 */
function getURLParameters() {
   var sURL = window.document.URL.toString();
   var params = "";
   if (sURL.indexOf("?") > 0)
   {
      var arrParams = sURL.split("?");

      var arrURLParams = arrParams[1].split("&");

      var arrParamNames = new Array(arrURLParams.length);
      var arrParamValues = new Array(arrURLParams.length);

      var i = 0;
      for (i = 0; i < arrURLParams.length; i ++ )
      {
         var sParam =  arrURLParams[i];
         arrParamNames[i] = sParam[0];
         if (sParam[1] != "")
         params += "&" + unescape(sParam);

      }
      params = params.substring(1, params.length)
   }
   return params;
}

/*
 * Create an array which contains different informations
 * about the component status and session variable
 */
function getSessionParameters() {
    var params = new Array();
    params["signature"] = session["signature"];
    params["enc_course_id"] = session["courseId"];
    params["enc_email"] = session["email"];
    params["enc_firstname"] = session["firstName"];
    params["enc_lastname"] = session["lastName"];
    params["enc_role"] = session["role"];
    params["time"] = session["timeOfLoad"];
    
    if ( !currentProduct.empty() ) 
    {
        params["product"] = currentProduct;
    }
    if ( !currentType.empty() ) 
    {
        params["type"] = currentType;
    }
    if ( !currentId.empty() ) 
    {
        params["resource_id"] = currentId;
    }
    if ( !currentIdtab.empty() ) 
    {
        params["idtab"] = currentIdtab;
    }
    if ( !currentCourseTab.empty() ) 
    {
        params["tab"] = currentCourseTab;
    }
    params["studentView"] = studentView;
    
    return params;
}
/*
 * Create a string which contains different informations
 * about the component status and session variable to be passed
 * as a GET parameters
 */
function getUrlParameters() {
    var url = "";
    url += "signature=" + session["signature"];
    url += "&enc_course_id=" + session["courseId"];
    url += "&enc_email=" + session["email"];
    url += "&enc_firstname=" + session["firstName"];
    url += "&enc_lastname=" + session["lastName"];
    url += "&enc_role=" + session["role"];
    url += "&time=" + session["timeOfLoad"];
    
    if ( !currentProduct.empty() ) {
        url += "&product=" + currentProduct;
    }
    if ( !currentType.empty() ) {
        url += "&type=" + currentType;
    }
    
    if ( !currentId.empty() ) {
        url += "&resource_id=" + currentId;
    }
    
    if ( !currentIdtab.empty() ) {
        url += "&idtab=" + currentIdtab;
    }
    if ( !currentCourseTab.empty() ) {
        url += "&tab = " + currentCourseTab;
    }
    url += "&studentView=" + studentView;

    return url;
}

/*
 * Display the principal page of the component
 * url : file which generate the xml 
 * div : id of the div where the html will be
 * action :
 * nextAction : 
 */
function DisplayFirstPage(url, div, action, nextAction) {
    currentId = "";
    currentGradeId = "-1";
    if ( !action.empty() ) 
    {
        url += "?action=" + action;
    }

    transform(url,div,true,nextAction)
}

/*
 * Create the context of the Ajax request and execute it
 * 
 */
function launchAjaxRequest(url, action, init, div) {

    //For the advanced settings
    if (lcPopup != null) 
    {
        lcPopup.close();
    }
    
    if (!action.empty()) 
    {
      url += "?action=" + action;
    }

    isFilter = false;
    
    transform(url,div)
    
    if (init == true) 
    {   
      currentId = "";
      currentIdtab = "Info";
      currentType = "";
      currentProduct="";
      currentGradeId="";
      advancedSettings = false;
    }
}

/*
* Function used to display the rss feed into the podcaster nugget
*/
function launchSimpleAjaxRequest(url, action, init, div) {
    //display the loading page
    var xml;
    var parameters = "";
    if (action != "") {
        url += "?action=" + action;
    }
    
    var xmlSource;
    
    /*new Ajax.Request(urlXml,
    {
        method:'post',
         mimetype:"application/xml",
        parameters:params,
        onSuccess: function(transport){      
          xmlSource = transport;
          createSessionByXmlDocument(transport.responseXML);
          new Ajax.Request(pathXsl,
          {
             method:'post',
             mimetype:"application/xml",
             onSuccess: function (transport) {

                    xsltTransformation(xmlSource,transport.responseXML,div,nextAction)
          }});    
        },
        onFailure: function(){  }
    });
    
    
    
    dojo.io.bind({
    url:url, mimetype:"text/plain",
    load:function (type, data, evt) {
        //error management
        if (data.match("error") == "error") {
            dom = xmlParse(data);
            xmlSource = dom;
            if (xslData == null) {       //get the stylesheet
                dojo.io.bind({
                url:pathXsl,
                mimetype:"application/xml", 
                load:function (type, data, evt) {
                  
                    xslData = data;
                    var xml = xsltProcess(xmlSource, xslData);  
                    hidePopup();
                    document.getElementById(div).innerHTML = xml;
                },
                error:BadResult,
                content:null,
                method:"POST"});
            } else {
                transform(xml, xslData, 'all');
            }
        } else {
            //launch the generation of the display
            displayRssFeed(data, div);
        }
    }, 
    error:BadResult,
    content:getSessionParameters(),
    method:"POST"});
*/
}

/*
 * Create the context of the Ajax request and execute it
 */
function getRss(url, id, tab) 
{
    if( !id.empty() )
    {
        document.getElementById("rss").innerHTML = document.getElementById("loadingPopup").innerHTML;
        currentId = id;
        currentCourseTab = tab;
        launchSimpleAjaxRequest(url, "getRss", true, "rss");
    }
}

/*
 * This function execute the ajax request to get the approppriate xml and
 * the xsl. Then, It execute the xslt transformation to generate the html
 * 
 * For IE: we used the native javascript function to get the xml,xsl by Ajax and 
 * excecute the xslt transformation
 * 
 * For Firefox : we used the dojo framework to get the xml,xsl by Ajax and the native
 * function to execute the xslt transformation
 * 
 * For Safari: we used he dojo framework to get the xml,xsl by Ajax and the google library
 * (Google AJAXSLT) to execute the xslt transformation because it is not supported natively
 */
function transform(urlXml, div, isFirst, nextAction) {
     
    if (div == "all") 
    {
        togglePopup();
    } 
     
    if( isFirst == true ) //the parameters used are in the url
    {    
        params=getURLParametersTab();
    }
    else
    {
         params=getSessionParameters();
    }
    
    new Ajax.Request(urlXml,
    {
        method:'post',
         mimetype:"application/xml",
        parameters:params,
        onSuccess: function(transport){      
          xmlSource = transport;
          if (div == "all") 
          {
             createSessionByXmlDocument(transport.responseXML);
          }
          new Ajax.Request(pathXsl,
          {
             method:'get',
             mimetype:"application/xml",
             onSuccess: function (transport) {
                    xsltTransformation(xmlSource,transport.responseXML,div,nextAction)
          }});    
        },
        onFailure: function(){  }
    });
}

/*
 * Call the appropriate function to execute the xslt transformation
 * and add the html into the page
 */
function xsltTransformation(xml,xsl,div,nextAction){
    
	var selectedIndex = 0;
    if (div == "all") 
    {
        togglePopup();
    }
    if($("view") != null)//the dropdown is not present for the student
    {
    	selectedIndex = $("view").selectedIndex;//save the current state of the dropdown
    }
    if(window.ActiveXObject)//ie
    {   
        var result = xml.responseXML.transformNode(xsl); 
        if (div == "all") 
        {
            document.getElementById(div).innerHTML = result;
        }
        if (div == "dialInfo") 
        {//click on Info Button
            document.getElementById("all").parentNode.innerHTML = document.getElementById("all").parentNode.innerHTML + result;
        }   
        
            
     }
     else if (window.XSLTProcessor && navigator.userAgent.indexOf( 'Safari' ) == -1)
     {//firefox 
        var processor = new XSLTProcessor(); 
        processor.setParameter(null, 'pathPictures', pathPictures);
        processor.importStylesheet(xsl); 
       
        var resultDocument = processor.transformToFragment(xml.responseXML, document);
        
        if (div == "all") {
            document.getElementById(div).innerHTML="";
            document.getElementById(div).appendChild(resultDocument);
        
        }
        if (div == "dialInfo") 
        {//click on Info Button
            document.getElementById("all").parentNode.appendChild(resultDocument);
        }
        
     }
     else
     {   
        xml=xmlParse(xml.responseText);
        var xml = xsltProcess(xml, xsl);    
        
        if (div == "all") {
            
            document.getElementById(div).innerHTML = xml;
        }
        if (div == "dialInfo") {//click on Info Button
            document.getElementById("all").parentNode.innerHTML = document.getElementById("all").parentNode.innerHTML + xml;
        }
    }   
    
    if(nextAction!="")
            eval(nextAction);

    //manage the display of the accent
    var content=new String(document.getElementById("all").innerHTML);
    content=content.replace(/&amp;/g,"&");
    
    document.getElementById("all").innerHTML=content;
    if($("view") != null)
    {
    	$("view").selectedIndex = selectedIndex ;//restore the state of the dropdown
    }
    init();
    detectSafari();
}



function BadResult(type, error) {

}

/*
 * load the setting of the Lc or Vt
 */
function loadSettings(url, action, div) 
{

    launchAjaxRequest(url, action, false, div);
}

/*
 * Load the settings of a existing element(room or voice tools)
 */
function loadNewSettings(url, action, product, type, div) 
{
    currentProduct = product;
    currentType = type;
    
    launchAjaxRequest(url, action, false, div);
}

/*
 * Save the comment and the title of the voice recorder
 */

function saveDatabase(url, title, comment) {
    var url = url + "?action=updateRecorder&title=" + title + "&comment=" + comment;
    
    new Ajax.Request(url,
    {
        method:'post',
         mimetype:"application/xml",
        parameters:getURLParametersTab(),
        onSuccess: function(transport){      
          if (transport.responseText == "bad") 
          {
            alert("problem to save");   
          }
        },
        onFailure: function(){  }
    });
}


/*
 * Get the dial-in informations fo a specific room
 */
function showInformation(url, id, product) {
    currentId = id;
    currentProduct = product;
    launchAjaxRequest(url, "getDialInformation", false, "dialInfo");
}

/*
 * Save the settings of a existing room. It used to save the room settings before 
 * launch the advanced settings
 */
function saveSettings(url, id) {
        
    url = url + "?action=saveSettings&id=" + id;
    var returnValue = true;
    window.document.myform.action=url;
    
    $(window.document.myform).request({ 
        method: 'post', 
        parameters:getSessionParameters() , 
        onComplete: function(){
            if (transport.responseText == "bad") 
            {
                alert("problem to save");
                returnValue = false;   
            }
       } 
    }) ;
    return returnValue;
}


function createNewResource(url,product,type,name,params){
        var url = url+"?longname="+escape(name)+"&default=true&action=createDefault&product="+product+"&type="+ type +"&" + params;
        new Ajax.Request(url,
        {
            method:'post',
            mimetype:"application/xml",
            onSuccess: function(transport){     
                $("newPopup").hide();
                
                if(transport.responseText != "error"){ 
                    $("hiddenDiv").hide();
                    var elOptNew = document.createElement('option');
                    elOptNew.text = name;
                    elOptNew.value = transport.responseText;
                    var elSel = $('id_resource');
                    var lastOptElement = $('id_resource').options[elSel.options.length -1 ]
                 
                    try 
                    {  
                        elSel.options[elSel.options.length -1]=elOptNew;
                        elSel.add(lastOptElement, null);// standards compliant; doesn't work in IE
                       
                    }
                    catch(ex) 
                    {
                        elSel.options[elSel.options.length -1]=lastOptElement;
                        elSel.add(elOptNew, elSel.options.length - 1); // IE only
                    } 
                    elSel.selectedIndex = elSel.options.length - 2; //select the new one
                    isValidate();
                    $('loading').hide();
                    var allSelect =  document.getElementsByTagName("select");
				    for( i=0;i<allSelect.length;i++)
				    {
				        allSelect[i].style.visibility="";
				    }  
                    
                }
                else
                {
                   $('error').show();
                }
            },
            onFailure: function(){  }
        });
}

function downloadAudioFile(url,action,id)
{

  new Ajax.Request(url+"?action="+action+"&rid_audio="+id,
  {
    method:'post',
    mimetype:"text/plain",
    parameters:getSessionParameters() , 
    onSuccess: function (transport)
    {
      var audioFileStatus = transport.responseText.split(";");

      if (audioFileStatus.length > 3 || transport.responseText.blank())
      {
        $("status").innerHTML = audioStatus["error"];
        $("hiddenDiv").show();
        $("downloadPopup").show();
   
      }
      else if (audioFileStatus[0] == "exists")
      {
        if (top == self)
          window.location.href = audioFileStatus[1];
        else
          window.top.location.href = audioFileStatus[1];
      }
      else
      {
        $("status").innerHTML = audioStatus[audioFileStatus[0]];
        $("hiddenDiv").show();
        $("downloadPopup").show();
      }
    },
    onFailure: function()
    {
      $("status").innerHTML = audioStatus["error"]; 
      $("hiddenDiv").show();
      $("downloadPopup").show();
   }
  });
}

