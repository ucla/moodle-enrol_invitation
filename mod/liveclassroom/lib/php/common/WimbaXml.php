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

     
/* $Id: $ */  
class WimbaXml
{
    var $xmldoc;
    var $part=array(); //contains the different part of the UI
    var $linePart=array();
    var $lineElement=array();  
    var $panelLines=array();  
    var $validationElements;
    var $Informations;
    var $error=NULL;
    var $finalstring;
      
    function WimbaXml()
    {           
        $this->xmldoc = domxml_new_doc("1.0");
    }
    
    /*
     * This function generate the global xml which will render the html
     * Each element of $part is grouped in only one xml
     */
    function getXml() 
    {
        if( empty($this->finalstring) )
        {
            $root = $this->xmldoc->create_element("root");
            $windows = $this->xmldoc->create_element("windows");
       
            if( $this->error == NULL )
            {
                if(isset($this->Informations))
                    $root->append_child($this->Informations);         
                      
                foreach ($this->part  as $key => $value)
                {
                    $windows->append_child($this->addWindowsElement($key, $value));
                }                
            }
            else
            {
                $windows->append_child($this->addWindowsElement("error",$this->error));    
            }
        
            $root->append_child($windows);
            $this->xmldoc->append_child($root);
            $xmlstring = $this->xmldoc->dump_mem(true);  // Xml datas into a string
            $this->finalstring = str_replace("\n", '', $xmlstring);
        }
        return $this->finalstring;    
    }
    
     /*
     * Add the headerBar element. This element is the bar at the top of the component. 
     * It composed by a logo and a drop down to switch the display of the component
     * @param pictureUrl : path of the logo
     * @param disabled : manage the disabled parameter for the drop down 
     * @param isInstructor : role of the current user( the drop down is never displayed for student)
     */   
    function addHeaderElement($pictureUrl,$disabled,$isInstructor)
    {
        if (!isset($this->part["headerBar"]))
        {
            $this->part["headerBar"]=$this->xmldoc->create_element("headerBarInformations");
        }
        
        $picture = $this->xmldoc->create_element("pictureUrl");
        $picture->append_child($this->xmldoc->create_text_node($pictureUrl));   
        $disable = $this->xmldoc->create_element("disabled");
        $disable->append_child($this->xmldoc->create_text_node($disabled));  
        $hbinstructorview = $this->xmldoc->create_element("instructorView");
        $hbinstructorview->append_child($this->xmldoc->create_text_node(get_string('instructorview', 'voiceboard')));
        $hbstudentview = $this->xmldoc->create_element("studentView");
        $hbstudentview->append_child($this->xmldoc->create_text_node(get_string('studentview', 'voiceboard')));
        $isInstructorElement = $this->xmldoc->create_element("isInstructor");
        $isInstructorElement->append_child($this->xmldoc->create_text_node($isInstructor));   
        $this->part["headerBar"]->append_child($picture);  
        $this->part["headerBar"]->append_child($hbinstructorview);  
        $this->part["headerBar"]->append_child($hbstudentview);  
        $this->part["headerBar"]->append_child($disable); 
        $this->part["headerBar"]->append_child($isInstructorElement);   
    }
    
    /*
     * Add a Filter to the filterBar element.
     * @param value: 
     * @param name:
     * @param action:
     * @param availibility:
     */
    function addFilter($value,$name,$action,$availibility)
    {
        if (!isset($this->part["filterBar"]))//first elemet of the filterBar
        {
            //creation of the filterbar element
            $this->part["filterBar"]= $this->xmldoc->create_element("filters");
        }
        
        $filter = $this->xmldoc->create_element('filter');
        $filterValue = $this->xmldoc->create_element("value");
        $filterValue->append_child($this->xmldoc->create_text_node($value));
        $filter->append_child($filterValue);
        $filterName = $this->xmldoc->create_element("name");
        $filterName->append_child($this->xmldoc->create_text_node($name));
        $filter->append_child($filterName);
        $filterValue = $this->xmldoc->create_element("value");
        $filterValue->append_child($this->xmldoc->create_text_node($value));
        $filter->append_child($filterValue);
        $filterAvailibility = $this->xmldoc->create_element("availibility");
        $filterAvailibility->append_child($this->xmldoc->create_text_node($availibility));
        $filter->append_child($filterAvailibility);
        $filterAction = $this->xmldoc->create_element("action");
        $filterAction->append_child($this->xmldoc->create_text_node($action));
        $filter->append_child($filterAction);   
        $this->part["filterBar"]->append_child($filter);
    }

    
    /*
     * Add the contextBar  element. This element is the bar after the header bar(Choice product panel and settings panel). 
     * @param context : context of the display ( settings or other)
     * @param product : current product selected
     * @param name : name of the resource
     * @param style : style that you want to apply to the bar
     */   
    function addContextBarElement($context, $product="",$name="",$style="")
    {
        if (!isset($this->part["contextBar"]))  
        {
            $this->part["contextBar"] = $this->xmldoc->create_element("contextBarInformations");
        }
        
        $contextBarType = $this->xmldoc->create_element("context");
        $contextBarType->append_child($this->xmldoc->create_text_node($context));
        $contextBarStyle = $this->xmldoc->create_element("style");
        $contextBarStyle->append_child($this->xmldoc->create_text_node($style));
        $contextBarProduct = $this->xmldoc->create_element("product");
        $contextBarProduct->append_child($this->xmldoc->create_text_node($product));
        $contextBarName = $this->xmldoc->create_element("name");
        $contextBarName->append_child($this->xmldoc->create_text_node($name));   
        $this->part["contextBar"]->append_child($contextBarType);
        $this->part["contextBar"]->append_child($contextBarProduct);
        $this->part["contextBar"]->append_child($contextBarName);
        $this->part["contextBar"]->append_child($contextBarStyle);
    }

