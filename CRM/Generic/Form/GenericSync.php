
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
	    
	    // $syncdirection = array(1=>ts('Import '),2=>ts('Export '),3=>ts('Dual Sync'));
	    // $this->addRadio('direction',ts('Sync Direction'),$syncdirection,NULL) ;
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

		// if($params['direction']==NULL){
		// 	CRM_Core_Session::setStatus("You need to select the Sync Direction","Sync Direction:");
		// }
		
		// else{
		// 	$object->SyncDirection($params['direction']) ; 
		// }
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

		// if($direction==1){ 
		// 	$this->syncobject->Pull(); 
		// }
		// elseif($direction==2){ 
		// 	$this->syncobject->Push(); 
		// }
		// else{ 
		// 	$this->syncobject->DualSync(); 
		// }
	}
	
}

class MailchimpService{

	private $mailchimp ;
	public function sync(){ // Sync pull from service to CiviCRM	

		require_once 'Mailchimp_Pull.php' ; 
		$mailchimp = new CRM_Mailchimp_Form_Pull(); 

		// deleted the functions which unsubscribes the contacts present in CiviCRM but not in Mailchimp 
		$mailchimp->postProcess(); 
	}

	// public function Push(){ // Sync Push from CiviCRM to service 
	// 	require_once 'Mailchimp_Sync.php';

	// 	$mailchimp = new CRM_Mailchimp_Form_Sync(); 

	// 	//deleted the function which unsubscribes the contacts present in Mailchimp but not in CiviCRM
	// 	return $mailchimp->postProcess() ; 
	// }

	// public function DualSync(){// Sync in both directions. Doesn't unsubscribe contacts. Both account add contact that are not present in them.
		
	// 	require_once 'Mailchimp_Pull.php' ;
	// 	require_once 'Mailchimp_Sync.php' ;

	// 	$mailchimp = new CRM_Mailchimp_Form_Pull(); 

	// 	$mailchimp->postProcess(); 

	// 	$mailchimp = new CRM_Mailchimp_Form_Sync(); 
		
	// 	$mailchimp->postProcess(); 

	// }

}

class ConstantContactService{ 

	//Calls the functions for Sync pull, Sync push and Dual Sync for Constant Contact. 
	public function sync(){	
		//require_once 'ConstantContactSync.php' ;
		//civicrm_api3_job_constant_contact_sync( $sync_params ) ;
		$result = civicrm_api3('Job', 'constant_contact_sync', array(
  		'sequential' => 1,
		));
		// crm_core_error::debug("Results of the ctct sync:",$result) ;
		if($result['is_error'] > 0 ){ 
			CRM_Core_Session::setStatus("Number of Errors: ".$result['is_error'],'Unsuccessful Sync'); 
		}
		else{
			CRM_Core_Session::setStatus("Sync Completed","Successful "); 
		}
	}  

}

class GoogleAppsService{
	//Calls the functions for Sync pull, Sync push and Dual Sync for Google Apps. 
	public function Sync(){ 

		$result = civicrm_api3('Job', 'googleapps_sync', array(
	  	'sequential' => 1,
		));	

		echo $result; 
		if($result['is_error']>0){ 
			CRM_Core_Session::setStatus("Number of Errors: ".$result['is_error'],'Unsuccessful Sync'); 
		}
		else{
			CRM_Core_Session::setStatus("Sync Completed","Successful "); 
		}
	}

}
  