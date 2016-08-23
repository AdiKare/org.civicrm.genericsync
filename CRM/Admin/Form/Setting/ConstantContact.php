<?php

require_once 'packages/Ctct/autoload.php';
use Ctct\ConstantContact;
use Ctct\Components\Contacts\Contact;
use Ctct\Exceptions\CtctException;

class CRM_Admin_Form_Setting_ConstantContact extends CRM_Admin_Form_Setting {
  protected $_settings;

  function preProcess() {
    // Needs to be here as from is build before default values are set
    $this->_settings = CRM_Sync_BAO_ConstantContact::getSettings();
    if (!$this->_settings) $this->_settings = array();
  }

  /**
   * Function to build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    $this->applyFilter('__ALL__', 'trim');
    $this->add('text', 'constantcontact_username',  ts('Username'),  array('size' => 50, 'maxlength' => 50), TRUE );
    $this->add('text', 'constantcontact_usertoken', ts('User Token'),array('size' => 50, 'maxlength' => 50), TRUE );
    $this->add('text', 'constantcontact_apikey',    ts('API Key'),   array('size' => 50, 'maxlength' => 50), TRUE );

    $timeout = array(
      '1000000' => '01 call/s',
      '200000'  => '05 call/s',
      '100000'  => '10 call/s',
      '66666'   => '15 call/s',
      '50000'   => '20 call/s',
    );
    $this->add('select', 'constantcontact_timeout', ts('Timeout'), $timeout);
    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => ts('Save'),
        'isDefault' => TRUE,
      ),
      array(
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ),
    ));
    $this->addFormRule(array('CRM_Admin_Form_Setting_ConstantContact', 'formRule'), $this);
  }

  function setDefaultValues() {
    $defaults = $this->_settings;
    return $defaults;
  }

  public static function formRule($fields, $files, $self) {
    $errors = array();
   // Validate Account details
    $cc_usertoken = CRM_Utils_Array::value('constantcontact_usertoken', $fields, false);
    $cc_apikey    = CRM_Utils_Array::value('constantcontact_apikey',    $fields, false);
    $cc = new ConstantContact($cc_apikey);

    try {
      $result = $cc->getLists($cc_usertoken);
      CRM_Core_Session::setStatus(ts('Connection tested successfully.'), ts('Success'), 'success');
    } catch (CtctException $ex) {
      foreach ($ex->getErrors() as $error) {
        $errors['constantcontact_apikey'] = $error['error_message'];
      }
    }
    return $errors;
  }

  /**
   * Function to process the form
   *
   * @access public
   * @return None
   */
  public function postProcess(){
    $params = $this->exportValues();    
    // Save all settings

    foreach ( CRM_Sync_BAO_ConstantContact::getCCParams() as $name) {
      CRM_Sync_BAO_ConstantContact::setSetting(CRM_Utils_Array::value($name, $params, 0), $name);
    }
  } //end of function
} // end class