    /*
     * Add a new button to the toolBar
     * @param typeOfUser : type of user which can see the button
     * @param typeOfProduct : type of product for which the button is available
     * @param availibility : availability by default(no room/resource selected)
     * @param category : the type of button( use for css)
     * param  value : text under the button
     * param  action : javascript function called by clicking on the button
     */   
    function addButtonElement($typeOfUser, $typeOfProduct, $availibility="", $category="", $value="", $action="")
    {
        if (!isset($this->part["toolBar"]))
        {
            $this->part["toolBar"]=$this->xmldoc->create_element("menuElements");
        }
        
        $element = $this->xmldoc->create_element("menuElement");
        $elementType = $this->xmldoc->create_element("type");//for student and isntructor or just for instructor
        $elementType->append_child($this->xmldoc->create_text_node("button"));
        $elementTypeOfUser = $this->xmldoc->create_element("typeOfUser");//for student and isntructor or just for instructor
        $elementTypeOfUser->append_child($this->xmldoc->create_text_node($typeOfUser));
        $elementTypeOfProduct = $this->xmldoc->create_element("typeOfProduct");//for student and isntructor or just for instructor
        $elementTypeOfProduct->append_child($this->xmldoc->create_text_node($typeOfProduct));
        $elementCategory = $this->xmldoc->create_element("category");
        $elementCategory->append_child($this->xmldoc->create_text_node($category));
        $elementValue = $this->xmldoc->create_element("value");
        $elementValue->append_child($this->xmldoc->create_text_node($value));
        $elementAvailibility = $this->xmldoc->create_element("availibility");
        $elementAvailibility->append_child($this->xmldoc->create_text_node($availibility));
        $elementAction = $this->xmldoc->create_element("action");
        $elementAction->append_child($this->xmldoc->create_text_node($action));
        $element->append_child($elementAvailibility);
        $element->append_child($elementType);
        $element->append_child($elementTypeOfUser);
        $element->append_child($elementTypeOfProduct);
        $element->append_child($elementCategory);
        $element->append_child($elementValue);
        $element->append_child($elementAction);
        $this->part["toolBar"]->append_child($element);
    }

    
    /*
     * Add a space element to the toolbar
     * @param width : width of the space
     * @param typeOfUSer : type of user for which the space is available
     */ 
    function addSpaceElement($width, $typeOfUser)
    {
        if (!isset($this->part["toolBar"]))
        {
            $this->part["toolBar"]=$this->xmldoc->create_element("menuElements");
        }
        $element = $this->xmldoc->create_element("menuElement");
        $elementType = $this->xmldoc->create_element("type");//for student and isntructor or just for instructor
        $elementType->append_child($this->xmldoc->create_text_node("space"));
        $elementTypeOfUser = $this->xmldoc->create_element("typeOfUser");//for student and isntructor or just for instructor
        $elementTypeOfUser->append_child($this->xmldoc->create_text_node($typeOfUser));
        $elementWidth = $this->xmldoc->create_element("width");//for student and isntructor or just for instructor
        $elementWidth->append_child($this->xmldoc->create_text_node($width));    
        $element->append_child($elementType);
        $element->append_child($elementTypeOfUser);
        $element->append_child($elementWidth);
        $this->part["toolBar"]->append_child($element);
    }

    /*
     * Add a separator to the toolbar
     * @param typeOfUSer : type of user for which the separator is available
     */ 
    function addSeparatorElement($typeOfUser)
    {
        if (!isset($this->part["toolBar"]))
        {
            $this->part["toolBar"]=$this->xmldoc->create_element("menuElements");
        }
        $element = $this->xmldoc->create_element("menuElement");
        $elementType = $this->xmldoc->create_element("type");//for student and isntructor or just for instructor
        $elementType->append_child($this->xmldoc->create_text_node("separator"));
        $elementTypeOfUser = $this->xmldoc->create_element("typeOfUser");//for student and isntructor or just for instructor
        $elementTypeOfUser->append_child($this->xmldoc->create_text_node($typeOfUser));
        $element->append_child($elementType);
        $element->append_child($elementTypeOfUser);
        $this->part["toolBar"]->append_child($element);
    }
    
