<?php
/*
 * Created on May 30, 2007
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
 
class XmlArchive{
 	var $id;
 	var $nameDisplay;
 	var $preview;
 	var $url;
 	var $param;
 	var $url_params;
 	var $tooltipAvailability;
	var $tooltipDial;
	var $parent;
	var $canDownloadMp3;
	var $canDownloadMp4;
	 /*
     * Constructor
     * @param id : id of the archive
     * @param nameDisplay : name of the archive
     * @param preview : avaibility of the room
     * @param url : path of the file which manage the launch of the archive
     * @param url_params : parameters needed to be able to call the specific file
     */
	function XmlArchive( $id, $nameDisplay, $preview,$canDownloadMp3, $canDownloadMp4, $url, $url_params) {
        $this->id = $id;
        $this->nameDisplay = $nameDisplay;
        if ($preview == false)
        {
            $this->preview = "available";
        }
        else
        {
            $this->preview = "unavailable";
        }
        $this->url = $url;
        $this->url_params = $url_params;
        $this->canDownloadMp3=$canDownloadMp3;
        $this->canDownloadMp4=$canDownloadMp4;
    }
    
    /*
     * Return the xml element of the object 
     */
    function getXml($xml){
    
    	$element = $xml->create_element('archive');
    	
	    $product = $xml->create_element("product");
	    $product->append_child($xml->create_text_node("liveclassroom"));
	    
	    $type = $xml->create_element("type");
	    $type->append_child($xml->create_text_node("archive"));	
	    
	    $id = $xml->create_element("id");
	    $id->append_child($xml->create_text_node($this->id));	
    	
	    $nameDisplay = $xml->create_element("nameDisplay");
	    $nameDisplay->append_child($xml->create_text_node($this->nameDisplay));	
    	
	    $tooltipAvailability = $xml->create_element("tooltipAvailability");
	    $tooltipAvailability->append_child($xml->create_text_node($this->tooltipAvailability));	
	   	
	    $tooltipDial = $xml->create_element("tooltipDial");
	    $tooltipDial->append_child($xml->create_text_node($this->tooltipDial));	
	   	
	    $preview = $xml->create_element("preview");
	    $preview->append_child($xml->create_text_node($this->preview));	
	    
	    $url = $xml->create_element("url");
	    $url->append_child($xml->create_text_node($this->url));
	    
	    $parent = $xml->create_element("parent");
        $parent->append_child($xml->create_text_node($this->parent));
        
	    $canDownloadMp3 = $xml->create_element("canDownloadMp3");
        $canDownloadMp3->append_child($xml->create_text_node($this->canDownloadMp3));

        $canDownloadMp4 = $xml->create_element("canDownloadMp4");
        $canDownloadMp4->append_child($xml->create_text_node($this->canDownloadMp4));
        
        
	    $param = $xml->create_element("param");
	    $param->append_child($xml->create_text_node($this->param));
	    
	    $element->append_child($product);
	    $element->append_child($id);
        $element->append_child($nameDisplay);
        $element->append_child($preview);
        $element->append_child($tooltipAvailability);
        $element->append_child($tooltipDial);
        $element->append_child($url);
        $element->append_child($canDownloadMp3);
        $element->append_child($canDownloadMp4);
        $element->append_child($param);
        $element->append_child($type);
        $element->append_child($parent);
        
        return $element;
    }
    
    function getId() {
        return $this->id;
    }
    
    function getAvailability() {
        return $this->preview;
    }
    
    function getName() {
        return $this->nameDisplay;
    }
    
    function setTooltipDial($tooltipDial)
    {
        $this->tooltipDial = $tooltipDial;
    }
     function setParent($parent)
    {
        $this->parent = $parent;
    }
    
    function setTooltipAvailability($tooltipAvailability) {
        $this->tooltipAvailability = $tooltipAvailability;
    }
    
    function getTooltipDial() {
        return $this->tooltipDial;
    }
    
    function getTooltipAvailability() {
        return $this->tooltipAvailability;
    }
    
    function canDownloadMp3() {
        return $this->canDownloadMp3;
    }
    
    function canDownloadMp4() {
        return $this->canDownloadMp4;
    }
}
?>
