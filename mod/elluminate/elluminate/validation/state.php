<?php

/**
 * This class is designed to represent the state of validation of a particular
 * business object.
 * 
 * There are 3 fields:
 * 
 * validationSuccess:  true - validation passed.  false - validation failed
 * 
 * if validation success, leave all other fields blank
 * 
 * if validation failure:
 *    -fill validationErrorMessageKey with the key for the error message in the moodle
 *    localization bundle (lang/<lang>/elluminate.php)
 *    -(OPTIONAL) fill validationMessageDetails with additional details used in the error
 *    message.
 *    
 *    For example, if the validation error is related to time and you want to display
 *    the incorrect time that was entered, you would create a localization error message like:
 *    
 *    "The session start time of {$a->timestart} is before the current time."
 *    
 *    Where $a is a php StdClass object with whatever fields you want to display in the 
 *    error message.
 *    
 *    This stdclass object would be set as the value of $validationMessageDetails
 * 
 * 
 * @author dwieser
 *
 */
class Elluminate_Validation_State{
	public $validationSuccess;
	public $validationErrorMessageKey;
	public $validationMessageDetails;
	
	public function __construct($success,$key='',$validationMessageDetails=''){
		$this->validationSuccess = $success;
		$this->validationErrorMessageKey = $key;
		$this->validationMessageDetails = $validationMessageDetails;
	}
}