    /*
     * Add a search element to the toolbar
     * @param typeOfUSer : type of user for which the separator is available
     * @param browser : type of borwser(safari has a speial html element for the search)
     */ 
    function addSearchElement($isInstructor,$browser,$disableSelectView='false')
    {
        if (!isset($this->part["toolBar"]))
        {
            $this->part["toolBar"]=$this->xmldoc->create_element("menuElements");
        }
        $element = $this->xmldoc->create_element("menuElement");
        $elementType = $this->xmldoc->create_element("type");//for student and isntructor or just for instructor
        $elementType->append_child($this->xmldoc->create_text_node("search"));

        $elementBrowser = $this->xmldoc->create_element("browser");//for student and isntructor or just for instructor
        $elementBrowser->append_child($this->xmldoc->create_text_node($browser)); 
         
        $disable = $this->xmldoc->create_element("disabled");
        $disable->append_child($this->xmldoc->create_text_node($disable));  
        $hbinstructorview = $this->xmldoc->create_element("instructorView");
        $hbinstructorview->append_child($this->xmldoc->create_text_node(get_string('instructorview', 'voiceboard')));
        $hbstudentview = $this->xmldoc->create_element("studentView");
        $hbstudentview->append_child($this->xmldoc->create_text_node(get_string('studentview', 'voiceboard')));
        $isInstructorElement = $this->xmldoc->create_element("isInstructor");
        $isInstructorElement->append_child($this->xmldoc->create_text_node($isInstructor));   
         
        $element->append_child($elementType);
        $element->append_child($elementBrowser);
        $element->append_child($hbinstructorview);  
         $element->append_child($hbstudentview);  
         $element->append_child($disable); 
         $element->append_child($isInstructorElement); 
        
        $this->part["toolBar"]->append_child($element);
        
    }

     /*
     * Add a product and his element to the list
     * @param name : name of the product ( LC or VT)
     * @param cssStyle : css which will be apply to the title bar
     * @param value : name of the tools( vb,vp,podcaster, lc..)
     * @param type : type of the tools(use for the search)
     * @param elements :  elements of the list( already xml elements) 
     * @param sentence : sentence when no elements are available
     */ 
    function addProduct($name, $cssStyle, $value,$type,$elements,$sentence,$arrayTitle)
    {
        if (!isset($this->part["list"]))
        {
            $this->part["list"]=$this->xmldoc->create_element("products");
        }
        
        $product= $this->xmldoc->create_element("product");
        $productName= $this->xmldoc->create_element("productName");
        $productName->append_child($this->xmldoc->create_text_node($name));
        $product->append_child($productName);
        $productCssStyle = $this->xmldoc->create_element("style");//for student and isntructor or just for instructor
        $productCssStyle->append_child($this->xmldoc->create_text_node($cssStyle));
        $product->append_child($productCssStyle);
        $productValue = $this->xmldoc->create_element("value");//for student and isntructor or just for instructor
        $productValue->append_child($this->xmldoc->create_text_node($value));
        $product->append_child($productValue);
        $productType = $this->xmldoc->create_element("type");//for student and isntructor or just for instructor
        $productType->append_child($this->xmldoc->create_text_node($type));
        $product->append_child($productType);
        
        if(count($arrayTitle)>0)
        {
            $titles = $this->xmldoc->create_element("titles");
            if($arrayTitle!=null){
                foreach ($arrayTitle as $key => $string)
                {
                      $title = $this->xmldoc->create_element("title");//for student and isntructor or just for instructor
                      $value = $this->xmldoc->create_element("value");
                      $value->append_child($this->xmldoc->create_text_node($string));
	    
                      $title->append_child($value);
                      $titles->append_child($title);
                }
            }
            $product->append_child($titles);
        }
        
        if(count($elements)>0)
        {
            $listElements = $this->xmldoc->create_element("listElements");
            if($elements!=null){
                foreach ($elements as $key => $value)
                {
                    $listElements->append_child($value->getXml($this->xmldoc)); 
                }
                }
            $product->append_child($listElements);
        }
        $productSentence = $this->xmldoc->create_element("NoElementSentence");//for student and isntructor or just for instructor
        $productSentence->append_child($this->xmldoc->create_text_node($sentence));
        $product->append_child($productSentence);
        $this->part["list"]->append_child($product);
    }
    
