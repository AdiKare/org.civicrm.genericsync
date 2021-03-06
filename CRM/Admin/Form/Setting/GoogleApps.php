<?php

require_once 'CRM/Sync/BAO/GoogleApps.php' ;
class CRM_Admin_Form_Setting_GoogleApps extends CRM_Admin_Form_Setting {
  protected $_values;
  protected $_oauth_ok;
  protected $_scheduledJob;

  function preProcess() {
    // Needs to be here as from is build before default values are set
    $this->_values = CRM_Sync_BAO_GoogleApps::getSettings();
    $this->_oauth_ok = $this->_checkOAuth($this->_values);
    $this->_scheduledJob = CRM_Sync_BAO_GoogleApps::get_scheduledJob();
  }

  /**
   * Function to build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    $this->applyFilter('__ALL__', 'trim');
    $element =& $this->add('text',
      'domain',
      ts('Google Apps domain'),
      $this->_values['domain'],
      true);
    if ($this->_oauth_ok) {
      $element->setAttribute('READONLY', 'true');
      $element->setAttribute('style', 'background-color:#EBECE4');
    } else {
      $element =& $this->add('text',
        'oauth_email',
        ts('OAuth email'),
        array('pre_help' => ts('This is usually the domain administrator email'),'pre_help' => ts('Or the domain administrator email')),
        true);
      $element =& $this->add('text',
        'oauth_key',
        ts('OAuth key'),
        $this->_values['oauth_key'],
        true);
      $element =& $this->add('text',
        'oauth_secret',
        ts('OAuth secret'),
        $this->_values['oauth_secret'],
        true);
    }

    $this->assign('oauth_ok', $this->_oauth_ok);

    if ($this->_scheduledJob) {
      $job = $this->_scheduledJob->toArray();
      $job['log_url'] = CRM_Utils_System::url('civicrm/admin/joblog', "jid=$job[id]&reset=1");
      $query = "SELECT COUNT(*) FROM ".CRM_Sync_BAO_GoogleApps::GOOGLEAPPS_QUEUE_TABLE_NAME;
      $job['remaining'] = CRM_Core_DAO::singleValueQuery($query);
      $job['last_sync'] = $this->_values['last_sync'];
      $job['processed'] = $this->_values['processed'];
      $this->assign('job', $job);
    }

    $this->addButtons(array(
      array(
        'type' => 'upload',
        'name' => ts('Save'),
        'isDefault' => TRUE,
      ),
      array(
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ),
    ));
  }

  function setDefaultValues() {
    $defaults = $this->_values;
    return $defaults;
  }

  /**
   * Function to validate the form
   *
   * @access public
   * @return None
   */
  public function validate() {
    $valid = parent::validate();
    if ($valid && (!$this->_oauth_ok) && (!$this->_checkOAuth($this->_submitValues))) {
      $valid = false;
      CRM_Core_Session::setStatus(ts('Cannot authenticate to this Google Apps domain. Check OAuth parameters.'));
    }
    return $valid;
  }

  /**
   * Function to process the form
   *
   * @access public
   * @return None
   */
  public function postProcess() {
    // store the submitted values in an array
    $params = $this->exportValues();

    // we will return to this form
    $session = CRM_Core_Session::singleton();
    $session->replaceUserContext(CRM_Utils_System::url('civicrm/admin/sync/googleapps', $resetStr));

    // If we got this far then all checked out in validate function
    if (!$this->_oauth_ok) {
      foreach(array('domain', 'oauth_email', 'oauth_key', 'oauth_secret') as $setting) {
        CRM_Sync_BAO_GoogleApps::setSetting($params[$setting], $setting);
      }
      // Rebuild menus (for the Google shortcuts)
      CRM_Core_Invoke::rebuildMenuAndCaches(TRUE);
      CRM_Core_Session::setStatus(ts("The 'More' menu has been added for your convenience."));
      // And perform the first run ...
      $params = array('version' => 3);
      $result = civicrm_api('job', 'googleapps_sync', $params);
    }
  } //end of function

  private function _checkOAuth($params) {
    // Check that current settings are still valid
    $gapps = new CRM_Sync_BAO_GoogleApps($params['oauth_email'], $params['oauth_key'], $params['oauth_secret']);
    try {
      $gapps->setScope($params['domain']);
      $return = $gapps->call('contact', 'get');
    } catch(Exception $e) {
      return false;
    }
    return true;
  }
} // end class
