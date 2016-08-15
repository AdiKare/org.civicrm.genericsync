<?php
//require_once 'vendor/autoload.php';
require_once 'generic.civix.php';

require_once 'vendor/mailchimp/Mailchimp.php';
require_once 'vendor/mailchimp/Mailchimp/Lists.php';
require_once 'packages/Ctct/autoload.php';
require_once 'cividesk.ext.php';
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

    if($form->getAction() == CRM_Core_Action::ADD OR $form->getAction() == CRM_Core_Action::UPDATE){
      
      $lists = array();
      $params = array(
        'version' => 3,
        'sequential' => 1,
      );
      $lists = civicrm_api('Mailchimp', 'getlists', $params);
     
      if(!$lists['is_error']){
        // Add form elements
        
        $form->add('select', 'mailchimp_list', ts('Mailchimp List'), array('' => '- select -') + $lists['values'] , FALSE );
        $form->add('select', 'mailchimp_group', ts('Mailchimp Group'), array('' => '- select -') , FALSE );

        $options = array(
          ts('Subscribers are NOT able to update this grouping using Mailchimp'),
          ts('Subscribers are able to update this grouping using Mailchimp')
        );
        $form->addRadio('is_mc_update_grouping', '', $options, NULL, '<br/>');

        $options = array(
          ts('No integration'),
          ts('Sync membership of this group with membership of a Mailchimp List'),
          ts('Sync membership of with a Mailchimp interest grouping')
        );
        $form->addRadio('mc_integration_option', '', $options, NULL, '<br/>');

        // Prepopulate details if 'edit' action
        $groupId = $form->getVar('_id');
        if ($form->getAction() == CRM_Core_Action::UPDATE AND !empty($groupId)) {

          $mcDetails  = CRM_Mailchimp_Utils::getGroupsToSync(array($groupId));

          if (!empty($mcDetails)) {
            $defaults['mailchimp_list'] = $mcDetails[$groupId]['list_id'];
            $defaults['is_mc_update_grouping'] = $mcDetails[$groupId]['is_mc_update_grouping'];
            if ($defaults['is_mc_update_grouping'] == NULL) {
              $defaults['is_mc_update_grouping'] = 0;
            }
            if ($mcDetails[$groupId]['list_id'] && $mcDetails[$groupId]['group_id']) {
              $defaults['mc_integration_option'] = 2;
            } else if ($mcDetails[$groupId]['list_id']) {
              $defaults['mc_integration_option'] = 1;
            } else {
              $defaults['mc_integration_option'] = 0;
            }

            $form->setDefaults($defaults);  
            $form->assign('mailchimp_group_id' , $mcDetails[$groupId]['group_id']);
            $form->assign('mailchimp_list_id' ,  $mcDetails[$groupId]['list_id']);
          } else {
            // defaults for a new group
            
            $defaults['mc_integration_option'] = 0;
            $defaults['is_mc_update_grouping'] = 0;
            $form->setDefaults($defaults);  
          }
        }
      }
    }
  }
}

function generic_civicrm_validateForm( $formName, &$fields, &$files, &$form, &$errors ) {
  //CRM_Core_Session::setStatus("Inside validation","Hi ") ; 
  if ($formName != 'CRM_Group_Form_Edit') {
    return;
  }
  if ($fields['mc_integration_option'] == 1) {
    // Setting up a membership group.
    if (empty($fields['mailchimp_list'])) {
      $errors['mailchimp_list'] = ts('Please specify the mailchimp list');
    }
    else {
      // We need to make sure that this is the only membership tracking group for this list.
      $otherGroups = CRM_Mailchimp_Utils::getGroupsToSync(array(), $fields['mailchimp_list'], TRUE);
      $thisGroup = $form->getVar('_group');
      if ($thisGroup) {
        unset($otherGroups[$thisGroup->id]);
      }
      if (!empty($otherGroups)) {
        $otherGroup = reset($otherGroups);
        $errors['mailchimp_list'] = ts('There is already a CiviCRM group tracking this List, called "'
          . $otherGroup['civigroup_title'].'"');
      }
    }
  }
  elseif ($fields['mc_integration_option'] == 2) {
    // Setting up a group mapped to an interest grouping.
    if (empty($fields['mailchimp_list'])) {
      $errors['mailchimp_list'] = ts('Please specify the mailchimp list');
    }
    else {
      // First we have to ensure that there is a pre-existing membership group
      // set up for this list.
      if (! CRM_Mailchimp_Utils::getGroupsToSync(array(), $fields['mailchimp_list'], TRUE)) {
        $errors['mailchimp_list'] = ts('The list you selected does not have a membership group set up. You must set up a group to track membership of the Mailchimp list before you set up group(s) for the lists\'s interest groupings.');
      }
      else {
        // The List is OK, now let's check the interest grouping...
        if (empty($fields['mailchimp_group'])) {
          // Check a grouping group was selected.
          $errors['mailchimp_group'] = ts('Please select an interest grouping.');
        }
        else {
          // OK, we have a group, let's check we're not duplicating work.
          $otherGroups = CRM_Mailchimp_Utils::getGroupsToSync(array(), $fields['mailchimp_list']);
          $thisGroup = $form->getVar('_group');
          if ($thisGroup) {
            unset($otherGroups[$thisGroup->id]);
          }
          list($mc_grouping_id, $mc_group_id) = explode('|', $fields['mailchimp_group']);
          foreach($otherGroups as $otherGroup) {
            if ($otherGroup['group_id'] == $mc_group_id) {
              $errors['mailchimp_group'] = ts('There is already a CiviCRM group tracking this interest grouping, called "'
                . $otherGroup['civigroup_title'].'"');
            }
          }
        }
      }
    }
  }
}