    /*
     * Return a message Elment
     * @param type : type of message ( error or something else)
     * @param sentemce : sentence of the message
     */ 
    function createMessageElement($type, $sentemce)
    {
        $message = $this->xmldoc->create_element("message");
        $messageType = $this->xmldoc->create_element("type");//for student and isntructor or just for instructor
        $messageType->append_child($this->xmldoc->create_text_node($type));
        $messageValue = $this->xmldoc->create_element("value");//for student and isntructor or just for instructor
        $messageValue->append_child($this->xmldoc->create_text_node($sentemce));
        $message->append_child($messageType);
        $message->append_child($messageValue);
        return $message;
    }
    
    /*
     * Create the information element which contains different parameters
     * @param timeOfLoad : time when the component was loading
     * @param firstName : first name of the current user
     * @param lastName : llast name of the current user
     * @param email : email of the current user
     * @param role : role of the current user
     * @param courseId : course id of the current course
     * @param signature : signature to avoid bad connexion
     * @picturesToLoad : list of pictures used on the integration to manage the preload of them
     *      
     */ 
    function CreateInformationElement($timeOfLoad, $firstName, $lastName, $email, $role, $courseId, $signature,$picturesToLoad=null)
    {
        $this->Informations = $this->xmldoc->create_element("information");
        $etimeOfLoad = $this->xmldoc->create_element("timeOfLoad");
        $etimeOfLoad->append_child($this->xmldoc->create_text_node($timeOfLoad));
        $efirstName = $this->xmldoc->create_element("firstName");
        $efirstName->append_child($this->xmldoc->create_text_node(wimbaEncode($firstName)));
        $elastName = $this->xmldoc->create_element("lastName");
        $elastName->append_child($this->xmldoc->create_text_node(wimbaEncode($lastName)));
        $eemail = $this->xmldoc->create_element("email");
        $eemail->append_child($this->xmldoc->create_text_node(wimbaEncode($email)));
        $erole = $this->xmldoc->create_element("role");
        $erole->append_child($this->xmldoc->create_text_node(wimbaEncode($role)));
        $ecourseId = $this->xmldoc->create_element("courseId");
        $ecourseId->append_child($this->xmldoc->create_text_node(wimbaEncode($courseId)));
        $esignature = $this->xmldoc->create_element("signature");
        $esignature->append_child($this->xmldoc->create_text_node(wimbaEncode($signature)));
        if($picturesToLoad!=null)
        {
            $epicturesToLoad = $this->xmldoc->create_element("picturesToLoad");
            $epicturesToLoad->append_child($this->xmldoc->create_text_node($picturesToLoad));
            $this->Informations->append_child($epicturesToLoad);
        }
        
        $this->Informations->append_child($etimeOfLoad);
        $this->Informations->append_child($efirstName);
        $this->Informations->append_child($elastName);
        $this->Informations->append_child($erole);
        $this->Informations->append_child($ecourseId);
        $this->Informations->append_child($esignature);
        $this->Informations->append_child($eemail);
 
    }
    
    /*
     * Create a windows element, This element 
     * @param type : type of the part
     * @param elementPart : xml of the part    
     */ 
    function addWindowsElement($type, $xmlElementPart)
    {
        $windowsElement = $this->xmldoc->create_element("windowsElement");
        $windowsElementType = $this->xmldoc->create_element("type");
        $windowsElementType->append_child($this->xmldoc->create_text_node($type));
        $windowsElement->append_child($windowsElementType);
        $windowsElement->append_child($xmlElementPart);
        return $windowsElement;
    }
    
    /*
     * Add a new tool in the choice panel
     * param pictureUrl : path of the tool picture
     * param name : name of tools
     * param description : description of the choice
     * param action :  javascript action behind the button 
     */ 
    function addProductChoice($pictureUrl,$name,$description,$action){
    
        if (!isset($this->part["productChoice"]))
        {
            $this->part["productChoice"]=$this->xmldoc->create_element("products");
        }
        
        $product = $this->xmldoc->create_element("product");
        $productPictureUrl =  $this->xmldoc->create_element('pictureUrl');
        $productPictureUrl->append_child( $this->xmldoc->create_text_node($pictureUrl));
        $product->append_child($productPictureUrl);
        $productValue =  $this->xmldoc->create_element('value');
        $productValue->append_child($this->xmldoc->create_text_node($name));
        $product->append_child($productValue);
        $productDescription = $this->xmldoc->create_element('description');
        $productDescription->append_child($this->xmldoc->create_text_node($description));
        $product->append_child($productDescription);
        $productAction = $this->xmldoc->create_element("action");
        $productAction->append_child($this->xmldoc->create_text_node($action));  
        $product->append_child($productAction);
        $this->part["productChoice"]->append_child($product);
    }
       
