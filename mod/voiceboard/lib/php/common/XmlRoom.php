<?php
/*
 * Created on May 30, 2007
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */   
 
 class XmlRoom{
 	
 	var $id;
 	var $nameDisplay;
 	var $preview;
 	var $param;
 	var $launchUrl;
 	var $url_params;
 	var $tooltipAvailability;
	var $tooltipDial;
	var $closedArchive;
	var $archives = array();
	
	/*
	 * Constructor
	 * @param id : id of the room
	 * @param nameDisplay : name of the room
	 * @param closedArchive : this room
	 * @param preview : avaibility of the room
	 * @param archives : list of archives linked to the room
	 * @param url : path of the file which manage the launch of the room
	 * @param url_params : parameters needed to be able to call the specific file
	 */
	function XmlRoom( $id, $nameDisplay, $closedArchive, $preview, $archives, $launchUrl, $url_params)
    {
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
        $this->launchUrl = $launchUrl;
        $this->url_params = $url_params;
        $this->archives=$archives;
        $this->closedArchive=$closedArchive;
    }
    
    /*
     * Return the xml element of the object 
     */
    function getXml($xml){
    	$element = $xml->create_element('Element');
    	
	    $product = $xml->create_element("product");
	    $product->append_child($xml->create_text_node("liveclassroom"));
	    

	    $type = $xml->create_element("type");
	    $type->append_child($xml->create_text_node("liveclassroom"));	
	    
	    $id = $xml->create_element("id");
	    $id->append_child($xml->create_text_node($this->id));	
	    
    	$nameDisplay = $xml->create_element("nameDisplay");
	    $nameDisplay->append_child($xml->create_text_node($this->nameDisplay));	
	    
	    $closedArchive = $xml->create_element("closedArchive");
	    $closedArchive->append_child($xml->create_text_node($this->closedArchive));	
	    
    	$tooltipAvailability = $xml->create_element("tooltipAvailability");
	    $tooltipAvailability->append_child($xml->create_text_node($this->tooltipAvailability));	
	    
	   	$tooltipDial = $xml->create_element("tooltipDial");
	    $tooltipDial->append_child($xml->create_text_node($this->tooltipDial));	
	    
	   	$preview = $xml->create_element("preview");
	    $preview->append_child($xml->create_text_node($this->preview));	
	    
	    $launchUrl = $xml->create_element("url");
	    $launchUrl->append_child($xml->create_text_node($this->launchUrl));
	    
	    $param = $xml->create_element("param");
	    $param->append_child($xml->create_text_node($this->param));
	  
        $element->append_child($product);
	    
	    if(count($this->archives)>0)
	    {
	    	$archives = $xml->create_element('archives');
	    	for($i=0;$i<count($this->archives);$i++)
	    	{
                $archives->append_child($this->archives[$i]->getXml($xml));	    		
	    	}
	    	$element->append_child($archives);
	    }
	         
	    $element->append_child($id);
        $element->append_child($nameDisplay);
        $element->append_child($preview);
        $element->append_child($tooltipAvailability);
        $element->append_child($tooltipDial);
        $element->append_child($launchUrl);
        $element->append_child($param);
        $element->append_child($type);
        
        return $element;
    }
    
    function AddOneArchive($archive){
       $this->archives[]=$archive;
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

    function setArchive($archives) {
        $this->archives=$archives;
    }
 	 	
 }
 
 
?>
