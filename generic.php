<?php
//require_once 'vendor/autoload.php';
require_once 'generic.civix.php';

require_once 'packages/Ctct/autoload.php';
use Ctct\ConstantContact;
use Ctct\Components\Contacts\Contact;
use Ctct\Components\Contacts\ContactList;
use Ctct\Components\Contacts\EmailAddress;
use Ctct\Exceptions\CtctException;

function generic_civicrm_buildForm($formName, &$form){
  if($formName == "CRM_Group_Form_Edit"){
    $settings = CRM_Sync_BAO_ConstantContact::getSettings();
    $cc_usertoken = CRM_Utils_Array::value('constantcontact_usertoken', $settings, false);
    $cc_apikey    = CRM_Utils_Array::value('constantcontact_apikey',    $settings, false);
    if($cc_usertoken != "" && $cc_apikey != ""){
      foreach($form->_groupTree as $group){
        if($group['title'] == "ConstantContact sync (by cividesk)" ){
          foreach($group['fields'] as $field){
            if($field['label'] == "ConstantContact List Id"){
              $cc = new ConstantContact($cc_apikey);
              try {
                $result = $cc->getLists($cc_usertoken);
                $options = array();
                foreach($result as $value){
                  $options[$value->id] = $value->name;
                }
                if (array_key_exists( $field['element_name'], $form->_elementIndex)) {
                  $form->removeElement($field['element_name']);
                }
                $form->add('select', $field['element_name'], ts('Constant Contact Sync Id'), array('' => ts('- select -')) + $options);
              } catch (CtctException $ex) {
                foreach ($ex->getErrors() as $error) {
                  CRM_Core_Session::setStatus($error['error_message'], ts('Failed.'), 'error');
                }
              }
            } 
          }
        }
      }
    }
  }
}

function generic_civicrm_postProcess($formName, &$form){
  if($formName == "CRM_Group_Form_Edit"){
    $result = civicrm_api3('Job', 'execute', array(
      'sequential' => 1,
      'api_action' => "constant_contact_sync",
    ));
  }
}


/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function generic_civicrm_config(&$config) {
  _generic_civix_civicrm_config($config);
  $extRoot = dirname( __FILE__ ) . DIRECTORY_SEPARATOR;
  set_include_path($extRoot . PATH_SEPARATOR . get_include_path());
  if (is_dir($extRoot . 'packages')) {
    set_include_path($extRoot . 'packages' . PATH_SEPARATOR . get_include_path());
  }
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @param array $files
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function generic_civicrm_xmlMenu(&$files) {
  _generic_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function generic_civicrm_install() {
  _generic_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function generic_civicrm_uninstall() {
  _generic_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function generic_civicrm_enable() {
  _generic_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function generic_civicrm_disable() {
  _generic_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed
 *   Based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function generic_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _generic_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function generic_civicrm_managed(&$entities) {
  _generic_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * @param array $caseTypes
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function generic_civicrm_caseTypes(&$caseTypes) {
  _generic_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function generic_civicrm_angularModules(&$angularModules) {
_generic_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function generic_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _generic_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Functions below this ship commented out. Uncomment as required.
 *

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_preProcess
 *
function generic_civicrm_preProcess($formName, &$form) {

} // */

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_navigationMenu
 *
 */

function generic_civicrm_navigationMenu(&$params) {
  $parentId         = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Navigation', 'Mailings', 'id', 'name');
  $genericsync      = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Navigation', 'Generic_Sync', 'id', 'name');
  $genericsettings  = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Navigation', 'Generic_Settings', 'id', 'name');
  $maximumId            = max(array_keys($params));
  $genericMaxId     =   empty($genericsync)     ? $maximumId+1          : $genericsync;
  $genericsettingsId =  empty($genericsettings) ? $genericMaxId+1       : $genericsettings;

  $params[$parentId]['child'][$genericMaxId] = array(
        'attributes' => array(
          'label'     => ts('Generic Sync'),
          'name'      => 'GenericSync',
          'url'       => CRM_Utils_System::url('civicrm/generic/genericsync', 'reset=1', TRUE),
          'active'    => 1,
          'parentID'  => $parentId,
          'operator'  => NULL,
          'navID'     => $genericMaxId,
          'permission'=> 'administer CiviCRM',
        ),
  );


  $params[$parentId]['child'][$genericsettingsId] = array(
        'attributes' => array(
          'label'     => ts('Generic Settings'),
          'name'      => 'Generic_Settings',
          'url'       => CRM_Utils_System::url('civicrm/generic/genericsettings', 'reset=1', TRUE),
          'active'    => 1,
          'parentID'  => $parentId,
          'operator'  => NULL,
          'navID'     => $genericsettingsId,
          'permission'=> 'administer CiviCRM',
        ),
  );
} 