    /*
     * Add a new element to the elements stack. An element can be all the basic html element(input, label....)
     * @param type: type of the html element
     * @parm display : string that have to be displayed
     * @param parameters : array of attributes ( Key = name of the attribute, Value = value of the attribute) 
     */
    function addSimpleLineElement($type,$display="",$parameters=NULL)
    {
        $element = $this->xmldoc->create_element("lineElement");
        $elementType = $this->xmldoc->create_element("type");
        $elementType->append_child($this->xmldoc->create_text_node($type));
        $element->append_child($elementType);
        if($display!="")
            {
            $elementDisplay = $this->xmldoc->create_element("display");
            $elementDisplay->append_child($this->xmldoc->create_text_node($display));
            $element->append_child( $elementDisplay);
        }       
        if($parameters !=NULL)
        { 
            $elementParameters = $this->xmldoc->create_element("parameters");
            foreach ($parameters as $key => $value)
            {
                $parameter = $this->xmldoc->create_element("parameter");
                $parameterName = $this->xmldoc->create_element("name");
                $parameterName->append_child($this->xmldoc->create_text_node($key));
                $parameterValue = $this->xmldoc->create_element("value");
                if (isset($value))
                {
                    $parameterValue->append_child($this->xmldoc->create_text_node($value)); 
                }
                $parameter->append_child($parameterName);
                $parameter->append_child($parameterValue);
                $elementParameters->append_child($parameter);
            }
            $element->append_child($elementParameters);
        }
        $this->lineElement[]=$element;
    }

    /*
     * Add a custom element which is a concatenation of two element like * + something
     * @parm first_part : first string
     * @parm display : string that have to be displayed
     * @param parameters : array of attributes ( Key = name of the attribute, Value = value of the attribute) 
     */
    function addCustomLineElement($firstPart,$firstStyle,$secondPart,$parameters=NULL)
    {
        $element = $this->xmldoc->create_element("lineElement");
        $elementType = $this->xmldoc->create_element("type");
        $elementType->append_child($this->xmldoc->create_text_node("custom"));
        $element->append_child($elementType);

        $elementFirstPart = $this->xmldoc->create_element("firstPart");
        $elementFirstPart->append_child($this->xmldoc->create_text_node($firstPart));
        $element->append_child( $elementFirstPart);

        $elementSecondPart = $this->xmldoc->create_element("secondPart");
        $elementSecondPart->append_child($this->xmldoc->create_text_node($secondPart));
        $element->append_child( $elementSecondPart);
        
        $elementfirstStyle = $this->xmldoc->create_element("firstStyle");
        $elementfirstStyle->append_child($this->xmldoc->create_text_node($firstStyle));
        $element->append_child( $elementfirstStyle);
               
        if($parameters !=NULL)
        { 
            $elementParameters = $this->xmldoc->create_element("parameters");
            foreach ($parameters as $key => $value)
            {
                $parameter = $this->xmldoc->create_element("parameter");
                $parameterName = $this->xmldoc->create_element("name");
                $parameterName->append_child($this->xmldoc->create_text_node($key));
                $parameterValue = $this->xmldoc->create_element("value");
                if (isset($value))
                {
                    $parameterValue->append_child($this->xmldoc->create_text_node($value)); 
                }
                $parameter->append_child($parameterName);
                $parameter->append_child($parameterValue);
                $elementParameters->append_child($parameter);
            }
            $element->append_child($elementParameters);
        }
        $this->lineElement[]=$element;
    }
        
    /*
     * Add a new input element 
     * @param parameters : array of attributes ( Key = name of the attribute, Value = value of the attribute) 
     */
    function addInputElement($parameters=NULL)
    {
        $this->addSimpleLineElement("input","",$parameters);
    }
    
    /*
     * Add a new textarea element 
     * @param parameters : array of attributes ( Key = name of the attribute, Value = value of the attribute) 
     */
    function addTextAreaElement($parameters=NULL,$display)
    {
        $this->addSimpleLineElement("textarea",$display,$parameters);
    }
    
    function addDivLineElement($type, $displayContext,$parameters)
    {
        $element = $this->xmldoc->create_element("lineElement");
        $elementType = $this->xmldoc->create_element("type");
        $elementType->append_child($this->xmldoc->create_text_node("div"));
        $elementDisplayContext = $this->xmldoc->create_element("displayContext");
        $elementDisplayContext->append_child($this->xmldoc->create_text_node($displayContext));
        $elementParameters = $this->xmldoc->create_element("parameters");
        for ($i = 0; $i < count($parameters);$i++)
        {
            $parameter = $this->xmldoc->create_element("parameter");
            $parameterName = $this->xmldoc->create_element("name");
            $parameterName->append_child($this->xmldoc->create_text_node($parameters[$i]));
            $parameterValue = $this->xmldoc->create_element("value");
            if ($parameters.GetByIndex(i) != NULL)
            {
                $parameterValue->append_child($this->xmldoc->create_text_node($parameters[$i]));
            }
            $parameter->append_child($parameterName);
            $parameter->append_child($parameterValue);
            $elementParameters->append_child($parameter);
        }
        $element->append_child($elementType);
        $element->append_child($elementDisplayContext);
        $element->append_child($elementParameters);
        $this->lineElement[]=$element;
    }
    
