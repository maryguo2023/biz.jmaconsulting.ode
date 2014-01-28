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
  $domain = get_domain($config->userFrameworkBaseURL);
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

function get_domain($domain, $debug = false)
{
	$original = $domain = strtolower($domain);
 
	if (filter_var($domain, FILTER_VALIDATE_IP)) { return $domain; }
 
	$debug ? print('<strong style="color:green">&raquo;</strong> Parsing: '.$original) : false;
 
	$arr = array_slice(array_filter(explode('.', $domain, 4), function($value){
		return $value !== 'www';
	}), 0); //rebuild array indexes
 
	if (count($arr) > 2)
	{
		$count = count($arr);
		$_sub = explode('.', $count === 4 ? $arr[3] : $arr[2]);
 
		$debug ? print(" (parts count: {$count})") : false;
 
		if (count($_sub) === 2) // two level TLD
		{
			$removed = array_shift($arr);
			if ($count === 4) // got a subdomain acting as a domain
			{
				$removed = array_shift($arr);
			}
			$debug ? print("<br>\n" . '[*] Two level TLD: <strong>' . join('.', $_sub) . '</strong> ') : false;
		}
		elseif (count($_sub) === 1) // one level TLD
		{
			$removed = array_shift($arr); //remove the subdomain
 
			if (strlen($_sub[0]) === 2 && $count === 3) // TLD domain must be 2 letters
			{
				array_unshift($arr, $removed);
			}
			else
			{
				// non country TLD according to IANA
				$tlds = array(
					'aero',
					'arpa',
					'asia',
					'biz',
					'cat',
					'com',
					'coop',
					'edu',
					'gov',
					'info',
					'jobs',
					'mil',
					'mobi',
					'museum',
					'name',
					'net',
					'org',
					'post',
					'pro',
					'tel',
					'travel',
					'xxx',
				);
 
				if (count($arr) > 2 && in_array($_sub[0], $tlds) !== false) //special TLD don't have a country
				{
					array_shift($arr);
				}
			}
			$debug ? print("<br>\n" .'[*] One level TLD: <strong>'.join('.', $_sub).'</strong> ') : false;
		}
		else // more than 3 levels, something is wrong
		{
			for ($i = count($_sub); $i > 1; $i--)
			{
				$removed = array_shift($arr);
			}
			$debug ? print("<br>\n" . '[*] Three level TLD: <strong>' . join('.', $_sub) . '</strong> ') : false;
		}
	}
	elseif (count($arr) === 2)
	{
		$arr0 = array_shift($arr);
 
		if (strpos(join('.', $arr), '.') === false
			&& in_array($arr[0], array('localhost','test','invalid')) === false) // not a reserved domain
		{
			$debug ? print("<br>\n" .'Seems invalid domain: <strong>'.join('.', $arr).'</strong> re-adding: <strong>'.$arr0.'</strong> ') : false;
			// seems invalid domain, restore it
			array_unshift($arr, $arr0);
		}
	}
 
	$debug ? print("<br>\n".'<strong style="color:gray">&laquo;</strong> Done parsing: <span style="color:red">' . $original . '</span> as <span style="color:blue">'. join('.', $arr) ."</span><br>\n") : false;
 
	return join('.', $arr);
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
