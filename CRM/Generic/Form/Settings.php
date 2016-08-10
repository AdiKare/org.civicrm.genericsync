<?php

require_once 'CRM/Core/Form.php';

/**
 * Form controller class
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_Generic_Form_Settings extends CRM_Core_Form {


	public function preProcess(){
		$currentVer = CRM_Core_BAO_Domain::version(TRUE);
	    //if current version is less than 4.4 dont save setting
	    if (version_compare($currentVer, '4.4') < 0) {
	      CRM_Core_Session::setStatus("You need to upgrade to version 4.4 or above to work with extension","Version:");
	    }
	}

	public function buildQuickForm() {

		$service = array('Mailchimp'=>ts('Mailchimp '),'ConstantContact'=>ts('Constant Contact '),
	    	'GoogleApps'=>ts('Google Apps')); 

		$this->addRadio('service',ts('Service'),$service,NULL) ;

	    $buttons = array(
	      array(
	        'type' => 'submit',
	        'name' => ts('Submit Serivce Option'),
	      ),
	    );
	    // Add the Buttons.
	    $this->addButtons($buttons);

	}

	public function postProcess(){
		
		$params = $this->controller->exportValues($this->_name);

		$class = $params['service']."Settings";
		$this->object = new $class();	
		$this->object->execute();
	}
}


class MailchimpSettings{

	public function execute(){ 
		require_once "Mailchimp_Setting.php" ;
		//$url = CRM_Utils_System::url('civicrm/generic/genericsettings/Service', 'reset=1', TRUE) ; 
		//header( "Location: $url" );

		$url = CRM_Utils_System::url('civicrm/generic/genericsettings/mailchimpSettings', 'reset=1', TRUE) ;
	    CRM_Utils_System::redirect($url);
	}
}

class ConstantContactSettings{

	public function execute(){
		$url = CRM_Utils_System::url('civicrm/generic/genericsettings/ccSettings', 'reset=1', TRUE) ;
	    CRM_Utils_System::redirect($url); 
 	}
	
	// public function execute(){
}