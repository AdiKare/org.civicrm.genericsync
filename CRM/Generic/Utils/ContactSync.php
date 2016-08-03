<?php

<?php

Class ContactSync {

	public function Service($Service) {
	 $this->_serviceNAme = $serivce;
	}
	public function list() {
	if mailchmip
	 	$list = new Mailchimp_Lists(CRM_Contact_Utils::mailchimp());
    	$result = $list->ListAll( $listID, $batch, $delete=FALSE, $send_bye=FALSE, $send_notify=FALSE);
	} else if Constanst contact
	  	$list = new ConstanstContact(CRM_Contact_Utils::constantcontact());
    	$result = $list->CClist( $listID, $batch, $delete=FALSE, $send_bye=FALSE, $send_notify=FALSE);
	}
	public function listAll() {
		
	}
	public function sycn() {


	}
	public function pull() {

	}
+ 
	public function delete() {}
	public functoin deleteBatch() {}
	public function unsubscribe() {}
	public function unsubscribeBatch() {}
	public function subscribe() {}
	public function subscribeBatch() {}

}

$object = new Contact_Sync();
$object->Service('Mailchimp'); // GoogleApp, ConstantContact
$object->ListAll();
