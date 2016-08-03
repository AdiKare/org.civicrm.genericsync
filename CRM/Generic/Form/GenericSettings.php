<? php

require_once 'CRM/Core/Form.php';
class CRM_Generic_Form_Settings extends CRM_Core_Form{
	public function preProcess(){
	
	}

	public function buildQuickForm() {

		$service = array(1=>ts('Mailchimp '),2=>ts('Constant Contact '),3=>ts('Google Apps '));
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
		
	}
}