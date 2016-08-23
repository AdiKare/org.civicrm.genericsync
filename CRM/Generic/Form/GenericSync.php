
<?php

require_once "vendor/autoload.php"; 
require_once 'packages/Ctct/autoload.php';
use Ctct\ConstantContact;
use Ctct\Components\Contacts\Contact;
use Ctct\Components\Contacts\ContactList;
use Ctct\Components\Contacts\EmailAddress;
use Ctct\Exceptions\CtctException;

class CRM_Generic_Form_GenericSync extends CRM_Core_Form {
	const END_PARAMS = 'state=done';

	public function preProcess(){
		$state = CRM_Utils_Request::retrieve('state', 'String', CRM_Core_DAO::$_nullObject, FALSE, 'tmp', 'GET');
		if ($state == 'done') CRM_Core_Session::setStatus("Sync Completed","Successful :");
	}

	public function buildQuickForm() {
	    // Create the Submit Button.
	    $buttons = array(
	      array(
	        'type' => 'submit',
	        'name' => ts('Sync with CiviCRM'),
	      ),
	    );
	    // Add the Buttons.
	    $this->addButtons($buttons);

	    $serviceoptions = array('Mailchimp'=>ts('Mailchimp '),'ConstantContact'=>ts('Constant Contact '),
	    	'GoogleApps'=>ts('Google Apps')); 
	    $this->addRadio('service',ts('Service'),$serviceoptions,NULL) ; 
	}

	public function postProcess(){
		$params = $this->controller->exportValues($this->_name);
		if($params['service']==NULL){ 
			CRM_Core_Session::setStatus("You need to select a Serivce","Service:");
		}
		else{
			$object = new ContactSync($params['service']) ;
			$object->sync(); 
		}
	}


}

class ContactSync{


	private $syncobject; 

	public function __construct($service){ 
		$class = $service."Service" ; 
		$this->syncobject = new $class() ; 
	}


	public function sync(){ 
		$this->syncobject->sync(); 

	}
	
}

class MailchimpService{

	private $mailchimp ;
	public function sync(){ // Sync service with CiviCRM	

		require_once 'Mailchimp_Pull.php' ; 
		$mailchimp = new CRM_Mailchimp_Form_Pull(); 

		// deleted the functions which unsubscribes the contacts present in CiviCRM but not in Mailchimp 
		$mailchimp->postProcess(); 
	}

}

class ConstantContactService{ 

	public function sync(){	
		$result = civicrm_api3('Job', 'constant_contact_sync', array(
  		'sequential' => 1,
		));
		
		if($result['is_error'] > 0 ){ 
			CRM_Core_Session::setStatus("Number of Errors: ".$result['is_error'],'Unsuccessful Sync'); 
		}
		else{
			CRM_Core_Session::setStatus("Sync Completed","Successful "); 
		}
	}  

}

class GoogleAppsService{
	
	public function sync(){ 

		$result = civicrm_api3('Job', 'googleapps_sync', array(
	  	'sequential' => 1,
		));	

		if($result['is_error']>0){ 
			CRM_Core_Session::setStatus("Number of Errors: ".$result['is_error'],'Unsuccessful Sync'); 
		}
		else{
			CRM_Core_Session::setStatus("Sync Completed","Successful "); 
		}
	}

}
  