    /*
     * Add a option element 
     * @param name : name of the elemtn
     * @param id : id of the element
     * @param listOptions : array of options ( each option contains an array of attributes)
     * @param disabled : manage the disable attribute of the html element
     */
    function createOptionElement($name,$id, $listOptions,$disabled="",$style="")
    {
        $element = $this->xmldoc->create_element("lineElement");
        $elementType = $this->xmldoc->create_element("type");
        $elementType->append_child($this->xmldoc->create_text_node("select"));
        $elementDisplayContext = $this->xmldoc->create_element("displayContext");
        $elementDisplayContext->append_child($this->xmldoc->create_text_node("all"));
        $elementStyle = $this->xmldoc->create_element("style");
        $elementStyle->append_child($this->xmldoc->create_text_node($style));
        $elementName = $this->xmldoc->create_element("name");
        $elementName->append_child($this->xmldoc->create_text_node($name));

        if($disabled!="")
        {
            $elementDisabled = $this->xmldoc->create_element("disabled");
            $elementDisabled->append_child($this->xmldoc->create_text_node($disabled));
            $element->append_child($elementDisabled);   
        }
        
        $elementId = $this->xmldoc->create_element("id");
        $elementId->append_child($this->xmldoc->create_text_node($id));
        $options=$this->xmldoc->create_element("options");
        
        for ($j = 0; $j < count($listOptions); $j++)
        {
            $parameters=$listOptions[$j];
            $option=$this->xmldoc->create_element("option");
            foreach ($parameters as $key=>$value)
            {
                $optionParameter = $this->xmldoc->create_element($key);
                $optionParameter->append_child($this->xmldoc->create_text_node($value));
                $option->append_child($optionParameter);
            }
            $options->append_child($option);
        }
        
        $element->append_child($elementType);
        $element->append_child($elementDisplayContext);
        $element->append_child($elementName);
        $element->append_child($elementId);
        $element->append_child($elementStyle);
        $element->append_child($options);
        $this->lineElement[]=$element;
    }
    
    /*
     * Create a linePart element which correspond to a <td> element.
     * This function add the elements of the stack LineElement to a new LinePartElement 
     * and add it to the LinePart stack
     * @param $parameters : array of attributes ( Key = name of the attribute, Value = value of the attribute) 
     */
    function createLinePart($parameters=NULL)
    {
        $panelLinePart = $this->xmldoc->create_element("panelLinePart");
        if(isset($parameters["style"]))
        {
            $elementStyle = $this->xmldoc->create_element("style");
            $elementStyle->append_child($this->xmldoc->create_text_node($parameters["style"]));
            $panelLinePart->append_child($elementStyle);
        }
        if(isset($parameters["align"]))
        {
            $elementAlign = $this->xmldoc->create_element("align");
            $elementAlign->append_child($this->xmldoc->create_text_node($parameters["align"]));
            $panelLinePart->append_child($elementAlign);
        }
        if(isset($parameters["colspan"]))
        {
            $elementColpsan = $this->xmldoc->create_element("colspan");
            $elementColpsan->append_child($this->xmldoc->create_text_node($parameters["colspan"]));
            $panelLinePart->append_child($elementColpsan);
        }
        if(isset($parameters["id"]))
        {
            $elementId = $this->xmldoc->create_element("id");
            $elementId->append_child($this->xmldoc->create_text_node($parameters["id"]));
            $panelLinePart->append_child($elementId);
        }
        if(isset($parameters["context"]))
        {
            $elementContext = $this->xmldoc->create_element("context");
            $elementContext->append_child($this->xmldoc->create_text_node($parameters["context"]));
            $panelLinePart->append_child($elementContext);
        }
        for ($j = 0; $j < count($this->lineElement); $j++)
        {
            $panelLinePart->append_child($this->lineElement[$j]);
        }
        $this->linePart[]=$panelLinePart;
        $this->lineElement=array();//clear the tab lineElement
    }
    
