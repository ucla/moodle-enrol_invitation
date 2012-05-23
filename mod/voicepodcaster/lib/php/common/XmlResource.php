<?php
/*
 * Created on May 30, 2007
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
 
 class XmlResource{
 	
 	var $id;
 	var $nameDisplay;
 	var $preview;
 	var $url;
 	var $url_params;
 	var $tooltipAvailability;
	var $type;
	var $grade;
	
    /*
     * Constructor
     * @param id : id of the resource
     * @param nameDisplay : name of the resource
     * @param preview : avaibility of the resource
     * @param url : path of the file which manage the launch of the resource
     * @param url_params : parameters needed to be able to call the specific file
     */
	function XmlResource( $id, $nameDisplay, $preview, $url, $url_params,$grade){
        $this->id = $id;
        $this->nameDisplay = $nameDisplay;
        if ($preview == 1)
        {
            $this->preview = "available";
        }
        else
        {
            $this->preview = "unavailable";
        }
        $this->url = $url;
        $this->url_params = $url_params;
        $this->grade = $grade;
    
    }
    
    /*
     * Return the xml element of the object 
     */
    function getXml($xml){
    
    	$element = $xml->create_element('Element');
	    
    	$product = $xml->create_element("product");
	    $product->append_child($xml->create_text_node("voicetools"));
	 
	    $type = $xml->create_element("type");
	    $type->append_child($xml->create_text_node($this->type));	
	    
	    $id = $xml->create_element("id");
	    $id->append_child($xml->create_text_node($this->id));	
    	
	    $nameDisplay = $xml->create_element("nameDisplay");
	    $nameDisplay->append_child($xml->create_text_node($this->nameDisplay));	

    	$tooltipAvailability = $xml->create_element("tooltipAvailability");
	    $tooltipAvailability->append_child($xml->create_text_node($this->tooltipAvailability));	
	
	   	$preview = $xml->create_element("preview");
	    $preview->append_child($xml->create_text_node($this->preview));	
	    
	    $url = $xml->create_element("url");
	    $url->append_child($xml->create_text_node($this->url));
	    
	    $grade = $xml->create_element("grade");
	    $grade->append_child($xml->create_text_node($this->grade));
	    
	    $param = $xml->create_element("param");
	    $param->append_child($xml->create_text_node($this->url_params));
	  
	    $element->append_child($id);
	    $element->append_child($product);
        $element->append_child($nameDisplay);
        $element->append_child($preview);
        $element->append_child($tooltipAvailability);
        $element->append_child($url);
        $element->append_child($grade);
        $element->append_child($param);
        $element->append_child($type);
        
        return $element;
    }
    
  
    
    function getRId() {
        return $this->id;
    }
    
    function getAvailability() {
        return $this->preview;
    }
    function getName() {
        return $this->nameDisplay;
    }
    
    function setTooltipAvailability($tooltipAvailability) {
        $this->tooltipAvailability = $tooltipAvailability;
    }
  
    
    function setType($type) {
        $this->type=$type;
    }
 	
 	
 }
 
 
?>
