<?php

require_once 'ode.civix.php';

/**
 * Implementation of hook_civicrm_config
 */
function ode_civicrm_config(&$config) {
  _ode_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 */
function ode_civicrm_xmlMenu(&$files) {
  _ode_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 */
function ode_civicrm_install() {
  return _ode_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 */
function ode_civicrm_uninstall() {
  return _ode_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 */
function ode_civicrm_enable() {
  return _ode_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 */
function ode_civicrm_disable() {
  return _ode_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 */
function ode_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _ode_civix_civicrm_upgrade($op, $queue);
}

function ode_civicrm_validate($formName, &$fields, &$files, &$form) {
  $errors = array();
  if ($formName == "CRM_Contribute_Form_ContributionPage_ThankYou" || $formName == "CRM_Event_Form_ManageEvent_Registration") {
    $config = CRM_Core_Config::singleton();
    $domain = $config->userFrameworkBaseURL;
    $isSSL = CRM_Core_BAO_Setting::getItem('CiviCRM Preferences', 'enableSSL');
    $details = array();
    if ($isSSL) { 
      preg_match('@^(?:https://)?([^/]+)@i', $domain, $matches);
    }
    else {
      preg_match('@^(?:http://)?([^/]+)@i', $domain, $matches);
    }
    $host = '@'.$matches[1];
    $hostLength = strlen($host);
    $email = $fields['receipt_from_email'] ? $fields['receipt_from_email'] : $fields['confirm_from_email'];
    $field = $fields['receipt_from_email'] ? 'receipt_from_email' : 'confirm_from_email';
    if (substr($email, -$hostLength) != $host) {
      $errors[$field] = ts('The Outbound Domain Enforcement extension has prevented this From Email Address from being used as it uses a different domain than the System-generated Mail Settings From Email Address configured at Administer > Communications > Organization Address and Contact Info.');
    }
  }
  return $errors;
}

function ode_civicrm_buildForm($formName, &$form) {
  if ($formName == "CRM_Contact_Form_Task_Email") {
    $form->_emails = $emails = array();

    $session = CRM_Core_Session::singleton();
    $contactID = $session->get('userID');

    $contactEmails = CRM_Core_BAO_Email::allEmails($contactID);

    $fromDisplayName = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',$contactID, 'display_name');

    foreach ($contactEmails as $emailId => $item) {
      $email = $item['email'];
      if (!$email && (count($emails) < 1)) {
      }
      else {
        if ($email) {
          if (in_array($email, $emails)) {
            continue;
          }
          $emails[$emailId] = '"' . $fromDisplayName . '" <' . $email . '> ';
        }
      }

      $form->_emails[$emailId] = $emails[$emailId];
      $emails[$emailId] .= $item['locationType'];

      if ($item['is_primary']) {
        $emails[$emailId] .= ' ' . ts('(preferred)');
      }
      $emails[$emailId] = htmlspecialchars($emails[$emailId]);
    }

    // now add domain from addresses
    $domainEmails = array();
    $domainFrom = CRM_Core_OptionGroup::values('from_email_address');
    foreach (array_keys($domainFrom) as $k) {
      $domainEmail = $domainFrom[$k];
      $domainEmails[$domainEmail] = htmlspecialchars($domainEmail);
    }

    $form->_fromEmails = CRM_Utils_Array::crmArrayMerge($emails, $domainEmails);
    $form->_fromEmails = suppressEmails($form->_fromEmails);
    $form->add('select', 'fromEmailAddress', ts('From'), $form->_fromEmails, TRUE);
  }
  if ($formName == "CRM_Mailing_Form_Upload") {
    $fromEmailAddress = CRM_Core_OptionGroup::values('from_email_address');
    $fromEmailAddress = suppressEmails($fromEmailAddress);
    
    foreach ($fromEmailAddress as $key => $email) {
      $fromEmailAddress[$key] = htmlspecialchars($fromEmailAddress[$key]);
    }
    $form->add('select', 'from_email_address',
      ts('From Email Address'), array(
        '' => '- select -') + $fromEmailAddress, TRUE
    );
  }
}

function suppressEmails($fromEmailAddress) {
  $config = CRM_Core_Config::singleton();
  $domain = $config->userFrameworkBaseURL;
  $details = array();
  $isSSL = CRM_Core_BAO_Setting::getItem('CiviCRM Preferences', 'enableSSL');
  if ($isSSL) { 
    preg_match('@^(?:https://)?([^/]+)@i', $domain, $matches);
  }
  else {
    preg_match('@^(?:http://)?([^/]+)@i', $domain, $matches);
  }
  $host = '@'.$matches[1];
  $hostLength = strlen($host);
  foreach ($fromEmailAddress as $keys => $headers) {
    $email = pluckEmailFromHeader(html_entity_decode($headers));
    if (substr($email, -$hostLength) != $host) {
      $invalidEmails[] = $email;
      unset($fromEmailAddress[$keys]);
    }
  }
  if (!empty($invalidEmails)) {
    //redirect user to enter from email address.
    $session = CRM_Core_Session::singleton();
    $message = "";
    if (empty($fromEmailAddress)) {
      $message = " You can add another one <a href='%2'>here.</a>";
      $url = CRM_Utils_System::url('civicrm/admin/options/from_email_address', 'group=from_email_address&action=add&reset=1');
      $fromEmailAddress = array('- select -');
    }
    $status = ts('The Outbound Domain Enforcement extension has prevented the following From Email Address option(s) from being used as it uses a different domain than the System-generated Mail Settings From Email Address configured at Administer > Communications > Organization Address and Contact Info: %1'. $message , array( 1=> implode(', ', $invalidEmails), 2=> $url));
    $session->setStatus($status, ts('Notice'));
  }
  return $fromEmailAddress;
}


function pluckEmailFromHeader($header) {
  preg_match('/<([^<]*)>/', $header, $matches);
  
  if (isset($matches[1])) {
    return $matches[1];
  }
  return NULL;
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 */
function ode_civicrm_managed(&$entities) {
  return _ode_civix_civicrm_managed($entities);
}