function generic_civicrm_pageRun( &$page ) {
  //CRM_Core_Session::setStatus("Inside pre","A") ; 
  if ($page->getVar('_name') == 'CRM_Group_Page_Group') {
    //CRM_Core_Session::setStatus("Inside pre","X") ; 
    $params = array(
      'version' => 3,
      'sequential' => 1,
    );
    // Get all the mailchimp lists/groups and pass it to template as JS array
    // To reduce the no. of AJAX calls to get the list/group name in Group Listing Page
    $result = civicrm_api('Mailchimp', 'getlistsandgroups', $params);
    if(!$result['is_error']){
    $list_and_groups = json_encode($result['values']);
    $page->assign('lists_and_groups', $list_and_groups);
    }
  }
}


function generic_civicrm_pre( $op, $objectName, $id, &$params ) {
  //CRM_Core_Session::setStatus("Inside pre","Hi") ; 
  $params1 = array(
    'version' => 3,
    'sequential' => 1,
    'contact_id' => $id,
    'id' => $id,
  );

  if($objectName == 'Email') {
    $email = new CRM_Core_BAO_Email();
    $email->id = $id;
    $email->find(TRUE);

    // If about to delete an email in CiviCRM, we must delete it from Mailchimp
    // because we won't get chance to delete it once it's gone.
    //
    // The other case covered here is changing an email address's status
    // from for-bulk-mail to not-for-bulk-mail.
    // @todo Note: However, this will delete a subscriber and lose reporting
    // info, where what they might have wanted was to change their email
    // address.
    if( ($op == 'delete') ||
        ($op == 'edit' && $params['on_hold'] == 0 && $email->on_hold == 0 && $params['is_bulkmail'] == 0)
    ) {
      CRM_Mailchimp_Utils::deleteMCEmail(array($id));
    }
  }

  // If deleting an individual, delete their (bulk) email address from Mailchimp.
  if ($op == 'delete' && $objectName == 'Individual') {
    $result = civicrm_api('Contact', 'get', $params1);
    foreach ($result['values'] as $key => $value) {
      $emailId  = $value['email_id'];
      if ($emailId) {
        CRM_Mailchimp_Utils::deleteMCEmail(array($emailId));
      }
    }
  }
}

