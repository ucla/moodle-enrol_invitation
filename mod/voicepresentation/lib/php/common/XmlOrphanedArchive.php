<?php
/*
 * Created on May 30, 2007
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
 
class XmlOrphanedArchive{
 	var $id;
 	var $nameDisplay;
 	var $preview;
 	var $url;
 	var $param;
 	var $url_params;
 	var $tooltipAvailability;
	var $tooltipDial;
	var $canDownloadMp3;
	var $canDownloadMp4;
	
	/*
     * Constructor
     * An orphaned archive is a arhive without link to a room
     * @param archive : initial archive
     * @param contextDisplay : context of display (all users, just for student)
     */
    function XmlOrphanedArchive($archive, $contextDisplay) {
         $this->id = $archive->getId();
         $this->nameDisplay = $archive->getName();
         $this->preview = $archive->getAvailability();
         $this->contextDisplay = $contextDisplay;
         $this->tooltipAvailability = $archive->getTooltipAvailability();
         $this->tooltipDial = $archive->getTooltipDial();
         $this->canDownloadMp3 = $archive->canDownloadMp3();
         $this->canDownloadMp4 = $archive->canDownloadMp4();
    }

    /*
     * Return the xml element of the object 
     */
    function getXml($xml){
    	$element = $xml->create_element('Element');
    	
	    $product = $xml->create_element("product");
	    $product->append_child($xml->create_text_node("liveclassroom"));
	    
	    $type = $xml->create_element("type");
	    $type->append_child($xml->create_text_node("orphanedarchive".$this->contextDisplay));	
	    
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
	    
	    $param = $xml->create_element("param");
	    $param->append_child($xml->create_text_node($this->param));

	    $canDownloadMp3 = $xml->create_element("canDownloadMp3");
        $canDownloadMp3->append_child($xml->create_text_node($this->canDownloadMp3));

        $canDownloadMp4 = $xml->create_element("canDownloadMp4");
        $canDownloadMp4->append_child($xml->create_text_node($this->canDownloadMp4));
        
	    
	    $element->append_child($product);
	    $element->append_child($id);
        $element->append_child($nameDisplay);
        $element->append_child($preview);
        $element->append_child($tooltipAvailability);
        $element->append_child($tooltipDial);
        $element->append_child($url);   
        $element->append_child($canDownloadMp3);
        $element->append_child($canDownloadMp4);    
        $element->append_child($type);
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
    
    function setTooltipDial($tooltipDial) {
        $this->tooltipDial = $tooltipDial;
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
	
}
?>