    /*
     * Create a line element which correspond to a <tr> element.
     * This function add the elements of the stack LinePart to a new panelLine 
     * and add it to the panelLines stack
     * @param style : style of the <tr>
     * @param id: id of the <tr>
     */
    function createLine( $style="", $context="",  $id="")
    {
        $line = $this->xmldoc->create_element("panelLine");
        $lineStyle = $this->xmldoc->create_element("style");
        $lineStyle->append_child($this->xmldoc->create_text_node($style));
        $line->append_child($lineStyle);
        //necessary for hidden div
        $lineId = $this->xmldoc->create_element("id");
        $lineId->append_child($this->xmldoc->create_text_node($id));
        $line->append_child($lineId);
        
        $lineContext = $this->xmldoc->create_element("context");
        $lineContext->append_child($this->xmldoc->create_text_node($context));
        $line->append_child($lineContext);
        
        for ($i = 0; $i < count($this->lineElement);$i++)
        {
            $line->append_child($this->lineElement[$i]);
        }
        $this->panelLines[]=$line;
        $this->lineElement=array();
    }
    
    
    /*
     * Add a button to the validation bar(at the bottom of the settings)
     * and add it to the panelLines stack
     * @param value : text under the button
     * @param style : style apply to the button
     * param  action : javascript function called by clicking on the button 
     * @param id: id of the button
     */    
    function createValidationButtonElement($value,$type,$action,$id,$style="")
    {
        if (!isset($this->part["validationBar"]))
        {
            $this->part["validationBar"]=$this->xmldoc->create_element("validationElements");
        }
        $element=$this->xmldoc->create_element("validationButton");
        $elementaction = $this->xmldoc->create_element("action");
        $elementaction->append_child($this->xmldoc->create_text_node($action));
        $elementValue = $this->xmldoc->create_element("value");
        $elementValue->append_child($this->xmldoc->create_text_node($value));
        $elementType = $this->xmldoc->create_element("type");
        $elementType->append_child($this->xmldoc->create_text_node($type));
        $elementStyle = $this->xmldoc->create_element("style");
        $elementStyle->append_child($this->xmldoc->create_text_node($style));
        $elementId = $this->xmldoc->create_element("id");
        $elementId->append_child($this->xmldoc->create_text_node($id));
        $element->append_child($elementaction);   
        $element->append_child($elementType);   
        $element->append_child($elementValue);   
        $element->append_child($elementId);   
        $element->append_child($elementStyle);   
        $this->part["validationBar"]->append_child($element);
    }
    
    /*
     * Add a comment(string) to the validation bar(at the bottom of the settings)
     * and add it to the panelLines stack
     * @param $parameters : array of attributes ( Key = name of the attribute, Value = value of the attribute) 
     */    
    function createValidationCommentElement($parameters)
    {
        if (!isset($this->part["validationBar"]))
        {
            $this->part["validationBar"]=$this->xmldoc->create_element("validationElements");
        }
        $element = $this->xmldoc->create_element("validationElement");
        $name = $this->xmldoc->create_element("type");
        $name->append_child($this->xmldoc->create_text_node("validationComment"));
        foreach ($parameters  as $key => $value)
        {
            $parameterName = $this->xmldoc->create_element($key);
            $parameterName->append_child($this->xmldoc->create_text_node($value));
            $element->append_child($parameterName);  
        }
        $element->append_child($name);
        $this->part["validationBar"]->append_child($element);
    }
   
     /*
     * Create a panel settings element which represent a part of the settings
     * This element is composed by two part
     *  -The first one manage the top of the tab ( use to navigate between the tab)
     *  -The second manage the content of the tab (form). This part is created with the elements
     * contained in the panelLines stack
     * @param name : name of the navigation tab
     * @param display : string which is displayed in the navigation tab
     * @param id : id of the navigation tab
     * @param style : style applied to the tab
     * @param contextDisplay : manage the default avaibility of the tab
     * @param additionalFunction : function called when you go out the advanced tab
     */   
    function createPanelSettings($name, $display, $id,$style, $contextDisplay,$additionalFunction="")
    {
        if (!isset($this->part["tabs"]))
        {
            $this->part["tabs"]=$this->xmldoc->create_element("tabsInformations");
        }
        if (!isset($this->part["tabsContent"]))
        {
            $this->part["tabsContent"]=$this->xmldoc->create_element("tabsContent");
        }
        $panelSettings = $this->xmldoc->create_element("tabInformation");
        $divName = $this->xmldoc->create_element("name");
        $divName->append_child($this->xmldoc->create_text_node($name));
        $divDisplay = $this->xmldoc->create_element("display");
        $divDisplay->append_child($this->xmldoc->create_text_node($display));
        $divId = $this->xmldoc->create_element("id");
        $divId->append_child($this->xmldoc->create_text_node($id));
        $divStyle = $this->xmldoc->create_element("style");
        $divStyle->append_child($this->xmldoc->create_text_node($style));
        $divContextDisplay = $this->xmldoc->create_element("contextDisplay");
        $divContextDisplay->append_child($this->xmldoc->create_text_node($contextDisplay));
        if($additionalFunction!="")
        {
            $divContextAdditionalFunction = $this->xmldoc->create_element("additionalFunction");
            $divContextAdditionalFunction->append_child($this->xmldoc->create_text_node($additionalFunction));
            $panelSettings->append_child($divContextAdditionalFunction);
        }
        $panelSettings->append_child($divName);
        $panelSettings->append_child($divDisplay);
        $panelSettings->append_child($divStyle);
        $panelSettings->append_child($divId);
        $panelSettings->append_child($divContextDisplay);
        $panelContent = $this->xmldoc->create_element("tabContent");
        $panelContent->append_child($divDisplay->clone_node(true));
        $panelContent->append_child($divId->clone_node(true));
            
        for ($i = 0; $i < count($this->panelLines);$i++)
        {
            $panelContent->append_child($this->panelLines[$i]);
        }  
        $this->panelLines=array();
        $this->part["tabs"]->append_child($panelSettings);
        $this->part["tabsContent"]->append_child($panelContent);
    }
    