function generic_civicrm_post( $op, $objectName, $objectId, &$objectRef,$formName, &$form) {
 if($formName == "CRM_Group_Form_Edit"){
    $result = civicrm_api3('Job', 'execute', array(
      'sequential' => 1,
      'api_action' => "constant_contact_sync",
    ));
    crm_core_error::debug('$results from main = ',$result) ;
  }


  /***** NO BULK EMAILS (User Opt Out) *****/
  if ($objectName == 'Individual' || $objectName == 'Organization' || $objectName == 'Household') {
    // Contact Edited
    if ($op == 'edit' || $op == 'create') {
      if($objectRef->is_opt_out == 1) {
        $action = 'unsubscribe';
      } else {
        $action = 'subscribe';
      }
      
      // Get all groups, the contact is subscribed to
      $civiGroups = CRM_Contact_BAO_GroupContact::getGroupList($objectId);
      $civiGroups = array_keys($civiGroups);

      if (empty($civiGroups)) {
        return;
      }

      // Get mailchimp details
      $groups = CRM_Mailchimp_Utils::getGroupsToSync($civiGroups);
      
      if (!empty($groups)) {
        // Loop through all groups and unsubscribe the email address from mailchimp
        foreach ($groups as $groupId => $groupDetails) {
          CRM_Mailchimp_Utils::subscribeOrUnsubsribeToMailchimpList($groupDetails, $objectId, $action);
        }
      }
    }
  }

  /***** Contacts added/removed/deleted from CiviCRM group *****/
  if ($objectName == 'GroupContact') {
    
    // FIXME: Dirty hack to skip hook
    require_once 'CRM/Core/Session.php';
    $session = CRM_Core_Session::singleton();
    $skipPostHook = $session->get('skipPostHook');
  
    // Added/Removed/Deleted - This works for both bulk action and individual add/remove/delete
    if (($op == 'create' || $op == 'edit' || $op == 'delete') && empty($skipPostHook)) {
      // Decide mailchimp action based on $op
      // Add / Rejoin Group
      if ($op == 'create' || $op == 'edit') {
        $action = 'subscribe';
      }
      // Remove / Delete
      elseif ($op == 'delete') {
        $action = 'unsubscribe';
      }
    
      // Get mailchimp details for the group
      $groups = CRM_Mailchimp_Utils::getGroupsToSync(array($objectId));
      
      // Proceed only if the group is configured with mailing list/groups
      if (!empty($groups[$objectId])) {
      
        // Loop through all contacts added/removed from the group
        foreach ($objectRef as $contactId) {
          // Subscribe/Unsubscribe in Mailchimp
          CRM_Mailchimp_Utils::subscribeOrUnsubsribeToMailchimpList($groups[$objectId], $contactId, $action);
        }
      }
    }   
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
  googleapps_civicrm_config(CRM_Core_Config::singleton());
  require_once 'CRM/Sync/BAO/GoogleApps.php';
  // Create sync queue table if not exists
  $query = "
    CREATE TABLE IF NOT EXISTS `" . CRM_Sync_BAO_GoogleApps::GOOGLEAPPS_QUEUE_TABLE_NAME . "` (
          `id` int(10) NOT NULL AUTO_INCREMENT,
          `civicrm_contact_id` int(10) NOT NULL,
          `google_contact_id` varchar(32) DEFAULT NULL,
          `first_name` varchar(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
          `last_name` varchar(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
          `organization` varchar(128) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
          `job_title` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
          `email` varchar(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
          `email_location_id` int(10) UNSIGNED DEFAULT NULL,
          `email_is_primary` tinyint(4) DEFAULT '0',
          `phone` varchar(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
          `phone_ext` varchar(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
          `phone_type_id` int(10) UNSIGNED DEFAULT NULL,
          `phone_location_id` int(10) UNSIGNED DEFAULT NULL,
          `phone_is_primary` tinyint(4) DEFAULT '0',
          `is_deleted` tinyint(1) NOT NULL DEFAULT '0',
      PRIMARY KEY (`id`),
      KEY `civicrm_contact_id` (`civicrm_contact_id`),
      KEY `google_contact_id` (`google_contact_id`)
    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
  CRM_Core_DAO::executeQuery($query);
  _generic_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function generic_civicrm_uninstall() {
  googleapps_civicrm_config(CRM_Core_Config::singleton());
  require_once 'CRM/Sync/BAO/GoogleApps.php';
  // Delete scheduled job
  $scheduledJob = CRM_Sync_BAO_GoogleApps::get_scheduledJob();
  $scheduledJob->delete();
  // Delete custom group & fields
  $custom_group = CRM_Sync_BAO_GoogleApps::get_customGroup();
  $custom_fields = CRM_Sync_BAO_GoogleApps::get_customFields($custom_group);
  foreach ($custom_fields as $custom_field) {
    $params = array('version' => 3, 'id' => $custom_field['id']);
    $result = civicrm_api('CustomField', 'delete', $params);
  }
  $params = array('version' => 3, 'id' => $custom_group['id']);
  $result = civicrm_api('CustomGroup', 'delete', $params);
  // Drop sync queue table
  $query = "DROP TABLE IF EXISTS `" . CRM_Sync_BAO_GoogleApps::GOOGLEAPPS_QUEUE_TABLE_NAME . "`;";
  CRM_Core_DAO::executeQuery($query);
  // Delete all settings
  CRM_Core_BAO_Setting::deleteItem(CRM_Sync_BAO_GoogleApps::GOOGLEAPPS_PREFERENCES_NAME);
  _generic_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function generic_civicrm_enable() {
  googleapps_civicrm_config(CRM_Core_Config::singleton());
  $params = CRM_Sync_BAO_GoogleApps::get_customGroup();
  $params['version'] = 3;
  $params['is_active'] = 1;
  $result = civicrm_api('CustomGroup', 'create', $params);
  // Create custom fields in this group
  $custom_fields = CRM_Sync_BAO_GoogleApps::get_customFields($params['id']);
  // Reminder to go to the configuration screen
  CRM_Core_Session::setStatus(
    ts('Extension enabled. Please go to the <a href="%1">setup screen</a> to configure it.',
      array(1 => CRM_Utils_System::url('civicrm/admin/sync/googleapps')))
  );
  _generic_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function generic_civicrm_disable() {
  $params = CRM_Sync_BAO_GoogleApps::get_customGroup();
  $params['version'] = 3;
  $params['is_active'] = 0;
  $result = civicrm_api('CustomGroup', 'create', $params);
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