    /*
     * Set the error. 
     * if this element is set, it will be the only one displayed on the component
     * @param $errorString : sentence of the erro
     */  
    function setError($errorString)
    {
        if( !isset($this->error) ) //display jsut the first error
        {
            $this->error = $this->xmldoc->create_element("message");
            $messageType = $this->xmldoc->create_element("type");//for student and isntructor or just for instructor
            $messageType->append_child($this->xmldoc->create_text_node("error"));
            $messageValue = $this->xmldoc->create_element("value");//for student and isntructor or just for instructor
            $messageValue->append_child($this->xmldoc->create_text_node($errorString));
            $this->error->append_child($messageType);
            $this->error->append_child($messageValue);
        }
    }
    
    /*
     * Add a message Element
     * @param messageString : sentence of the message
     */   
    function addMessage($messageString)
    {
        if (!isset($this->part["message"]))
        {
            $this->part["message"]=$this->xmldoc->create_element("message");
        }
        
        $message = $this->xmldoc->create_element("message");//for student and isntructor or just for instructor
        $messageValue = $this->xmldoc->create_element("value");//for student and isntructor or just for instructor
        $messageValue->append_child($this->xmldoc->create_text_node($messageString));
        $this->part["message"]->append_child($messageValue);
    }
    
    /*
     * Create the Dial popup which represent the popup displayed for the dial information
     * @param titleLabel : title of the popup
     * @param phoneLabel : label
     * @param pinLabel : label
     * @param pinI : pin code of the instructor
     * @param pinS : pin code of the student
     * @param toll : array of phone number
     */       
    function createPopupDialElement($titleLabel,$phoneLabel,$pinLabel,$pinI, $pinS, $toll)
    {
        if (!isset($this->part["popupDial"]))
        {
            $this->part["popupDial"]=$this->xmldoc->create_element("popupDial"); 
        }
        $pinNumbers = $this->xmldoc->create_element("pin");
        $title = $this->xmldoc->create_element("popupTitle");
        $title->append_child($this->xmldoc->create_text_node($titleLabel));
        $epinL = $this->xmldoc->create_element("pinLabel");
        $epinL->append_child($this->xmldoc->create_text_node($pinLabel));
        $epinI = $this->xmldoc->create_element("instructor");
        $epinI->append_child($this->xmldoc->create_text_node($pinI));
        $epinS = $this->xmldoc->create_element("student");
        $epinS->append_child($this->xmldoc->create_text_node($pinS));
        $pinNumbers->append_child($epinL);
        $pinNumbers->append_child($epinI);
        $pinNumbers->append_child($epinS);
        $ephones = $this->xmldoc->create_element("phones");
        $ephoneL = $this->xmldoc->create_element("phoneLabel");
        $ephoneL->append_child($this->xmldoc->create_text_node($phoneLabel));
        $ephones->append_child($ephoneL);
        foreach ($toll as $key => $value) 
        {
            if($key!="" && $value!="")
            {
                $ephone = $this->xmldoc->create_element("phone");
                $ephoneDesc = $this->xmldoc->create_element("phoneDesc");
                $ephoneDesc->append_child($this->xmldoc->create_text_node($key));
                $ephone->append_child($ephoneDesc);
                $ephoneNumber = $this->xmldoc->create_element("number");
                $ephoneNumber->append_child($this->xmldoc->create_text_node($value));;
                $ephone->append_child($ephoneNumber);
                $ephones->append_child($ephone);
            }
        }
        $this->part["popupDial"]->append_child($title);
        $this->part["popupDial"]->append_child($pinNumbers);
        $this->part["popupDial"]->append_child($ephones);
    
    }
    
    
    
     /*
     * Create the advanced popup which represent the popup displayed when you click on advanced settings
     * @param $popupTitle : title of the popup
     * @param $popupSentence : sentence displayed in the popup
     */
    function  createAdvancedPopup($popupTitle, $popupSentence)
    {
        if (!isset($this->part["advancedPopup"]))
        {
            $this->part["advancedPopup"]=$this->xmldoc->create_element("advancedPopup");   
        }
        $title = $this->xmldoc->create_element("popupTitle");
        $title->append_child($this->xmldoc->create_text_node($popupTitle));
        $sentence = $this->xmldoc->create_element("popupSentence");
        $sentence->append_child($this->xmldoc->create_text_node($popupSentence));
        $this->part["advancedPopup"]->append_child($title);
        $this->part["advancedPopup"]->append_child($sentence);
    }
}
?>